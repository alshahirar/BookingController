<?php

namespace App\Http\Controllers;

use App\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class VendorController extends Controller {
	public function index( Request $request ) {

		$perPage = $request->per_page ?? getPerPage();

		$data['items'] = Vendor::where( 'name', 'like', '%' . $request->search . '%' )
		                       ->paginate( $perPage )
		                       ->appends( [ 'search' => $request->search, 'per_page' => $request->per_page ] );

		$data['itemFound']  = $data['items']->count();
		$data['searchItem'] = $request->search;

		return view( 'vendors.index', $data );
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {

		return view( 'vendors.create' );
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function store( Request $request ) {

		$this->validate( $request,
			[
				'name' => 'required|min:3|max:20|unique:vendors,name',
				'imei' => 'required|numeric|digits:11|unique:vendors,imei',
				'pin'  => 'required|min:4|max:4'
			],[
				'imei.required' => 'The mobile number field is required',
				'imei.numeric' => 'The mobile number must be a number',
				'imei.digits' => 'The mobile number must be 11 digits',
				'imei.unique' => 'The mobile number has already been taken',
			]
		);

		$vendor       = new Vendor();
		$vendor->name = $request->name;
		$vendor->imei = $request->imei;
		$vendor->pin  = $request->pin;
		$vendor->save();

		Session::flash( 'success', 'Vendor added successfully' );

		return redirect( '/vendor' );
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function edit( Vendor $vendor ) {

		$data['item'] = $vendor;

		return view( 'vendors.edit', $data );
	}

	/**
	 * Update the specified resource in storage.Community
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function update( Request $request, Vendor $vendor ) {

		$this->validate( $request,
			[
				'name' => 'required|min:3|max:20|unique:vendors,name,' . $vendor->id,
				'imei' => 'required|numeric|digits:11|unique:vendors,imei,' . $vendor->id,
				'pin'  => 'required|min:4|max:4'
			],[
				'imei.required' => 'The mobile number field is required',
				'imei.numeric' => 'The mobile number must be a number',
				'imei.digits' => 'The mobile number must be 11 digits',
				'imei.unique' => 'The mobile number has already been taken',
			]
		);

		$vendor->name = $request->name;
		$vendor->imei = $request->imei;
		$vendor->pin  = $request->pin;

		if($vendor->isDirty()) {
			Session::flash( 'success', 'Vendor update successfully' );
		} elseif ($vendor->save()) {
			Session::flash( 'warning', 'No change made!' );
		} else {
			Session::flash( 'error', 'Vendor update failed' );
		}

		return redirect( '/vendor' );
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function destroy( Vendor $vendor ) {

		if ( $vendor->forceDelete() ) {
			Session::flash( 'success', 'Vendor item deleted successfully' );
		}

		return redirect( '/vendor' );
	}
}
