<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/21/17
 * Time: 11:08 AM
 */

namespace App\Http\Controllers\Admin\Reports\Verizon;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use Illuminate\Http\Request;
use App\Model\Transaction;
use App\Model\Account;
use Validator;
use Carbon\Carbon;
use Session;
use Auth;
use Log;
use Excel;
use App\Model\VerizonChargeback;
use Illuminate\Support\Facades\Input;
use DB;

class ChargeBackController extends Controller
{
    public function show(Request $request) {
        if (Auth::user()->account_type !== 'L') {
            return redirect('/admin');
        }

        $sdate = null;//Carbon::now();
        $edate = null;Carbon::now();

        if (!empty($request->sdate)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $query = VerizonChargeback::query();
        if (Auth::user()->account_type != 'L') {
            $account = Account::find(Auth::user()->account_id);

            $query = $query->join('accounts', 'accounts.id', 'verizon_chargeback.account_id')
                ->where('accounts.path', 'like', $account->path . '%');

        }

        if (!empty($request->phone)) {
            $query = $query->where('verizon_chargeback.mobile_id', 'like', '%' . $request->phone . '%');
        }

        if (!empty($request->year)) {
            $query = $query->where('verizon_chargeback.year', $request->year);
        }

        if (!empty($request->month)) {
            $query = $query->where('verizon_chargeback.month', $request->month);
        }

        if (!empty($sdate)) {
            $query = $query->where('verizon_chargeback.deactivation_date', '>=', $sdate);
        }

        if (!empty($edate)) {
            $query = $query->where('verizon_chargeback.deactivation_date', '<=', $edate);
        }

        $data = $query->select('verizon_chargeback.*')
            ->paginate();

        return view('admin.reports.verizon.chargeback', [
            'data' => $data,
            'sdate' => empty($sdate) ? '' : $sdate->format('Y-m-d'),
            'edate' => empty($edate) ? '' : $edate->format('Y-m-d'),
            'phone' => $request->phone,
            'year' => $request->year,
            'month' => $request->month
        ]);
    }

    public function upload(Request $request) {

        DB::beginTransaction();

        try {

            $v = Validator::make($request->all(), [
                'file' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v as $m) {
                    $msg .= $m . "\n";
                }

                DB::rollback();
                $this->output($msg);
            }

            $key = 'file';

            if (Input::hasFile($key) && Input::file($key)->isValid()) {
                $path = Input::file($key)->getRealPath();

                Helper::log('### FILE ###', [
                    'key' => $key,
                    'path' => $path
                ]);

                $name = Input::file($key)->getClientOriginalName();
                if (!ends_with($name, '.dat')) {
                    DB::rollback();
                    $this->output('Please select valid .dat file from Verizon export');
                }
                $handle = fopen($path, "r");
                if ($handle) {



                    while (($line = fgets($handle)) !== false) {
                        $cols = explode("\t", $line);
                        if (count($cols) < 32) {
                            DB::rollback();
                            $this->output('File line format is invalid: ' . count($cols));
                        }

                        $va = VerizonChargeback::find($cols[9]);
                        if (!empty($va)) {
                            //DB::rollback();
                            //$this->output('Duplicated record found. Please check if you already imported same file');
                            $va->delete();
                        }

                        $va = new VerizonChargeback;
                        $va->billing = $cols[0];
                        $va->outlet_id = $cols[1];
                        $va->vendor = $cols[2];
                        $va->year = $cols[3];
                        $va->month = $cols[4];
                        $va->original_mobile_id = $cols[5];
                        $va->mobile_id = $cols[6];
                        $va->device_category = $cols[7];
                        $va->device_id = $cols[8];
                        $va->account_number = $cols[9];
                        $va->price_plan = $cols[10];
                        $va->customer_name = $cols[11];
                        $va->activation_date = Carbon::createFromFormat('m/d/Y', $cols[12]);
                        $va->deactivation_date = Carbon::createFromFormat('m/d/Y', $cols[13]);
                        $va->days_of_service = $cols[14];
                        $va->access_charge = $cols[15];
                        $va->contract_term = $cols[16];
                        $va->chargeback_amount = $cols[17];
                        $va->spiff = $cols[18];
                        $va->col20 = $cols[19];
                        $va->col21 = $cols[20];
                        $va->col22 = $cols[21];
                        $va->col23 = $cols[22];
                        $va->model = $cols[23];
                        $va->col25 = $cols[24];
                        $va->col26 = $cols[25];
                        $va->col27 = $cols[26];
                        $va->col28 = $cols[27];
                        $va->col29 = $cols[28];
                        $va->col30 = $cols[29];
                        $va->col31 = $cols[30];
                        $va->col32 = $cols[31];

                        $trans = Transaction::where('phone', $va->mobile_id)
                            ->where('cdate', '>=', $va->activation_date->addDays(-1))
                            ->where('cdate', '<', $va->activation_date->addDays(1))
                            ->where('status', '!=', 'F')
                            ->first();
                        if (!empty($trans)) {
                            $va->tx_id = $trans->id;
                            $va->account_id = $trans->account_id;
                        }

                        $va->save();
                    }

                    fclose($handle);
                } else {
                    // error opening the file.
                    DB::rollback();
                    $this->output('Error while opening file');
                }
            } else {
                DB::rollback();
                $this->output('Please select valid file');
            }

            DB::commit();

            $this->output('Your request has been processed successfully!', true, false);

        } catch (\Exception $ex) {
            DB::rollback();
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    private function output($msg, $close_modal = false, $is_error = true) {
        echo "<script>";

        if ($close_modal) {
            echo "parent.close_modal('div_upload');";
        }

        $msg = addslashes($msg);
        $msg = str_replace("\r\n", "\t", $msg);
        $msg = str_replace("\n", "\t", $msg);
        $msg = str_replace("\r", "\t", $msg);

        if ($is_error) {
            echo "parent.myApp.hideLoading();";
            echo "parent.myApp.showError('$msg');";
        } else {
            echo "parent.myApp.hideLoading();";
            echo "parent.myApp.showSuccess('$msg');";
        }

        echo "</script>";
        exit;
    }
}