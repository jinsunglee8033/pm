<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 12/11/17
 * Time: 10:27 AM
 */

namespace App\Http\Controllers\Admin\Settings;


use App\Model\PermissionAction;
use App\Model\PermissionPath;
use App\Model\Role;
use App\Model\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Mockery\Exception;

class PermissionController
{

    public function show(Request $request) {


        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $data = PermissionPath::query();
        if (!empty($request->path)) {
            $data = $data->where('path', 'like', '%' . strtolower($request->path) . '%');
        }

        $data = $data->orderBy('path', 'asc')->paginate(20);

        return view('admin.settings.permission', [
            'data' => $data
        ]);
    }

    public function addPath(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'path' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $path = strtolower(trim($request->path));

            $o = PermissionPath::where('path', $path)->first();
            if (!empty($o)) {
                throw new \Exception('Path already exists', -1);
            }

            $o = new PermissionPath;
            $o->path = $path;
            $o->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function loadPath(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $o = PermissionPath::find($request->id);
            if (empty($o)) {
                throw new \Exception('Unable to find path', -2);
            }

            return response()->json([
                'msg' => '',
                'detail' => $o
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function updatePath(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required',
                'path' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $path = strtolower(trim($request->path));

            $o = PermissionPath::where('path', $path)
                ->where('id', '!=', $request->id)
                ->first();
            if (!empty($o)) {
                throw new \Exception('Path already exists', -1);
            }

            $o = PermissionPath::find($request->id);
            if (empty($o)) {
                throw new \Exception('Unable to find path', -2);
            }
            $o->path = $path;
            $o->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function loadActions(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'path_id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $path = PermissionPath::find($request->path_id);
            if (empty($path)) {
                throw new \Exception('Invalid path ID provied', -1);
            }

            $actions = PermissionAction::where('path_id', $request->path_id)->get();
            return response()->json([
                'msg' => '',
                'actions' => $actions,
                'path' => $path
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode()
            ]);
        }
    }

    public function addAction(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'path_id' => 'required',
                'action' => 'required'
            ]);

            $action = strtolower(trim($request->action));

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $path = PermissionPath::find($request->path_id);
            if (empty($path)) {
                throw new \Exception('Invalid path ID provied', -1);
            }

            $o = PermissionAction::where('path_id', $path->id)
                ->where('action', $action)
                ->first();

            if (!empty($o)) {
                throw new \Exception('Action already exists in the path', -2);
            }

            $o = new PermissionAction;
            $o->path_id = $path->id;
            $o->action = $action;
            $o->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function updateAction(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required',
                'path_id' => 'required',
                'action' => 'required'
            ]);

            $action = strtolower(trim($request->action));

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $path = PermissionPath::find($request->path_id);
            if (empty($path)) {
                throw new \Exception('Invalid path ID provied', -1);
            }

            $o = PermissionAction::where('path_id', $path->id)
                ->where('action', $action)
                ->where('id', '!=', $request->id)
                ->first();

            if (!empty($o)) {
                throw new \Exception('Action already exists in the path', -2);
            }

            $o = PermissionAction::find($request->id);
            if (empty($o)) {
                throw new \Exception('Invalid action ID provided', -3);
            }

            $o->path_id = $path->id;
            $o->action = $action;
            $o->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function loadAction(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $o = PermissionAction::find($request->id);
            if (empty($o)) {
                throw new \Exception('Unable to find action', -2);
            }

            return response()->json([
                'msg' => '',
                'detail' => $o
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function loadPermissions(Request $request) {
        try {

            $actions = PermissionAction::join('permission_path', 'permission_action.path_id', 'permission_path.id');
            $site = strtolower(trim($request->site));
            if (!empty($site)) {
                $actions = $actions->where('permission_path.path', 'like', $site . '%');
            }

            $actions = $actions->select('permission_action.*')->orderBy('permission_path.path', 'asc')->orderBy('permission_action.action', 'asc')->get();

            if ($site == 'admin/') {
                $roles = Role::where('account_type', '!=', 'S')->get();
            } else {
                $roles = Role::where('account_type', 'S')->get();
            }

            $data = [];

            if (count($actions) > 0) {
                foreach ($actions as $a) {

                    $row = [
                        'action_id' => $a->id
                    ];

                    foreach ($roles as $r) {

                        $rp = RolePermission::where('role_id', $r->id)
                            ->where('action_id', $a->id)
                            ->first();

                        $has_permission = 'N';
                        if (!empty($rp)) {
                            $has_permission = $rp->has_permission;
                        }

                        $row['role_' . $r->id] = $has_permission;

                    }

                    $data[] = $row;

                }
            }

            return response()->json([
                'actions' => $actions,
                'roles' => $roles,
                'data' => $data
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function updatePermission(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'action_id' => 'required',
                'role_id' => 'required',
                'has_permission' => 'required|in:Y,N'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $rp = RolePermission::where('action_id', $request->action_id)
                ->where('role_id', $request->role_id)
                ->first();

            if (empty($rp)) {
                $rp = new RolePermission;
                $rp->action_id = $request->action_id;
                $rp->role_id = $request->role_id;
            }

            $rp->has_permission = $request->has_permission;
            $rp->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }
}