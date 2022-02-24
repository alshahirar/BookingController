<?php

namespace App\Http\Controllers;

use App\Area;
use App\Booking;
use App\Department;
use App\Exports\MealReportExport;
use App\Exports\ReportMealExport;
use App\GuestBooking;
use App\Setting;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Psy\Util\Str;

class BookingController extends Controller
{
    public function index(Request $request)
    {

        $days = Setting::retriveSettingsData('days') ?: getDefaultDays();

        try {

            if (!$request->q or !Carbon::parse(base64_decode($request->q))
                    ->between(Carbon::yesterday(), Carbon::parse("$days days"))) {

                throw new \Exception();
            }

        } catch (\Exception $e) {
            \Session::flash('error', 'Please proceed with a valid date');

            return redirect('/');
        }

        if (!$this->isModifiable(NULL, NULL, base64_decode($request->q))) {
            \Session::flash('error', 'Sorry time expired! Please try another day');

            return back();
        }

        $data['user'] = auth()->user();

        return view('booking.index', $data);
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'lunch_dinner' => 'required',
            'area' => 'required',
            'date' => 'required|date|after:yesterday',
        ]);

        $booking = new Booking();
        $booking->user_id = auth()->id();
        $booking->area_id = $request->area;
        $booking->date = $request->date;

        $contentLunchDinner = explode('_', $request->lunch_dinner);

        $lunchDinner = end($contentLunchDinner) === 'lunch' ? 'lunch' : 'dinner';

        if ($request->lunch_dinner && strtotime($request->date) <= strtotime('tomorrow')) {

            if ($lunchDinner === 'lunch' && strtotime("$request->date -1 day 5pm") <= strtotime("now")) {

                \Session::flash('error', 'Sorry, time expired. You cann\'t place order for lunch');

                return back();

            } elseif ($lunchDinner === 'dinner' && strtotime("$request->date -1 day 11:45pm") <= strtotime("now")) {

                \Session::flash('error', 'Sorry, time expired. You cann\'t place order for dinner');

                return back();

            }
        }

        $booking->$lunchDinner = $contentLunchDinner[0];

        $booking->save();

        \Session::flash('success', 'Booking saved successfully');

        if (auth()->user()->isAdmin()){
            return redirect(route('own.admin'));
        } else {
            return redirect('');
        }
    }

    public function edit($id)
    {

        Booking::withTrashed()->findOrFail($id);

        $booking = Booking::withTrashed()->whereId($id)->with(['lunch', 'dinner', 'area'])->first();

        if (!$this->isModifiable($booking, 'edit')) {
            return back();
        }

        return view('booking.edit', compact('booking'));
    }

    public function update(Request $request, $id)
    {

        $this->validate($request, [
            'lunch_dinner' => 'required',
            'area' => 'required',
            'date' => 'required|date|after:yesterday',
        ]);

        $booking = Booking::withTrashed()->findOrFail($id);

        $booking->lunch = NULL;
        $booking->dinner = NULL;
        $booking->area_id = $request->area;

        $contentLunchDinner = explode('_', $request->lunch_dinner);

        $lunchDinner = end($contentLunchDinner) === 'lunch' ? 'lunch' : 'dinner';

        if (!$this->isModifiable($booking, 'update')) {
            return back();
        }

        if(!$this->isChangeableMeal($booking, $lunchDinner, 'change')) {
            return back();
        }

        $booking->$lunchDinner = $contentLunchDinner[0];

        if ($booking->trashed()) {
            $booking->deleted_at = NULL;
        }

        $booking->save();

        \Session::flash('success', 'Booking updated successfully');

        if (auth()->user()->isAdmin()){
            return redirect(route('own.admin'));
        } else {
            return redirect('');
        }
    }

    public function destroy(Request $request, Booking $booking)
    {

        if (!$this->isModifiable($booking, 'delete')) {
            return back();
        }

        if ($booking->delete()){
            $booking->lunch = null;
            $booking->dinner = null;
        }

        $booking->save();

        \Session::flash('error', 'Booking canceled successfully');

        if (auth()->user()->isAdmin()){
            return redirect(route('own.admin'));
        } else {
            return redirect('');
        }
    }

    public function generateMealReport()
    {

        $areas = Area::all();
        $departments = Department::all();

        return view('reports.meal', compact('areas', 'departments'));
    }

    public function downloadMealReport(Request $request)
    {

        $this->validate($request, [
            'from' => 'required|date',
            'to' => 'required|date',
            'eid' => 'nullable|numeric',
        ]);

        $from = Carbon::parse($request->from)->format('Y-m-d') ;
        $to = Carbon::parse($request->to)->format('Y-m-d');
        $eid = isset($request->eid) ? " AND u.employee_id = '" . $request->eid . "'" : '' ;
        $area = isset($request->area_id) && $request->area_id != '0' ? " AND b.area_id = '" . $request->area_id . "'"  : '' ;
        $department = isset($request->department_id) && $request->department_id != '0' ? " AND u.department_id = '" . $request->department_id . "'"  : '' ;
        
        //dd($area);
//        $area_guest = isset($request->area_id) ? $request->area_id  : '' ;


//        $bookings_sql = " SELECT u.name, (SELECT employee_id FROM users AS u WHERE u.id = b.user_id) AS eid, date, CASE WHEN
//                            lunch IS NULL THEN 'NO' ELSE 'YES' END AS lunch,CASE WHEN dinner IS NULL THEN 'NO' ELSE 'YES' END AS dinner,
//                            (SELECT name FROM areas AS ar WHERE ar.id = b.area_id) AS area
//                            FROM bookings AS b INNER JOIN users AS u ON u.id = b.user_id  AND
//                            DATE(date) BETWEEN '" . $from . "' AND '" . $to . "' $eid $area";

        $bookings_sql = " SELECT u.name, (SELECT departments_name FROM departments AS d WHERE d.id = u.department_id) AS department, 
                            (SELECT employee_id FROM users AS u WHERE u.id = b.user_id) AS eid, date, CASE WHEN
                            lunch = 1 THEN 'YES' ELSE 'NO' END AS lunch, CASE WHEN dinner = 1 THEN 'YES' ELSE 'NO' END AS dinner,
                            (SELECT name FROM areas AS ar WHERE ar.id = b.area_id) AS area
                            FROM bookings AS b INNER JOIN users AS u ON u.id = b.user_id  AND
                            DATE(date) BETWEEN '" . $from . "' AND '" . $to . "' $eid $area $department";


        //echo $bookings_sql; die();

        $booking_report = DB::select(DB::raw($bookings_sql));



        $th_from = Carbon::parse($from);
        $th_to = Carbon::parse($to);
        $count = $th_from->diffInDays($th_to);

        $dates = [Carbon::parse($th_from)->format('Y-m-d')];
        for($i = 1; $i <= $count; $i++) {
            $dates[$i] = Carbon::parse($th_from->addDay(1))->format('Y-m-d');
        }
        $users = [];
        foreach ($booking_report as $key => $value) {
            foreach ($dates as $date) {
                if($value->date == $date) {
                    $users[$value->name][$value->date] = [
                        'lunch' => $value->lunch,
                        'dinner' => $value->dinner,
                    ];
                }
            }
        }

//        dd($request->area_id);

//        $guest_report = GuestBooking::where('area_id', $request->area_id)->whereBetween('date', [$from, $to])->get(); //
        $guest_report = GuestBooking::whereBetween('date', [$from, $to])->get(); //
        //dd($guest_report);

        if (!count($booking_report)) {

            \Session::flash('info', 'Sorry!, no record found');

            return redirect()->back();
        }

        fileCache()->set("meal_report_" . auth()->id(), $booking_report, 2);
        fileCache()->set("daterange_" . auth()->id(), $dates, 2);
        fileCache()->set("guest_meal_" . auth()->id(), $guest_report, 2);

        return new MealReportExport();
    }

    public function isModifiable(?Booking $booking, $action = 'cancel', $date = NULL)
    {
        if (!$booking) {

            return isDinnerBookOrEditTimeExpired($date) ? FALSE : TRUE;

        } elseif ($booking->lunch && isLunchBookOrEditTimeExpired($booking->date)) {

            \Session::flash("error", "Sorry, time expired. You can not $action your lunch order");

            return FALSE;

        } elseif ($booking->dinner && isDinnerBookOrEditTimeExpired($booking->date)) {

            \Session::flash("error", "Sorry, time expired. You can not $action your dinner order");

            return FALSE;

        }

        return TRUE;
    }

    public function isChangeableMeal(?Booking $booking, $type, $action = 'cancel', $date = NULL)
    {
        if (!$booking) {

            return isDinnerBookOrEditTimeExpired($date) ? FALSE : TRUE;

        } elseif ($type == 'lunch' && isLunchBookOrEditTimeExpired($booking->date)) {

            \Session::flash("error", "Sorry, time expired. You can not <b>$action</b> your lunch order");

            return FALSE;

        } elseif ($type == 'dinner' && isDinnerBookOrEditTimeExpired($booking->date)) {

            \Session::flash("error", "Sorry, time expired. You can not <b>$action</b> your dinner order");

            return FALSE;

        }

        return TRUE;
    }

    public function userBooking(){

        $users = User::orderBy('name')->get();;

        return view('booking.admin-booking', compact('users'));
    }

    public function userBookingStore(Request $request){

        $this->validate($request, [
            'lunch_dinner' => 'required',
            'user_id' => 'required',
            'area' => 'required',
            'date' => 'required|date|after:yesterday',
        ]);



        $bookingCheck = Booking::where([
            ['date', $request->date],
            ['user_id', $request->user_id],
        ])->first();

//        dd($bookingCheck);

        $mealCheck = function ($type) use($request) {
            return Booking::where([[$type, 1], ['date', $request->date],
                ['user_id', $request->user_id]])->first();
        };

        $contentLunchDinner = explode('_', $request->lunch_dinner);

        $lunchDinner = end($contentLunchDinner) === 'lunch' ? 'lunch' : 'dinner';

        if (empty($bookingCheck)){
            $useFoodBooking = new Booking();
            $useFoodBooking->date = $request->date;
            $useFoodBooking->user_id = $request->user_id;
            $useFoodBooking->area_id = $request->area;



            $useFoodBooking->$lunchDinner = $contentLunchDinner[0];

            if ($useFoodBooking->save()){
                \Session::flash('success', 'Employee booking saved successfully');
            }
        } else {
//            $userBooking = Booking::where('user_id', $request->user_id)->get();

            if($lunchDinner === 'lunch') {

                if(is_null($mealCheck('lunch')) && is_null($bookingCheck['eaten'])) {
                    $meal = Booking::where([['date', $request->date],
                        ['user_id', $request->user_id]])->first();
                    $meal->lunch = 1;
                    $meal->dinner = null;
                    $meal->area_id = $request->area;
                    $meal->save();
                    \Session::flash('success', 'User booking updated successfully');
                } else {
                    \Session::flash('error', 'Meal already exist!!!');
                }
            } else if($lunchDinner === 'dinner') {
                $userBooking = new Booking();
                if(is_null($mealCheck('dinner')) && is_null($bookingCheck['eaten'])) {
                    $meal = Booking::where([['date', $request->date],
                        ['user_id', $request->user_id]])->first();
                    $meal->lunch = null;
                    $meal->dinner = 1;
                    $meal->area_id = $request->area;
                    $meal->save();
                    \Session::flash('success', 'User booking updated successfully');
                } else {
                    \Session::flash('error', 'Meal already exist!!!');
                }
            } else {
                \Session::flash('error', 'Same user and date already exist!!!');
            }
        }

        return redirect(route('user.booking'));

    }

}
