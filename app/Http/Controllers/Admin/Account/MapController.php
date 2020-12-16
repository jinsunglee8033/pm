<?php
/**
 * Created by PhpStorm.
 * User: Jin
 * Date: 10/10/19
 * Time: 04:58 PM
 */

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\AccountStoreHours;
use App\Model\AccountStoreIp;
use App\Model\Account;
use App\Model\State;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class MapController extends Controller
{

    public function show() {

        $account_id = Auth::user()->account_id;
        $states = State::orderBy('name', 'asc')->get();
        return view('admin.account.map',[
            'account'   => $account_id,
            'states'    => $states
        ]);
    }

    public function find(Request $request) {
        try{

            $zip = $request->zip;

            // Check lat, lng first!
            $accounts = Account::leftJoin('account_files_att', function ($join){
                $join->on('accounts.id', 'account_files_att.account_id')
                    ->where('account_files_att.type', 'FILE_ATT_AGREEMENT')
                    ->whereNotnull('account_files_att.data');
                })->whereNull('lat');

            if (!empty($request->ids)) {
                $ids = preg_split('/[\ \r\n\,]+/', $request->ids);
                $accounts = $accounts->whereIn('accounts.id', $ids);
            }
            if (!empty($request->att_ids)) {
                $att_ids = preg_split('/[\ \r\n\,]+/', $request->att_ids);
                $accounts = $accounts->whereIn('att_tid', $att_ids)
                    ->orWhereIn('att_tid2', $att_ids);
            }
            if (!empty($request->att_tid)) {
                $accounts = $accounts->where('accounts.type', 'S');

                if ($request->att_tid == 'Y') {
                    $accounts = $accounts->whereRaw('((att_tid is not null) or (att_tid2 is not null))');
                } else if ($request->att_tid == 'N') {
                    $accounts = $accounts->whereRaw('((att_tid is null) and (att_tid2 is null) )');
                } else {
                    $accounts = $accounts->whereRaw('(att_tid = "' . $request->att_tid . '" or att_tid2 = "' . $request->att_tid . '" )');
                }
            }

            if(!empty($request->address)){
                $accounts = $accounts->whereRaw("lower(address1) like ?", '%'. strtolower($request->address) . '%');
            }
            if(!empty($zip)){
                $accounts = $accounts->where('zip', $zip);
            }
            if(!empty($request->state)){
                $accounts = $accounts->where('state', $request->state);
            }
            if(!empty($request->city)){
                $accounts = $accounts->whereRaw("lower(city) like ?", '%'. strtolower($request->city) . '%');
            }
            if(!empty($request->name)){
                $accounts = $accounts->whereRaw("lower(name) like ?", '%'. strtolower($request->name) . '%');
            }
            if(!empty($request->status)){
                $accounts = $accounts->where('status', $request->status);
            }
            if(!empty($request->has_doc)){
                if($request->has_doc == 'Y') {
                    $accounts = $accounts->whereNotNull('account_files_att.account_id');
                }else if($request->has_doc == 'N'){
                    $accounts = $accounts->whereNull('account_files_att.account_id');
                }
            }
            $accounts = $accounts->orderBy('zip', 'asc')
                                ->select('accounts.id as id',
                                    'accounts.name as name',
                                    'accounts.address1 as address1',
                                    'accounts.address2 as address2',
                                    'accounts.city as city',
                                    'accounts.state as state',
                                    'accounts.zip as zip',
                                    'accounts.status as status',
                                    'accounts.lat as lat',
                                    'accounts.lng as lng',
                                    'accounts.att_tid as att_tid',
                                    'accounts.att_tid2 as att_tid2',
                                    'account_files_att.type as file_name'
                                )
                                ->get();


            foreach ($accounts as $a) {
                $account_id = $a->id;
                $address    = $a->address1 . " " . $a->city . " " . $a->state . " " . $a->zip;

                Helper::insert_location_info($account_id, $address);
            }

            // After location info
            $real_accounts = Account::leftJoin('account_files_att', function ($join){
                $join->on('accounts.id', 'account_files_att.account_id')
                    ->where('account_files_att.type', 'FILE_ATT_AGREEMENT')
                    ->whereNotnull('account_files_att.data');
                })->whereNotNull('lat');

            if (!empty($request->ids)) {
                $ids = preg_split('/[\ \r\n\,]+/', $request->ids);
                $real_accounts = $real_accounts->whereIn('accounts.id', $ids);
            }
            if (!empty($request->att_ids)) {
                $att_ids = preg_split('/[\ \r\n\,]+/', $request->att_ids);
                $real_accounts = $real_accounts->whereIn('att_tid', $att_ids)
                    ->orWhereIn('att_tid2', $att_ids);
            }
            if (!empty($request->att_tid)) {
                $real_accounts = $real_accounts->where('accounts.type', 'S');

                if ($request->att_tid == 'Y') {
                    $real_accounts = $real_accounts->whereRaw('((att_tid is not null) or (att_tid2 is not null))');
                } else if ($request->att_tid == 'N') {
                    $real_accounts = $real_accounts->whereRaw('((att_tid is null) and (att_tid2 is null) )');
                } else {
                    $real_accounts = $real_accounts->whereRaw('(att_tid = "' . $request->att_tid . '" or att_tid2 = "' . $request->att_tid . '" )');
                }
            }

            if(!empty($request->address)){
                $real_accounts = $real_accounts->whereRaw("lower(address1) like ?", '%'. strtolower($request->address) . '%');
            }
            if(!empty($zip)){
                $real_accounts = $real_accounts->where('zip', $zip);
            }
            if(!empty($request->state)){
                $real_accounts = $real_accounts->where('state', $request->state);
            }
            if(!empty($request->city)){
                $real_accounts = $real_accounts->whereRaw("lower(city) like ?", '%'. strtolower($request->city) . '%');
            }
            if(!empty($request->name)){
                $real_accounts = $real_accounts->whereRaw("lower(name) like ?", '%'. strtolower($request->name) . '%');
            }
            if(!empty($request->status)){
                $real_accounts = $real_accounts->where('status', $request->status);
            }
            if(!empty($request->has_doc)){
                if($request->has_doc == 'Y') {
                    $real_accounts = $real_accounts->whereNotNull('account_files_att.account_id');
                }else if($request->has_doc == 'N'){
                    $real_accounts = $real_accounts->whereNull('account_files_att.account_id');
                }
            }

            if ($request->excel == 'Y') {
                $addrs = $real_accounts->orderBy('zip', 'asc')
                    ->select('accounts.id as id',
                        'accounts.name as name',
                        'accounts.address1 as address1',
                        'accounts.address2 as address2',
                        'accounts.city as city',
                        'accounts.state as state',
                        'accounts.zip as zip',
                        'accounts.status as status',
                        'accounts.lat as lat',
                        'accounts.lng as lng',
                        'accounts.att_tid as att_tid',
                        'accounts.att_tid2 as att_tid2',
                        'account_files_att.type as file_name'
                    )->get();
                Excel::create('map', function($excel) use($addrs) {

                    $excel->sheet('reports', function($sheet) use($addrs) {

                        $data = [];
                        $num = 1;

                        foreach ($addrs as $key => $a) {
                            $file = '';
                            if($a->file_name){
                                $file = 'Yes';
                            }
                            $row = [
                                'No.' => $num,
                                'ID' => $a->id,
                                'Name' => $a->name,
                                'Address' => $a->address1,
                                'Address2' => $a->address2,
                                'City' => $a->city,
                                'State' => $a->state,
                                'Zip' => $a->zip,
                                'Status' => $a->status,
                                'ATT TID' => $a->att_tid,
                                'ATT TID2' => $a->att_tid2,
                                'ATT Doc' => $file
                            ];
                            $data[] = $row;
                            $num++;
                        }
                        $sheet->fromArray($data);
                    });
                })->export('xlsx');
            }

            $real_accounts = $real_accounts->orderBy('zip', 'asc')
                            ->select('accounts.id as id',
                                    'accounts.name as name',
                                    'accounts.address1 as address1',
                                    'accounts.address2 as address2',
                                    'accounts.city as city',
                                    'accounts.state as state',
                                    'accounts.zip as zip',
                                    'accounts.status as status',
                                    'accounts.lat as lat',
                                    'accounts.lng as lng',
                                    'accounts.att_tid as att_tid',
                                    'accounts.att_tid2 as att_tid2',
                                    'account_files_att.type as file_name'
                                    )
                            ->get();

            if(!count($real_accounts)){
                return response()->json([
                    'msg'   => 'No Result!',
                    'data'  => $real_accounts,
                ]);
            }
            return response()->json([
                'msg'   => '',
                'data'  => $real_accounts,
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

}