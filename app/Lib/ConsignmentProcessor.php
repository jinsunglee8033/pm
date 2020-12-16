<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/6/18
 * Time: 5:36 PM
 */

namespace App\Lib;


use App\Model\Account;
use App\Model\ConsignmentCharge;
use App\Model\StockESN;
use App\Model\StockSim;
use App\Model\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ConsignmentProcessor
{
    public static function get_balance($owner_id) {
        try {

            $owner = Account::find($owner_id);
            if (empty($owner)) {
                throw new \Exception('Invalid owner ID', -1);
            }

            $balance_esn = 0;
            $balance_sim = 0;
            switch ($owner->type) {
                case 'M':
                    $balance_esn = StockESN::join('accounts', function($join) use ($owner) {
                            $join->on('stock_esn.owner_id', 'accounts.id');
                            $join->where('accounts.path', 'like', $owner->path . '%');
                        })->where('stock_esn.type', 'C')
                        ->where('stock_esn.status', 'A')
                        ->sum('stock_esn.charge_amount_m');

                    $balance_sim = StockSim::join('accounts', function($join) use ($owner) {
                            $join->on('stock_sim.owner_id', 'accounts.id');
                            $join->where('accounts.path', 'like', $owner->path . '%');
                        })->where('stock_sim.type', 'C')
                        ->where('stock_sim.status', 'A')
                        ->sum('stock_sim.charge_amount_m');

                    break;
                case 'D':
                    $balance_esn = StockESN::join('accounts', function($join) use ($owner) {
                        $join->on('stock_esn.owner_id', 'accounts.id');
                        $join->where('accounts.path', 'like', $owner->path . '%');
                    })->where('stock_esn.type', 'C')
                        ->where('stock_esn.status', 'A')
                        ->sum('stock_esn.charge_amount_d');

                    $balance_sim = StockSim::join('accounts', function($join) use ($owner) {
                        $join->on('stock_sim.owner_id', 'accounts.id');
                        $join->where('accounts.path', 'like', $owner->path . '%');
                    })->where('stock_sim.type', 'C')
                        ->where('stock_sim.status', 'A')
                        ->sum('stock_sim.charge_amount_d');
                    break;
                case 'S':
                    $balance_column = 'charge_amount_r';
                    $balance_esn = StockESN::where('owner_id', $owner_id)
                        ->where('type', 'C')
                        ->where('status', 'A')
                        ->sum($balance_column);

                    $balance_sim = StockSim::where('owner_id', $owner_id)
                        ->where('type', 'C')
                        ->where('status', 'A')
                        ->sum($balance_column);
                    break;
            }



            $balance = $balance_esn + $balance_sim;
            return $balance;

        } catch (\Exception $ex) {
            $msg = $ex->getMessage() . ' [' . $ex->getCode() . ']';
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Consignment::get_balance() error', $msg);

            return 0;
        }
    }

    public static function charge(Transaction $trans) {
        try {

            $account = Account::find($trans->account_id);

            $sim_obj = StockSim::where('sim_serial', $trans->sim)->where('product', $trans->product_id)->first();
            $esn_obj = StockESN::where('esn', $trans->esn)->where('product', $trans->product_id)->first();

            ### for sub-agent ###
            $charge_amt_s = StockSim::get_sim_charge_amt($esn_obj, $sim_obj, 'S');
            $charge_amt_d = StockSim::get_sim_charge_amt($esn_obj, $sim_obj, 'D');
            $charge_amt_m = StockSim::get_sim_charge_amt($esn_obj, $sim_obj, 'M');

            $c = new ConsignmentCharge;
            $c->type = 'S';
            $c->trans_id = $trans->id;
            $c->account_type = 'S';
            $c->account_id = $account->id;
            $c->amt = $charge_amt_s;
            $c->cdate = Carbon::now();
            $c->created_by = Auth::user()->user_id;
            $c->save();

            ### for distributor ###
            # - distributor ID = transaction account's parent which type is 'D'
            $distributor = Account::where('id', $account->parent_id)
                ->where('type', 'D')
                ->first();

            if (!empty($distributor)) {
                $c = new ConsignmentCharge;
                $c->type = 'S';
                $c->trans_id = $trans->id;
                $c->account_type = 'D';
                $c->account_id = $distributor->id;
                $c->amt = $charge_amt_d;
                $c->cdate = Carbon::now();
                $c->created_by = Auth::user()->user_id;
                $c->save();
            } else {
                $charge_amt_m = $charge_amt_m + $charge_amt_d;
            }

            ### for master ###
            $master = Account::where('id', $account->master_id)
                ->where('type', 'M')
                ->first();
            if (!empty($master)) {
                $c = new ConsignmentCharge;
                $c->type = 'S';
                $c->trans_id = $trans->id;
                $c->account_type = 'M';
                $c->account_id = $master->id;
                $c->amt = $charge_amt_m;
                $c->cdate = Carbon::now();
                $c->created_by = Auth::user()->user_id;
                $c->save();
            }

            return [
                'error_code' => '',
                'error_msg' => ''
            ];

        } catch (\Exception $ex) {
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];
        }
    }
}