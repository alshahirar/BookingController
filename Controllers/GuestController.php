<?php

namespace App\Http\Controllers;

use App\GuestBooking;
use Illuminate\Http\Request;

class GuestController extends Controller {

    public function index() {
        $guestBook = GuestBooking::orderBy('date', 'asc')->get();

        return view('booking.guest-booking', compact('guestBook'));
    }

    public function store(Request $request) {
        $rules = [
            'lunch'  => 'required_without:dinner',
            'dinner' => 'required_without:lunch',
            'area'   => 'required',
            'date'   => 'required|date|after:yesterday',
        ];

        $customMessages = [
            'dinner.required_without' => 'The dinner field is required',
            'lunch.required_without'  => 'The lunch field is required',
        ];

        $this->validate($request, $rules, $customMessages);

        $found = GuestBooking::where([ ['area_id', $request->area], ['date', $request->date] ])->first();
        if($found) {
            $found->lunch   += $request->lunch;
            $found->dinner  += $request->dinner;

            if ($found->save()) {
                \Session::flash('success', 'Guest food booking saved successfully');
            }
        } else {
            $guestBookings = new GuestBooking();

            $guestBookings->lunch   = $request->lunch;
            $guestBookings->dinner  = $request->dinner;
            $guestBookings->date    = $request->date;
            $guestBookings->area_id = $request->area;

            if ($guestBookings->save()) {
                \Session::flash('success', 'Guest food booking saved successfully');
            }
        }

        return redirect('/guest');
    }

    public function destroy($id) {

        $guestBookings = GuestBooking::find($id);

        if ($guestBookings->delete($guestBookings->id)) {
            \Session::flash('success', 'Guest food booking deleted successfully');
        }

        return redirect('/guest');
    }

    public function edit($id) {

        $item = GuestBooking::find($id);

        return view('booking.guest-booking-edit', compact('item'));
    }

    public function update(Request $request, $id) {

        $rules = [
            'lunch'  => 'required_without:dinner',
            'dinner' => 'required_without:lunch',
        ];

        $customMessages = [
            'dinner.required_without' => 'The dinner field is required',
            'lunch.required_without'  => 'The lunch field is required',
        ];

        $this->validate($request, $rules, $customMessages);

//        $this->validate($request, [
//            'lunch'  => 'required',
//            'dinner' => 'required',
//        ]);

        $guestBooking = GuestBooking::find($id);

        $guestBooking->lunch   = $request->lunch;
        $guestBooking->dinner  = $request->dinner;
        $guestBooking->area_id = $request->area;

        if ($guestBooking->save()) {
            \Session::flash('success', 'Booking updated');
        }

        return redirect('/guest');
    }

}
