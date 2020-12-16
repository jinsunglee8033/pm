<?php
/**
 * User: Royce
 * Date: 7/25/18
 */

namespace App\Http\Controllers\SubAgent\Reports;

use App\Http\Controllers\Controller;
use App\Model\Carrier;
use App\Model\GenActivation;
use Illuminate\Http\Request;
use App\Model\Transaction;
use App\Model\Product;
use App\Model\Account;

class ReceiptController extends Controller {

    public function show(Request $request, $id) {
        
        $trans = Transaction::find($id);
        if (!empty($trans)) {
            $trans->product = Product::where('id', $trans->product_id)->first();

            if($trans->product_id ==='WGENA' || $trans->product_id ==='WGENTA' || $trans->product_id ==='WGENOA' || $trans->product_id ==='WGENTOA'){
                $gen_info = GenActivation::where('trans_id', $trans->id)->first();
                if(!empty($gen_info)) {
                    $trans->msid = $gen_info->msid;
                    $trans->msl = $gen_info->msl;
                }
            }

            return view('receipt')->with([
                'trans' => $trans
            ]);
        } else {
            return view('errors.404');
        }
    }

}