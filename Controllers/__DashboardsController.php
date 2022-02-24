<?php

namespace App\Http\Controllers;

use App\Booking;
use App\Setting;
use Carbon\Carbon;

/**
 * Class DashboardsController
 * @package App\Http\Controllers
 */
class DashboardsController extends Controller {
	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index() {

		$data = auth()->user()->isAdmin() ? $this->prepareDashboardDataForAdmin() : $this->prepareDashboardDataForEmployee();

		return view( 'dashboard', $data );
	}

	/**
	 * @return array
	 */
	public function prepareDashboardDataForAdmin() {

		$bookings = Booking::with( [ 'lunch', 'dinner', 'area' ] )
		                   ->where( function ( $q ) {
			                   $q->whereDate( 'date', Carbon::today() )
			                     ->whereNull( 'canceled_by' );
		                   } )->get();

		$totalBreakfast = 0;
		$totalSnacks    = 0;
		$totalLunch     = 0;
		$totalDinner    = 0;

		foreach ( $bookings as $key => $booking ) {

			if ( $booking->breakfast ) {

				$data['totalBreakfast'] = ++ $totalBreakfast;

			} elseif ( $booking->snacks ) {

				$data['totalSnacks'] = ++ $totalSnacks;
			}

			if ( $booking->lunch ) {

				$data['totalLunch'] = ++ $totalLunch;

				isset( $data[ $booking->lunch->name . 'qty' ] ) ? ++ $data[ $booking->lunch->name . 'qty' ] : $data[ $booking->lunch->name . 'qty' ] = 1;

				$data[ $booking->area->name ]['lunch'][ $booking->lunch->id ] = [
					'id'   => $booking->lunch->id,
					'name' => $booking->lunch->name,
					'qty'  => $data[ $booking->lunch->name . 'qty' ]
				];


			} elseif ( $booking->dinner ) {

				$data['totalDinner'] = ++ $totalDinner;

				isset( $data[ $booking->dinner->name . 'qty' ] ) ? ++ $data[ $booking->dinner->name . 'qty' ] : $data[ $booking->dinner->name . 'qty' ] = 1;

				$data[ $booking->area->name ]['dinner'][ $booking->dinner->id ] = [
					'id'   => $booking->dinner->id,
					'name' => $booking->dinner->name,
					'qty'  => $data[ $booking->dinner->name . 'qty' ]
				];
			}

			$data['areas'][ $booking->area->name ] = [
				'id'        => $booking->area->id,
				'name'      => $booking->area->name,
				'breakfast' => $booking->breakfast ? ( isset( $data[ $booking->area->name . 'breakfast_qty' ] ) ? ++ $data[ $booking->area->name . 'breakfast_qty' ] : $data[ $booking->area->name . 'breakfast_qty' ] = 1 ) : ( isset( $data[ $booking->area->name . 'breakfast_qty' ] ) ? $data[ $booking->area->name . 'breakfast_qty' ] : $data['breakfast_qty'] = 0 ),
				'snacks'    => $booking->snacks ? ( isset( $data[ $booking->area->name . 'snacks_qty' ] ) ? ++ $data[ $booking->area->name . 'snacks_qty' ] : $data[ $booking->area->name . 'snacks_qty' ] = 1 ) : ( isset( $data[ $booking->area->name . 'snacks_qty' ] ) ? $data[ $booking->area->name . 'snacks_qty' ] : $data['snacks_qty'] = 0 ),
				'lunchQty'  => $booking->lunch ? ( isset( $data[ $booking->area->name . 'lunch_qty' ] ) ? ++ $data[ $booking->area->name . 'lunch_qty' ] : $data[ $booking->area->name . 'lunch_qty' ] = 1 ) : ( isset( $data[ $booking->area->name . 'lunch_qty' ] ) ? $data[ $booking->area->name . 'lunch_qty' ] : $data['lunch_qty'] = 0 ),
				'dinnerQty' => $booking->dinner ? ( isset( $data[ $booking->area->name . 'dinner_qty' ] ) ? ++ $data[ $booking->area->name . 'dinner_qty' ] : $data[ $booking->area->name . 'dinner_qty' ] = 1 ) : ( isset( $data[ $booking->area->name . 'dinner_qty' ] ) ? $data[ $booking->area->name . 'dinner_qty' ] : $data['dinner_qty'] = 0 ),
			];
		}

		if ( isset( $data['areas'] ) ) {

			foreach ( $data['areas'] as $name => $perArea ) {
				$data['areas'][ $name ]['menuItems'] = $data[ $name ];
			}

		}

		return $data ?? [];
	}

	/**
	 * @return array
	 */
	public function prepareDashboardDataForEmployee() {

		$modifiedBookingArray = [];
		$bakedBookings        = [];

		$days = Setting::retriveSettingsData( 'days' ) ?: getDefaultDays();

		$bookings = Booking::withTrashed()
		                   ->with( [ 'lunch', 'dinner', 'area' ] )
		                   ->where( 'user_id', auth()->id() )
		                   ->whereBetween( 'date', [
			                   Carbon::yesterday(),
			                   Carbon::parse( "$days days" )
		                   ] )->get();

		foreach ( $bookings as $booking ) {

			$modifiedBookingArray[ $booking->date ] = [
				'id'          => $booking->id,
				'date'        => $booking->date,
				'breakfast'   => $booking->breakfast ?: '',
				'lunch'       => optional( $booking->lunch )->name,
				'lunch_icon'  => optional( $booking->lunch )->icon_path,
				'snacks'      => $booking->snacks ?: '',
				'dinner'      => optional( $booking->dinner )->name,
				'dinner_icon' => optional( $booking->dinner )->icon_path,
				'area'        => optional( $booking->area )->name,
				'eaten'       => $booking->eaten,
				'created_at'  => $booking->created_at,
				'deleted_at'  => $booking->deleted_at,
			];
		}

		foreach ( getSpecifiedDates() as $key => $date ) {

			if ( array_key_exists( $date, $modifiedBookingArray ) ) {

				$bakedBookings[ $date ] = $modifiedBookingArray[ $date ];
			} else {
				$bakedBookings[ $date ] = $this->getDefaultArraysetForBookings( $date );
			}
		}

		$data['bakedBookins'] = $bakedBookings;

		return $data ?? [];
	}

	/**
	 * @param $date
	 *
	 * @return array
	 */
	public function getDefaultArraysetForBookings( $date ) {
		return [
			'id'          => '',
			'date'        => $date,
			'breakfast'   => '',
			'lunch'       => '',
			'lunch_icon'  => '',
			'snacks'      => '',
			'dinner'      => '',
			'dinner_icon' => '',
			'area'        => '',
			'eaten'       => null,
			'created_at'  => '',
			'deleted_at'  => '',
		];
	}
}
