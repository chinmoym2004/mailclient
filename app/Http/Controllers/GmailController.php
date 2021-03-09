<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Gmail;
use App\Models\User;
use File;
use App\Models\EmailTracker;

class GmailController extends Controller
{
    function getClient()
	{
	    $client = new Google_Client();
	    //$client->setApplicationName('Gmail API PHP Quickstart');
	    $client->setScopes(Google_Service_Gmail::MAIL_GOOGLE_COM);
	    $client->setAuthConfig(public_path().'/'.env('CLIENT_FILE'));
	    $client->setAccessType('offline');
	    //$client->setRedirectUri('/mail');
	    $client->setPrompt('select_account consent');
	    return $client;
	}

	public function authorization(){

		$user = User::find(1);
		$client = $this->getClient();

		if(isset($user->google_token))
		{
			$token = json_decode($user->google_token,true);
			//print_r($token);exit;
			$accessToken = $client->fetchAccessTokenWithAuthCode($token['access_token']);
            $client->setAccessToken($token);
            if ($client->isAccessTokenExpired()) 
            {
            	$authUrl = $client->createAuthUrl();
	            return redirect($authUrl);
            }
            return redirect('/inbox');
		}
		
		$authUrl = $client->createAuthUrl();
	    return redirect($authUrl);
	}

	public function callback2(Request $request)
	{
		
		if($request->code)
		{
			$user = User::find(1);

			$authCode = $request->code;
			$client = $this->getClient();
			// Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }

            $user->google_token = $accessToken;
            $user->save();

            if(!$client->getAccessToken())
            	return redirect('/gmail/auth');

            return redirect()->route('threads');
		}
		else
		{
			return redirect('/gmail/auth');
		}	
	}

	public function refreshToken($user)
	{
		$client = $this->getClient();
		$accessToken = $client->refreshToken($user->provider_refresh_token);
		if (array_key_exists('error', $accessToken)) {
			//throw new Exception(join(', ', $accessToken));
			return false;
		}

		$user->provider_token = $accessToken['access_token'];
		$user->enable_tracking = 1;
		$user->provider_refresh_token = $accessToken['refresh_token'];
		$user->expires_at = $accessToken['expires_in'];
		$user->save();

		$client->setAccessToken($accessToken);
		return $client;
	}

	// This call back is modified one to match with the request genrated in Homecontroller. 
	public function callback(Request $request)
	{
		
		if($request->code)
		{
			//TODO : check if the email same as request one 

			$user = EmailTracker::where('email',$request->session()->get('email'))->first();

			$authCode = $request->code;
			$client = $this->getClient();
			
			try
			{

				// Exchange authorization code for an access token.
				$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
				// Check to see if there was an error.
				
				if (array_key_exists('error', $accessToken)) {
					throw new Exception(join(', ', $accessToken));
				}
	
				$user->provider_token = $accessToken['access_token'];
				$user->enable_tracking = 1;
				$user->provider_refresh_token = $accessToken['refresh_token'];
				$user->expires_at = $accessToken['expires_in'];
				$user->save();
	
				return redirect('/')->with('alert-success','Authenticated successfully');
			}
			catch(\Exception $e)
			{
				return redirect('/')->with('alert-error','Failed to authenticate. Try again');
			}
		}
		else
		{
			return redirect('/')->with('alert-error','Failed to authenticate');
		}	
	}

	// This function to be called before each API call - to validate token 

	public function isValidToken($user_token)
	{
		if(isset($user_token))
		{
			$client = $this->getClient();
			$token = json_decode($user_token,true);
			$accessToken = $client->fetchAccessTokenWithAuthCode($token['access_token']);
            $client->setAccessToken($token);
            if ($client->isAccessTokenExpired()) 
            {
            	return 0;
            }
            return $client;
		}
		return 0;
	}


	public function myInbox(Request $request)
	{
		
		$user = User::find(1);

		$client = $this->isValidToken($user->google_token);
		if($client)
		{
			$service = new \Google_Service_Gmail($client);
			// Print the labels in the user's account.
			$user = 'me';
			$labels = $service->users_labels->listUsersLabels($user);

			//$mails = $service->users_messages->listUsersMessages($user,['maxResults'=>20]);
			//dd($mails);exit;

			$optParams = [];
        	$optParams['maxResults'] = 20; // Return Only 20 Messages

        	if(!empty($request->label)) {

        		$optParams['labelIds'] = $request->label; // Show messages based on the lave
        	} else {
				$optParams['labelIds'] = "INBOX";
			}


        	if(!empty($request->pageToken)) {
        		$optParams['pageToken'] = $request->pageToken; // Page Token
        	}
        	

			$threads = $service->users_threads->listUsersThreads($user, $optParams);


			//$threads = $service->users_threads->listUsersThreads($user,['maxResults'=>20]);
			//dd($threads);exit;

			if (count($labels->getLabels()) == 0) 
			{
			  print "No labels found.\n";
			} 
			else 
			{
				return view("threads",compact('labels','threads','service'));

			  // print "Labels:\n";
			  // foreach ($results->getLabels() as $label) {
			  //   printf("- %s\n", $label->getName());
			  // }
			}
		}
		else
		{
			return redirect('/gmail/auth');
		}
	}

	public function createMessage(Request $request) {
		$user = User::find(1);

		$client = $this->isValidToken($user->google_token);
		if($client)
		{
			return view("create-message");
		} else {
			return redirect('/gmail/auth');
		}
	}

	public function sendEmail(Request $request) {
		$user = User::find(1);
		$client = $this->isValidToken($user->google_token);
		if($client)
		{
			try {
				$service = new \Google_Service_Gmail($client);
				$to = explode(",", $request->to);
				$cleanedMails = array_map('trim', $to);

				$user = 'me';
				$message = new \Swift_Message();
				$message->setFrom("muthusharp1st@gmail.com");
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
						}
					} else {
						$path = $request->file('attachment')->getPathName();
						$fileName = $request->file('attachment')->getClientOriginalName();  
						$message->attach(
							\Swift_Attachment::fromPath($path)->setFilename($fileName)
							);
					}
				} 
				// The message needs to be encoded in Base64URL
				$mime = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
				$msg = new \Google_Service_Gmail_Message();
				$msg->setRaw($mime);
		
				$service->users_messages->send("me", $msg);
				return redirect()->back()->withSuccess("Mail sent!");
			} catch (Exception $e) {
				echo $e->getMessage();
			}
			
		}else
		{
			return redirect('/gmail/auth');
		}
	}

	public function replyEmail(Request $request) {
		$user = User::find(1);
		$client = $this->isValidToken($user->google_token);
		if($client)
		{
			try {
				$service = new \Google_Service_Gmail($client);
			
				$user = 'me';
				$message = new \Swift_Message();
				$message->setFrom("muthusharp1st@gmail.com");
				$message->setTo(['marimuthu.m@dsignzmedia.in'=>'Marimuthu']);
				$message->setContentType("text/html");
				$message->setBody($request->body);
				$message->setSubject('Here is my subject');
				$message->toString();
			
				// The message needs to be encoded in Base64URL
				$mime = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
				$msg = new \Google_Service_Gmail_Message();
				$msg->setRaw($mime);
		
				$service->users_messages->send("me", $msg);
			} catch (Exception $e) {
				echo $e->getMessage();
			}
			
		}else
		{
			return redirect('/gmail/auth');
		}
	}

	public function getAttachments($service , $message_id, $parts) {
		$attachments = [];
		foreach ($parts as $part) {
			if (!empty($part->body->attachmentId)) {
				$attachment = $service->users_messages_attachments->get('me', $message_id, $part->body->attachmentId);
				$attachments[] = [
					'filename' => $part->filename,
					'mimeType' => $part->mimeType,
					'data'     => strtr($attachment->data, '-_', '+/')
				];
			} else if (!empty($part->parts)) {
				//$attachments = array_merge($attachments, $this->getAttachments($service, $message_id, $part->parts));
			}
		}
		return $attachments;
	}

	public function myInbox2(Request $request)
	{
		$user = User::find(1);

		$client = $this->isValidToken($user->google_token);
		if($client)
		{
			$service = new \Google_Service_Gmail($client);
			// Print the labels in the user's account.
			$user = 'me';
			$labels = $service->users_labels->listUsersLabels($user);

			$optParams = [];
        	$optParams['maxResults'] = 20; // Return Only 20 Messages

        	if(!empty($request->label)) {
        		$optParams['labelIds'] = $request->label; // Show messages based on the lave
        	}

        	if(!empty($request->pageToken)) {
        		$optParams['pageToken'] = $request->pageToken; // Page Token
        	}
        	

			$mails = $service->users_messages->listUsersMessages($user, $optParams);


			//$threads = $service->users_threads->listUsersThreads($user,['maxResults'=>20]);
			//dd($threads);exit;

			/* foreach($mails->getMessages() as $message)
			{
				$full = $service->users_messages->get('me',$message->getId(),['format'=>['FULL']]);
				$headers = $full->payload->headers;
				$parts = $full->getPayload();

				$attachments = $this->getAttachments($service, $message->id, $parts);

	
				echo "<pre/>";
				print_r($attachments);
				exit;
				$body = $parts[0]['body']; //We can use 0 or 1
    			$rawData = $body->data;
    			$sanitizedData = strtr($rawData,'-_', '+/');
        		$decodedMessage = base64_decode($sanitizedData);
				
						//Message ID :{{$message->getId()}}, Thread ID : {{$message->getThreadId()}}
			} */

			if (count($labels->getLabels()) == 0) 
			{
			  print "No labels found.\n";
			} 
			else 
			{
				return view("inbox_mail",compact('labels','mails','service'));

			  // print "Labels:\n";
			  // foreach ($results->getLabels() as $label) {
			  //   printf("- %s\n", $label->getName());
			  // }
			}
		}
		else
		{
			return redirect('/gmail/auth');
		}
	}

	public function threadJs()
	{
		$user = User::find(1);
		$accessToken = "";
		$client = $this->isValidToken($user->google_token);
		if($client)
		{
			$service = new \Google_Service_Gmail($client);
			// Print the labels in the user's account.
			$users = 'me';
			$labels = $service->users_labels->listUsersLabels($users);

			$accessToken = json_decode($user->google_token, TRUE)['access_token'];
			return view('inbox_readonly', compact("accessToken", "labels"));
		} else{
			return redirect('/gmail/auth');
		}
	}

	public function updateToken(Request $request) {
		try{
			$user = User::find(1);
			$user->timestamps = false;
			$user->google_token = $request->all();
			$user->save();
			return 1;
		}catch(\Exception $e) {
			return 0;
		}
		
	}

	public function singleThreadJs(Request $request, $threadId) {
		$user = User::find(1);

		$accessToken = "";
		$client = $this->isValidToken($user->google_token);
		if($client)
		{
			$subject = $request->subject;
			$accessToken = json_decode($user->google_token, TRUE)['access_token'];
			return view('inbox_readonly_single', compact("accessToken", "threadId", "subject"));
		} else{
			return redirect('/gmail/auth');
		}
	}
	
	public function decodeBody($body) {
		$rawData = $body;
		$sanitizedData = strtr($rawData,'-_', '+/');
		$decodedMessage = base64_decode($sanitizedData);
		if(!$decodedMessage){
			$decodedMessage = FALSE;
		}
		return $decodedMessage;
	}

	public function singleThreadphp(Request $request, $threadId) {
		$user = User::find(1);
		$client = $this->isValidToken($user->google_token);
		$service = new \Google_Service_Gmail($client);
		// Print the labels in the user's account.
		$users = 'me';

		$thread = $service->users_threads->get($users, $threadId);
		$messages = $thread->messages;
		$time_start = microtime(true);
		$BODY = "";
		$attachments = [];
		foreach($messages as $message) {
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
														$BODY = $this->decodeBody($y['body']->data);
														break;
													}
												}
											} else if(($p['mimeType'] === 'text/html') && $p['body']) {
												$BODY = $this->decodeBody($p['body']->data);
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
											'filename' => $part->filename,
											'mimeType' => $part->mimeType,
											'data'     => strtr($attachment->data, '-_', '+/'),
											'attachment_id' => $part->body->attachmentId,
											'file_path' => $folderPath
										];
										$save_file_path = public_path($folderPath."/".$part->filename);
										$image_file     = base64_decode(strtr($attachment->data, '-_', '+/'));  
										file_put_contents($save_file_path, $image_file);
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
										$BODY = $this->decodeBody($part['body']->data);
										break;
									}
								}
							}
							// With no attachment, the payload might be directly in the body, encoded.
							if(!$BODY) {
								$BODY = $this->decodeBody($body['data']);
							}
							// Last try: if we didn't find the body in the last parts, 
							// let's loop for the first parts (text-plain only)
							if(!$BODY) {
								foreach ($parts  as $part) {
									if($part['body']) {
										$BODY = $this->decodeBody($part['body']->data);
										break;
									}
								}
							}
							if(!$BODY) {
								$BODY = '(No message)';
							}

		}
		
		$accessToken = "";
		$client = $this->isValidToken($user->google_token);
		if($client)
		{
			$subject = $request->subject;
			$accessToken = json_decode($user->google_token, TRUE)['access_token'];
			return view('inbox_readonly_single_attachment', compact("accessToken", "threadId", "subject", "BODY", "attachments"));
		} else{
			return redirect('/gmail/auth');
		}
	}

}
