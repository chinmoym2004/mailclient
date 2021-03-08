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
                $newemail->platform = 'gmail';
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
}
