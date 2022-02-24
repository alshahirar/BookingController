<?php

namespace App\Http\Controllers;

use App\Booking;
use App\Dinner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class DinnerController extends Controller {
	public function index( Request $request ) {

		$perPage = $request->per_page ?? getPerPage();

		$data['items'] = Dinner::where( 'name', 'like', '%' . $request->search . '%' )
		                       ->paginate( $perPage )
		                       ->appends( [ 'search' => $request->search, 'per_page' => $request->per_page ] );

		$data['itemFound']  = $data['items']->count();
		$data['searchItem'] = $request->search;

		return view( 'dinners.index', $data );
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {

		return view( 'dinners.create' );
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
				'name' => 'required|min:3|max:20|unique:dinner_items,name',
			]
		);

		$dinner       = new Dinner();
		$dinner->name = $request->name;
		$dinner->save();

		Session::flash( 'success', 'Dinner added successfully' );

		return redirect( '/dinner' );
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function edit( Dinner $dinner ) {

		$data['item'] = $dinner;

		return view( 'dinners.edit', $data );
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function update( Request $request, Dinner $dinner ) {

		$this->validate( $request,
			[
				'name' => 'required|min:3|max:20|unique:dinner_items,name,' . $dinner->id,
			]
		);

		$dinner->name = $request->name;
		$dinner->save();

		return redirect( '/dinner' );
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function destroy( Dinner $dinner ) {

		if ( Booking::isThereAnyRelatedBooking( 'dinner_item_id', $dinner->id ) ) {
			Session::flash('error', 'Sorry, Some booking are being made with this item');
			return back();
		}

		if ( $dinner->forceDelete() ) {
			Session::flash( 'success', 'Dinner item deleted successfully' );
		}

		return redirect( '/dinner' );
	}
}
