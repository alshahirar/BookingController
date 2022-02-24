<?php

namespace App\Http\Controllers;

use App\Booking;
use App\Exports\ReportMealExport;
use App\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BookingController extends Controller {

	public function index( Request $request ) {

		$days = Setting::retriveSettingsData( 'days' ) ?: getDefaultDays();

		try {

			if ( ! $request->q or ! Carbon::parse( base64_decode( $request->q ) )
			                              ->between( Carbon::yesterday(), Carbon::parse( "$days days" ) ) ) {

				throw new \Exception();
			}

		} catch ( \Exception $e ) {
			\Session::flash( 'error', 'Please proceed with a valid date' );

			return redirect( '/' );
		}

		$data['user'] = auth()->user();

		return view( 'booking.index', $data );
	}

	public function store( Request $request ) {

		$this->validate( $request, [
			'breakfast_snacks' => 'nullable',
			'lunch_dinner'     => 'required',
			'area'             => 'required',
			'date'             => 'required|date|after:yesterday',
		] );

		$booking          = new Booking();
		$booking->user_id = auth()->id();
		$booking->area_id = $request->area;
		$booking->date    = $request->date;

		if ( $request->breakfast_snacks ) {

			$contentBreakfastSnacks = explode( '_', $request->breakfast_snacks );

			$breakfastSnacks = end( $contentBreakfastSnacks );

			$booking->$breakfastSnacks = $contentBreakfastSnacks[0];
		}

		$contentLunchDinner = explode( '_', $request->lunch_dinner );

		$lunchDinner = end( $contentLunchDinner ) === 'lunch' ? 'lunch_item_id' : 'dinner_item_id';

		/*if ( $request->lunch_dinner && strtotime( $request->date ) <= strtotime( 'tomorrow' ) ) {

			if ( $lunchDinner === 'lunch_item_id' && strtotime( "$request->date -1 day 8pm" ) <= strtotime( "now" ) ) {

				\Session::flash( 'error', 'Sorry, time expired. You cann\'t place order for lunch' );

				return back();

			} elseif ( $lunchDinner === 'dinner_item_id' && strtotime( "$request->date -1 day 11:59pm" ) <= strtotime( "now" ) ) {

				\Session::flash( 'error', 'Sorry, time expired. You cann\'t place order for dinner' );

				return back();

			}
		}*/

		$booking->$lunchDinner = $contentLunchDinner[0];

		$booking->save();

		\Session::flash( 'success', 'Booking saved successfully' );

		return redirect( '' );
	}

	public function edit( $id ) {

		Booking::withTrashed()->findOrFail( $id );

		$booking = Booking::withTrashed()->whereId( $id )->with( [ 'lunch', 'dinner', 'area' ] )->first();

		if ( ! $this->isModifyable( $booking, 'edit' ) ) {
			return back();
		}

		return view( 'booking.edit', compact( 'booking' ) );
	}

	public function update( Request $request, $id ) {

		$this->validate( $request, [
			'breakfast_snacks' => 'nullable',
			'lunch_dinner'     => 'required',
			'area'             => 'required',
			'date'             => 'required|date|after:yesterday',
		] );

		$booking = Booking::withTrashed()->findOrFail( $id );

		$booking->breakfast      = null;
		$booking->lunch_item_id  = null;
		$booking->snacks         = null;
		$booking->dinner_item_id = null;
		$booking->area_id        = $request->area;

		if ( $request->breakfast_snacks ) {

			$contentBreakfastSnacks = explode( '_', $request->breakfast_snacks );

			$breakfastSnacks = end( $contentBreakfastSnacks );

			$booking->$breakfastSnacks = $contentBreakfastSnacks[0];
		}

		$contentLunchDinner = explode( '_', $request->lunch_dinner );

		$lunchDinner = end( $contentLunchDinner ) === 'lunch' ? 'lunch_item_id' : 'dinner_item_id';

		if ( ! $this->isModifyable( $booking, 'update' ) ) {
			return back();
		}

		$booking->$lunchDinner = $contentLunchDinner[0];

		if ( $booking->trashed() ) {
			$booking->deleted_at = null;
		}

		$booking->save();

		\Session::flash( 'success', 'Booking saved successfully' );

		return redirect( '' );
	}

	public function destroy( Request $request, Booking $booking ) {

		if ( ! $this->isModifyable( $booking, 'delete' ) ) {
			return back();
		}

		$booking->delete();

		return redirect( '' );
	}

	public function generateMealReport() {

		return view( 'reports.meal' );
	}

	public function downloadMealReport( Request $request ) {

		$this->validate( $request, [
			'from' => 'required|date',
			'to'   => 'required|date'
		] );

		$from = Carbon::parse( $request->from )->format( 'Y-m-d' );
		$to   = Carbon::parse( $request->to )->format( 'Y-m-d' );

		$bookings = Booking::whereBetween( 'date', [ $from, $to ] )
		                   ->with( [
			                   'area',
			                   'lunch',
			                   'dinner',
			                   'user'
		                   ] )
		                   ->whereNull( 'canceled_by' )
		                   ->when( ! auth()->user()->isAdmin(), function ( $query ) {
			                   $query->where( 'user_id', auth()->id() );
		                   } )->get();

		if ( ! count( $bookings ) ) {

			\Session::flash( 'info', 'Sorry!, no record found' );

			return redirect()->back();
		}

		fileCache()->set( "meal_report_" . auth()->id(), $bookings, 2 );

		return Excel::download( new ReportMealExport(), 'meal_report_' . date( 'Y-m-d h:i a' ) . '.csv' );
	}

	public function isModifyable( Booking $booking, $action = 'cancel' ) {

		if ( $booking->lunch_item_id && strtotime( "$booking->date 1am" ) <= strtotime( "now" ) ) {

			\Session::flash( "error", "Sorry, time expired. You can not $action your lunch order" );

			return false;

		} elseif ( $booking->dinner_item_id && strtotime( "$booking->date 6am" ) <= strtotime( "now" ) ) {

			\Session::flash( "error", "Sorry, time expired. You can not $action your dinner order" );

			return false;

		}

		return true;
	}

}
