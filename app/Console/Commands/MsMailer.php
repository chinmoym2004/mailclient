<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\MsmailController;
use App\Models\Thread;
use App\Models\Attachment;
use App\Models\EmailTracker;

use File;
use Str;
class MsMailer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ms-mail:reader';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    

    public function handle()
    {
        $graph_url = 'https://graph.microsoft.com/v1.0/';
        
        //$pullfor = date('Y-m-d',strtotime('2021-03-26'));

        $ms = new MsmailController;

        $emails = EmailTracker::where('enable_tracking',1)->where('platform','msmail')->get();
        foreach($emails as $email)
        {
            $message_url = $graph_url.'me/messages?$top=10';

            $mail_array = $this->pullMail($message_url,$email->provider_token);
            if(!$ms->isValidResponse($mail_array))
            {
                if($ms->refreshToken($email))
                {
                    $mail_array = $this->pullMail($message_url,$email->provider_token);
                }
                else
                {
                    echo "ERROR : Failed to get Refresh token.  Will retry in next run";
                }
            }
            if(count($mail_array['value']))
            {
                foreach ($mail_array['value'] as $eachmail) {

                    if(isset($eachmail['isDraft']) && $eachmail['isDraft']==1)
                        continue;

                    $data['thread_id']=$eachmail['conversationId'];
                    $data['subject'] = $eachmail['subject'];
                    $data['record_time'] = $eachmail['receivedDateTime'];
                    $data['is_inbox']=1;

                    $thread = $email->threads()->where('thread_id',$eachmail['conversationId'])->first();
                    if(!$thread)
                        $thread = $email->threads()->create($data); 

                    if(!$thread->messages()->where('message_id',$eachmail['id'])->count())
                    {
                        $new = 1;
                        // check if the internetMessageId exist in table 
                        $message = $thread->messages()->where('meta_data',$eachmail['internetMessageId'])->first();
                        if(!$message)
                            $message = $thread->messages()->create(['body'=>$eachmail['body']['content'],'message_id'=>$eachmail['id'],'from'=>$this->cleanEmail($eachmail['from']),'to'=>$this->cleanEmail($eachmail['toRecipients']),'record_time'=>$eachmail['receivedDateTime'],'meta_data'=>$eachmail['internetMessageId']]);
                        else 
                        {
                            $new=0;
                            $message->message_id=$eachmail['id'];
                            $message->save();
                        }

                        // check if has attachemnts 
                        if($eachmail['hasAttachments'] && $new)
                        {
                            $att_url = $graph_url.'me/messages/'.$eachmail['id'].'/attachments';
                            $attachments =  $this->pullAttachments($att_url,$email->provider_token);

                            if(!$ms->isValidResponse($att_url))
                            {
                                if($ms->refreshToken($email))
                                {
                                    $attachments =  $this->pullAttachments($att_url,$email->provider_token);
                                }
                                else
                                {
                                    $attachments = [];
                                    echo "ERROR : Failed to get Refresh token.  Attachment will be missed. Need to add tra machanism to fetch is in next run";
                                }
                            }
                            
                            if(isset($attachments['value']))
                            {
                                $process_attachments = $this->processAttachments($eachmail['id'],$attachments['value']);
                                if(count($process_attachments))
                                    $message->attachments()->insert($process_attachments);
                            }
                        }

                    }
                }
            }
        }
    }

    public function processAttachments($mid,$attachments)
    {
        $att=[];
        foreach ($attachments as $attachment) {

            $extension = pathinfo($attachment['name'])['extension'];
            $newfilename = Str::uuid().'.'.$extension;

            $att[] =  [
                'message_id'=>$mid,
                'filename' => $attachment['name'],
                'mimeType' => $attachment['contentType'],
                'data'     => '',
                'attachment_id' => $attachment['id'],
                'file_path' => $newfilename
            ];
            $filecontent     =  base64_decode(strtr($attachment['contentBytes'], '-_', '+/'));  
            //file_put_contents($save_file_path, $filecontent);
            \Storage::put($newfilename,$filecontent);
        }

        return $att;
    }

    public function pullMail($url,$token)
    {
        $mails =  \Illuminate\Support\Facades\Http::withToken($token)->get($url);
        return json_decode($mails,true);
    }

    public function pullAttachments($url,$token)
    {
        $attachments =  \Illuminate\Support\Facades\Http::withToken($token)->get($url);
        return json_decode($attachments,true);
    }

    public function cleanEmail($emails)
    {

        $ret_email = '';
        foreach ($emails as $email) {
            if(isset($email['emailAddress']))
            {
                $tmp = $email['emailAddress']['address'];
            }
            else
            {
                $tmp = $email['address'];
            }
            if($ret_email!='')
                $ret_email.=','.$tmp;
            else 
                $ret_email.=$tmp;
        }
        return $ret_email;
    }
}
