<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    //////////// SHOW FUNCTIONS ////////////
    
    /**
     * @brief Method for showing register form view.
     */
    public function showRegister(){
        return view('user/register');
    }

    /**
     * @brief Method for showing login form view.
     */
    public function showLogin(){
        return view('user/login');
    }

    public function showProfile(){
        $user = auth()->user();

        return view('user/myProfile', [
            'user' => $user
        ]);
    }

    //////////// DATA FUNCTIONS ////////////

    /**
     * @brief Method for validating register information and creating new user.
     * 
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(),[
            'email' => ['required', 'max:180', 'email', Rule::unique('User', 'email')],
            'nickname' => ['required', 'min:3', Rule::unique('User', 'nickname')],
            'password' => 'required|confirmed|min:6'
        ]);

        if($validator->passes()){
            $formFields = $validator->validated();

            // Hash Password
            $formFields['password'] = bcrypt($formFields['password']);
            $formFields['creation_time'] = Carbon::now('Europe/Prague');

            // Create User
            $user = User::create($formFields);
            // return response()->json(['success' => view('user/login')->render()]);
            return response()->json(['success'=>'OK']);
        }

        $errors = $validator->errors();
        $errMsgs = [];

        if($errors->has('email')){
            if(strlen($request['email']) > 180){
                $errMsgs['email'] = '* Email je příliš dlouhý.';
            }
            else{
                $errMsgs['email'] = '* Uživatel s touto e-mailovou adresou již existuje.';
            }
        }

        if($errors->has('password')){
            if(strlen($request['password']) < 6){
                $errMsgs['password'] = '* Heslo musí obsahovat alespoň 6 znaků.';
            }
            else{
                $errMsgs['password'] = '* Hesla se neshodují. Zopakujte prosím znovu.';
            }
        }
        
        return response()->json(['error'=>$errMsgs]);
    }

    /**
     * @brief Method for dynamic checking of nickname.
     */
    public function check_for_nick(Request $request){
        if(is_null($request['nickname'])){
            return response()->json(['error' => '* Nickname je povinný parametr.']);
        }

        if(strlen($request['nickname']) < 3){
            return response()->json(['error' => '* Nickname musí mít alespoň 3 znaky.']);
        }

        $validator = Validator::make($request->all(),[
            'nickname' => [Rule::unique('User', 'nickname')]
        ]);

        if($validator->fails()){
            return response()->json(['error' => '* Tento nick už je zabraný.']);
        }

        return response()->json(['success' => 'OK']);
    }

    /**
     * @brief Method for authenticating user.
     */
    public function authenticate(Request $request) {
        $validator = Validator::make($request->all(),[
            'email' => ['required', 'email'],
            'password' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['error' => '* Některý přihlašovací parametr nebyl zadán.']);
        }

        // Try to login
        if(auth()->attempt($validator->validated())) {
            $request->session()->regenerate();

            return response()->json(['success' => 'OK']);
        }

        return response()->json(['error' => '* Zadali jste nesprávnou e-mailovou adresu nebo heslo.']);
    }

    /**
     * @brief Method for user logout.
     */
    public function logout(Request $request) {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('mainPage');
    }

    /**
     * Function for changing users password.
     */
    public function changePassword(Request $request){
        $validator = Validator::make($request->all(),[
            'password' => 'required|confirmed|min:6'
        ]);

        if($validator->passes()){
            $formFields = $validator->validated();
            $user = auth()->user();

            $user['password'] = bcrypt($formFields['password']);

            $user->save();
            return response()->json(['success'=>'OK']);
        }

        $errors = $validator->errors();
        if($errors->has('password')){
            if(strlen($request['password']) < 6){
                $errMsgs['password'] = '* Heslo musí obsahovat alespoň 6 znaků.';
            }
            else{
                $errMsgs['password'] = '* Hesla se neshodují. Zopakujte prosím znovu.';
            }

            return response()->json(['error'=>$errMsgs]);
        }

        return response()->json(['error'=>'UNKNOWN']);
    }

    /**
     * Show reset password page.
     */
    public function forgot(){
        return view('user.resetPassword', ['reseted' => false]);
    }

    /**
     * Function for sending reset password email.
     */
    public function resetPassword(Request $request){
        $request->validate([
            'email' => 'required|email|exists:User,email'
        ]);

        $tkn = Str::random(64);
        DB::table('PasswordReset')->insert(['email' => $request['email'], 'token' => $tkn, 'date' => Carbon::now('Europe/Prague')]);

        $link = route('resetForm', ['token' => $tkn, 'email' => $request['email']]);

        $mail_message = 'Obdrželi jsme žádost o resetování hesla. Jestliže chcete pokračovat klikněte na tlačítko níže. Pokud se nejednalo o Vás tak tento email ignorujte.';
    
        Mail::send('user.email', ['action_link' => $link, 'body' => $mail_message], function ($message) use ($request) {
            $message->from('gedhelp4@gmail.com', 'GedHelp');
            $message->to($request['email'], 'Jmeno');
            $message->subject('Obnova hesla');
        });

        return back()->with('reseted', true);
    }

    /**
     * Show page for setting new password.
     */
    public function showResetForm(Request $request, $token = null){
        return view('user.setNewPassword', ['email' => $request['email'], 'token' => $token, 'failed' => false]);
    }

    /**
     * Function for setting new password after reset.
     */
    public function setNewPassword(Request $request){
        $request->validate(['password' => 'required|confirmed|min:6']);

        $tkn_exists = DB::table('PasswordReset')->where('token', '=', $request['token'])->where('email', '=', $request['email'])->first();
        if($tkn_exists){
            $user = User::where('email', '=', $request['email'])->first();
            $user['password'] = bcrypt($request['password']);
            $user->save();

            DB::table('PasswordReset')->where('email', '=', $request['email'])->delete();
        }
        else{
            return back()->with('failed', true);
        }

        return view('user.login');
    }

}
