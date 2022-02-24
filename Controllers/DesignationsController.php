<?php

namespace App\Http\Controllers;

use App\CustomHelpers\CustomHelper;
use App\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Class DesignationsController
 * @package App\Http\Controllers
 */
class DesignationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['editPermission'] = CustomHelper::checkUserHasRightWithoutFlash('designation_edit');

        return view('designations.index', ['designations' => Designation::all()]);
//        return "kisui nai";
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('designations.create');
//        return "Ekhaneo kisu nai";
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, ['name' => 'required|unique:designations,name']);

        $designation = new Designation();
        $designation->name = request('name');
        $designation->save();

        Session::flash('success', 'Designation added successfully');
        return redirect('/designation');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data['designation'] = Designation::findOrFail($id);

        return view('designations.show', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Designation $designation)
    {
        $this->validate($request, ['name' => 'required|unique:designations,name']);

        $designation->name = request('name');

        if($designation->save()){
            $data['status'] = 'success';
            $data['header'] = 'Success';
            $data['message'] = 'Updated successfully';
            $data['name'] = request('name');
        }else{
            $data['status'] = 'error';
            $data['header'] = 'Error';
            $data['message'] = 'Something went wrong';
        }

        return $data;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Designation $designation)
    {
        return $designation->delete() ? "true" : "false";
    }
}
