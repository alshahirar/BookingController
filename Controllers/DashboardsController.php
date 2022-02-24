<?php

namespace App\Http\Controllers;

use App\Area;
use App\Booking;
use App\Department;
use App\CustomHelpers\BookingStatus;
use App\GuestBooking;
use App\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Class DashboardsController
 * @package App\Http\Controllers
 */
class DashboardsController extends Controller {
    
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index_NEW() {

        $data = auth()->user()->isAdmin() ? $this->prepareAdminDashboard() : $this->prepareDashboardDataForEmployee();
        
        //dd($data);
        return view('dashboard', $data);
    }

    /**
     * @return array
     */
    public function prepareAdminDashboard() {

        $data['bookings'] = $bookings = Booking::with(['lunch', 'dinner', 'area'])
            ->where(function ($q) {
                $q->whereDate('date', Carbon::today())
                    ->whereNull('canceled_by');
            })->get();

        $totalLunch  = 0;
        $totalDinner = 0;

        $areas = Area::all();
        $departments = Department::all();

        $data['departments'] = [
            'Software Development' => [
                'MOHAKHALI' => [
                    'lunch' => 10,
                    'dinner' => 12
                ],
                'MBD' => [
                    'lunch' => 23,
                    'dinner' => 43
                ],
            ],
            'Creative Development' => [
                'MOHAKHALI' => [
                    'lunch' => 30,
                    'dinner' => 24
                ],
                'MBD' => [
                    'lunch' => 56,
                    'dinner' => 98
                ],
            ],
            'Information Technology and Security' => [
                'MOHAKHALI' => [
                    'lunch' => 78,
                    'dinner' => 89
                ],
                'MBD' => [
                    'lunch' => 88,
                    'dinner' => 57
                ],
            ],
        ];

        if ($bookings->count()) {
            foreach ($bookings as $key => $booking) {
                if ($booking->lunch) {
                    $data['totalLunch'] = ++$totalLunch;
                } elseif ($booking->dinner) {
                    $data['totalDinner'] = ++$totalDinner;
                }
                $data['areas'][$booking->area->name] = [
                    'id'        => $booking->area->id,
                    'name'      => $booking->area->name,
                    'lunchQty'  => $booking->lunch ? (isset($data[$booking->area->name . 'lunch_qty']) ? ++$data[$booking->area->name . 'lunch_qty'] : $data[$booking->area->name . 'lunch_qty'] = 1) : (isset($data[$booking->area->name . 'lunch_qty']) ? $data[$booking->area->name . 'lunch_qty'] : $data['lunch_qty'] = 0),
                    'dinnerQty' => $booking->dinner ? (isset($data[$booking->area->name . 'dinner_qty']) ? ++$data[$booking->area->name . 'dinner_qty'] : $data[$booking->area->name . 'dinner_qty'] = 1) : (isset($data[$booking->area->name . 'dinner_qty']) ? $data[$booking->area->name . 'dinner_qty'] : $data['dinner_qty'] = 0),
                ];
            }

            foreach ($areas as $area) {
                if (array_key_exists($area->name, $data['areas'])) {
                    continue;
                } else {
                    $data['areas'][$area->name] = [
                        'id'        => $area->id,
                        'name'      => $area->name,
                        'lunchQty'  => 0,
                        'dinnerQty' => 0,
                    ];
                }
            }
        } else {
            $data = [
                'areas' => [
                    $areas[0]->name => [
                        'id'        => $areas[0]->id,
                        'name'      => $areas[0]->name,
                        'lunchQty'  => 0,
                        'dinnerQty' => 0,
                    ],
                    $areas[1]->name => [
                        'id'        => $areas[1]->id,
                        'name'      => $areas[1]->name,
                        'lunchQty'  => 0,
                        'dinnerQty' => 0,
                    ],
                ],
            ];
        }
        return $data;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {

        $data = auth()->user()->isAdmin() ? $this->prepareDashboardDataForAdmin() : $this->prepareDashboardDataForEmployee();

        $data['guestBook'] = GuestBooking::all();

        $today    = \Carbon\Carbon::today()->format("Y-m-d");
        $tomorrow = \Carbon\Carbon::tomorrow()->format("Y-m-d");

        $areas = Area::pluck('id', 'name');


        foreach ($areas as $name => $id) {
            $data['today_guest_lunch'][$name]  = GuestBooking::where([['area_id', '=', $id], ['date', '=', $today]])->sum('lunch');
            $data['today_guest_dinner'][$name] = GuestBooking::where([['area_id', '=', $id], ['date', '=', $today]])->sum('dinner');

            $data['tomorrow_guest_lunch'][$name]  = GuestBooking::where([['area_id', '=', $id], ['date', '=', $tomorrow]])->sum('lunch');
            $data['tomorrow_guest_dinner'][$name] = GuestBooking::where([['area_id', '=', $id], ['date', '=', $tomorrow]])->sum('dinner');
        }

        // dd($data);

        //  $lunch = GuestBooking::where('')->sum('lunch');

        // dd($todayDinnerMbd);

        $todayLunchMohakhali  = "SELECT SUM(lunch) FROM guest_bookings WHERE date = '" . $today . "' AND area_id = '1'  ";
        $todayDinnerMohakhali = "SELECT SUM(dinner) FROM guest_bookings WHERE date = '" . $today . "' AND area_id = '1'  ";

        $tomorrowLunchMohakhali  = "SELECT SUM(lunch) FROM guest_bookings WHERE date = '" . $tomorrow . "' AND area_id = '1'  ";
        $tomorrowDinnerMohakhali = "SELECT SUM(dinner) FROM guest_bookings WHERE date = '" . $tomorrow . "' AND area_id = '1'  ";

        $todayLunchMbd  = "SELECT SUM(lunch) FROM guest_bookings WHERE date = '" . $today . "' AND area_id = '7'  ";
        $todayDinnerMbd = "SELECT SUM(dinner) FROM guest_bookings WHERE date = '" . $today . "' AND area_id = '7'  ";

        $tomorrowLunchMbd  = "SELECT SUM(lunch) as mbdLunch FROM guest_bookings WHERE date = '" . $tomorrow . "' AND area_id = '7'  ";
        $tomorrowDinnerMbd = "SELECT SUM(dinner) FROM guest_bookings WHERE date = '" . $tomorrow . "' AND area_id = '7'  ";

        $data['todayLunchMohakhaliRes']  = DB::select(DB::raw($todayLunchMohakhali));
        $data['todayDinnerMohakhaliRes'] = DB::select(DB::raw($todayDinnerMohakhali));

        $data['tomorrowLunchMohakhaliRes']  = DB::select(DB::raw($tomorrowLunchMohakhali));
        $data['tomorrowDinnerMohakhaliRes'] = DB::select(DB::raw($tomorrowDinnerMohakhali));

        $data['todayLunchMbdRes']  = DB::select(DB::raw($todayLunchMbd));
        $data['todayDinnerMbdRes'] = DB::select(DB::raw($todayDinnerMbd));

//        $data['tomorrowLunchMbdRes'] = $lunch;
        $data['tomorrowDinnerMbdRes'] = DB::select(DB::raw($tomorrowDinnerMbd));

//        dd($todaysLunchResult);

        return view('dashboard', $data);
    }

    /**
     * @return array
     */
    public function prepareDashboardDataForAdmin() {

        $data['all_areas'] = Area::all()->toArray();
        $data['today']     = $this->prepareDashboardDataForAdminToday();
        $data['tomorrow']  = $this->prepareDashboardDataForAdminTomorrow();
        $data['style']     = [
            $data['all_areas'][0]['name'] => [
                'icon'  => 'fa fa-bank',
                'color' => 'bg-success',
            ],
            $data['all_areas'][1]['name'] => [
                'icon'  => 'fa fa-building',
                'color' => 'bg-primary',
            ],
        ];

        return $data;
    }

    public function prepareDashboardDataForAdminToday() {

        $bookings = Booking::with(['lunch', 'dinner', 'area'])
            ->where(function ($q) {
                $q->whereDate('date', Carbon::today())
                    ->whereNull('canceled_by');
            })->get();

        $totalLunch  = 0;
        $totalDinner = 0;

        $areas = Area::all();

        if ($bookings->count()) {
            foreach ($bookings as $key => $booking) {
                if ($booking->lunch) {
                    $data['totalLunch'] = ++$totalLunch;
                } elseif ($booking->dinner) {
                    $data['totalDinner'] = ++$totalDinner;
                }
                $data['areas'][$booking->area->name] = [
                    'id'        => $booking->area->id,
                    'name'      => $booking->area->name,
                    'lunchQty'  => $booking->lunch ? (isset($data[$booking->area->name . 'lunch_qty']) ? ++$data[$booking->area->name . 'lunch_qty'] : $data[$booking->area->name . 'lunch_qty'] = 1) : (isset($data[$booking->area->name . 'lunch_qty']) ? $data[$booking->area->name . 'lunch_qty'] : $data['lunch_qty'] = 0),
                    'dinnerQty' => $booking->dinner ? (isset($data[$booking->area->name . 'dinner_qty']) ? ++$data[$booking->area->name . 'dinner_qty'] : $data[$booking->area->name . 'dinner_qty'] = 1) : (isset($data[$booking->area->name . 'dinner_qty']) ? $data[$booking->area->name . 'dinner_qty'] : $data['dinner_qty'] = 0),
                ];
            }

            foreach ($areas as $area) {
                if (array_key_exists($area->name, $data['areas'])) {
                    continue;
                } else {
                    $data['areas'][$area->name] = [
                        'id'        => $area->id,
                        'name'      => $area->name,
                        'lunchQty'  => 0,
                        'dinnerQty' => 0,
                    ];
                }
            }
        } else {
            $data = [
                'areas' => [
                    $areas[0]->name => [
                        'id'        => $areas[0]->id,
                        'name'      => $areas[0]->name,
                        'lunchQty'  => 0,
                        'dinnerQty' => 0,
                    ],
                    $areas[1]->name => [
                        'id'        => $areas[1]->id,
                        'name'      => $areas[1]->name,
                        'lunchQty'  => 0,
                        'dinnerQty' => 0,
                    ],
                ],
            ];
        }

        return $data ?? [];
    }

    public function prepareDashboardDataForAdminTomorrow() {

        $bookings = Booking::with(['lunch', 'dinner', 'area'])
            ->where(function ($q) {
                $q->whereDate('date', Carbon::tomorrow())
                    ->whereNull('canceled_by');
            })->get();

        $totalLunch  = 0;
        $totalDinner = 0;

        $areas = Area::all();

        if ($bookings->count()) {
            foreach ($bookings as $key => $booking) {
                if ($booking->lunch) {
                    $data['totalLunch'] = ++$totalLunch;
                } elseif ($booking->dinner) {
                    $data['totalDinner'] = ++$totalDinner;
                }
                $data['areas'][$booking->area->name] = [
                    'id'        => $booking->area->id,
                    'name'      => $booking->area->name,
                    'lunchQty'  => $booking->lunch ? (isset($data[$booking->area->name . 'lunch_qty']) ? ++$data[$booking->area->name . 'lunch_qty'] : $data[$booking->area->name . 'lunch_qty'] = 1) : (isset($data[$booking->area->name . 'lunch_qty']) ? $data[$booking->area->name . 'lunch_qty'] : $data['lunch_qty'] = 0),
                    'dinnerQty' => $booking->dinner ? (isset($data[$booking->area->name . 'dinner_qty']) ? ++$data[$booking->area->name . 'dinner_qty'] : $data[$booking->area->name . 'dinner_qty'] = 1) : (isset($data[$booking->area->name . 'dinner_qty']) ? $data[$booking->area->name . 'dinner_qty'] : $data['dinner_qty'] = 0),
                ];
            }

            foreach ($areas as $area) {

                if (array_key_exists($area->name, $data['areas'])) {
                    continue;
                } else {
                    $data['areas'][$area->name] = [
                        'id'        => $area->id,
                        'name'      => $area->name,
                        'lunchQty'  => 0,
                        'dinnerQty' => 0,
                    ];
                }

            }

        } else {
            $data = [
                'areas' => [
                    $areas[0]->name => [
                        'id'        => $areas[0]->id,
                        'name'      => $areas[0]->name,
                        'lunchQty'  => 0,
                        'dinnerQty' => 0,
                    ],
                    $areas[1]->name => [
                        'id'        => $areas[1]->id,
                        'name'      => $areas[1]->name,
                        'lunchQty'  => 0,
                        'dinnerQty' => 0,
                    ],
                ],
            ];
        }

        return $data ?? [];
    }

    /**
     * @return array
     */
    public function prepareDashboardDataForEmployee() {

        $modifiedBookingArray = [];
        $bakedBookings        = [];

        $days = Setting::retriveSettingsData('days') ? : getDefaultDays();

        $bookings = Booking::withTrashed()
            ->with(['lunch', 'dinner', 'area'])
            ->where('user_id', auth()->id())
            ->whereBetween('date', [
                Carbon::yesterday(),
                Carbon::parse("$days days"),
            ])->get();

        foreach ($bookings as $booking) {

            $modifiedBookingArray[$booking->date] = [
                'id'             => $booking->id,
                'date'           => $booking->date,
                'lunch'          => $booking->lunch ? : '',
                'dinner'         => $booking->dinner ? : '',
                'area'           => optional($booking->area)->name,
                'eaten'          => $booking->eaten,
                'created_at'     => $booking->created_at,
                'deleted_at'     => $booking->deleted_at,
                'booking_status' => (new BookingStatus($booking))->getStatus(),
            ];

        }

        foreach (getSpecifiedDates() as $key => $date) {

            if (array_key_exists($date, $modifiedBookingArray)) {

                $bakedBookings[$date] = $modifiedBookingArray[$date];

            } else {
                $bakedBookings[$date] = $this->getDefaultDataSetForBookings($date);
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
    public function getDefaultDataSetForBookings($date) {

        $status = isDinnerBookOrEditTimeExpired($date)
            ? array_merge($this->defaultDataSetIfNotBooked(), $this->dinnerExpiredStatusDataSet())
            : array_merge($this->defaultDataSetIfNotBooked(), $this->dinnerNotExpiredStatusDataSet($date));

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
            'eaten'          => NULL,
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
            "bookHref"        => "/booking?q=" . base64_encode($date),
            "actionBookAttr"  => "",
            "actionBookClass" => "btn-primary",
            "statusColor"     => "label-primary",
        ];
    }

    public function adminBooking() {

//        dd("dfafdaa");

        $modifiedBookingArray = [];
        $bakedBookings        = [];

        $days = Setting::retriveSettingsData('days') ? : getDefaultDays();

        $bookings = Booking::withTrashed()
            ->with(['lunch', 'dinner', 'area'])
            ->where('user_id', auth()->id())
            ->whereBetween('date', [
                Carbon::yesterday(),
                Carbon::parse("$days days"),
            ])->get();

        foreach ($bookings as $booking) {

            $modifiedBookingArray[$booking->date] = [
                'id'             => $booking->id,
                'date'           => $booking->date,
                'lunch'          => $booking->lunch ? : '',
                'dinner'         => $booking->dinner ? : '',
                'area'           => optional($booking->area)->name,
                'eaten'          => $booking->eaten,
                'created_at'     => $booking->created_at,
                'deleted_at'     => $booking->deleted_at,
                'booking_status' => (new BookingStatus($booking))->getStatus(),
            ];

        }

        foreach (getSpecifiedDates() as $key => $date) {

            if (array_key_exists($date, $modifiedBookingArray)) {

                $bakedBookings[$date] = $modifiedBookingArray[$date];

            } else {
                $bakedBookings[$date] = $this->getDefaultDataSetForBookings($date);
            }
        }

        $data['bakedBookins'] = $bakedBookings;

        return view('booking.food-booking-admin', $data ?? []);;
    }

}
