<?php

namespace App\Http\Controllers;

use App\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller {

	public function login( Request $request ) {
		
		$vendor = Vendor::whereImei($request->imei)->wherePin($request->pin)->first();
		
		if ( $vendor ) {
			if( !$vendor->api_token ) 
				$vendor->api_token = Hash::make($request->imei.$request->pin); //str_random( 60 );

			$vendor->save();

			auth()->loginUsingId( $vendor );
			return new \App\Http\Resources\Vendor($vendor);	

		}

		return response()->json( [

			'data' => [
				'name' => '',
		    	'token' => ''
			],
			'code'  => 401,
			'status' => 'error',
			'message' => 'Unauthenticated vendor'

		], 401 );

	}

	public function logout( Request $request ) {

		if ( auth()->user() ) {
			$user            = auth()->user();
			$user->api_token = null; // clear api token
			$user->save();

			return response()->json( [
				'message' => 'Thank you for using our application',
			] );
		}

		return response()->json( [
			'error' => 'Unable to logout user',
			'code'  => 401,
		], 401 );
	}

}
