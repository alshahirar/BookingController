<?php

namespace App\Http\Controllers;

use App\Area;
use App\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Class AreasController
 * @package App\Http\Controllers
 */
class AreasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
	    $perPage = $request->per_page ?? getPerPage();

	    $data['items'] = Area::where( 'name', 'like', '%' . $request->search . '%' )
	                         ->paginate( $perPage )
	                         ->appends( [ 'search' => $request->search, 'per_page' => $request->per_page ] );

	    $data['itemFound'] = $data['items']->count();
	    $data['searchItem'] = $request->search;

	    return view( 'areas.index', $data );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('areas.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validate($request, ['name' => 'required|min:3|max:20|unique:areas,name']);

        $area = new Area();
        $area->name = $request->name;
        $area->save();

        Session::flash('success', 'Area added successfully');
        return redirect('/area');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Area $area)
    {
        $data['item'] = $area;

        return view('areas.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Area $area)
    {
        $this->validate($request, ['name' => 'required|min:3|max:20|unique:areas,name,' . $area->id,]);

        $area->name = request('name');

        if($changed = $area->isDirty())
            $data['changed'] = true;

        if($area->save()){
            Session::flash('success', 'Item edited Successful');
        }else{
            Session::flash('error', 'Something went wrong');
        }

        return redirect('/area');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Area $area)
    {
	    if ( Booking::isThereAnyRelatedBooking( 'area_id', $area->id ) ) {
		    Session::flash('error', 'Sorry, Some booking are being made with this item');
		    return back();
	    }

        if($area->forceDelete()){
            Session::flash('success', 'Area deleted successfully');
        }

        return redirect('/area');
    }
}
