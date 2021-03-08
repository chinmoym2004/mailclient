<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\GmailController;
use App\Models\EmailTracker;

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

            // loop and save thread in DB 
            dd($threads);
        }
    }
}
