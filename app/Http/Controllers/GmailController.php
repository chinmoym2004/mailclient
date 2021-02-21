<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Gmail;
use App\Models\User;

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

	public function callback(Request $request)
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

	// This function to be called before each API call - to validate token 

	public function isValidToken($user_token)
	{
		if(isset($user_token))
		{
			$client = $this->getClient();
			$token = json_decode($user_token,true);
			//print_r($token);exit;
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

	public function sendEmail(Request $request) {
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
				$message->setBody('Here is my body');
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
		return view('inbox_readonly');
	}
}
