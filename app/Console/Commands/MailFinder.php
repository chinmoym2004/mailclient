<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\GmailController;
use App\Models\EmailTracker;
use App\Models\Thread as DBThread;
use File;
class MailFinder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:reader';

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
        $gc = new GmailController;
        $emails = EmailTracker::where('enable_tracking',1)->get();
        foreach($emails as $email)
        {
            $existingtoken = json_encode(['access_token'=>$email->provider_token,'expires_in'=>$email->expires_at,'refresh_token'=>$email->provider_refresh_token]);
            // check if the token is validate 
            $client = $gc->isValidToken($existingtoken);
            if(!$client)
            {
                // if not then refresh it and update the table
               $client =  $gc->refreshToken($email);
            }

            // pull the threads 
            $service = new \Google_Service_Gmail($client);
			// Print the labels in the user's account.
			$user = 'me';
			//$labels = $service->users_labels->listUsersLabels($user);

			//$mails = $service->users_messages->listUsersMessages($user,['maxResults'=>20]);
			//dd($mails);exit;

			$optParams = [];
        	$optParams['maxResults'] = 20; // Return Only 20 Messages

        	if(!empty($request->label)) {

        		$optParams['labelIds'] = $request->label; // Show messages based on the lave
        	} else {
				$optParams['labelIds'] = "INBOX";
			}


        	// if(!empty($request->pageToken)) {
        	// 	$optParams['pageToken'] = $request->pageToken; // Page Token
        	// }
            
            if($email->last_pulled)
            {
                $optParams['after']=$email->last_pulled;
            }

            $threads = $service->users_threads->listUsersThreads($user, $optParams);
            $record_last_pulltime = time();

            // Do the processing on the data 
            foreach($threads as $thread)
            {
                // get thread details 
                // try{

                // }
                // catch(\Exception $e)
                // {

                // }
                $threadRequest = $service->users_threads->get($user, $thread->id);
                $firstmessage = $threadRequest->messages[0];
                $subject = $this->getHeader($firstmessage->payload->headers, 'Subject');
                $data['thread_id']=$thread->id;
                $data['subject'] = $subject;

                $dbthread = $email->threads()->where('thread_id',$thread->id)->first();
                if(!$dbthread)
                    $dbthread = $email->threads()->create($data); 

                $return = $this->getBodyAndAttachment($service,$gc,$dbthread,$threadRequest->messages);
            }

            $email->last_pulled = $record_last_pulltime;
            $email->save();
        }
    }

    function getHeader($headers, $index)
    {
        $header_text = '';

        foreach($headers as $header)
        {
            if($header['name']==$index)
            {
                $header_text=$header['value'];
                break;
            }
        }
        return $header_text;
    }

    function getBodyAndAttachment($service,$gc,$dbthread,$messages)
    {
        foreach($messages as $message) {
            $attachments=[];
			$full = $message;
			$headers = $full->payload->headers;
            $parts = $full->getPayload()->getParts();
            $lookingfor = ['Delivered-To','To','Subject','Reply-To','Received'];
            foreach($headers as $eachel)
            {
                if(in_array($eachel['name'],$lookingfor))
                {
                    $printH[$eachel['name']]=$eachel['value'];
                }
            }

            $payload = $full->getPayload();
            $parts = $payload->getParts();

            // With no attachment, the payload might be directly in the body, encoded.
            $body = $payload->getBody();
            $BODY = FALSE;
            // If we didn't find a body, let's look for the parts
            if(!$BODY) {
                
                foreach ($parts  as $part) {
                    if($part['parts'] && !$BODY) {
                        foreach ($part['parts'] as $p) {
                            if($p['parts'] && count($p['parts']) > 0){
                                foreach ($p['parts'] as $y) {
                                    if(($y['mimeType'] === 'text/html') && $y['body']) {
                                        $BODY = $gc->decodeBody($y['body']->data);
                                        break;
                                    }
                                }
                            } else if(($p['mimeType'] === 'text/html') && $p['body']) {
                                $BODY = $gc->decodeBody($p['body']->data);
                                break;
                            }
                        }
                    }
                    if($BODY) {
                        break;
                    }
                }
            
            }
            // let's save all the images linked to the mail's body:
            if($BODY && count($parts) > 1){
                $images_linked = array();
                foreach ($parts  as $part) {
                    if($part['filename']){
                        array_push($images_linked, $part);
                    } else{
                        if($part['parts']) {
                            foreach ($part['parts'] as $p) {
                                if($p['parts'] && count($p['parts']) > 0){
                                    foreach ($p['parts'] as $y) {
                                        if(($y['mimeType'] === 'text/html') && $y['body']) {
                                            array_push($images_linked, $y);
                                        }
                                    }
                                } else if(($p['mimeType'] !== 'text/html') && $p['body']) {
                                    array_push($images_linked, $p);
                                }
                            }
                        }
                    }
                    if (!empty($part->body->attachmentId)) {
                        $folderPath = "attachments/".$message->id;
                        if(!File::exists(public_path($folderPath))) {
                            File::makeDirectory(public_path($folderPath), 0777, true, true);
                        }
                        $attachment = $service->users_messages_attachments->get('me', $message->id, $part->body->attachmentId);
                        $attachments[$message->id][] =  [
                            'message_id'=>$message->id,
                            'filename' => $part->filename,
                            'mimeType' => $part->mimeType,
                            'data'     => strtr($attachment->data, '-_', '+/'),
                            'attachment_id' => $part->body->attachmentId,
                            'file_path' => $folderPath
                        ];
                        $save_file_path = public_path($folderPath."/".$part->filename);
                        $image_file     = base64_decode(strtr($attachment->data, '-_', '+/'));  
                        //file_put_contents($save_file_path, $image_file);
                        \Storage::put($part->filename,$image_file);
                        $attachmentHtml = "";
                        $attachmentHtml .= "<table>";
                    }
                }
                // special case for the wdcid...
                preg_match_all('/wdcid(.*)"/Uims', $BODY, $wdmatches);
                if(count($wdmatches)) {
                    $z = 0;
                    foreach($wdmatches[0] as $match) {
                        $z++;
                        if($z > 9){
                            $BODY = str_replace($match, 'image0' . $z . '@', $BODY);
                        } else {
                            $BODY = str_replace($match, 'image00' . $z . '@', $BODY);
                        }
                    }
                }
                preg_match_all('/src="cid:(.*)"/Uims', $BODY, $matches);
                if(count($matches)) {
                    $search = array();
                    $replace = array();
                    // let's trasnform the CIDs as base64 attachements 
                    foreach($matches[1] as $match) {
                        foreach($images_linked as $img_linked) {
                            foreach($img_linked['headers'] as $img_lnk) {
                                if( $img_lnk['name'] === 'Content-ID' || $img_lnk['name'] === 'Content-Id' || $img_lnk['name'] === 'X-Attachment-Id'){
                                    if ($match === str_replace('>', '', str_replace('<', '', $img_lnk->value)) 
                                            || explode("@", $match)[0] === explode(".", $img_linked->filename)[0]
                                            || explode("@", $match)[0] === $img_linked->filename){
                                        $search = "src=\"cid:$match\"";
                                        $mimetype = $img_linked->mimeType;
                                        $attachment = $service->users_messages_attachments->get('me', $message->getId(), $img_linked['body']->attachmentId);
                                        $data64 = strtr($attachment->getData(), array('-' => '+', '_' => '/'));
                                        $replace = "src=\"data:" . $mimetype . ";base64," . $data64 . "\"";
                                        $BODY = str_replace($search, $replace, $BODY);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // If we didn't find the body in the last parts, 
            // let's loop for the first parts (text-html only)
            if(!$BODY) {
                foreach ($parts  as $part) {
                    if($part['body'] && $part['mimeType'] === 'text/html') {
                        $BODY = $gc->decodeBody($part['body']->data);
                        break;
                    }
                }
            }
            // With no attachment, the payload might be directly in the body, encoded.
            if(!$BODY) {
                $BODY = $gc->decodeBody($body['data']);
            }
            // Last try: if we didn't find the body in the last parts, 
            // let's loop for the first parts (text-plain only)
            if(!$BODY) {
                foreach ($parts  as $part) {
                    if($part['body']) {
                        $BODY = $gc->decodeBody($part['body']->data);
                        break;
                    }
                }
            }
            if(!$BODY) {
                $BODY = '(No message)';
            }

            $message = $dbthread->messages()->create(['body'=>$BODY,'message_id'=>$message->id]);
            $message->attachments()->create([$attachments]);

        }
        
        return 1;
    }
}
