<?php

namespace App\Http\Controllers;

use App\Booking;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingApiController extends Controller {

    public function isThereAnyBookingForUser($employeeId) {

        if ($employeeId && User::where('employee_id', $employeeId)->first()) {
            $requestDate = date('Y-m-d');

            $isBooking = Booking::with(['area', 'user', 'lunch', 'dinner'])
                ->whereHas('user', function ($query) use ($employeeId) {
                    return $query->where('employee_id', $employeeId);
                })
                //->whereNull('eaten')
                ->where('date', $requestDate)
                ->first();

            $todayTwelvePM = Carbon::parse('today 12pm');
            $todayFourPM   = Carbon::parse('today 4pm');

            $todayEightPM           = Carbon::parse('today 8pm'); // changed
            $todayElevenFortyFivePM = Carbon::parse('today 11:59pm');

            $now = Carbon::now();

            if ($isBooking) {

                if($isBooking->eaten) {

                    //changed
                    return [
                        'code'    => 4001,
                        'status'  => "eaten",
                        'message' => "Meal has been eaten",
                    ];
                }

                if ($isBooking->lunch) {

                    if ($todayTwelvePM->lte($now) && $todayFourPM->gte($now)) {
                        $isBooking->eaten     = 1;
                        $isBooking->served_by = auth()->user()->id;
                        $isBooking->save();

                        return new \App\Http\Resources\Booking($isBooking);
                    } else {

                        //changed - make same response format
                        return [
                            'code'    => 5001,
                            'status'  => "expired",
                            'message' => "Lunch Expired",
                        ];
                    }
                } else {
                    if ($isBooking->dinner) {

                        if ($todayEightPM->lte($now) && $todayElevenFortyFivePM->gte($now)) {
                            $isBooking->eaten     = 1;
                            $isBooking->served_by = auth()->user()->id;
                            $isBooking->save();

                            return new \App\Http\Resources\Booking($isBooking);
                        } else {

                            //changed - make same response format
                            return [
                                'code'    => 6001,
                                'status'  => "expired",
                                'message' => "Dinner Expired",
                            ];

                        }
                    } else {

                        //changed - make same response format
                        return [
                            'code'    => 1001,
                            'status'  => "canceled",
                            'message' => "Booking has been canceled",
                        ];
                        
                    }
                }

            } else {

                //changed - make same response format
                //changed - proper http response code
                return response()->json(
                    [
                        'code'    => 404,
                        'status'  => "error",
                        'message' => "Booking not found",
                    ], 200
                );
            }
        }

        //changed - make same response format
        //changed - proper http response code   
        return response()->json(
            [
                'code'    => 404,
                'status'  => "failed",
                'message' => "User not found",
            ], 200
        );

    }

    public function servedToday(Request $request) {

        //changed - make same response format
        return [
            'code'    => 200,
            'status'  => 'success',
            'message' => 'total meal served today',
            'served'  => \App\Booking::todayMealServedByVendor(),
        ];
    }

}
