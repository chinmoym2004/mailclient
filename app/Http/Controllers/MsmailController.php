<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Http\GraphRequest;
use Beta\Microsoft\Graph\Model as BetaModel;
use Session;
use App\Models\EmailTracker;

class MsmailController extends Controller
{


    public function isValidResponse($response)
    {   

        if(isset($response['error']) && $response['error']['code']=='InvalidAuthenticationToken')
        {
            return 0;
        }
        else 
            return 1;
    }

	public function getClient()
	{
	    $provider = new \TheNetworg\OAuth2\Client\Provider\Azure([
            'clientId'          => env('MS_CLIENT_ID'),
            'clientSecret'      => env('MS_CLIENT_SECRET'),
            'redirectUri'       => 'http://localhost:8085/ms-callback',
            //Optional
            'scopes'            => ['openid'],
            //Optional
            'defaultEndPointVersion' => '2.0'
        ]);

        // Set to use v2 API, skip the line or set the value to Azure::ENDPOINT_VERSION_1_0 if willing to use v1 API
        $provider->defaultEndPointVersion = \TheNetworg\OAuth2\Client\Provider\Azure::ENDPOINT_VERSION_2_0;

        $baseGraphUri = $provider->getRootMicrosoftGraphUri(null);
        $provider->scope = 'openid profile email offline_access ' . $baseGraphUri . '/User.Read '.$baseGraphUri . '/Mail.ReadWrite '.$baseGraphUri . '/Mail.Send';

        return $provider;
	}

    public function refreshToken($user)
    {
        $client = $this->getClient();

        $accessToken = \Illuminate\Support\Facades\Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
            'client_id'     => env('MS_CLIENT_ID'),
            'client_secret' => env('MS_CLIENT_SECRET'),
            'refresh_token' => $user->provider_refresh_token,
            'grant_type'    => 'refresh_token',
            'redirect_uri'  => 'http://localhost:8085/ms-callback',
            'scope'         => 'User.Read Mail.Send Mail.ReadWrite offline_access'
        ]);

        $user->provider_token = $accessToken['access_token'];
        $user->enable_tracking = 1;
        $user->provider_refresh_token = $accessToken['refresh_token'];
        $user->expires_at = $accessToken['expires_in'];
        $user->save();

        return $user->provider_token;
    }

	public function callback(Request $request)
	{
        //print_r($request->all());exit;
        // Process 2---
        $accessToken = \Illuminate\Support\Facades\Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
            'client_id'     => env('MS_CLIENT_ID'),
            'client_secret' => env('MS_CLIENT_SECRET'),
            'code'          => $request->input('code'),
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => 'http://localhost:8085/ms-callback',
            'scope'         => 'User.Read Mail.Send Mail.ReadWrite offline_access'
        ]);

        // $accessToken = $authReponse['access_token'];
        // $refresh_toen = $authReponse['refresh_token'];
        // $expires_in = $authReponse['expires_in'];

        $ms_user =  \Illuminate\Support\Facades\Http::withToken($accessToken['access_token'])->get('https://graph.microsoft.com/v1.0/me');
        $ms_user = json_decode($ms_user,true);

        if(isset($ms_user['mail']))
        {
            $req_mail = EmailTracker::where('email',$ms_user['mail'])->first();
            $req_mail->enable_tracking = 1;
            $req_mail->provider_refresh_token = $accessToken['refresh_token'];
            $req_mail->expires_at = $accessToken['expires_in'];
            $req_mail->provider_token = $accessToken['access_token'];
            $req_mail->save();

            return redirect('/');
        }
        else
        {
            return redirect('/ms-authenticate');
        }

        // Process 1---

        $ms = new MsmailController;
		$client = $ms->getClient();

       // print_r(Session::all());
       // echo "<br/><br/>";
       // print_r($request->all());
       // echo Session::get('OAuth2.state');
       // exit;

		if(isset($request->code) && Session::has('OAuth2.state') && isset($request->state)) {
            if ($request->state == Session::get('OAuth2.state')) {
                Session::forget('OAuth2.state');

                // Try to get an access token (using the authorization code grant)
                /** @var AccessToken $token */
                $token = $client->getAccessToken('client_credentials', [
                    //'scope' => $client->scope,
                    'code' => $request->code,
                ]);

                print_r($token);

                // Verify token
                // Save it to local server session data
                
                return $token->getToken();
            } else {
                echo 'Invalid state';

                return null;
            }
        }
        else 
        {
            echo 'Invalid state';
            return null;
        }
	}
}
