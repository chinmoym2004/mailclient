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

    public function isValidResponse($response)
    {   

        if(isset($response['error']) && $response['error']['code']=='InvalidAuthenticationToken')
        {
            return 0;
        }
        else 
            return 1;
    }

    public function handle()
    {
        $graph_url = 'https://graph.microsoft.com/v1.0/';

        $ms = new MsmailController;
        $emails = EmailTracker::where('enable_tracking',1)->where('platform','msmail')->get();
        foreach($emails as $email)
        {
            $message_url = $graph_url.'me/messages';
            $mail_array = $this->pullMail($message_url,$email->provider_token);
            if(!$this->isValidResponse($mail_array))
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
                    $data['thread_id']=$eachmail['conversationId'];
                    $data['subject'] = $eachmail['subject'];
                    $data['record_time'] = $eachmail['receivedDateTime'];

                    $thread = $email->threads()->where('thread_id',$eachmail['conversationId'])->first();
                    if(!$thread)
                        $thread = $email->threads()->create($data); 

                    if(!$thread->messages()->where('message_id',$eachmail['id'])->count())
                    {
                        $message = $thread->messages()->create(['body'=>$eachmail['body']['content'],'message_id'=>$eachmail['id'],'from'=>$this->cleanEmail($eachmail['from']),'to'=>$this->cleanEmail($eachmail['toRecipients']),'record_time'=>$eachmail['receivedDateTime']]);

                        // check if has attachemnts 
                        if($eachmail['hasAttachments'])
                        {
                            $att_url = $graph_url.'me/messages/'.$eachmail['id'].'/attachments';
                            $attachments =  $this->pullAttachments($att_url,$email->provider_token);

                            if(!$this->isValidResponse($att_url))
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
            $filecontent     = $attachment['contentBytes'];//base64_decode(strtr($attachment->data, '-_', '+/'));  
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
