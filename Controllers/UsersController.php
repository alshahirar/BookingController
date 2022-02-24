<?php

namespace App\Http\Controllers;

use App\Associate;
use App\CustomHelpers\CustomHelper;
use App\Region;
use App\Role;
use App\Territory;
use App\User;
use App\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class UsersController extends Controller {

	/**
	 * @param Request $request
	 *
	 * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index( Request $request ) {

		if ( $marked = $request->marked ) {

			$users = User::findOrFail( $marked );

			return view( 'qr-codes', compact( 'users' ) );
		}

		$perPage = $request->per_page ?? getPerPage();

		$data['items'] = User::select('users.*','departments.departments_name as departments_name','areas.name as area_name' )
		             		 ->leftJoin('departments', 'users.department_id','=','departments.id')
							 ->leftJoin('areas', 'users.area_id','=','areas.id')
							 ->where( 'users.name', 'like', '%' . $request->search . '%' )
		                     ->orWhere( 'email', 'like', '%' . $request->search . '%' )
		                     ->orWhere( 'username', 'like', '%' . $request->search . '%' )
		                     ->orWhere( 'employee_id', 'like', '%' . $request->search . '%' )
		                     ->orWhere( 'departments_name', 'like', '%' . $request->search  . '%' )
							 ->orWhere( 'areas.name', 'like', '%' . $request->search  . '%' )
		                     ->paginate( $perPage )
		                     ->appends( [ 'search' => $request->search, 'per_page' => $request->per_page ] );

		$data['itemFound']  = $data['items']->count();
		$data['searchItem'] = $request->search;

		return view( 'users.index', $data );
	}

	/**
	 * @param Request $request
	 *
	 * @return array|\Illuminate\Http\RedirectResponse
	 */
	public function getUsersAlongWithInfo( Request $request ) {

		$data['editPermission'] = CustomHelper::checkUserHasRightWithoutFlash( 'user_edit' );

		$take    = $request->has( 'take' ) ? $request->take : 10;
		$skip    = $request->has( 'skip' ) ? ( $request->skip * $take ) : FALSE;
		$input   = $request->has( 'searchKeyword' ) ? $request->searchKeyword : '';
		$userIds = [];
		$isRM    = FALSE;

		if ( CustomHelper::isManager() ) {

		}

		$users = ( new User() )->withTrashed();

		if ( $isRM ) {
			$users = $users->whereIn( 'id', $userIds );
		} elseif ( CustomHelper::isSuperAdmin() ) {

		} elseif ( CustomHelper::isFF() ) {
			$users = $users->where( 'id', auth()->id() );
		} else {
			$users = $users->where( 'id', '!=', auth()->id() );
		}

		$users = $users->with( 'roles' );

		if ( $request->has( 'searchKeyword' ) ) {
			$users = $users->where( 'name', 'like', '%' . $input . '%' );
		}

		$data['totalNumberOfUser'] = $users->count();

		$users = $users->skip( $skip )->take( $take )->get();

		if ( $request->has( 'skip' ) || $request->has( 'searchKeyword' ) ) {
			return $result = [
				'users'             => $users,
				'totalNumberOfUser' => $data['totalNumberOfUser'],
			];
		}

		$data['users'] = $users;

		return $data;
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	/*public function create() {

		$data['roles'] = Role::where( 'name', '!=', 'ff' )
		                     ->when( ! CustomHelper::isSuperAdmin(), function ( $q ) {
			                     $q->where( 'name', '!=', 'Super Admin' );
		                     } )
		                     ->get();

		return view( 'users.create', $data );
	}*/

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	/*public function store( Request $request ) {

		$this->validate( $request, [
			'username' => 'required|max:10|unique:users,name',
			'password' => 'required|confirmed|min:6',
			'email'    => 'sometimes|required|email|unique:users,email'
		] );

		if ( $request->has( 'role' ) && ! CustomHelper::isSuperAdmin() && ( Role::whereName( 'Super Admin' )->value( 'id' ) == $request->role ) ) {
			Session::flash( 'error', 'Only super admin can create a user as super admin' );

			return back();
		}

		$user           = new User();
		$user->name     = request( 'username' );
		$user->email    = request( 'email' );
		$user->password = request( 'password' );

		if ( $user->save() ) {
			$associate               = new Associate();
			$associate->user_id      = $user->id;
			$associate->first_name   = request( 'fname' );
			$associate->last_name    = request( 'lname' );
			$associate->code         = CustomHelper::generateAssociateCode();
			$associate->sfdc_code    = request( 'sfdc_code' );
			$associate->user_type_id = UserType::firstOrCreate( [ 'name' => 'General' ] )->id;

			if ( $associate->save() ) {

				$user->attachRole( request( 'role' ) );

				Session::flash( 'success', 'User has been created successfully' );

				return redirect( '/user' );
			}
		}

		Session::flash( 'error', 'Something went wrong' );

		return redirect()->back();
	}*/

	/**
	 * @param User $user
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function show( User $user ) {

		$data['user'] = $user;

		return view( 'users.show', $data );
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function profile() {
		$data['user'] = auth()->user();

		if ( count( $data['user']->roles ) ) {
			$role_id = $data['user']->roles->first()->id;

			// Role-permissions
			$data['dataModalPermissions'] = CustomHelper::alignArrayForModuleRolePermission();
			$data['dataExtraPermissions'] = CustomHelper::alignArrayForExtraPermissions();

			$permissionIds = DB::table( 'permission_role' )->where( 'permission_role.role_id', $role_id )
			                   ->pluck( 'permission_role.permission_id' )->toArray();

			$data['permissionIds'] = $permissionIds;
		}

		return view( 'users.profile', $data );
	}

	public function setProfile( Request $request ) {

		$this->validate( $request, [
			'area' => 'required|integer',
		] );


		$user = \Auth::user();

		$user->area_id = $request->area;
		$user->save();

		Session::flash( 'success', 'Profile has been updated successfully' );

		return redirect( '/user/profile' );
	}

	/**
	 * @param User $user
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	/*public function edit( User $user ) {

		UserType::firstOrCreate( [ 'name' => 'General' ] );

		$userRoleName = ( $userRoleName = $user->roles->first() ) ? $userRoleName->name : '';

		$isFF             = CustomHelper::isFF();
		$isSuperAdmin     = CustomHelper::isSuperAdmin();
		$isAdminLevelUser = CustomHelper::isAdminLevelUser();

		if ( $isFF and ( auth()->id() != $user->id ) ) {
			Session::flash( 'error', 'FF can\'t edit other\'s profile' );

			return redirect()->back();
		}

		if ( strcasecmp( $userRoleName, 'Super Admin' ) == 0 && ! $isAdminLevelUser ) {
			Session::flash( 'error', 'Only Super Admin can access this page' );

			return redirect()->back();
		}

		$data['user'] = $user;

		if ( $isFF ) {
			$data['roles'] = '';
		} else {
			$data['roles'] = Role::when( ! $isSuperAdmin, function ( $q ) {
				$q->where( 'name', '!=', 'Super Admin' );
			} )->get();
		}

		return view( 'users.edit', $data );
	}*/

	/**
	 * @param Request $request
	 * @param User $user
	 *
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	/*public function update( Request $request, User $user ) {

		if ( $request->password ) {
			$this->validate( $request, [
				'username'  => 'required|unique:users,name,' . $user->id,
				'user_type' => 'required',
				'password'  => 'required|confirmed|min:6',
			] );
		} else {
			$this->validate( $request, [
				'username'  => 'required|unique:users,name,' . $user->id,
				'user_type' => 'required',
			] );
		}

		$changed = false;

		if ( $userRole = $user->roles->first() ) {

			$userRoleName = $userRole->name;

			if ( $userRoleName != 'Super Admin' || $userRoleName != 'Manager' || $userRoleName != 'FF' ) {
				$user->name = $request->username;
			}
		}

		$user->email = $request->email;

		if ( $request->has( 'password' ) ) {
			$user->password = $request->password;
		}

		if ( $user->isDirty() ) {
			$changed = true;
		}

		if ( $user->save() ) {

			$associate                 = Associate::where( 'user_id', $user->id )->first();
			$associate->first_name     = request( 'fname' );
			$associate->last_name      = request( 'lname' );
			$associate->sfdc_code      = request( 'sfdc_code' );
			$associate->user_type_id   = request( 'user_type' );
			$associate->designation_id = request( 'designation' );

			if ( $associate->isDirty() ) {
				$changed = true;
			}

			if ( $associate->save() ) {

				if ( $user->roles->first() ) {

					if ( $user->roles->first()->id != request( 'role' ) ) {

						$changed = true;

						$user->roles()->detach();
						$user->attachRole( request( 'role' ) );
					}
				} else if ( request( 'role' ) ) {

					$changed = true;

					$user->attachRole( request( 'role' ) );
				}

				$changed ? Session::flash( 'success', 'User has been edited successfully' ) : Session::flash( 'warning', 'No change found' );

				return redirect( '/user' );
			}
		}

		Session::flash( 'error', 'Something went wrong' );

		return redirect()->back();
	}*/

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function destroy( $id ) {
		$data['status'] = 'error';
		$data['header'] = 'Error';

		$user = User::withTrashed()->find( $id );

		if ( $userRole = $user->roles->first() ) {

			if ( CustomHelper::isFF() ) {
				$data['message'] = 'Your can\'t delete any user';

				return $data;
			}

			if ( $userRole->name === 'Super Admin' && ! CustomHelper::isSuperAdmin() ) {
				$data['message'] = 'Your can\'t delete a super admin';

				return $data;
			}
		}

		$data['status'] = 'success';
		$data['header'] = 'Success';

		if ( $user->deleted_at != NULL ) {
			$user->restore();
			$data['message'] = $user->name . ' activated successfully';
		} elseif ( $user->deleted_at == NULL ) {
			$user->delete();
			$data['message'] = $user->name . ' deactivated successfully';
		}

		return $data;
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function test( Request $request ) {
		if ( $request->has( 'code' ) ) {
			$codes = explode( ',', $request->code );
			foreach ( $codes as $code ) {
				Associate::whereCode( $code )->update( [ 'code' => CustomHelper::generateAssociateCode() ] );
			}
		}

		return redirect( '/' );
	}

	public function generateQRCode( Request $request ) {

		dd( $request->all() );
	}

	/**
	 *
	 */
	public function testTargetDoctors() {

	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {

		return view( 'users.create' );
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function store( Request $request ) {

		$this->validate( $request,
			[
				'name'        	=> 'required',
				'email'       	=> 'required|email|unique:users,email',
				'employee_id' 	=> 'required|unique:users,employee_id',
				'password'    	=> 'required|confirmed|min:6|max:64',
				'department' 	=> 'required',
				'area'		  	=> 'required',
			]
		);

		$user                = new User;
		$user->name          = $request->name;
		$user->username      = str_replace('@sebpo.com', '', $request->email, );
		$user->email         = $request->email;
		$user->employee_id   = $request->employee_id;
		$user->department_id = $request->department;
		$user->password      = $request->password;
		$user->area_id       = $request->area;
		$user->save();

		Session::flash( 'success', 'User added successfully' );

		return redirect( '/user' );
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  User $user
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function edit( User $user ) {

		$data['item'] = $user;

		return view( 'users.edit', $data );
	}

	/**
	 * Update the specified resource in storage.Community
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  User $user
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function update( Request $request, User $user ) {

		$this->validate( $request,
			[
				'name'                  => 'required',
				'email'                 => 'required|email|unique:users,email,' . $user->id,
				'employee_id'           => 'required|unique:users,employee_id,' . $user->id,
				'department'            => 'required',
				'password'              => 'nullable|required_with:password_confirmation|min:6|max:64',
				'password_confirmation' => 'nullable|same:password|min:6|max:64',
			]
		);

		$user->name           = $request->name;
		$user->email       	  = $request->email;
		$user->area_id    	  = $request->area;
		$user->employee_id 	  = $request->employee_id;
		$user->department_id  = $request->department;

		if ( ! is_null( $request->password ) && $request->password !== $user->password ) {
			$user->password = $request->password;
		}

		if ( $user->isClean() ) {
			Session::flash( 'warning', 'No change made!' );
		} elseif ( $user->save() ) {
			Session::flash( 'success', 'User update successfully' );
		} else {
			Session::flash( 'error', 'User update failed' );
		}

		return redirect( '/user' );
	}
}
