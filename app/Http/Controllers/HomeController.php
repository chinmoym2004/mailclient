<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailTracker;
use App\Http\Controllers\GmailController;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $emails = EmailTracker::all();
        return view('email_tracker',compact('emails'));
    }

    public function AddToTracking(Request $request)
    {
        if($request->email)
        {
            $newemail = EmailTracker::where('email',$request->email)->first();
            if(!$newemail)
            {
                $newemail = new EmailTracker;
                $newemail->email = $request->email;
                $newemail->platform = $request->platform ?? 'gmail';
                $newemail->save();
            }
        }

        return back();
    }

    public function getToken(Request $request)
    {
        if($request->email)
        {
            $gc = new GmailController;
            $request->session()->put('email',$request->email);
            $client = $gc->getClient();
            $authUrl = $client->createAuthUrl();
            return redirect($authUrl);
        }
    }

    public function getMSToken(Request $request)
    {
        $token = null;
        $req_mail = EmailTracker::where('email',$request->email)->first();
        if($request->email)
        {
            try
            {
                $guzzle = new \GuzzleHttp\Client();
                $tenantId = env('MS_TANENT_ID');
                $url = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/token?api-version=1.0';
                $token = json_decode($guzzle->post($url, [
                    'form_params' => [
                        'client_id' => env('MS_CLIENT_ID'),
                        'client_secret' => env('MS_CLIENT_SECRET'),
                        'resource' => 'https://graph.microsoft.com/',
                        'grant_type' => 'client_credentials',
                    ],
                ])->getBody()->getContents());
            }
            catch(\Exeption $e)
            {
                report($e);
                return back();
            }

            $req_mail->enable_tracking = 1;
            $req_mail->provider_refresh_token = '';
            $req_mail->expires_at = $token->expires_in;
            $req_mail->provider_token = $token->access_token;
            $req_mail->save();

            return back();
        }
    }
}
