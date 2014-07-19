<?php


class UserController extends BaseController {


    // Display all Users
    public function index()
    {
        $users = User::paginate(10);
        return View::make('user.index', compact('users'));
    }


    // Display the registration form for account creation

    public function register()
    {
        return View::make('auth.register');
    }


    // Save registration details, create user account and send activation email

    public function processRegister()
    {
        $data = Input::all();
        $validator = User::validate_registration($data);
        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput(Input::except('password', 'password_confirm'));
        }
        $code = str_random(32);
        $user = new User;
        $user->username = Input::get('username');
        $user->email = Input::get('email');
        $user->password = Hash::make(Input::get('password'));
        $user->activation_code = $code;
        $user->activated =  0;
        $user->save();
        $activation_link = URL::route('activate', $code);
        //$user->email is out of scope for the mail closure, hence to access it, we have defined "use ($user)"
        Mail::send('emails.users.activate', ['link' => $activation_link, 'username' => Input::get('username')], function($message) use($user) {
            $message->to($user->email, $user->username)->subject('Activate Your Account');
        });
        return Redirect::action('UserController@login')->withInfo('To Activate your account, please check your Email for instructions');
    }


    // Display the Login form

    public function login()
    {
        return View::make('auth.login');
    }


    // Attempt to Login user

    public function processLogin()
    {
        $data = Input::all();
        $validator = User::validate_login($data);
        if ($validator->fails())    {
            return Redirect::back()->withErrors($validator)->withInput(Input::except('password'));
        }
        else {
            $user = User::where('email', '=', Input::get('email'))->first();
            if ( ! $user == null) {  // Check if user in DB
                if ( $user->activated == 0)  {  // Check if user is activated
                    return Redirect::back()->withWarning('Account Activation is pending. We have already sent you an Activation Email. Resend activation email');
                }
                $attempt = Auth::attempt(['email' => $data['email'], 'password' => $data['password']], Input::get('remember'));
                if ( $attempt == true) {  // Check if user was authenticated
                    return Redirect::intended('dashboard')->withSuccess('Your have successfully logged in');
            }
                return Redirect::back()->withInput(Input::except('password'))->withError('Invalid Credentials - Your email or password is incorrect.');
            }
            return Redirect::back()->withInput(Input::except('password'))->withError('Invalid Credentials - Your email or password is incorrect.');
        }
    }


    // Attempt to activate account with code

    public function activate($code)
    {
        $user = User::where('activation_code', '=', $code)->where('activated', '=', 0)->first();
        if ( ! $user == null) {
            $user->activated = 1;
            $user->save();
            return Redirect::to('login')->withSuccess('Your Account is now Activated');
        }
        return Redirect::to('login')->withError('Invalid Activation Code: Your account could not be activated. Resend activation email');
    }


    // Logout the user

    public function logout()
    {
        Auth::logout();
        return Redirect::to('login')->withInfo('You have logged out');
    }



}