<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thread;
use App\Models\Message;
use App\Models\Attachment;

use App\Models\EmailTracker;
use App\Http\Controllers\GmailController;
use App\Http\Controllers\MsmailController;

use Str;

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use Storage;

class MailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        $limit = 20;
        $threads = Thread::paginate($limit);
        return view('mail.inbox',compact('threads'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $thread = Thread::where('thread_id',$id)->first();
        return view('mail.show',compact('thread'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function createMessage(Request $request) {
        $emails = EmailTracker::where('enable_tracking',1)->get();
        $view = view("create-message-ajax",compact('emails'))->render();
		return response()->json(['html'=>$view]);
	}

	public function sendEmail(Request $request) {

        $this->validate($request,[
            'from'=>'required',
            'to'=>'required',
            'subject'=>'required'
        ]);


        $email = EmailTracker::find($request->from);

        if($email->platform=='gmail')
        {
            // Send for Gmail 
            $gc = new GmailController;     
            $existingtoken = json_encode(['access_token'=>$email->provider_token,'expires_in'=>$email->expires_at,'refresh_token'=>$email->provider_refresh_token]);
            // check if the token is validate 
            $client = $gc->isValidToken($existingtoken);
            if(!$client)
            {
                // if not then refresh it and update the table
                $client =  $gc->refreshToken($email);
            }

    		if($client)
    		{
    			try {
    				$service = new \Google_Service_Gmail($client);
    				$to = explode(",", $request->to);
    				$cleanedMails = array_map('trim', $to);

    				$user = 'me';
    				$message = new \Swift_Message();
    				$message->setFrom($email->email);
    				$message->setTo($cleanedMails);
    				$message->setContentType("text/html");
    				$message->setBody($request->body);
    				$message->setSubject($request->subject);
    				$message->toString();
    				    

    				if( $request->file('attachment')) {
    					if(is_array($request->file('attachment'))) {
    						foreach($request->file('attachment') as $attachment) {
    							$path = $attachment->getPathName();
    							$fileName = $attachment->getClientOriginalName();  
    							$message->attach(
    							\Swift_Attachment::fromPath($path)->setFilename($fileName)
                                );

                                $extension = $attachment->extension();
                                $newfilename = Str::uuid().'.'.$extension;
                                \Storage::put($newfilename,$attachment);

                                $attachments[] =  [
                                    'filename' => $fileName,
                                    'mimeType' => $attachment->getClientMimeType(),
                                    'data'     => '',
                                    'attachment_id' => '',
                                    'file_path' => $newfilename
                                ];
                                //file_put_contents($save_file_path, $image_file);
                                
    						}
    					} else {
    						$path = $request->file('attachment')->getPathName();
    						$fileName = $request->file('attachment')->getClientOriginalName();  
    						$message->attach(
    							\Swift_Attachment::fromPath($path)->setFilename($fileName)
                                );
                                
                            $extension = $request->file('attachment')->extension();
                            $newfilename = Str::uuid().'.'.$extension;
                            \Storage::put($newfilename,$request->file('attachment'));

                            $attachments[] =  [
                                'filename' => $fileName,
                                'mimeType' => $request->file('attachment')->getClientMimeType(),
                                'data'     => '',
                                'attachment_id' => '',
                                'file_path' => $newfilename
                            ];
    					}
    				} 
    				// The message needs to be encoded in Base64URL
    				$mime = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    				$msg = new \Google_Service_Gmail_Message();
                    $msg->setRaw($mime);
                    
    		
                    $messageobj = $service->users_messages->send("me", $msg);

                    // Enter into DB , Thread
                    $data['thread_id']=$messageobj->threadId;
                    $data['subject'] = $request->subject;
                    $data['record_time'] = date('Y-m-d H:i:s');
                    $dbthread = $email->threads()->where('thread_id',$messageobj->threadId)->first();
                    if(!$dbthread)
                        $dbthread = $email->threads()->create($data);

                    // Enter into DB , Messages
                    $message = $dbthread->messages()->create(['body'=>$request->body,'message_id'=>$messageobj->id,'from'=>$email->email,'to'=>$request->to,'record_time'=>Date('Y-m-d H:i:s')]);

                    if(count($attachments))
                    {
                        foreach($attachments as $key=>$att)
                            $attachments[$key]['message_id']=$messageobj->id;

                        $message->attachments()->insert($attachments);
                    }
                   // return redirect('/custom-mail');

                    return response()->json(['redirect_to'=>url('/custom-mail')],200);
                    
    			} catch (Exception $e) {
                    report($e);
                    return response()->json(['message'=>'Someting went wrong.\nError: '.$e->getMessage()],400);
    			}
    			
    		}else
    		{
    			return response()->json(['message'=>'Invalid Token, Can\'t send mail'],400);
    		}
        }
        elseif($email->platform=='msmail')
        {
            // Send for MS mail
            $process_to = [];
            $tos = explode(",", $request->to);
            foreach ($tos as $to) {
                $process_to[]=[
                    'emailAddress'=>[
                        'address'=>$to
                    ],
                ];
            }
            $attachments = [];

            $ms = new MsmailController;
            $client = $ms->getClient();

            $data_to_send = [
                "subject"=>$request->subject,
                'body'=>[
                    'contentType'=>'HTML',
                    'content'=>$request->body
                ],
                'toRecipients'=>$process_to
            ];
            
            
            // CREATE =================
            // First  /me/messages crete mail and then send it. 
            // On create we'll get the mail id as return and on send we don't get anything return 
            // This will create a draft and we can add files to it

            $messageobj = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->post('https://graph.microsoft.com/v1.0/me/messages',$data_to_send);
            $messageobj = json_decode($messageobj,true);

            if(!$ms->isValidResponse($messageobj))
            {
                if($ms->refreshToken($email))
                {
                    $messageobj = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->post('https://graph.microsoft.com/v1.0/me/messages',$data_to_send);
                    $messageobj = json_decode($messageobj,true);
                }
                else
                {
                    echo "ERROR : Failed to get Refresh token.  Failed to send mail";
                    exit;
                }
            }
            
            // Enter into DB , Thread
            $data['thread_id']=$messageobj['conversationId'];
            $data['subject'] = $request->subject;
            $data['record_time'] = date('Y-m-d H:i:s');
            $dbthread = $email->threads()->where('thread_id',$messageobj['conversationId'])->first();
            if(!$dbthread)
                $dbthread = $email->threads()->create($data);

            // Enter into DB , Messages
            $message = $dbthread->messages()->create(['body'=>$request->body,'message_id'=>$messageobj['id'],'from'=>$email->email,'to'=>$request->to,'record_time'=>Date('Y-m-d H:i:s'),'meta_data'=>$messageobj['internetMessageId']]);

            // Check each attachment and add 1 after another with the mesage            
            // Do the file upload 
            if($request->file('attachment'))
                $this->MsFileUpload($email,$message,$messageobj,$request->file('attachment'),$ms);
            

            // ================  Prepare Send mail 

            $sendmail = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->post('https://graph.microsoft.com/v1.0/me/messages/'.$messageobj['id'].'/send');
            $sendmail = json_decode($sendmail,true);

            if(!$ms->isValidResponse($sendmail))
            {
                if($ms->refreshToken($email))
                {
                    $sendmail = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->post('https://graph.microsoft.com/v1.0/me/messages/'.$messageobj['id'].'/send');
                    $sendmail = json_decode($sendmail,true);
                }
                else
                {
                    echo "ERROR : Failed to get Refresh token.  Failed to send mail";
                }
            }

            // Message ID will be different at this point .. So we should find the new id by sending get top 1 mail , we'll take top 3 just to be on the safe side
            sleep(1);// Need to wait for a while to get the update , not always works fast. 
            $this->searchAndUpdateNewIdForThisMessage($email,$ms);

            return response()->json(['redirect_to'=>url('/custom-mail')],200);
            
        }
	}

    public function getCleanFromEmail($str)
    {
        $emails = explode(",",$str);
        $clean_emails = [];
        foreach($emails as $email)
        {
            $tmp = explode("<",$email);
            if(count($tmp)>1)
            {
                $tmp = str_replace(">","",$tmp[1]);
                $clean_emails[]=$tmp;
            }
            else
                $clean_emails[]=$tmp[0];
        }
        return $clean_emails[0] ?? '';
    }

    
    public function getCleanToEmail($str)
    {
        $emails = explode(",",$str);
        $clean_emails = [];
        foreach($emails as $email)
        {
            $tmp = explode("<",$email);
            if(count($tmp)>1)
            {
                $em = str_replace(">","",$tmp[1]);
                $clean_emails[]=$em;//[$em=>$tmp[0]];
            }
            else
                $clean_emails[]=$tmp[0];
        }
        return $clean_emails;
    } 

    public function searchAndUpdateNewIdForThisMessage($email,$ms)
    {
        $message_url = 'https://graph.microsoft.com/v1.0/me/messages?$top=3';
        $searchmail =  \Illuminate\Support\Facades\Http::withToken($email->provider_token)->get($message_url);
        $searchmail = json_decode($searchmail,true);

        if(!$ms->isValidResponse($searchmail))
        {
            if($ms->refreshToken($email))
            {
                $message_url = 'https://graph.microsoft.com/v1.0/me/messages?$top=3';
                $searchmail =  \Illuminate\Support\Facades\Http::withToken($email->provider_token)->get($message_url);
                $searchmail = json_decode($searchmail,true);
            }
            else
            {
                echo "ERROR : Failed to get Refresh token.  Failed to send mail";
            }
        }

        foreach ($searchmail['value'] as $eachmail) {
            $message = Message::where('meta_data',$eachmail['internetMessageId'])->first();
            if($message)
            {
                $old_id = $message->message_id;
                info("Some update has happedned");
                info($message->id);
                info("Before : ".$message->message_id);
                $message->message_id=$eachmail['id'];
                $message->save();
                info("After : ".$message->message_id);
                //update all atachent ids too
                Attachment::where('message_id',$old_id)->update(['message_id'=>$message->message_id]);
                break;
            }
        }
    }

    public function MsFileUpload($email,$message,$messageobj,$files,$ms)
    {
        foreach($files as $attachment) 
        {
            $path = $attachment->getPathName();
            $fileName = $attachment->getClientOriginalName();  
            $extension = $attachment->extension();
            $newfilename = Str::uuid().'.'.$extension;
                
            // save locally
            Storage::put($newfilename,$attachment);

            $eachattr=  [
                'filename' => $fileName,
                'mimeType' => $attachment->getClientMimeType(),
                'data'     => '',
                'attachment_id' => '',
                'file_path' => $newfilename
            ];


            //if file size more than 4 MB
            // 1. create upload session
            // $file =  $attachment;


            // $uploadfile=['AttachmentItem'=>['attachmentType'=>"file","name"=>$fileName,'size'=>$attachment->getSize()]];

            // $uploadsession=$graph->createRequest("POST", "/me/messages/".$messageobj['id']."/attachments/createUploadSession")
            // ->attachBody($uploadfile)
            // ->setReturnType(Model\UploadSession::class)
            // ->execute();

            // //2. upload bytes
            // $fragSize =320 * 1024;// 1024 * 1024 * 4;
            // $graph_url = $uploadsession->getUploadUrl();

            // $fileSize = $attachment->getSize();
            // $numFragments = ceil($fileSize / $fragSize);
            // $bytesRemaining = $fileSize;
            // $i = 0;
            // while ($i < $numFragments) {
            //     $chunkSize = $numBytes = $fragSize;
            //     $start = $i * $fragSize;
            //     $end = $i * $fragSize + $chunkSize - 1;
            //     $offset = $i * $fragSize;
            //     if ($bytesRemaining < $chunkSize) 
            //     {
            //         $chunkSize = $numBytes = $bytesRemaining;
            //         $end = $fileSize - 1;
            //     }
                
            //     // get contents using offset
            //     $data = stream_get_contents($attachment, $chunkSize, $offset);
            //     $content_range = "bytes " . $start . "-" . $end . "/" . $fileSize;
            //     $headers = array(
            //                 "Content-Length"=> $numBytes,
            //                 "Content-Range"=> $content_range
            //             );
            //     $uploadByte = $graph->createRequest("PUT", $graph_url)
            //             ->addHeaders($headers)
            //             ->attachBody($data)
            //                 ->setReturnType(Model\UploadSession::class)
            //                 ->setTimeout("1000")
            //                 ->execute();
            //     $bytesRemaining = $bytesRemaining - $chunkSize;
            //             $i++;
            // }


            // $response  =  $graph->createRequest("PUT", "")->upload($fileName);

      
            $attachmentData=[
                "@odata.type"=>"#microsoft.graph.fileAttachment",
                "name"=>$fileName,
                "contentType"=>$attachment->getClientMimeType(),
                "contentBytes"=>base64_encode(file_get_contents($attachment))
            ];

            $sendattachents = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->post('https://graph.microsoft.com/v1.0/me/messages/'.$messageobj['id'].'/attachments',$attachmentData);
            $sendattachents = json_decode($sendattachents,true);

            if(!$ms->isValidResponse($sendattachents))
            {
                if($ms->refreshToken($email))
                {
                    $sendattachents = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->post('https://graph.microsoft.com/v1.0/me/messages/'.$messageobj['id'].'/attachments',$attachmentData);
                    $sendattachents = json_decode($sendattachents,true);
                }
                else
                {
                    echo "ERROR : Failed to get Refresh token.  Failed to send mail";
                }
            }

            // once we have the message id , lets save it in DB 
            $eachattr['attachment_id']=$sendattachents['id'];
            $message->attachments()->create($eachattr);
        }
    }

	public function replyEmail(Request $request) {
        
        $message = Message::find($request->message_id);
        $thread = Thread::where('thread_id',$message->thread_id)->first();

        $email = $thread->user;

        if($email->platform=='gmail')
        {

            $gc = new GmailController;
                
            $existingtoken = json_encode(['access_token'=>$email->provider_token,'expires_in'=>$email->expires_at,'refresh_token'=>$email->provider_refresh_token]);
            // check if the token is validate 
            $client = $gc->isValidToken($existingtoken);
            if(!$client)
            {
                // if not then refresh it and update the table
                $client =  $gc->refreshToken($email);
            }

            if($client)
    		{
                try 
                {
                    //dd(implode(",",$this->getCleanToEmail($message->from)));
    				$service = new \Google_Service_Gmail($client);
    				$user = 'me';
    				$newmessage = new \Swift_Message();
    				$newmessage->setFrom($this->getCleanFromEmail($message->to));//$this->getCleanFromEmail($message->to)
    				$newmessage->setTo($this->getCleanToEmail($message->from)); // array inputs
    				$newmessage->setContentType("text/html");
    				$newmessage->setBody($request->body);
                    $newmessage->setSubject($thread->subject);
    				$newmessage->toString();
    				    
                    $attachments = [];
    				if( $request->file('attachment')) {
    					if(is_array($request->file('attachment'))) {
    						foreach($request->file('attachment') as $attachment) {
    							$path = $attachment->getPathName();
    							$fileName = $attachment->getClientOriginalName();  
    							$newmessage->attach(
    							\Swift_Attachment::fromPath($path)->setFilename($fileName)
                                );

                                $extension = $attachment->extension();
                                $newfilename = Str::uuid().'.'.$extension;
                                \Storage::put($newfilename,$attachment);

                                $attachments[] =  [
                                    'filename' => $fileName,
                                    'mimeType' => $attachment->getClientMimeType(),
                                    'data'     => '',
                                    'attachment_id' => '',
                                    'file_path' => $newfilename
                                ];
                                //file_put_contents($save_file_path, $image_file);
                                
    						}
    					} else {
    						$path = $request->file('attachment')->getPathName();
    						$fileName = $request->file('attachment')->getClientOriginalName();  
    						$newmessage->attach(
    							\Swift_Attachment::fromPath($path)->setFilename($fileName)
                                );
                                
                            $extension = $request->file('attachment')->extension();
                            $newfilename = Str::uuid().'.'.$extension;
                            \Storage::put($newfilename,$request->file('attachment'));

                            $attachments[] =  [
                                'filename' => $fileName,
                                'mimeType' => $request->file('attachment')->getClientMimeType(),
                                'data'     => '',
                                'attachment_id' => '',
                                'file_path' => $newfilename
                            ];
    					}
    				} 
    				// The message needs to be encoded in Base64URL
    				$mime = rtrim(strtr(base64_encode($newmessage), '+/', '-_'), '=');
                    $msg = new \Google_Service_Gmail_Message();
                    $msg->setThreadId($thread->thread_id);
                    $msg->setRaw($mime);
                    
                    // print_r($gc->decodeBody($msg->raw));exit;
                    // echo base64_decode($msg->raw);
                    // exit;

    		
                    $messageobj = $service->users_messages->send("me", $msg);                

                    // Enter into DB , Messages
                    $message = $thread->messages()->create(['body'=>$request->body,'message_id'=>$messageobj->id,'from'=>$message->to,'to'=>$message->from,'record_time'=>Date('Y-m-d H:i:s')]);

                    if(count($attachments))
                    {
                        foreach($attachments as $key=>$att)
                            $attachments[$key]['message_id']=$messageobj->id;

                        $message->attachments()->insert($attachments);
                    }

                    return response()->json(['reload'=>true],200);
                    
    			} catch (Exception $e) {
                    report($e);
                    return response()->json(['message'=>'Someting went wrong.\nError: '.$e->getMessage()],400);
    			}
    			
    		}else
    		{
    			return response()->json(['message'=>'Invalid Token, Can\'t send mail'],400);
    		}
        }
        elseif($email->platform=='msmail')
        {
            //print_r($request->all());exit;
            // Send for MS mail
            $process_to = [];
            $tos = explode(",", $message->to);
            foreach ($tos as $to) {
                $process_to[]=[
                    'emailAddress'=>[
                        'address'=>$to
                    ],
                ];
            }
            $attachments = [];

            $ms = new MsmailController;
            $client = $ms->getClient();

            $data_to_send = [
                'body'=>[
                    'contentType'=>'HTML',
                    'content'=>$request->body
                ]
            ];

            
           
            $messageobj = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->post('https://graph.microsoft.com/v1.0/me/messages/'.$message->message_id.'/createReplyAll');
            $messageobj = json_decode($messageobj,true);


            if(!$ms->isValidResponse($messageobj))
            {
                if($ms->refreshToken($email))
                {
                    $messageobj = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->post('https://graph.microsoft.com/v1.0/me/messages/'.$message->message_id.'/createReplyAll');
                    $messageobj = json_decode($messageobj,true);
                }
                else
                {
                    echo "ERROR : Failed to get Refresh token.  Failed to send mail";
                }
            }

            // Enter into DB , Thread
            $data['thread_id']=$messageobj['conversationId'];
            $data['subject'] = $request->subject;
            $data['record_time'] = date('Y-m-d H:i:s');
            $dbthread = $email->threads()->where('thread_id',$messageobj['conversationId'])->first();
            if(!$dbthread)
                $dbthread = $email->threads()->create($data);

            // Enter into DB , Messages
            $body = $request->body.'<br/><br/>'.$messageobj['body']['content'];
            $message = $dbthread->messages()->create(['body'=>$body,'message_id'=>$messageobj['id'],'from'=>$email->email,'to'=>$message->to,'record_time'=>Date('Y-m-d H:i:s'),'meta_data'=>$messageobj['internetMessageId']]);

            
            // ================ Update Mail  
            $updatemail = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->patch('https://graph.microsoft.com/v1.0/me/messages/'.$message->message_id,['body'=>['content'=>$body,'contentType'=>'HTML']]);
            $updatemail = json_decode($updatemail,true);

            if(!$ms->isValidResponse($updatemail))
            {
                if($ms->refreshToken($email))
                {
                    $updatemail = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->patch('https://graph.microsoft.com/v1.0/me/messages/'.$message->message_id,[['body']=>['content'=>$body,'contentType'=>'HTML']]);
                    $updatemail = json_decode($updatemail,true);
                }
                else
                {
                    echo "ERROR : Failed to get Refresh token.  Failed to send mail";
                }
            }

            // Do the file upload 
            if($request->file('attachment'))
                $this->MsFileUpload($email,$message,$messageobj,$request->file('attachment'),$ms);
            

            /// ================ Now we need to send the Mail 

            $sendmail = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->post('https://graph.microsoft.com/v1.0/me/messages/'.$message->message_id.'/send');
            $sendmail = json_decode($sendmail,true);

            if(!$ms->isValidResponse($sendmail))
            {
                if($ms->refreshToken($email))
                {
                    $sendmail = \Illuminate\Support\Facades\Http::asJson()->withToken($email->provider_token)->post('https://graph.microsoft.com/v1.0/me/messages/'.$message->message_id.'/send');
                    $sendmail = json_decode($sendmail,true);
                }
                else
                {
                    echo "ERROR : Failed to get Refresh token.  Failed to send mail";
                }
            }

            // Message ID will be different at this point .. So we should find the new id by sending get top 1 mail , we'll take top 3 just to be on the safe side
            sleep(1);// Need to wait for a while to get the update , not always works fast. 
            $this->searchAndUpdateNewIdForThisMessage($email,$ms);

            return response()->json(['reload'=>true],200);

        }
	}
}
