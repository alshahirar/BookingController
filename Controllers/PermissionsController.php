<?php

namespace App\Http\Controllers;

use App\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Class PermissionsController
 * @package App\Http\Controllers
 */
class PermissionsController extends Controller {
	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index() {
		return view( 'permissions.index', [ 'permissions' => Permission::all() ] );
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function create() {
		return view( 'permissions.create' );
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function store( Request $request ) {
		$permission = new Permission();

		$permission->name         = $request->name;
		$permission->display_name = $request->display_name;
		$permission->description  = $request->description;

		if ( $permission->save() ) {

			Session::flash( 'success', 'Permission enlisted successfully' );
		} else {
	    	Session::flash('error', 'Something went wrong');
		}

		return redirect( '/permission' );

	}

	/**
	 * @param Permission $permission
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function show( Permission $permission ) {
		return view( 'permissions.show', compact( 'permission' ) );
	}

	/**
	 * @param Permission $permission
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function edit( Permission $permission ) {
		return view( 'permissions.edit', compact( 'permission' ) );
	}

	/**
	 * @param Request $request
	 * @param Permission $permission
	 *
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function update( Request $request, Permission $permission ) {
		$this->validate( $request, [
			'name'         => 'required|unique:permissions,name,' . $permission->id,
			'display_name' => 'required|unique:permissions,display_name,' . $permission->id,
			'description'  => 'required',
		] );

		if ( $permission->update( $request->all() ) ) {

			Session::flash( 'success', 'Permission updated successfully' );

			return redirect( '/permission' );
		}
		Session::flash( 'error', 'Something went wrong' );

		return redirect( '/permission' );
	}

	/**
	 * @param Permission $permission
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function destroy( Permission $permission ) {
		return $permission->delete() ? 'true' : 'false';
	}
}
