<?php
/**
 * Created by PhpStorm.
 * User: Jin
 * Date: 2/25/20
 * Time: 10:38 AM
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Lib\RebateProcessor;
use App\Model\AccountFee;
use App\Model\BoostFee;
use App\Model\CricketFee;
use App\Model\MetroFee;
use App\Model\Product;
use App\Model\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Model\Account;
use App\Model\AccountActivationLimit;
use App\Lib\Helper;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class FeeController extends Controller
{
    public function show(Request $request)
    {

        if(empty($request->product) && $request->product != 'all'){
            $product = 'WBST';
        }else{
            $product = $request->product;
        }

        if(empty($request->show) && $request->show != 'A'){
            $show = 'A'; // Show All
        }else{
            $show = $request->show;
        }

        if($show == 'A') {
            $accounts = Account::LeftJoin('account_fee', function ($join) use ($product) {
                $join->on('account_fee.account_id', 'accounts.id');
                $join->where('account_fee.prod_id', $product);
            });
        }else{
            $accounts = Account::Join('account_fee', function ($join) use ($product) {
                $join->on('account_fee.account_id', 'accounts.id');
                $join->where('account_fee.prod_id', $product);
            });
        }

        if (!empty($request->type)) {
            if ($request->include_sub_account == 'Y') {
                $types = [];
                switch ($request->type) {
                    case 'L':
                        $types = ['L', 'M', 'D', 'S'];
                        break;
                    case 'M':
                        $types = ['M', 'D', 'S'];
                        break;
                    case 'D':
                        $types = ['D', 'S'];
                        break;
                    case 'S':
                        $types = ['S'];
                        break;
                }
                $accounts = $accounts->whereIn('accounts.type', $types);
            } else {
                $accounts = $accounts->where('accounts.type', $request->type);
            }
        }

        if (!empty($request->name)) {
            $accounts = $accounts->whereRaw("lower(accounts.name) like ?", '%' . strtolower($request->name) . '%');
            if ($request->include_sub_account_name == 'Y') {
                $searched_accounts = Account::whereRaw("lower(accounts.name) like ?", '%'. strtolower($request->name) . '%')->get();
                foreach($searched_accounts as $sa) {
                    $accounts = $accounts->orWhere('accounts.path', 'like', $sa->path . '%');
                }
            }
        }

        if (!empty($request->status)) {
            $accounts = $accounts->where('accounts.status', $request->status);
        }

        if (!empty($request->email)) {
            $accounts = $accounts->whereRaw('lower(accounts.email) like \'%' . strtolower($request->email) . '%\'');
        }

        if (!empty($request->id)) {
            $target_account = Account::find($request->id);
            if ($request->include_sub_account_id == 'Y') {
                $accounts = $accounts->where('accounts.path', 'like', $target_account->path . '%');
            } else {
                $accounts = $accounts->where('accounts.id', $request->id);
            }
        }

        if (!empty($request->acct_ids)) {
            $acct_ids = preg_split('/[\ \r\n\,]+/', $request->acct_ids);
            $accounts = $accounts->whereIn('accounts.id', $acct_ids);
        }

        if (!empty($request->name)) {
            $accounts = $accounts->whereRaw("lower(accounts.name) like ?", '%' . strtolower($request->name) . '%');
            if ($request->include_sub_account_name == 'Y') {
                $searched_accounts = Account::whereRaw("lower(accounts.name) like ?", '%'. strtolower($request->name) . '%')->get();
                foreach($searched_accounts as $sa) {
                    $accounts = $accounts->orWhere('accounts.path', 'like', $sa->path . '%');
                }
            }
        }

        $accounts = $accounts->select('accounts.*',
            'account_fee.prod_id',
            'account_fee.r_fee',
            'account_fee.m_fee',
            'account_fee.d_fee',
            'account_fee.s_fee'
        );

        if ($request->excel == 'Y' && Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system'])) {
            $data = $accounts->orderBy('accounts.id', 'asc')->get();

            Excel::create('Fee', function($excel) use($data) {
                $excel->sheet('reports', function($sheet) use($data) {
                    $reports = [];
                    foreach ($data as $a) {
                        $reports[] = [
                            'Account' => $a->id,
                            'Product' => $a->prod_id,
                            'Root.Fee' => $a->r_fee,
                            'Master.Fee' => $a->m_fee,
                            'Distributor.Fee' => $a->d_fee,
                            'SubAgent.Fee' => $a->s_fee
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }
        $accounts = $accounts->orderBy('accounts.id', 'asc')
            ->paginate(20);

        $products = Product::where('acct_fee', 'Y')->get();

        return view('admin.settings.fee', [
            'accounts'  => $accounts,
            'types'     => $this->get_types_filter(),
            'type'      => $request->type,
            'id'        => $request->id,
            'acct_ids'  => $request->acct_ids,
            'name'      => $request->name,
            'status' => $request->status,
            'email' => $request->email,
            'name' => $request->name,
            'products'  => $products,
            'product'   => $product,
            'show'      => $show,
            'include_sub_account'       => $request->include_sub_account,
            'include_sub_account_name'  => $request->include_sub_account_name,
            'include_sub_account_id'    => $request->include_sub_account_id,
        ]);
    }

    public function get_types_filter() {
        $type = Auth::user()->account_type;
        switch ($type) {
            case 'L':
                return [
                    ['code' => 'L', 'name' => 'Root'],
                    ['code' => 'M', 'name' => 'Master'],
                    ['code' => 'D', 'name' => 'Distributor'],
                    ['code' => 'S', 'name' => 'Sub-Agent']
                ];
            case 'M':
                return [
                    ['code' => 'M', 'name' => 'Master'],
                    ['code' => 'D', 'name' => 'Distributor'],
                    ['code' => 'S', 'name' => 'Sub-Agent']
                ];
            case 'D':
                return [
                    ['code' => 'D', 'name' => 'Distributor'],
                    ['code' => 'S', 'name' => 'Sub-Agent']
                ];
        }
    }

    public function show_modal(Request $request) {
        try {

            $af = AccountFee::where('account_id', $request->account_id)
                    ->where('prod_id', $request->product_id)
                    ->first();

            return response()->json([
                'msg' => '',
                'data' => [
                    'r_fee'    => empty($af->r_fee) ? 0 : $af->r_fee,
                    'm_fee'     => empty($af->m_fee) ? 0 : $af->m_fee,
                    'd_fee'     => empty($af->d_fee) ? 0 : $af->d_fee,
                    's_fee'     => empty($af->s_fee) ? 0 : $af->s_fee
                ]
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode . ']'
            ]);
        }
    }

    public function remove(Request $request) {
        try {

            AccountFee::where('account_id', $request->account_id)
                ->where('prod_id', $request->product_id)->delete();

            return response()->json([
                'msg' => 'Successfully Removed !!!'
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }


    }

    public function post(Request $request) {
        try {

            $r_fee = empty($request->r_fee) ? 0 : $request->r_fee;
            $m_fee = empty($request->m_fee) ? 0 : $request->m_fee;
            $d_fee = empty($request->d_fee) ? 0 : $request->d_fee;
            $s_fee = empty($request->s_fee) ? 0 : $request->s_fee;

            // Root //
            $af = AccountFee::where('account_id', $request->account_id)
                ->where('prod_id', $request->product_id)->first();

            if(empty($af)) {
                $new_af = New AccountFee();
                $new_af->account_id = $request->account_id;
//                $new_af->account_type = '';
                $new_af->r_fee = $r_fee;
                $new_af->m_fee = $m_fee;
                $new_af->d_fee = $d_fee;
                $new_af->s_fee = $s_fee;
                $new_af->prod_id = $request->product_id;
                $new_af->cdate = Carbon::now();
                $new_af->save();
            }else{

                $af->account_id = $request->account_id;
//                $af->account_type = '';
                $af->r_fee = $r_fee;
                $af->m_fee = $m_fee;
                $af->d_fee = $d_fee;
                $af->s_fee = $s_fee;
                $af->prod_id = $request->product_id;
                $af->mdate = Carbon::now();
                $af->update();
            }

            return response()->json([
                'msg' => 'Successfully updated !!!'
            ]);
        } catch (\Exception $ex) {

            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }
}