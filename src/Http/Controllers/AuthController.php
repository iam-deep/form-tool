<?php

namespace Biswadeep\FormTool\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function index()
    {
        return redirect()->route('login');
    }

    public function login(Request $request)
    {
        $data['title'] = 'Login';

        return view('form-tool::auth.login', $data);
    }

    public function loginPost(Request $request)
    {
        $postData = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = DB::table('users')->where('email', $postData['email'])->where('status', 1)->first();
        if ($user && Hash::check($postData['password'], $user->password)) {
            /*if (isset($_POST['rememberMe'])) {
                $key = hash('sha512', $this->getRandStr(64).$data['salt']);
                $this->input->set_cookie('_seting', serialize(array(0 => $data['userID'], 1 => $key)), $this->config->item('remember_me_validity') * 24 * 60 * 60);
                $this->core_model->setRememberMe($data['userID'], hash('sha512', $key.$data['password']));
            }
            else {
                $this->input->set_cookie('_seting', '');
                $this->core_model->unsetRememberMe($data['userID']);
            }
            */

            $user->adminLoginToken = Hash::make($user->password.$user->email.$_SERVER['HTTP_USER_AGENT']);

            unset($user->password);

            Session::put('user', $user);
            Cookie::make('i_am_admin', $user->userId, now()->addDays(90)->diffInSeconds());

            $loginRedirect = config('form-tool.adminURL').config('form-tool.loginRedirect', '/dashboard');

            return redirect($loginRedirect);
        }

        return redirect()->route('login')->with('error', 'Your email or password is incorrect!')->withInput($request->except('password'));
    }

    public function logout()
    {
        Session::pull('user');

        //TODO: Remove testing
        Session::flush();

        return redirect()->route('login')->with('success', 'You have been successfully logged out!');
    }
}
