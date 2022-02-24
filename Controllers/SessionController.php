<?php

namespace App\Http\Controllers;

use App\CustomHelpers\CustomHelper;
use App\User;
use Illuminate\Http\Request;

/**
 * Class SessionController
 * @package App\Http\Controllers
 */
class SessionController extends Controller {

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
	 */
	public function create() {

		if ( \Auth::check() ) {
			return redirect( '/' );
		}

		return view( 'elements.login' );
	}

	/**
	 * @param Request $request
	 *
	 * @return mixed
	 */
	public function login( Request $request ) {

		$this->validate($request, [
			'email' => 'required|email',
			'password' => 'required',
			'remember' => 'nullable|bool'
		]);

		$email 	  = $request->email;
		$password = $request->password;
		$remember = $request->has( 'remember' );
		
		if (\Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
			
			$intendedUri = redirect()->intended()->getTargetUrl();
			$data['intended_uri'] = $intendedUri;

			$data = $this->message();
			
			$data['status']  = 'success';
			$data['header']  = 'Success';
			$data['message'] = "You are now logged in";
		} else {
			$data['status']  = 'error';
		    $data['header']  = 'Opps!';
		    $data['message'] = 'Credential Mismatch';
		}

		return $data;
	}

	/**
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function logout() {
		CustomHelper::forgetNecessaryCacheData();

		auth()->logout();

		return redirect( '/' );
	}

	/**
	 * @return mixed
	 */
	public function message() {
		CustomHelper::forgetNecessaryCacheData();

		$data['status']  = 'success';
		$data['header']  = 'Success';
		$data['message'] = "Welcome, " . auth()->user()->name;

		CustomHelper::setAuthFullName();

		return $data;
	}

}
