<?php

namespace App\Http\Controllers;

use App\Booking;
use App\GuestBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingCancelController extends Controller
{
    public function cancel(){
        $today = Carbon::today()->format("Y-m-d");
        $tomorrow = Carbon::tomorrow()->format("Y-m-d");
        $dayAftertomorrow = Carbon::tomorrow()->addDays(1)->format("Y-m-d");

//        $bookingCancel = Booking::whereBetween('date', [$today, $tomorrow])->orderBy('id',  'asc')->get();

        $bookingCancel = Booking::where(function($q) use ($today, $tomorrow){
            return $q->whereNull('eaten');
        })->whereBetween('date', [$tomorrow, $dayAftertomorrow])->orderBy('date',  'asc')->get();


        return view('booking.cancel', compact('bookingCancel'));
    }

    public function destroy( $id ) {

        $bookingCancel = \App\Booking::find( $id );

        if($bookingCancel->delete($bookingCancel->id)){
            \Session::flash('success', 'Booking deleted successfully');
        }

        return redirect('/cancel/bookings');
    }

}
