<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thread;
use App\Models\Message;

use App\Models\EmailTracker;
use App\Http\Controllers\GmailController;
use Str;

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

        $gc = new GmailController;
        $email = EmailTracker::find($request->from);        
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

	public function replyEmail(Request $request) {
        
        $message = Message::find($request->message_id);
        $thread = Thread::where('thread_id',$message->thread_id)->first();


        $gc = new GmailController;
        $email = $thread->user;    
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

        // dd($thread);
		// $user = User::find(1);
		// $client = $this->isValidToken($user->google_token);
		// if($client)
		// {
		// 	try {
		// 		$service = new \Google_Service_Gmail($client);
			
		// 		$user = 'me';
		// 		$message = new \Swift_Message();
		// 		$message->setFrom("muthusharp1st@gmail.com");
		// 		$message->setTo(['marimuthu.m@dsignzmedia.in'=>'Marimuthu']);
		// 		$message->setContentType("text/html");
		// 		$message->setBody($request->body);
		// 		$message->setSubject('Here is my subject');
		// 		$message->toString();
			
		// 		// The message needs to be encoded in Base64URL
		// 		$mime = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
		// 		$msg = new \Google_Service_Gmail_Message();
		// 		$msg->setRaw($mime);
		
		// 		$service->users_messages->send("me", $msg);
		// 	} catch (Exception $e) {
		// 		echo $e->getMessage();
		// 	}
			
		// }else
		// {
		// 	return redirect('/gmail/auth');
		// }
	}
}
