<?php

namespace App\Http\Controllers;

use App\Booking;
use App\Lunch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LunchController extends Controller {
	public function index( Request $request ) {

		$perPage = $request->per_page ?? getPerPage();

		$data['items'] = Lunch::where( 'name', 'like', '%' . $request->search . '%' )
		                      ->paginate( $perPage )
		                      ->appends( [ 'search' => $request->search, 'per_page' => $request->per_page ] );

		$data['itemFound']  = $data['items']->count();
		$data['searchItem'] = $request->search;

		return view( 'lunches.index', $data );
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {

		return view( 'lunches.create' );
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
				'name' => 'required|min:3|max:20|unique:lunch_items,name',
			]
		);

		$lunch       = new Lunch();
		$lunch->name = $request->name;
		$lunch->save();

		Session::flash( 'success', 'Lunch added successfully' );

		return redirect( '/lunch' );
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function edit( Lunch $lunch ) {

		$data['item'] = $lunch;

		return view( 'lunches.edit', $data );
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function update( Request $request, Lunch $lunch ) {

		$this->validate( $request,
			[
				'name' => 'required|min:3|max:20|unique:lunch_items,name,' . $lunch->id,
			]
		);

		$lunch->name = $request->name;
		$lunch->save();

		return redirect( '/lunch' );
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function destroy( Lunch $lunch ) {

		if ( Booking::isThereAnyRelatedBooking( 'lunch_item_id', $lunch->id ) ) {
			Session::flash('error', 'Sorry, Some booking are being made with this item');
			return back();
		}

		if ( $lunch->forceDelete() ) {
			Session::flash( 'success', 'Lunch item deleted successfully' );
		}

		return redirect( '/lunch' );
	}
}
