<?php

namespace App\Helpers;

use Google_Client;
use Google_Service_Gmail;

class GmailHelper
{
    
    public $client;

    public $user;

    public function __construct() {
        $this->client = new Google_Client();
	    //$client->setApplicationName('Gmail API PHP Quickstart');
	    $this->client->setScopes(Google_Service_Gmail::MAIL_GOOGLE_COM);
	    $this->client->setAuthConfig(public_path().'/client_id_marimuthu.json');
	    $this->client->setAccessType('offline');
	    //$client->setRedirectUri('/mail');
        $this->client->setPrompt('select_account consent');
        $this->user = "me";
    }

    public function getClient() {
	    return $this->client;
    }

    public function getService() {
        return  new \Google_Service_Gmail($this->client);
    }

    public function getThreads($optParams = []) {
        $service = $this->getService();
        return  $service->users_threads->listUsersThreads($this->user, $optParams);
    }

    public function getLabels($optParams = []) {
        $service = $this->getService();
        return $service->users_labels->listUsersLabels($this->user);
    }
}