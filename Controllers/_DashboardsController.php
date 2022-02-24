<?php

namespace App\Http\Controllers;

use App\Booking;
use App\CustomHelpers\BookingStatus;
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

		$data['today'] = $this->prepareDashboardDataForAdminToday();
		$data['tomorrow'] = $this->prepareDashboardDataForAdminTomorrow();

		return $data;
	}

	public function prepareDashboardDataForAdminToday() {

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

	public function prepareDashboardDataForAdminTomorrow() {

		$bookings = Booking::with( [ 'lunch', 'dinner', 'area' ] )
		                   ->where( function ( $q ) {
			                   $q->whereDate( 'date', Carbon::tomorrow() )
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
				'id'             => $booking->id,
				'date'           => $booking->date,
				'breakfast'      => $booking->breakfast ?: '',
				'lunch'          => optional( $booking->lunch )->name,
				'snacks'         => $booking->snacks ?: '',
				'dinner'         => optional( $booking->dinner )->name,
				'area'           => optional( $booking->area )->name,
				'eaten'          => $booking->eaten,
				'created_at'     => $booking->created_at,
				'deleted_at'     => $booking->deleted_at,
				'booking_status' => ( new BookingStatus( $booking ) )->getStatus(),
			];

		}

		foreach ( getSpecifiedDates() as $key => $date ) {

			if ( array_key_exists( $date, $modifiedBookingArray ) ) {

				$bakedBookings[ $date ] = $modifiedBookingArray[ $date ];

			} else {
				$bakedBookings[ $date ] = $this->getDefaultDataSetForBookings( $date );
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
	public function getDefaultDataSetForBookings( $date ) {

		$status = isDinnerBookOrEditTimeExpired( $date )
			? array_merge( $this->defaultDataSetIfNotBooked(), $this->dinnerExpiredStatusDataSet() )
			: array_merge( $this->defaultDataSetIfNotBooked(), $this->dinnerNotExpiredStatusDataSet($date) );

		$status = [
			'id'             => '',
			'date'           => $date,
			'breakfast'      => '',
			'lunch'          => '',
			'lunch_icon'     => '',
			'snacks'         => '',
			'dinner'         => '',
			'dinner_icon'    => '',
			'area'           => '',
			'eaten'          => null,
			'created_at'     => '',
			'deleted_at'     => '',
			'booking_status' => $status,
		];

		return $status;
	}

	public function defaultDataSetIfNotBooked() {

		return [
			"booking"           => "",
			"status"            => "Not Booked",
			"actionBookTxt"     => "Book",
			"actionCancelClass" => "btn-default",
			"actionCancelAttr"  => "disabled",
			"cancelHref"        => "#",
		];
	}

	public function dinnerExpiredStatusDataSet() {

		return [
			"bookHref"        => "#",
			"actionBookAttr"  => "disabled",
			"actionBookClass" => "btn-default",
			"statusColor"     => "label-info",
		];
	}

	public function dinnerNotExpiredStatusDataSet($date) {
		return [
			"bookHref"        => "/booking?q=" . base64_encode( $date ),
			"actionBookAttr"  => "",
			"actionBookClass" => "btn-primary",
			"statusColor"     => "label-primary",
		];
	}
}
