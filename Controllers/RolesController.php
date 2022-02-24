<?php

namespace App\Http\Controllers;

use App\CustomHelpers\CustomHelper;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Class RolesController
 * @package App\Http\Controllers
 */
class RolesController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index()
    {

        $data['roles'] = Role::all();

        $data['viewPermission'] = CustomHelper::checkUserHasRightWithoutFlash('role_view');
        $data['editPermission'] = CustomHelper::checkUserHasRightWithoutFlash('role_edit');
        $data['deletePermission'] = CustomHelper::checkUserHasRightWithoutFlash('role_delete');

        return view('roles.index', $data);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function create()
    {
        $data['dataModalPermissions'] = CustomHelper::alignArrayForModuleRolePermission();
        $data['dataExtraPermissions'] = CustomHelper::alignArrayForExtraPermissions();

        return view('roles.create', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'display_name' => 'required|unique:roles,display_name',
            'description' => 'max:200',
            'permissions' => 'required'
        ]);

        $role = new Role();
        $role->name = request('name');
        $role->display_name = request('display_name');
        $role->description = request('description');
        $role->save();

        foreach (request('permissions') as $permission_key => $permission_val) {
            $role->attachPermission($permission_val);
        }

        CustomHelper::validatePermissionsAccordingRole($role);

        Session::flash('success', 'Role enlisted successfully');
        return redirect('/role');
    }

    /**
     * @param Role $role
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function show(Role $role)
    {
        $data['dataModalPermissions'] = CustomHelper::alignArrayForModuleRolePermission();
        $data['dataExtraPermissions'] = CustomHelper::alignArrayForExtraPermissions();

        $permissionIds = DB::table('permission_role')->where('permission_role.role_id', $role->id)
            ->pluck('permission_role.permission_id')->toArray();

        $data['permissionIds'] = $permissionIds;
        $data['role'] = $role;

        return view('roles.show', $data);
    }

    /**
     * @param Role $role
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function edit(Role $role)
    {
        $data['dataModalPermissions'] = CustomHelper::alignArrayForModuleRolePermission();
        $data['dataExtraPermissions'] = CustomHelper::alignArrayForExtraPermissions();

        $permissionIds = DB::table('permission_role')->where('permission_role.role_id', $role->id)
            ->pluck('permission_role.permission_id')->toArray();

        $data['permissionIds'] = $permissionIds;
        $data['role'] = $role;

        return view('roles.edit', $data);
    }

    /**
     * @param Request $request
     * @param Role $role
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(Request $request, Role $role)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name,' . $role->id,
            'display_name' => 'required|unique:roles,display_name,' . $role->id,
            'description' => 'max:200',
            'permissions' => 'required'
        ]);

        if (CustomHelper::isSuperAdmin() || CustomHelper::isFF() || CustomHelper::isManager()) {
            $modifiedRequest = $request->except(['name']);
        } else {
            $modifiedRequest = $request->all();
        }

        if ($role->update($modifiedRequest)) {
            $role->permission()->sync([]);

            foreach (request('permissions') as $permission_key => $permission_val) {
                $role->attachPermission($permission_val);
            }

            CustomHelper::validatePermissionsAccordingRole($role);

            Session::flash('success', 'Roles updated successfully');
            return redirect('/role/' . $role->id . '/show');
        }

        Session::flash('error', 'Something went wrong');
        return redirect('/role');
    }

    /**
     * @param Role $role
     * @return mixed
     */
    public function destroy(Role $role)
    {
        $data['status'] = 'error';
        $data['header'] = 'Error';
        $data['message'] = 'Something went wrong';

        if (!$role->users->count()) {
            $role->delete();
            $role->permission()->sync([]);

            $data['status'] = 'success';
            $data['header'] = 'Success';
            $data['message'] = 'Deleted successfully';
            $data['name'] = request('name');
        }

        return $data;
    }
}
