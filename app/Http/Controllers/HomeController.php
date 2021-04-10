<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailTracker;
use App\Http\Controllers\GmailController;
use Session;
use Str;
use Storage;
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
        // Process 1---
        // $token = null;
        // $req_mail = EmailTracker::where('email',$request->email)->first();
        // if($request->email)
        // {
        //     try
        //     {
        //         $guzzle = new \GuzzleHttp\Client();
        //         $tenantId = env('MS_TANENT_ID');
        //         $url = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/token?api-version=1.0';
        //         $token = json_decode($guzzle->post($url, [
        //             'form_params' => [
        //                 'client_id' => env('MS_CLIENT_ID'),
        //                 'client_secret' => env('MS_CLIENT_SECRET'),
        //                 'resource' => 'https://graph.microsoft.com/',
        //                 'grant_type' => 'client_credentials',
        //             ],
        //         ])->getBody()->getContents());
        //     }
        //     catch(\Exeption $e)
        //     {
        //         report($e);
        //         return back();
        //     }

        //     $req_mail->enable_tracking = 1;
        //     $req_mail->provider_refresh_token = '';
        //     $req_mail->expires_at = $token->expires_in;
        //     $req_mail->provider_token = $token->access_token;
        //     $req_mail->save();

        //     return back();
        // }

        // $ms = new MsmailController;
        // $client = $ms->getClient();

        // $authorizationUrl = $client->getAuthorizationUrl(['scope' => $client->scope]);
        // Session::put('OAuth2.state',$client->getState());
        // return redirect($authorizationUrl);

        // Process 2---
        $authUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
        $query   = http_build_query([
            'client_id'     => env('MS_CLIENT_ID'),
            'client_secret' => env('MS_CLIENT_SECRET'),
            'response_type' => 'code',
            'redirect_uri'  => 'http://localhost:8019/msmail',
            'scope'         => 'User.Read Mail.Send Mail.ReadWrite offline_access'
        ]);

        return redirect()->away($authUrl . '?' . $query);
    }

    public function uploadFile(Request $request)
    {
        if($request->hasFile('editorfile'))
        {
            $file = $request->file('editorfile');

            $fullName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);;
            $extension = strtolower($file->getClientOriginalExtension());
            $mime = $file->getClientMimeType();
            $newfilename = Str::uuid().'.'.$extension;
            Storage::put($newfilename, file_get_contents($file));

            return env('APP_URL').Storage::url($newfilename);
        }
    }
}
