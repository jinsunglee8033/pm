<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Lib\emida;
use App\Mail\ACHBounced;
use App\Model\ACHPosting;
use App\User;
use App\Model\Account;
use App\Model\AccountFile;
use App\Model\AccountFileAtt;
use App\Model\CKEditorFile;
use Carbon\Carbon;
use App\Lib\eSignature;
use App\Lib\echoSig;
use App\Lib\Helper;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

//use Illuminate\Http\Request;
//use \Request;
//use \Auth;

### QUERY LOGGING BEGIN ###
\DB::listen(
    function ($query) {

        if (preg_match('/\`jobs\`/', str_replace("\n", "", $query->sql), $match)) {
            return;
        }

        //  $sql - select * from `ncv_users` where `ncv_users`.`id` = ? limit 1
        //  $bindings - [5]
        //  $time(in milliseconds) - 0.38
        Helper::log("### SQL QUERY###", [
            'QUERY' => str_replace("\n", "", $query->sql),
            'BINDINGS' => $query->bindings,
            'TIME' => $query->time . " ms."
        ]);
    }
);
### QUERY LOGGING END ###

Route::get('/', function () {

    $value = \Cookie::get('repeated_customer');

    if($value == 'yes'){
        return redirect('/login');
    }else{
        return view('welcome');
    }

});

Route::post('/ckeditor/upload', 'CKEditorController@upload');

Route::get('/why-perfect-mobile', function() {
    return view('why-perfect-mobile');
});

Route::get('/contact-us', 'ContactUsController@show');
Route::post('/contact-us', 'ContactUsController@post');

Route::get('/apply-subagent', 'ApplySubagentController@show');
Route::post('/apply-subagent', 'ApplySubagentController@post');

Route::get('/apply-masteragent', 'ApplyMasteragentController@show');
Route::post('/apply-masteragent', 'ApplyMasteragentController@post');


Route::auth();
Auth::routes();
Route::get('/logout', 'Auth\LoginController@logout');

Route::get('/activate', function() {
    return view('activate');
});

Route::group(['prefix' => 'att'], function() {
    Route::get('/activate', 'ATT\ActivateController@show');
    Route::post('/activate', 'ATT\ActivateController@show');
    Route::get('/activate/sim', 'ATT\ActivateController@sim');
    Route::get('/activate/esn', 'ATT\ActivateController@esn');
    Route::post('/activate/post', 'ATT\ActivateController@post');
    Route::get('/activate/success/{id}', 'ATT\ActivateController@success');
    Route::get('/recharge', 'ATT\RechargeController@show');
    Route::post('/recharge', 'ATT\RechargeController@show');
    Route::post('/recharge/process', 'ATT\RechargeController@process');
});

Route::group(['prefix' => 'gen'], function() {
    Route::get('/recharge', 'GEN\RechargeController@show');
    Route::post('/recharge', 'GEN\RechargeController@show');
    Route::get('/recharge/check_mdn', 'GEN\RechargeController@check_mdn');
    Route::post('/recharge/process', 'GEN\RechargeController@process');

//    Route::post('/recharge/post', 'GEN\RechargeController@post');
    Route::post('/recharge/process', 'GEN\RechargeController@process');
    Route::get('/recharge/phone', 'GEN\RechargeController@phone');
//    ROute::post('/rtr/gen/post', 'SubAgent\RTR\GENController@post');
    Route::get('/recharge/success/{id}', 'GEN\RechargeController@success');
//    Route::get('/rtr/gen/success/{id}', 'SubAgent\RTR\GENController@success');

    Route::get('/activate', 'GEN\ActivateController@show');
    Route::post('/activate', 'GEN\ActivateController@show');
    Route::get('/activate/esn', 'GEN\ActivateController@esn');
    Route::get('/activate/zip', 'GEN\ActivateController@zip');
    Route::get('/activate/sim', 'GEN\ActivateController@sim');
    Route::get('/activate/commission', 'GEN\ActivateController@commission');
    Route::post('/activate/post', 'GEN\ActivateController@post');
    Route::post('/activate/process', 'GEN\ActivateController@process');
    Route::get('/activate/success/{id}', 'GEN\ActivateController@success');

    Route::get('/activate-tmo', 'GEN\ActivateController@show_tmo');
    Route::post('/activate-tmo', 'GEN\ActivateController@show_tmo');
    Route::get('/activate/sim_tmo', 'GEN\ActivateController@sim_tmo');
    Route::get('/activate/zip_tmo', 'GEN\ActivateController@zip_tmo');
    Route::post('/activate/post_tmo', 'GEN\ActivateController@post_tmo');
    Route::post('/activate/process_tmo', 'GEN\ActivateController@process_tmo');

    Route::get('/redemption', 'GEN\RedemptionController@show');
    Route::post('/redemption', 'GEN\RedemptionController@show');
    Route::get('/redemption/check_mdn', 'GEN\RedemptionController@check_mdn');
    Route::post('/redemption/process', 'GEN\RedemptionController@process');

});

Route::group(['prefix' => 'freeup'], function() {
    Route::get('/activate', 'FreeUP\ActivateController@show');
    Route::post('/activate', 'FreeUP\ActivateController@show');
    Route::get('/activate/sim/{type}', 'FreeUP\ActivateController@sim');
    Route::get('/activate/imei', 'FreeUP\ActivateController@imei');
    Route::get('/activate/commission', 'FreeUP\ActivateController@commission');
    Route::post('/activate/post', 'FreeUP\ActivateController@post');
    Route::post('/activate/process', 'FreeUP\ActivateController@process');
    Route::get('/activate/success/{id}', 'FreeUP\ActivateController@success');
//    Route::get('/pin', 'FreeUP\PinController@show');
//    Route::post('/pin', 'FreeUP\PinController@show');
//    Route::post('/pin/process', 'FreeUP\PinController@process');
    Route::get('/recharge', 'FreeUP\RechargeController@show');
    Route::post('/recharge', 'FreeUP\RechargeController@show');
    Route::post('/recharge/process', 'FreeUP\RechargeController@process');
});

Route::group(['prefix' => 'rok'], function() {
    Route::get('/activate', 'ROK\ActivateController@show');
    Route::post('/activate', 'ROK\ActivateController@show');
    Route::post('/activate/post', 'ROK\ActivateController@post');
    Route::get('/recharge', 'ROK\RechargeController@show');
    Route::post('/recharge', 'ROK\RechargeController@show');
    Route::post('/recharge/process', 'ROK\RechargeController@process');
    Route::post('/recharge/lookup-phone', 'ROK\RechargeController@lookup');
});

Route::group(['prefix' => 'rokit'], function() {
    Route::get('/pin', 'ROKiT\PinController@show');
    Route::post('/pin', 'ROKiT\PinController@show');
    Route::post('/pin/process', 'ROKiT\PinController@process');
});

Route::group(['prefix' => 'boom'], function() {
    Route::get('/activate', 'Boom\ActivateController@show');
    Route::post('/activate', 'Boom\ActivateController@show');
    Route::get('/activate/sim', 'Boom\ActivateController@sim');
    Route::get('/activate/esn', 'Boom\ActivateController@esn');
    Route::get('/activate/commission_blue', 'Boom\ActivateController@commission_blue');
    Route::post('/activate/post', 'Boom\ActivateController@post');
    Route::post('/activate/process', 'Boom\ActivateController@process');
    Route::get('/activate/success/{id}', 'Boom\ActivateController@success');
    Route::get('/recharge', 'Boom\RechargeController@show');
    Route::post('/recharge', 'Boom\RechargeController@show');
    ROute::get('/recharge/check_mdn', 'Boom\RechargeController@check_mdn');
    ROute::get('/recharge/get_processing_fee', 'Boom\RechargeController@get_processing_fee');
    Route::post('/recharge/process', 'Boom\RechargeController@process');
});


Route::group(['prefix' => 'virtual-rep'], function() {
    Route::any('/', 'VirtualRepController@show');
});

Route::group(['prefix' => 'locus'], function() {
    Route::get('/activate', 'Locus\ActivateController@show');
    Route::post('/activate', 'Locus\ActivateController@show');
});


Route::any('/payment/paypal/log', 'Payment\PaypalController@log');

Route::get('/admin2', 'Admin\Dashboard2Controller@show');

Route::group(['middleware' => 'admin', 'prefix' => '/admin'], function() {
    Route::get('/', 'Admin\DashboardController@show');
    Route::post('/', 'Admin\DashboardController@show');

    Route::get('/news', 'Admin\NewsController@show');
    Route::post('/news', 'Admin\NewsController@show');
    Route::get('/advertise', 'Admin\AdvertiseController@show');
    Route::post('/advertise', 'Admin\AdvertiseController@show');
    Route::get('/digital', 'Admin\DigitalController@show');
    Route::post('/digital', 'Admin\DigitalController@show');
    Route::get('/task', 'Admin\TaskController@show');
    Route::post('/task', 'Admin\TaskController@show');
    Route::get('/follow', 'Admin\FollowController@show');
    Route::post('/follow', 'Admin\FollowController@show');
    Route::get('/document', 'Admin\DocumentController@show');
    Route::post('/document', 'Admin\DocumentController@show');
    Route::get('/communication', 'Admin\CommunicationController@show');
    Route::post('/communication', 'Admin\CommunicationController@show');

    Route::get('/virtual-rep/shop', 'Admin\VirtualRepController@shop');
    Route::post('/virtual-rep/shop', 'Admin\VirtualRepController@shop');
    Route::get('/virtual-rep/add_to_cart', 'Admin\VirtualRepController@add_to_cart');
    Route::any('/virtual-rep/cart', 'Admin\VirtualRepController@cart');
    Route::any('/virtual-rep/cart/status', 'Admin\VirtualRepController@check_status');
    Route::any('/virtual-rep/cart/contact_me', 'Admin\VirtualRepController@contact_me');
    Route::any('/virtual-rep/cart/paid', 'Admin\VirtualRepController@cart_paid');
    Route::any('/virtual-rep/cart/cod', 'Admin\VirtualRepController@cart_cod');
    Route::any('/virtual-rep/cart/remove/{id}', 'Admin\VirtualRepController@cart_remove');
    Route::any('/virtual-rep/cart/shipping_method/{method}', 'Admin\VirtualRepController@shipping_method');
    Route::any('/virtual-rep/general_request', 'Admin\VirtualRepController@general_request');
    Route::any('/virtual-rep/general_request/save', 'Admin\VirtualRepController@general_request_save');
    Route::post('/virtual-rep/save', 'Admin\VirtualRepController@save');
    Route::post('/virtual-rep/log-request', 'Admin\VirtualRepController@logRequest');

    Route::any('/account', 'Admin\Account\AccountController@show');
    Route::post('/account/rate-plan/update', 'Admin\Account\RatePlanController@update');
    Route::post('/account/rate-plan/add', 'Admin\Account\RatePlanController@add');
    Route::post('/account/rate-plan/load-owned-plans', 'Admin\Account\RatePlanController@loadOwnedPlans');
    Route::post('/account/rate-plan/load-plan', 'Admin\Account\RatePlanController@loadPlan');
    Route::post('/account/rate-plan/copy', 'Admin\Account\RatePlanController@copy');
    Route::post('/account/rate-plan/remove', 'Admin\Account\RatePlanController@remove');

    Route::post('/account/rate-detail/load', 'Admin\Account\RateDetailController@load');
    Route::post('/account/rate-detail/update', 'Admin\Account\RateDetailController@update');
    Route::post('/account/rate-detail/load-excel', 'Admin\Account\RateDetailController@load_excel');

    Route::post('/account/spiff-detail/load', 'Admin\Account\AccountController@spiff_detail_load');

    Route::post('/account/payment/list', 'Admin\Account\PaymentController@getList');
    Route::post('/account/payment/add', 'Admin\Account\PaymentController@add');

    Route::post('/account/credit/list', 'Admin\Account\CreditController@getList');
    Route::post('/account/credit/add', 'Admin\Account\CreditController@add');

    Route::post('/account/vcb/list', 'Admin\Account\ConsignmentVendorController@getList');
    Route::post('/account/vcb/add', 'Admin\Account\ConsignmentVendorController@add');
    Route::post('/account/vcb/update', 'Admin\Account\ConsignmentVendorController@update');

    Route::post('/account/get-parent-info', 'Admin\Account\AccountController@getParentInfo');
    Route::post('/account/get-account-info', 'Admin\Account\AccountController@getAccountInfo');
    Route::post('/account/get-user-list', 'Admin\Account\AccountController@getUserList');
    Route::post('/account/get-user-info', 'Admin\Account\AccountController@getUserInfo');
    Route::post('/account/get-rate-plans', 'Admin\Account\AccountController@getRatePlans');

    Route::post('/account/create', 'Admin\Account\AccountController@create');
    Route::post('/account/create/address-check', 'Admin\Account\AccountController@createAddressCheck');
    Route::post('/account/update', 'Admin\Account\AccountController@update');
    Route::post('/account/remove', 'Admin\Account\AccountController@remove');

    Route::get('/account/add_new/{p_account_id}', 'Admin\Account\AccountController@add_new');
    Route::get('/account/edit/{p_account_id}', 'Admin\Account\AccountController@edit');
    Route::get('/account/edit/{p_account_id}/{account_id}', 'Admin\Account\AccountController@edit');
    Route::post('/account/edit/send_welcome_email', 'Admin\Account\AccountController@send_welcome_email');

    Route::post('/account/credit-info/update', 'Admin\Account\AccountController@updateCreditInfo');

    Route::post('/account/add-user', 'Admin\Account\AccountController@addUser');
    Route::post('/account/update-user', 'Admin\Account\AccountController@updateUser');
    Route::post('/account/remove-user', 'Admin\Account\AccountController@removeUser');
    Route::post('/account/login-as', 'Admin\Account\AccountController@loginAs');

    Route::post('/account/add_account_shipping_fee', 'Admin\Account\AccountController@add_account_shipping_fee');
    Route::post('/account/delete_account_shipping_fee', 'Admin\Account\AccountController@delete_account_shipping_fee');

    Route::post('/account/load_parent_account_info', 'Admin\Account\AccountController@load_parent_account_info');
    Route::post('/account/parent_transfer', 'Admin\Account\AccountController@parent_transfer');


    Route::post('/account/authority', 'Admin\Account\AccountController@authority');
    Route::post('/account/authority/post', 'Admin\Account\AccountController@authority_post');

    Route::post('/account/spiff_template', 'Admin\Account\AccountController@spiff_template');
    Route::post('/account/spiff_template/check', 'Admin\Account\AccountController@spiff_template_check');

    Route::any('/account/lookup', 'Admin\Account\AccountController@lookup');
    Route::any('/account/lookup_new', 'Admin\Account\AccountController@lookup_new');
    Route::any('/account/spiff', 'Admin\Account\SpiffController@show');

    Route::any('/account/remove_plan', 'Admin\Account\AccountController@remove_plan');

    Route::any('/account/vr', 'Admin\Account\AccountController@vr');
    Route::any('/account/vr/save', 'Admin\Account\AccountController@vr_save');
    Route::any('/account/vr_product', 'Admin\Account\AccountController@vr_product');
    Route::any('/account/vr_product/save', 'Admin\Account\AccountController@vr_product_save');

    Route::any('/account/activation_controller', 'Admin\Account\AccountController@activation_controller');

    Route::get('/account/store', 'Admin\Account\StoreController@show');
    Route::post('/account/store/update', 'Admin\Account\StoreController@update');
    Route::post('/account/store/remove', 'Admin\Account\StoreController@remove');
    Route::post('/account/store/add-ip', 'Admin\Account\StoreController@add_ip');
    Route::post('/account/store/remove-ip', 'Admin\Account\StoreController@remove_ip');
    Route::post('/account/store/update-ip', 'Admin\Account\StoreController@update_ip');
    Route::post('/account/store/update-tz', 'Admin\Account\StoreController@update_tz');

    Route::get('/account/map', 'Admin\Account\MapController@show');
    Route::post('/account/map/find', 'Admin\Account\MapController@find');

    Route::get('/account/user-hour', 'Admin\Account\UserHourController@show');
    Route::post('/account/user-hour/update', 'Admin\Account\UserHourController@update');
    Route::post('/account/user-hour/remove', 'Admin\Account\UserHourController@remove');
    Route::post('/account/user-hour/add-ip', 'Admin\Account\UserHourController@add_ip');
    Route::post('/account/user-hour/remove-ip', 'Admin\Account\UserHourController@remove_ip');

    Route::get('/reports/transaction', 'Admin\Reports\TransactionController@show');
    Route::post('/reports/transaction', 'Admin\Reports\TransactionController@show');
    Route::post('/reports/transaction/get-detail', 'Admin\Reports\TransactionController@detail');
    Route::post('/reports/transaction/update', 'Admin\Reports\TransactionController@update');
    Route::post('/reports/transaction/update-boom', 'Admin\Reports\TransactionController@update_boom');
    Route::post('/reports/transaction/update-note2', 'Admin\Reports\TransactionController@update_note2');
    Route::post('/reports/transaction/retry', 'Admin\Reports\TransactionController@retry');
    Route::post('/reports/transaction/batch-lookup', 'Admin\Reports\TransactionController@batchLookup');
    Route::get('/reports/transaction/rtrque/{trans_id}', 'Admin\Reports\TransactionController@rtrque');
    Route::post('/reports/transaction/void-transaction', 'Admin\Reports\TransactionController@void_transaction');
    Route::post('/reports/transaction/action_required', 'Admin\Reports\TransactionController@action_required');
    Route::get('/reports/transaction/{id}', 'Admin\Reports\TransactionController@detail_sub');
    Route::post('/reports/transaction/{id}', 'Admin\Reports\TransactionController@update_sub');

    Route::get('/reports/rtr-q', 'Admin\Reports\RTRQueueController@show');
    Route::post('/reports/rtr-q', 'Admin\Reports\RTRQueueController@show');
    Route::post('/reports/rtr-q/retry', 'Admin\Reports\RTRQueueController@retry');

    Route::get('/reports/document', 'Admin\Reports\DocumentController@show');
    Route::post('/reports/document', 'Admin\Reports\DocumentController@show');
    Route::post('/reports/document/pdf', 'Admin\Reports\DocumentController@pdf');
    Route::post('/reports/document/set-status/{id}', 'Admin\Reports\DocumentController@setStatus');

    Route::get('/reports/document-att', 'Admin\Reports\DocumentAttController@show');
    Route::post('/reports/document-att', 'Admin\Reports\DocumentAttController@show');
    Route::post('/reports/document-att/pdf', 'Admin\Reports\DocumentAttController@pdf');
    Route::post('/reports/document-att/set-status/{id}', 'Admin\Reports\DocumentAttController@setStatus');

    Route::get('/reports/document-h2o', 'Admin\Reports\DocumentH2oController@show');
    Route::post('/reports/document-h2o', 'Admin\Reports\DocumentH2oController@show');
    Route::post('/reports/document-h2o/pdf', 'Admin\Reports\DocumentH2oController@pdf');
    Route::post('/reports/document-h2o/set-status/{id}', 'Admin\Reports\DocumentH2oController@setStatus');

    Route::get('/reports/verizon/activation', 'Admin\Reports\Verizon\ActivationController@show');
    Route::post('/reports/verizon/activation', 'Admin\Reports\Verizon\ActivationController@show');
    Route::post('/reports/verizon/activation/upload', 'Admin\Reports\Verizon\ActivationController@upload');

    Route::get('/reports/verizon/chargeback', 'Admin\Reports\Verizon\ChargeBackController@show');
    Route::post('/reports/verizon/chargeback', 'Admin\Reports\Verizon\ChargeBackController@show');
    Route::post('/reports/verizon/chargeback/upload', 'Admin\Reports\Verizon\ChargeBackController@upload');

    Route::get('/reports/virtual-rep', 'Admin\Reports\VirtualRepController@show');
    Route::post('/reports/virtual-rep', 'Admin\Reports\VirtualRepController@show');
    Route::post('/reports/virtual-rep/load-detail', 'Admin\Reports\VirtualRepController@loadDetail');
    Route::post('/reports/virtual-rep/update', 'Admin\Reports\VirtualRepController@update');

    Route::get('/reports/vr-cart', 'Admin\Reports\VirtualRepController@cart');
    Route::post('/reports/vr-cart', 'Admin\Reports\VirtualRepController@cart');
    Route::post('/reports/vr-cart/detail', 'Admin\Reports\VirtualRepController@cart_detail');
    Route::post('/reports/vr-cart/update', 'Admin\Reports\VirtualRepController@cart_update');

    Route::get('/reports/vr-request', 'Admin\Reports\VirtualRepController@showRequest');
    Route::post('/reports/vr-request', 'Admin\Reports\VirtualRepController@showRequest');
    Route::post('/reports/vr-request/load-detail', 'Admin\Reports\VirtualRepController@loadDetailRequest');

    Route::get('/reports/vr-request-for-master', 'Admin\Reports\VirtualRepController@showRequestForMaster');
    Route::post('/reports/vr-request-for-master', 'Admin\Reports\VirtualRepController@showRequestForMaster');
    Route::post('/reports/vr-request/load-detail-for-master', 'Admin\Reports\VirtualRepController@loadDetailRequestForMaster');

    Route::post('/reports/vr_request/update', 'Admin\Reports\VirtualRepController@updateRequest');
    Route::post('/reports/vr_request/update/memo', 'Admin\Reports\VirtualRepController@update_memo');
    Route::post('/reports/vr_request/update/kickback', 'Admin\Reports\VirtualRepController@update_kickback');
    Route::post('/reports/vr_request/additional_shipping_fee', 'Admin\Reports\VirtualRepController@additional_shipping_fee');
    Route::post('/reports/vr_request/apply_to_debit', 'Admin\Reports\VirtualRepController@apply_to_debit');

    Route::any('/reports/vr-sales', 'Admin\Reports\VirtualRepController@sales');

    Route::get('/reports/billing', 'Admin\Reports\BillingController@show');
    Route::post('/reports/billing', 'Admin\Reports\BillingController@show');
    Route::get('/reports/billing/detail/{id}', 'Admin\Reports\BillingController@detail');
    Route::post('/reports/billing/detail/{id}', 'Admin\Reports\BillingController@detail');

    Route::get('/reports/payments', 'Admin\Reports\PaymentsController@show');
    Route::post('/reports/payments', 'Admin\Reports\PaymentsController@show');
    Route::post('/reports/payments/paypal/add', 'Admin\Reports\PaymentsController@addPayPal');

    Route::any('/reports/payments/root', 'Admin\Reports\PaymentsController@root');

    Route::get('/reports/vendor/commission', 'Admin\Reports\Vendor\CommissionController@show');
    Route::post('/reports/vendor/commission', 'Admin\Reports\Vendor\CommissionController@show');

    Route::post('/reports/vendor/commission/upload', 'Admin\Reports\Vendor\CommissionController@upload');
    Route::post('/reports/vendor/commission/upload_temp', 'Admin\Reports\Vendor\CommissionController@upload_temp');
    Route::get('/reports/vendor/commission_temp', 'Admin\Reports\Vendor\CommissionController@show_temp');
    Route::post('/reports/vendor/commission_temp', 'Admin\Reports\Vendor\CommissionController@show_temp');
    Route::post('/reports/vendor/commission/upload_final', 'Admin\Reports\Vendor\CommissionController@upload_final');
    Route::get('/reports/vendor/commission/upload_final', 'Admin\Reports\Vendor\CommissionController@upload_final');

    Route::post('/reports/vendor/commission/upload_bonus', 'Admin\Reports\Vendor\CommissionController@upload_bonus');
    Route::post('/reports/vendor/commission/upload_bonus_temp', 'Admin\Reports\Vendor\CommissionController@upload_bonus_temp');
    Route::get('/reports/vendor/commission_bonus_temp', 'Admin\Reports\Vendor\CommissionController@show_bonus_temp');
    Route::post('/reports/vendor/commission_bonus_temp', 'Admin\Reports\Vendor\CommissionController@show_bonus_temp');
    Route::post('/reports/vendor/commission/upload_bonus_final', 'Admin\Reports\Vendor\CommissionController@upload_bonus_final');
    Route::get('/reports/vendor/commission/upload_bonus_final', 'Admin\Reports\Vendor\CommissionController@upload_bonus_final');

    Route::post('/reports/vendor/commission/upload_bonus_by_acct', 'Admin\Reports\Vendor\CommissionController@upload_bonus_by_acct');
    Route::post('/reports/vendor/commission/upload_bonus_by_acct_temp', 'Admin\Reports\Vendor\CommissionController@upload_bonus_by_acct_temp');
    Route::get('/reports/vendor/commission_bonus_by_acct_temp', 'Admin\Reports\Vendor\CommissionController@show_bonus_by_acct_temp');
    Route::post('/reports/vendor/commission_bonus_by_acct_temp', 'Admin\Reports\Vendor\CommissionController@show_bonus_by_acct_temp');
    Route::post('/reports/vendor/commission/upload_bonus_by_acct_final', 'Admin\Reports\Vendor\CommissionController@upload_bonus_by_acct_final');
    Route::get('/reports/vendor/commission/upload_bonus_by_acct_final', 'Admin\Reports\Vendor\CommissionController@upload_bonus_by_acct_final');

    Route::post('/reports/vendor/commission/batch-lookup', 'Admin\Reports\Vendor\CommissionController@batchLookup');

    Route::get('/reports/vendor/bonus', 'Admin\Reports\Vendor\BonusController@show');
    Route::post('/reports/vendor/bonus', 'Admin\Reports\Vendor\BonusController@show');
    Route::post('/reports/vendor/bonus/add_rule', 'Admin\Reports\Vendor\BonusController@add_rule');
    Route::post('/reports/vendor/bonus/remove_rule', 'Admin\Reports\Vendor\BonusController@remove_rule');
    Route::post('/reports/vendor/bonus/add_exception', 'Admin\Reports\Vendor\BonusController@add_exception');
    Route::post('/reports/vendor/bonus/remove_exception', 'Admin\Reports\Vendor\BonusController@remove_exception');
    Route::post('/reports/vendor/bonus/pay_out', 'Admin\Reports\Vendor\BonusController@pay_out');

    Route::get('/reports/vendor/reup/commission', 'Admin\Reports\Vendor\ReUP\CommissionController@show');
    Route::post('/reports/vendor/reup/commission', 'Admin\Reports\Vendor\ReUP\CommissionController@show');
    Route::post('/reports/vendor/reup/commission/upload', 'Admin\Reports\Vendor\ReUP\CommissionController@upload');
    Route::post('/reports/vendor/reup/commission/batch-lookup', 'Admin\Reports\Vendor\ReUP\CommissionController@batchLookup');

    Route::get('/reports/vendor/reup/charge-back', 'Admin\Reports\Vendor\ReUP\ChargeBackController@show');
    Route::post('/reports/vendor/reup/charge-back', 'Admin\Reports\Vendor\ReUP\ChargeBackController@show');
    Route::post('/reports/vendor/reup/charge-back/upload', 'Admin\Reports\Vendor\ReUP\ChargeBackController@upload');
    Route::post('/reports/vendor/reup/charge-back/batch-lookup', 'Admin\Reports\Vendor\ReUP\ChargeBackController@batchLookup');

    Route::get('/reports/vendor/reup/rebate', 'Admin\Reports\Vendor\ReUP\RebateController@show');
    Route::post('/reports/vendor/reup/rebate', 'Admin\Reports\Vendor\ReUP\RebateController@show');
    Route::post('/reports/vendor/reup/rebate/upload', 'Admin\Reports\Vendor\ReUP\RebateController@upload');
    Route::post('/reports/vendor/reup/rebate/batch-lookup', 'Admin\Reports\Vendor\ReUP\RebateController@batchLookup');

    Route::get('/reports/vendor/boom/commission', 'Admin\Reports\Vendor\Boom\CommissionController@show');
    Route::post('/reports/vendor/boom/commission', 'Admin\Reports\Vendor\Boom\CommissionController@show');
    Route::post('/reports/vendor/boom/commission/upload', 'Admin\Reports\Vendor\Boom\CommissionController@upload');
    Route::post('/reports/vendor/boom/commission/upload_temp', 'Admin\Reports\Vendor\Boom\CommissionController@upload_temp');

    Route::get('/reports/spiff', 'Admin\Reports\SpiffController@show');
    Route::post('/reports/spiff', 'Admin\Reports\SpiffController@show');

    Route::get('/reports/rebate', 'Admin\Reports\RebateController@show');
    Route::post('/reports/rebate', 'Admin\Reports\RebateController@show');

    Route::get('/reports/credit', 'Admin\Reports\CreditController@show');
    Route::post('/reports/credit', 'Admin\Reports\CreditController@show');

    Route::get('/reports/activity', 'Admin\Reports\ActivityController@show');
    Route::post('/reports/activity', 'Admin\Reports\ActivityController@show');

    Route::get('/reports/consignment/charge', 'Admin\Reports\Consignment\ChargeController@show');
    Route::post('/reports/consignment/charge', 'Admin\Reports\Consignment\ChargeController@show');

    Route::get('/reports/consignment/balance', 'Admin\Reports\Consignment\BalanceController@show');
    Route::post('/reports/consignment/balance', 'Admin\Reports\Consignment\BalanceController@show');

    Route::any('/reports/consignment-vendor/balance', 'Admin\Reports\ConsignmentVendor\BalanceController@show');

    Route::get('/reports/promotion', 'Admin\Reports\PromotionController@show');
    Route::post('/reports/promotion', 'Admin\Reports\PromotionController@show');

    Route::get('/reports/ach-bounce', 'Admin\Reports\AchBounceController@show');
    Route::post('/reports/ach-bounce', 'Admin\Reports\AchBounceController@show');

    Route::get('/reports/login-history', 'Admin\Reports\LogInHistoryController@show');
    Route::post('/reports/login-history', 'Admin\Reports\LogInHistoryController@show');

    Route::get('/reports/monitor/plansim', 'Admin\Reports\MonitorController@plansim');
    Route::post('/reports/monitor/plansim', 'Admin\Reports\MonitorController@plansim');
    Route::get('/reports/monitor/recharge', 'Admin\Reports\MonitorController@recharge');
    Route::post('/reports/monitor/recharge', 'Admin\Reports\MonitorController@recharge');
    Route::post('/reports/monitor/batch-lookup', 'Admin\Reports\MonitorController@batchLookup');
    Route::get('/reports/monitor/esn-swap-history', 'Admin\Reports\MonitorController@esn_swap_history');
    Route::post('/reports/monitor/esn-swap-history', 'Admin\Reports\MonitorController@esn_swap_history');
    Route::get('/reports/monitor/boom-sim-swap', 'Admin\Reports\MonitorController@boom_sim_swap');
    Route::post('/reports/monitor/boom-sim-swap', 'Admin\Reports\MonitorController@boom_sim_swap');

    Route::get('/reports/spiff-setup-report', 'Admin\Reports\SpiffSetupReportController@show');
    Route::post('/reports/spiff-setup-report', 'Admin\Reports\SpiffSetupReportController@show');

    Route::get('/reports/discount-setup-report', 'Admin\Reports\DiscountSetupReportController@show');
    Route::post('/reports/discount-setup-report', 'Admin\Reports\DiscountSetupReportController@show');

    Route::get('/settings/activation-limit', 'Admin\Settings\ActivationLimitController@show');
    Route::post('/settings/activation-limit/update', 'Admin\Settings\ActivationLimitController@update');

    Route::get('/settings/news', 'Admin\Settings\NewsController@show');
    Route::post('/settings/news', 'Admin\Settings\NewsController@show');
    Route::post('/settings/news/add', 'Admin\Settings\NewsController@add');
    Route::post('/settings/news/update', 'Admin\Settings\NewsController@update');
    Route::post('/settings/news/get-detail', 'Admin\Settings\NewsController@detail');
    Route::post('/settings/news/void', 'Admin\Settings\NewsController@void');
    Route::post('/settings/news/copy_add', 'Admin\Settings\NewsController@copy_add');
    Route::post('/settings/news/remove', 'Admin\Settings\NewsController@remove');

    Route::get('/settings/fee', 'Admin\Settings\FeeController@show');
    Route::post('/settings/fee', 'Admin\Settings\FeeController@show');
    Route::post('/settings/fee/show_modal', 'Admin\Settings\FeeController@show_modal');
    Route::post('/settings/fee/post', 'Admin\Settings\FeeController@post');
    Route::post('/settings/fee/remove', 'Admin\Settings\FeeController@remove');

    Route::get('/settings/phones', 'Admin\Settings\PhonesController@show');
    Route::post('/settings/phones/add', 'Admin\Settings\PhoneController@add');
    Route::post('/settings/phones/update', 'Admin\Settings\PhoneController@update');

    Route::get('/settings/sim', 'Admin\Settings\StockSimController@show');
    Route::post('/settings/sim', 'Admin\Settings\StockSimController@show');
    Route::post('/settings/sim/upload', 'Admin\Settings\StockSimController@upload');
    Route::post('/settings/sim/assign', 'Admin\Settings\StockSimController@assign');
    Route::post('/settings/sim/batch-lookup', 'Admin\Settings\StockSimController@batchLookup');
    Route::post('/settings/sim/bulk_update', 'Admin\Settings\StockSimController@bulk_update');
    Route::post('/settings/sim/get_buyer_info', 'Admin\Settings\StockSimController@get_buyer_info');

    Route::get('/settings/esn', 'Admin\Settings\StockESNController@show');
    Route::post('/settings/esn', 'Admin\Settings\StockESNController@show');
    Route::post('/settings/esn/upload', 'Admin\Settings\StockESNController@upload');
    Route::post('/settings/esn/assign', 'Admin\Settings\StockESNController@assign');
    Route::post('/settings/esn/batch-lookup', 'Admin\Settings\StockESNController@batchLookup');
    Route::post('/settings/esn/bulk_update', 'Admin\Settings\StockESNController@bulk_update');

    Route::get('/settings/pin', 'Admin\Settings\StockPinController@show');
    Route::post('/settings/pin', 'Admin\Settings\StockPinController@show');
    Route::post('/settings/pin/upload', 'Admin\Settings\StockPinController@upload');
    Route::post('/settings/pin/assign', 'Admin\Settings\StockPinController@assign');
    Route::post('/settings/pin/batch-lookup', 'Admin\Settings\StockPinController@batchLookup');

    Route::get('/settings/mapping', 'Admin\Settings\MappingController@show');
    Route::post('/settings/mapping', 'Admin\Settings\MappingController@show');
    Route::post('/settings/mapping/bind', 'Admin\Settings\MappingController@bind');
    Route::post('/settings/mapping/batch-lookup', 'Admin\Settings\MappingController@batchLookup');

    Route::get('/settings/esn/pm', 'Admin\Settings\PMESNController@show');
    Route::post('/settings/esn/pm', 'Admin\Settings\PMESNController@show');
    Route::post('/settings/esn/pm/upload', 'Admin\Settings\PMESNController@upload');
    Route::post('/settings/esn/pm/batch-lookup', 'Admin\Settings\PMESNController@batchLookup');

    Route::get('/settings/vr-upload', 'Admin\Settings\VRUploadController@show');
    Route::post('/settings/vr-upload', 'Admin\Settings\VRUploadController@show');
    Route::post('/settings/vr-upload/upload', 'Admin\Settings\VRUploadController@upload');
    Route::post('/settings/vr-upload/update', 'Admin\Settings\VRUploadController@update');
    Route::get('/settings/vr-upload/update/stock', 'Admin\Settings\VRUploadController@update_stock');
    Route::get('/settings/vr-upload/update/sorting', 'Admin\Settings\VRUploadController@update_sorting');
    Route::post('/settings/vr-upload/upload/image', 'Admin\Settings\VRUploadController@upload_image');
    Route::post('/settings/vr-upload/show-detail', 'Admin\Settings\VRUploadController@show_detail');
//    Route::post('/settings/vr-upload/update-detail', 'Admin\Settings\VRUploadController@update_detail');
    Route::get('/settings/vr-upload/update-detail', 'Admin\Settings\VRUploadController@update_detail');
    Route::get('/settings/vr-upload/clone-detail', 'Admin\Settings\VRUploadController@clone_detail');
    Route::get('/settings/vr-upload/add-detail', 'Admin\Settings\VRUploadController@add_detail');

    Route::get('/settings/vr-upload2', 'Admin\Settings\VRUpload2Controller@show');
    Route::post('/settings/vr-upload2', 'Admin\Settings\VRUpload2Controller@show');
    Route::post('/settings/vr-upload2/update', 'Admin\Settings\VRUpload2Controller@update');
    Route::post('/settings/vr-upload2/upload/image', 'Admin\Settings\VRUpload2Controller@upload_image');
    Route::post('/settings/vr-upload2/show-detail', 'Admin\Settings\VRUpload2Controller@show_detail');
    Route::get('/settings/vr-upload2/update-detail', 'Admin\Settings\VRUpload2Controller@update_detail');
    Route::get('/settings/vr-upload2/copy_update', 'Admin\Settings\VRUpload2Controller@copy_update');
    Route::get('/settings/vr-upload2/add-detail', 'Admin\Settings\VRUpload2Controller@add_detail');

    Route::get('/settings/vr-product-price', 'Admin\Settings\VRProductPriceController@show');
    Route::post('/settings/vr-product-price', 'Admin\Settings\VRProductPriceController@show');
    Route::post('/settings/vr-product-price/show-detail', 'Admin\Settings\VRProductPriceController@show_detail');
    Route::post('/settings/vr-product-price/show-detail-price', 'Admin\Settings\VRProductPriceController@show_detail_price');
    Route::post('/settings/vr-product-price/update-product-price', 'Admin\Settings\VRProductPriceController@update_product_price');
    Route::get('/settings/vr-product-price/assign', 'Admin\Settings\VRProductPriceController@assign');
    Route::get('/settings/vr-product-price/delete', 'Admin\Settings\VRProductPriceController@delete');
    Route::get('/settings/vr-product-price/delete_all', 'Admin\Settings\VRProductPriceController@delete_all');
    Route::get('/settings/vr-product-price/account-check', 'Admin\Settings\VRProductPriceController@account_check');

    Route::get('/settings/vendor-consignment', 'Admin\Settings\VendorConsignmentController@show');
    Route::post('/settings/vendor-consignment', 'Admin\Settings\VendorConsignmentController@show');

    Route::get('/settings/spiff-setup', 'Admin\Settings\SpiffSetupController@show');
    Route::post('/settings/spiff-setup', 'Admin\Settings\SpiffSetupController@show');
    Route::post('/settings/spiff-setup/load-product', 'Admin\Settings\SpiffSetupController@loadProduct');
    Route::post('/settings/spiff-setup/load-denoms', 'Admin\Settings\SpiffSetupController@loadDenoms');
    Route::post('/settings/spiff-setup/load-detail', 'Admin\Settings\SpiffSetupController@loadDetail');
    Route::post('/settings/spiff-setup/add', 'Admin\Settings\SpiffSetupController@add');
    Route::post('/settings/spiff-setup/update', 'Admin\Settings\SpiffSetupController@update');
    Route::post('/settings/spiff-setup/load-special', 'Admin\Settings\SpiffSetupController@loadSpecial');
    Route::post('/settings/spiff-setup/add-special', 'Admin\Settings\SpiffSetupController@addSpecial');
    Route::post('/settings/spiff-setup/update-special', 'Admin\Settings\SpiffSetupController@updateSpecial');
    Route::any('/settings/spiff-setup/download-special', 'Admin\Settings\SpiffSetupController@downloadSpecial');
    Route::post('/settings/spiff-setup/add/template', 'Admin\Settings\SpiffSetupController@add_template');

    Route::get('/settings/spiff-setup2', 'Admin\Settings\SpiffSetup2Controller@show');
    Route::post('/settings/spiff-setup2', 'Admin\Settings\SpiffSetup2Controller@show');
    Route::post('/settings/spiff-setup2/add/template', 'Admin\Settings\SpiffSetup2Controller@add_template');
    Route::post('/settings/spiff-setup2/load/template', 'Admin\Settings\SpiffSetup2Controller@load_template');
    Route::post('/settings/spiff-setup2/edit/template', 'Admin\Settings\SpiffSetup2Controller@edit_template');
    Route::post('/settings/spiff-setup2/update', 'Admin\Settings\SpiffSetup2Controller@update');
    Route::post('/settings/spiff-setup2/call-amount', 'Admin\Settings\SpiffSetup2Controller@call_amount');
    Route::post('/settings/spiff-setup2/add-new-spiff', 'Admin\Settings\SpiffSetup2Controller@add_new_spiff');
    Route::post('/settings/spiff-setup2/reset-exist-only', 'Admin\Settings\SpiffSetup2Controller@reset_exist_only');
    Route::post('/settings/spiff-setup2/inc-dec-exist-spiff-only', 'Admin\Settings\SpiffSetup2Controller@inc_dec_exist_spiff_only');

    Route::get('/settings/account-spiff-setup', 'Admin\Settings\AccountSpiffSetupController@show');
    Route::post('/settings/account-spiff-setup', 'Admin\Settings\AccountSpiffSetupController@show');
    Route::post('/settings/account-spiff-setup/spiff-template', 'Admin\Settings\AccountSpiffSetupController@spiff_template');
    Route::post('/settings/account-spiff-setup/update-dis-temps', 'Admin\Settings\AccountSpiffSetupController@update_dis_temps');
    Route::post('/settings/account-spiff-setup/update-sub-temps', 'Admin\Settings\AccountSpiffSetupController@update_sub_temps');
    Route::post('/settings/account-spiff-setup/all-active', 'Admin\Settings\AccountSpiffSetupController@all_active');
    Route::post('/settings/account-spiff-setup/all-inactive', 'Admin\Settings\AccountSpiffSetupController@all_inactive');

    Route::get('/settings/spiff-setup/special', 'Admin\Settings\SpiffSetup\SpecialController@show');
    Route::post('/settings/spiff-setup/special', 'Admin\Settings\SpiffSetup\SpecialController@show');
    Route::post('/settings/spiff-setup/special/add', 'Admin\Settings\SpiffSetup\SpecialController@add');
    Route::post('/settings/spiff-setup/special/update', 'Admin\Settings\SpiffSetup\SpecialController@update');
    Route::post('/settings/spiff-setup/special/download', 'Admin\Settings\SpiffSetup\SpecialController@download');

    Route::get('/settings/product-setup', 'Admin\Settings\ProductSetupController@show');
    Route::post('/settings/product-setup', 'Admin\Settings\ProductSetupController@show');
    Route::post('/settings/product-setup/load-detail', 'Admin\Settings\ProductSetupController@loadDetail');
    Route::post('/settings/product-setup/add', 'Admin\Settings\ProductSetupController@add');
    Route::post('/settings/product-setup/update', 'Admin\Settings\ProductSetupController@update');
    Route::post('/settings/product-setup/add-denom', 'Admin\Settings\ProductSetupController@addDenom');
    Route::post('/settings/product-setup/update-denom', 'Admin\Settings\ProductSetupController@updateDenom');
    Route::post('/settings/product-setup/add-vendor-denom', 'Admin\Settings\ProductSetupController@addVendorDenom');
    Route::post('/settings/product-setup/update-vendor-denom', 'Admin\Settings\ProductSetupController@updateVendorDenom');
    Route::post('/settings/product-setup/init-rates', 'Admin\Settings\ProductSetupController@init_rates');
    Route::post('/settings/product-setup/delete-rates', 'Admin\Settings\ProductSetupController@delete_rates');
    Route::post('/settings/product-setup/update-init-denoms', 'Admin\Settings\ProductSetupController@update_init_denoms');
    Route::post('/settings/product-setup/vendor-fee-setup', 'Admin\Settings\ProductSetupController@vendorFeeSetup');
    Route::post('/settings/product-setup/vendor-fee-setup-del', 'Admin\Settings\ProductSetupController@vendorFeeSetupDel');

    Route::get('/settings/permission', 'Admin\Settings\PermissionController@show');
    Route::post('/settings/permission', 'Admin\Settings\PermissionController@show');
    Route::post('/settings/permission/path/add', 'Admin\Settings\PermissionController@addPath');
    Route::post('/settings/permission/path/update', 'Admin\Settings\PermissionController@updatePath');
    Route::post('/settings/permission/path/load', 'Admin\Settings\PermissionController@loadPath');
    Route::post('/settings/permission/action/list', 'Admin\Settings\PermissionController@loadActions');
    Route::post('/settings/permission/action/add', 'Admin\Settings\PermissionController@addAction');
    Route::post('/settings/permission/action/update', 'Admin\Settings\PermissionController@updateAction');
    Route::post('/settings/permission/action/load', 'Admin\Settings\PermissionController@loadAction');
    Route::post('/settings/permission/permission/list', 'Admin\Settings\PermissionController@loadPermissions');
    Route::post('/settings/permission/permission/update', 'Admin\Settings\PermissionController@updatePermission');


    Route::get('/esig/{doc_type}', function(\Illuminate\Http\Request $request, $doc_type) {
        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            die("Session expired!");
        }
        //$ret = eSignature::get_url($account);
        $ret = echoSig::get_url(
            $doc_type . '.pdf',
            $doc_type,
            $account->name,
            $account->address1 . ' ' . $account->address2,
            $account->city . ', ' . $account->state . ' ' . $account->zip,
            $account->email,
            $doc_type
        );
        if (!empty($ret['msg'])) {
            die($ret["msg"]);
        }
        //var_dump($url);
        //echo "<script>";
        //echo "window.open('" . $ret['url'] . "', '_blank');";
        //echo "</script>";

        return redirect($ret['url']);
    });

    Route::get('/error', function() {

        if (empty(session('error_msg'))) {
            return redirect('/admin');
        }

        return view('admin.error', [
            'error_msg' => session('error_msg')
        ]);
    });
});

Route::group(['middleware' => 'sub-agent', 'prefix' => '/sub-agent'], function() {
    Route::get('/', 'SubAgent\MainController@show');

    Route::get('/news', 'SubAgent\NewsController@show');
    Route::post('/news', 'SubAgent\NewsController@show');

    Route::get('/digital', 'SubAgent\InformationCenterController@show');
    Route::post('/digital', 'SubAgent\InformationCenterController@show');

    Route::get('/advertise', 'SubAgent\AdvertiseController@show');
    Route::post('/advertise', 'SubAgent\AdvertiseController@show');

    Route::get('/task', 'SubAgent\TaskController@show');
    Route::post('/task', 'SubAgent\TaskController@show');

    Route::get('/follow', 'SubAgent\FollowController@show');
    Route::post('/follow', 'SubAgent\FollowController@show');

    Route::get('/document', 'SubAgent\DocumentController@show');
    Route::post('/document', 'SubAgent\DocumentController@show');

    Route::get('/communication', 'SubAgent\CommunicationController@show');
    Route::post('/communication', 'SubAgent\CommunicationController@show');

    Route::get('/activate/verizon', 'SubAgent\Activate\VerizonController@show');
    Route::post('/activate/verizon', 'SubAgent\Activate\VerizonController@show');
    Route::post('/activate/verizon/post', 'SubAgent\Activate\VerizonController@post');

    Route::get('/activate/h2o', 'SubAgent\Activate\H2OController@show');
    Route::post('/activate/h2o', 'SubAgent\Activate\H2OController@show');
    Route::post('/activate/h2o/post', 'SubAgent\Activate\H2OController@post');

    Route::get('/activate/h2oe', 'SubAgent\Activate\H2OEmidaController@show');
    Route::post('/activate/h2e', 'SubAgent\Activate\H2OEmidaController@show');
    Route::post('/activate/h2oe/post', 'SubAgent\Activate\H2OEmidaController@post');
    Route::get('/activate/h2oe/sim/{type}', 'SubAgent\Activate\H2OEmidaController@sim');
    Route::get('/activate/h2oe/esn', 'SubAgent\Activate\H2OEmidaController@esn');
    Route::get('/activate/h2oe/commission', 'SubAgent\Activate\H2OEmidaController@commission');
    Route::get('/activate/h2oe/get_portin_form', 'SubAgent\Activate\H2OEmidaController@get_portin_form');
    Route::post('/activate/h2oe/post', 'SubAgent\Activate\H2OEmidaController@post');
    Route::get('/activate/h2oe/success/{id}', 'SubAgent\Activate\H2OEmidaController@success');

    Route::get('/activate/h2o-multi-line/step-1', 'SubAgent\Activate\H2OMultiLineController@step1');
    Route::post('/activate/h2o-multi-line/step-1/check-sim', 'SubAgent\Activate\H2OMultiLineController@checkSim');

    Route::get('/activate/h2o-multi-line/step-2', 'SubAgent\Activate\H2OMultiLineController@step2');
    Route::post('/activate/h2o-multi-line/step-2/process', 'SubAgent\Activate\H2OMultiLineController@process');
    Route::get('/activate/h2o-multi-line/step-3', 'SubAgent\Activate\H2OMultiLineController@step3');

    Route::get('/portin/h2o', 'SubAgent\PortIn\H2OController@show');
    Route::post('/portin/h2o', 'SubAgent\PortIn\H2OController@show');
    Route::post('/portin/h2o/post', 'SubAgent\PortIn\H2OController@post');

    Route::get('/activate/patriot', 'SubAgent\Activate\PatriotController@show');

    Route::get('/activate/rok', 'SubAgent\Activate\ROKController@show');
    Route::post('/activate/rok', 'SubAgent\Activate\ROKController@show');
    Route::post('/activate/rok/post', 'SubAgent\Activate\ROKController@post');

    Route::get('/activate/freeup', 'SubAgent\Activate\FreeUpController@show');
    Route::post('/activate/freeup', 'SubAgent\Activate\FreeUpController@show');
    Route::get('/activate/freeup/sim/{type}', 'SubAgent\Activate\FreeUpController@sim');
    Route::get('/activate/freeup/esn', 'SubAgent\Activate\FreeUpController@esn');
    Route::get('/activate/freeup/commission', 'SubAgent\Activate\FreeUpController@commission');
    Route::get('/activate/freeup/get_portin_form/{phone_type}', 'SubAgent\Activate\FreeUpController@get_portin_form');
    Route::post('/activate/freeup/post', 'SubAgent\Activate\FreeUpController@post');
    Route::get('/activate/freeup/success/{id}', 'SubAgent\Activate\FreeUpController@success');

    Route::get('/activate/att', 'SubAgent\Activate\ATTController@show');
    Route::post('/activate/att', 'SubAgent\Activate\ATTController@show');
    Route::get('/activate/att/sim', 'SubAgent\Activate\ATTController@sim');
    Route::get('/activate/att/esn', 'SubAgent\Activate\ATTController@esn');
    Route::get('/activate/att/commission', 'SubAgent\Activate\ATTController@commission');
    Route::get('/activate/att/get_portin_form', 'SubAgent\Activate\ATTController@get_portin_form');
    Route::post('/activate/att/post', 'SubAgent\Activate\ATTController@post');
    Route::get('/activate/att/success/{id}', 'SubAgent\Activate\ATTController@success');

    Route::get('/activate/attprvi', 'SubAgent\Activate\ATTPRVIController@show');
    Route::post('/activate/attprvi', 'SubAgent\Activate\ATTPRVIController@show');
    Route::get('/activate/attprvi/sim', 'SubAgent\Activate\ATTPRVIController@sim');
    Route::get('/activate/attprvi/esn', 'SubAgent\Activate\ATTPRVIController@esn');
    Route::get('/activate/attprvi/commission', 'SubAgent\Activate\ATTPRVIController@commission');
    Route::get('/activate/attprvi/get_portin_form', 'SubAgent\Activate\ATTPRVIController@get_portin_form');
    Route::post('/activate/attprvi/post', 'SubAgent\Activate\ATTPRVIController@post');
    Route::get('/activate/attprvi/success/{id}', 'SubAgent\Activate\ATTPRVIController@success');

    Route::get('/activate/attdataonly', 'SubAgent\Activate\ATTDataOnlyController@show');
    Route::post('/activate/attdataonly', 'SubAgent\Activate\ATTDataOnlyController@show');
    Route::get('/activate/attdataonly/sim', 'SubAgent\Activate\ATTDataOnlyController@sim');
    Route::get('/activate/attdataonly/esn', 'SubAgent\Activate\ATTDataOnlyController@esn');
    Route::get('/activate/attdataonly/commission', 'SubAgent\Activate\ATTDataOnlyController@commission');
    Route::get('/activate/attdataonly/get_portin_form', 'SubAgent\Activate\ATTDataOnlyController@get_portin_form');
    Route::post('/activate/attdataonly/post', 'SubAgent\Activate\ATTDataOnlyController@post');
    Route::get('/activate/attdataonly/success/{id}', 'SubAgent\Activate\ATTDataOnlyController@success');

    Route::get('/activate/gen', 'SubAgent\Activate\GENController@show');
    Route::post('/activate/gen', 'SubAgent\Activate\GENController@show');
    Route::get('/activate/gen/esn', 'SubAgent\Activate\GENController@esn');
    Route::get('/activate/gen/zip', 'SubAgent\Activate\GENController@zip');
    Route::get('/activate/gen/sim', 'SubAgent\Activate\GENController@sim');
    Route::get('/activate/gen/commission', 'SubAgent\Activate\GENController@commission');
    Route::get('/activate/gen/get_portin_form', 'SubAgent\Activate\GENController@get_portin_form');
    Route::post('/activate/gen/post', 'SubAgent\Activate\GENController@post');
    Route::get('/activate/gen/success/{id}', 'SubAgent\Activate\GENController@success');

    Route::get('/activate/gen_tmo', 'SubAgent\Activate\GENController@show_tmo');
    Route::post('/activate/gen_tmp', 'SubAgent\Activate\GENController@show_tmo');
    Route::get('/activate/gen/sim_gen_tmo', 'SubAgent\Activate\GENController@sim_gen_tmo');
    Route::get('/activate/gen/commission_tmo', 'SubAgent\Activate\GENController@commission_tmo');
    Route::post('/activate/gen/post_tmo', 'SubAgent\Activate\GENController@post_tmo');
    Route::get('/activate/gen/success_tmo/{id}', 'SubAgent\Activate\GENController@success_tmo');
    Route::get('/activate/gen/get_portin_form_tmo', 'SubAgent\Activate\GENController@get_portin_form_tmo');

    Route::get('/activate/liberty', 'SubAgent\Activate\LibertyController@show');
    Route::post('/activate/liberty', 'SubAgent\Activate\LibertyController@show');
    Route::get('/activate/liberty/byod', 'SubAgent\Activate\LibertyController@byod');
    Route::get('/activate/liberty/serviceActivation', 'SubAgent\Activate\LibertyController@serviceActivation');
    Route::get('/activate/liberty/refillByLot', 'SubAgent\Activate\LibertyController@refillByLot');
    Route::get('/activate/liberty/get_portin_form', 'SubAgent\Activate\LibertyController@get_portin_form');
    Route::get('/activate/liberty/getmdninfo', 'SubAgent\Activate\LibertyController@getMdnInfo');
    Route::post('/activate/liberty/post', 'SubAgent\Activate\LibertyController@post');
    Route::get('/activate/liberty/esn', 'SubAgent\Activate\LibertyController@esn');
    Route::get('/activate/liberty/sim', 'SubAgent\Activate\LibertyController@sim');
    Route::get('/activate/liberty/commission', 'SubAgent\Activate\LibertyController@commission');
    Route::get('/activate/liberty/success/{id}', 'SubAgent\Activate\LibertyController@success');

    Route::get('/activate/boom_blue', 'SubAgent\Activate\BoomController@show_blue');
    Route::post('/activate/boom_blue', 'SubAgent\Activate\BoomController@show_blue');
    Route::get('/activate/boom_red', 'SubAgent\Activate\BoomController@show_red');
    Route::post('/activate/boom_red', 'SubAgent\Activate\BoomController@show_red');
    Route::get('/activate/boom_purple', 'SubAgent\Activate\BoomController@show_purple');
    Route::post('/activate/boom_purple', 'SubAgent\Activate\BoomController@show_purple');

    Route::get('/activate/boom/test', 'SubAgent\Activate\BoomController@test');

    Route::get('/activate/boom/byod', 'SubAgent\Activate\BoomController@byod');
    Route::get('/activate/boom/serviceActivation', 'SubAgent\Activate\BoomController@serviceActivation');
    Route::get('/activate/boom/refillByLot', 'SubAgent\Activate\BoomController@refillByLot');
    Route::get('/activate/boom/get_portin_form', 'SubAgent\Activate\BoomController@get_portin_form');
    Route::get('/activate/boom/get_portin_form_blue', 'SubAgent\Activate\BoomController@get_portin_form_blue');
    Route::get('/activate/boom/get_portin_form_red', 'SubAgent\Activate\BoomController@get_portin_form_red');
    Route::get('/activate/boom/get_portin_form_purple', 'SubAgent\Activate\BoomController@get_portin_form_purple');
    Route::get('/activate/boom/validate_mdn', 'SubAgent\Activate\BoomController@validate_mdn');

    Route::post('/activate/boom/post', 'SubAgent\Activate\BoomController@post');
    Route::post('/activate/boom/post_blue', 'SubAgent\Activate\BoomController@post_blue');
    Route::post('/activate/boom/post_red', 'SubAgent\Activate\BoomController@post_red');
    Route::post('/activate/boom/post_purple', 'SubAgent\Activate\BoomController@post_purple');

    Route::get('/activate/boom/sim_blue', 'SubAgent\Activate\BoomController@sim_blue');
    Route::get('/activate/boom/esn_valid_red', 'SubAgent\Activate\BoomController@esn_valid_red');
    Route::get('/activate/boom/esn_valid_blue', 'SubAgent\Activate\BoomController@esn_valid_blue');
    Route::get('/activate/boom/sim_red', 'SubAgent\Activate\BoomController@sim_red');
    Route::get('/activate/boom/sim_purple', 'SubAgent\Activate\BoomController@sim_purple');
    Route::get('/activate/boom/commission_blue', 'SubAgent\Activate\BoomController@commission_blue');
    Route::get('/activate/boom/commission_red', 'SubAgent\Activate\BoomController@commission_red');
    Route::get('/activate/boom/commission_purple', 'SubAgent\Activate\BoomController@commission_purple');
    Route::get('/activate/boom/success/{id}', 'SubAgent\Activate\BoomController@success');

    Route::get('/activate/xfinity', 'SubAgent\Activate\XfinityController@show');
    Route::post('/activate/xfinity', 'SubAgent\Activate\XfinityController@show');

    Route::get('/activate/air_voice', 'SubAgent\Activate\AirVoiceController@show');
    Route::post('/activate/air_voice', 'SubAgent\Activate\AirVoiceController@show');

    Route::get('/activate/rokit', 'SubAgent\Activate\RokitController@show');
    Route::post('/activate/rokit', 'SubAgent\Activate\rokitController@show');

    Route::get('/activate/test', 'SubAgent\Activate\TestController@show');
    Route::post('/activate/test', 'SubAgent\Activate\TestController@show');
    Route::get('/activate/test/post', 'SubAgent\Activate\TestController@post');

    Route::get('/activate/lyca', 'SubAgent\Activate\LycaController@show');
    Route::post('/activate/lyca', 'SubAgent\Activate\LycaController@show');
    Route::get('/activate/lyca/sim', 'SubAgent\Activate\LycaController@sim');
    Route::get('/activate/lyca/esn', 'SubAgent\Activate\LycaController@esn');
    Route::get('/activate/lyca/commission', 'SubAgent\Activate\LycaController@commission');
    Route::get('/activate/lyca/get_portin_form', 'SubAgent\Activate\LycaController@get_portin_form');
    Route::post('/activate/lyca/post', 'SubAgent\Activate\LycaController@post');
    Route::get('/activate/lyca/success/{id}', 'SubAgent\Activate\LycaController@success');

    Route::any('/tools/rok', 'SubAgent\Tools\ROKToolsController@show');
    Route::any('/tools/rok/simswap', 'SubAgent\Tools\ROKToolsController@simswap');
    Route::any('/tools/rok/mdninfo', 'SubAgent\Tools\ROKToolsController@mdninfo');
    Route::any('/tools/rok/get_plans', 'SubAgent\Tools\ROKToolsController@get_plans');
    Route::any('/tools/rok/portin_status', 'SubAgent\Tools\ROKToolsController@portin_status');

    Route::any('/tools/att', 'SubAgent\Tools\ATTToolsController@show');
    Route::any('/tools/att/simswap', 'SubAgent\Tools\ATTToolsController@simswap');
    Route::any('/tools/att/changeplan', 'SubAgent\Tools\ATTToolsController@changeplan');
    Route::any('/tools/att/eprovision', 'SubAgent\Tools\ATTToolsController@eprovision');
    Route::any('/tools/att/eprovision/update', 'SubAgent\Tools\ATTToolsController@eprovision_update');

    Route::any('/tools/att-batch', 'SubAgent\Tools\ATTBatchController@show');
    Route::any('/tools/att-batch/add', 'SubAgent\Tools\ATTBatchController@add');
    Route::any('/tools/att-batch/delete', 'SubAgent\Tools\ATTBatchController@delete');

    Route::any('/tools/freeup', 'SubAgent\Tools\FreeUpToolsController@show');
    Route::any('/tools/freeup/eprovision', 'SubAgent\Tools\FreeUpToolsController@eprovision');
    Route::any('/tools/freeup/eprovision/update', 'SubAgent\Tools\FreeUpToolsController@eprovision_update');

    Route::any('/tools/gen', 'SubAgent\Tools\GenToolsController@show');
    Route::any('/tools/gen/puk', 'SubAgent\Tools\GenToolsController@puk');

    Route::any('/tools/boom', 'SubAgent\Tools\BoomToolsController@show');
    Route::any('/tools/boom/post', 'SubAgent\Tools\BoomToolsController@post');

    ROute::get('/rtr/domestic', 'SubAgent\RTR\DomesticController@show');
    ROute::post('/rtr/domestic', 'SubAgent\RTR\DomesticController@show');
    ROute::post('/rtr/domestic/process', 'SubAgent\RTR\DomesticController@process');

    ROute::get('/rtr/boss', 'SubAgent\RTR\BossController@show');
    ROute::post('/rtr/boss', 'SubAgent\RTR\BossController@show');
    ROute::post('/rtr/boss/process', 'SubAgent\RTR\BossController@process');

    ROute::get('/rtr/dpp', 'SubAgent\RTR\DppController@show');
    ROute::post('/rtr/dpp', 'SubAgent\RTR\DppController@show');
    ROute::post('/rtr/dpp/process', 'SubAgent\RTR\DppController@process');

    ROute::get('/rtr/gen', 'SubAgent\RTR\GENController@show');
    ROute::post('/rtr/gen', 'SubAgent\RTR\GENController@show');
    ROute::get('/rtr/gen/check_mdn', 'SubAgent\RTR\GENController@check_mdn');
    ROute::get('/rtr/gen/check_mdn_wallet', 'SubAgent\RTR\GENController@check_mdn_wallet');
    ROute::get('/rtr/gen/get_processing_fee', 'SubAgent\RTR\GENController@get_processing_fee');
    ROute::post('/rtr/gen/post', 'SubAgent\RTR\GENController@post');
    Route::get('/rtr/gen/success/{id}', 'SubAgent\RTR\GENController@success');

    ROute::get('/rtr/boom', 'SubAgent\RTR\BoomController@show');
    ROute::post('/rtr/boom', 'SubAgent\RTR\BoomController@show');
    ROute::get('/rtr/boom/check_mdn', 'SubAgent\RTR\BoomController@check_mdn');
    ROute::get('/rtr/boom/get_processing_fee', 'SubAgent\RTR\BoomController@get_processing_fee');
    ROute::post('/rtr/boom/post', 'SubAgent\RTR\BoomController@post');
    Route::get('/rtr/boom/success/{id}', 'SubAgent\RTR\BoomController@success');

    ROute::get('/rtr/boom_blue', 'SubAgent\RTR\BoomController@show_blue');
    ROute::post('/rtr/boom_blue', 'SubAgent\RTR\BoomController@show_blue');
    ROute::get('/rtr/boom_purple', 'SubAgent\RTR\BoomController@show_purple');
    ROute::post('/rtr/boom_purple', 'SubAgent\RTR\BoomController@show_purple');

    ROute::get('/rtr/gen_esn_swap', 'SubAgent\RTR\GENController@esn_swap');
    ROute::post('/rtr/gen_esn_swap', 'SubAgent\RTR\GENController@esn_swap');
    ROute::get('/rtr/gen_mdn_swap', 'SubAgent\RTR\GENController@mdn_swap');
    ROute::post('/rtr/gen_mdn_swap', 'SubAgent\RTR\GENController@mdn_swap');
    ROute::get('/rtr/gen/check_mdn_for_esn_swap', 'SubAgent\RTR\GENController@check_mdn_for_esn_swap');
    ROute::get('/rtr/gen/check_mdn_for_mdn_swap', 'SubAgent\RTR\GENController@check_mdn_for_mdn_swap');
    ROute::get('/rtr/gen/send_text_pin', 'SubAgent\RTR\GENController@send_text_pin');
    ROute::get('/rtr/gen/check_pin', 'SubAgent\RTR\GENController@check_pin');
    ROute::get('/rtr/gen/esn_swap_post', 'SubAgent\RTR\GENController@esn_swap_post');
    ROute::get('/rtr/gen/mdn_swap_post', 'SubAgent\RTR\GENController@mdn_swap_post');

    ROute::get('/rtr/gen_addon', 'SubAgent\RTR\GENController@addon');
    ROute::post('/rtr/gen_addon', 'SubAgent\RTR\GENController@addon');
    ROute::post('/rtr/gen_addon/post', 'SubAgent\RTR\GENController@addon_post');
    Route::get('/rtr/gen_addon/success/{id}', 'SubAgent\RTR\GENController@addon_success');

    ROute::get('/wallet/gen', 'SubAgent\RTR\GENController@wallet');
    ROute::post('/wallet/gen', 'SubAgent\RTR\GENController@wallet');
    ROute::post('/wallet/gen/post', 'SubAgent\RTR\GENController@wallet_post');
    Route::get('/wallet/gen/success/{id}', 'SubAgent\RTR\GENController@wallet_success');

    ROute::get('/pin/domestic', 'SubAgent\PIN\DomesticController@show');
    ROute::post('/pin/domestic', 'SubAgent\PIN\DomesticController@show');
    ROute::post('/pin/domestic/process', 'SubAgent\PIN\DomesticController@process');

    Route::get('/reports/transaction', 'SubAgent\Reports\TransactionController@show');
    Route::post('/reports/transaction', 'SubAgent\Reports\TransactionController@show');

    Route::get('/reports/transaction/{id}', 'SubAgent\Reports\TransactionController@detail');
    Route::post('/reports/transaction/{id}', 'SubAgent\Reports\TransactionController@update');

    Route::get('/reports/transaction-new', 'SubAgent\Reports\TransactionNewController@show');
    Route::post('/reports/transaction-new', 'SubAgent\Reports\TransactionNewController@show');

    Route::get('/reports/transaction-new/{id}', 'SubAgent\Reports\TransactionNewController@detail');
    Route::post('/reports/transaction-new/{id}', 'SubAgent\Reports\TransactionNewController@update');

    Route::get('/reports/promotion', 'SubAgent\Reports\PromotionController@show');
    Route::post('/reports/promotion', 'SubAgent\Reports\PromotionController@show');

    Route::get('/reports/ach-bounce', 'SubAgent\Reports\AchBounceController@show');
    Route::post('/reports/ach-bounce', 'SubAgent\Reports\AchBounceController@show');

    Route::get('/reports/virtual-rep', 'SubAgent\Reports\VirtualRepController@show');
    Route::post('/reports/virtual-rep', 'SubAgent\Reports\VirtualRepController@show');
    Route::post('/reports/virtual-rep/load-detail', 'SubAgent\Reports\VirtualRepController@loadDetail');


    Route::get('/reports/vr-request', 'SubAgent\Reports\VirtualRepController@showRequest');
    Route::post('/reports/vr-request', 'SubAgent\Reports\VirtualRepController@showRequest');
    Route::post('/reports/vr-request/load-detail', 'SubAgent\Reports\VirtualRepController@loadDetailRequest');
    Route::post('/reports/vr-request/cancel', 'SubAgent\Reports\VirtualRepController@cancelRequest');
    Route::post('/reports/vr-request/paypal/add', 'SubAgent\Reports\VirtualRepController@addPayPal');

    Route::get('/reports/rtr-q', 'SubAgent\Reports\RTRQueueController@show');
    Route::post('/reports/rtr-q', 'SubAgent\Reports\RTRQueueController@show');

    Route::get('/reports/payments', 'SubAgent\Reports\PaymentsController@show');
    Route::post('/reports/payments', 'SubAgent\Reports\PaymentsController@show');
    Route::post('/reports/payments/paypal/add', 'SubAgent\Reports\PaymentsController@addPayPal');
    Route::post('/reports/payments/paypal/pre-save', 'SubAgent\Reports\PaymentsController@addPayPalPreSave');

    Route::get('/reports/credit', 'SubAgent\Reports\CreditController@show');
    Route::post('/reports/credit', 'SubAgent\Reports\CreditController@show');

    Route::get('/reports/invoices', 'SubAgent\Reports\InvoicesController@show');
    Route::post('/reports/invoices', 'SubAgent\Reports\InvoicesController@show');
    Route::get('/reports/invoices/{id}', 'SubAgent\Reports\InvoicesController@detail');
    Route::post('/reports/invoices/{id}', 'SubAgent\Reports\InvoicesController@detail');

    Route::get('/reports/receipt/{id}', 'SubAgent\Reports\ReceiptController@show');

    Route::get('/reports/spiff', 'SubAgent\Reports\SpiffController@show');
    Route::post('/reports/spiff', 'SubAgent\Reports\SpiffController@show');

    Route::get('/reports/rebate', 'SubAgent\Reports\RebateController@show');
    Route::post('/reports/rebate', 'SubAgent\Reports\RebateController@show');

    Route::get('/reports/residual', 'SubAgent\Reports\ResidualController@show');
    Route::post('/reports/residual', 'SubAgent\Reports\ResidualController@show');

    Route::get('/reports/activity', 'SubAgent\Reports\ActivityController@show');
    Route::post('/reports/activity', 'SubAgent\Reports\ActivityController@show');

    Route::get('/reports/consignment', 'SubAgent\Reports\ConsignmentController@show');
    Route::post('/reports/consignment', 'SubAgent\Reports\ConsignmentController@show');

    Route::get('/reports/gen', 'SubAgent\Reports\GENController@show');
    Route::post('/reports/gen', 'SubAgent\Reports\GENController@show');

    Route::get('/reports/discount-setup', 'SubAgent\Reports\DiscountSetupController@show');
    Route::post('/reports/discount-setup', 'SubAgent\Reports\DiscountSetupController@show');

    Route::get('/reports/spiff-setup', 'SubAgent\Reports\SpiffSetupController@show');
    Route::post('/reports/spiff-setup', 'SubAgent\Reports\SpiffSetupController@show');

    Route::get('/phones', 'SubAgent\PhonesController@show');
    Route::get('/phones/accessory', 'SubAgent\Phones\AccessoryController@show');

    Route::get('/setting/my-password', 'SubAgent\Setting\MyPasswordController@show');
    Route::post('/setting/my-password', 'SubAgent\Setting\MyPasswordController@post');

    Route::get('/setting/my-account', 'SubAgent\Setting\MyAccountController@show');
    Route::post('/setting/my-account', 'SubAgent\Setting\MyAccountController@post');

    Route::get('/setting/users', 'SubAgent\Setting\UsersController@show');
    Route::post('/setting/users', 'SubAgent\Setting\UsersController@show');
    Route::get('/setting/user/{user_id}', 'SubAgent\Setting\UsersController@detail');
    Route::post('/setting/user/{user_id}', 'SubAgent\Setting\UsersController@updateUser');
    Route::get('/setting/new-user', 'SubAgent\Setting\UsersController@newUser');
    Route::post('/setting/new-user', 'SubAgent\Setting\UsersController@createUser');

    Route::get('/setting/documents', 'SubAgent\Setting\DocumentsController@show');
    Route::post('/setting/documents', 'SubAgent\Setting\DocumentsController@post');

    Route::get('/setting/att-documents', 'SubAgent\Setting\AttDocumentsController@show');
    Route::post('/setting/att-documents', 'SubAgent\Setting\AttDocumentsController@post');

    Route::get('/setting/h2o-documents', 'SubAgent\Setting\DocumentsController@show_h2o');
    Route::post('/setting/h2o-documents', 'SubAgent\Setting\AttDocumentsController@post');

    Route::get('/setting/store', 'SubAgent\Setting\StoreController@show');
    Route::post('/setting/store/update', 'SubAgent\Setting\StoreController@update');
    Route::post('/setting/store/remove', 'SubAgent\Setting\StoreController@remove');
    Route::post('/setting/store/add-ip', 'SubAgent\Setting\StoreController@add_ip');
    Route::post('/setting/store/remove-ip', 'SubAgent\Setting\StoreController@remove_ip');
    Route::post('/setting/store/update-ip', 'SubAgent\Setting\StoreController@update_ip');
    Route::post('/setting/store/update-tz', 'SubAgent\Setting\StoreController@update_tz');

    Route::get('/setting/user-hour', 'SubAgent\Setting\UserHourController@show');
    Route::post('/setting/user-hour/update', 'SubAgent\Setting\UserHourController@update');
    Route::post('/setting/user-hour/remove', 'SubAgent\Setting\UserHourController@remove');
    Route::post('/setting/user-hour/add-ip', 'SubAgent\Setting\UserHourController@add_ip');
    Route::post('/setting/user-hour/remove-ip', 'SubAgent\Setting\UserHourController@remove_ip');

    Route::get('/virtual-rep/shop', 'SubAgent\VirtualRepController@shop');
    Route::post('/virtual-rep/shop', 'SubAgent\VirtualRepController@shop');
    Route::get('/virtual-rep/add_to_cart', 'SubAgent\VirtualRepController@add_to_cart');
    Route::any('/virtual-rep/cart', 'SubAgent\VirtualRepController@cart');
    Route::any('/virtual-rep/cart/status', 'SubAgent\VirtualRepController@check_status');
    Route::any('/virtual-rep/cart/paid', 'SubAgent\VirtualRepController@cart_paid');
    Route::any('/virtual-rep/cart/cod', 'SubAgent\VirtualRepController@cart_cod');
    Route::any('/virtual-rep/cart/remove/{id}', 'SubAgent\VirtualRepController@cart_remove');
    Route::any('/virtual-rep/cart/shipping_method/{method}', 'SubAgent\VirtualRepController@shipping_method');
    Route::any('/virtual-rep/general_request', 'SubAgent\VirtualRepController@general_request');
    Route::any('/virtual-rep/general_request/save', 'SubAgent\VirtualRepController@general_request_save');
    Route::post('/virtual-rep/save', 'SubAgent\VirtualRepController@save');
    Route::post('/virtual-rep/log-request', 'SubAgent\VirtualRepController@logRequest');

    Route::get('/error', function() {

        if (empty(session('error_msg'))) {
            return redirect('/sub-agent');
        }

        return view('sub-agent.error', [
            'error_msg' => session('error_msg')
        ]);
    });

    Route::get('/att-error', function() {
        return view('sub-agent/att-error', [
            'error_msg' => session('error_msg')
        ]);
    });

    Route::get('/get_boost_pin', function(\Illuminate\Http\Request $request) {
        $res = \App\Lib\DollarPhone::get_boost_pin($request->mdn);

        return response()->json([
            'msg' => $res['error_msg']
        ]);
    });

    Route::get('/esig/{doc_type}', function(\Illuminate\Http\Request $request, $doc_type) {
        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            die("Session expired!");
        }
        //$ret = eSignature::get_url($account);
        $ret = echoSig::get_url(
            $doc_type . '.pdf',
            $doc_type,
            $account->name,
            $account->address1 . ' ' . $account->address2,
            $account->city . ', ' . $account->state . ' ' . $account->zip,
            $account->email,
            $doc_type
        );
        if (!empty($ret['msg'])) {
            die($ret["msg"]);
        }
        //var_dump($url);
        //echo "<script>";
        //echo "window.open('" . $ret['url'] . "', '_blank');";
        //echo "</script>";

        return redirect($ret['url']);
    });

});


Route::get('/file/view/{id}', function($id) {
    try {
        $file = AccountFile::find($id);
        $response = Response::make(base64_decode($file->data), 200);
        $response->header('Content-Type', 'application/octet-stream');
        $response->header('Content-Disposition', 'attachment; filename="' . $file->file_name . '"');

        return $response;
    } catch (\Exception $ex) {
        return response()->json([
            'msg' => $ex->getMessage()
        ]);
    }
});

Route::get('/file/att_view/{id}', function($id) {
    try {
        $file = AccountFileAtt::find($id);
        $response = Response::make(base64_decode($file->data), 200);
        $response->header('Content-Type', 'application/octet-stream');
        $response->header('Content-Disposition', 'attachment; filename="' . $file->file_name . '"');

        return $response;
    } catch (\Exception $ex) {
        return response()->json([
            'msg' => $ex->getMessage()
        ]);
    }
});

Route::get('/ckeditor/download/{id}', function($id) {
    try {
        $file = CKEditorFile::find($id);
        $response = Response::make(base64_decode($file->data), 200);
        $response->header('Content-Type', 'application/octet-stream');
        $response->header('Content-Disposition', 'attachment; filename="' . $file->file_name . '"');

        return $response;
    } catch (\Exception $ex) {
        return response()->json([
            'msg' => $ex->getMessage()
        ]);
    }
});

Route::get('dpt', function() {
    return view('dpt');
});

Route::get('adobe-sign-test', function() {
    $file_name = 'dealer_agreement.pdf';
    $doc_name = 'dealer-agreement';
    //$file_name = 'a.txt';

    //echo $file_path;

    //echo filesize($file_path);
//echoSig::base_urls();
    //$ret = echoSig::transientDocuments($file_name);
    //$ret2 = echoSig::get_url($file_name, $doc_name, 'Test Account', '800 Park Ave PH2F', 'Fort Lee, NJ 07024', 'jyk2000@gmail.com');
    $ret2 = echoSig::download_document("3AAABLblqZhCbXTNA9KpySgz7GiLds92eKdKz54NzoxCOv3jWmr2nRa3MnwN__UfKAe5pAb5CTyXSCPk5Y3VoAnBaq59WHOXe");
    dd($ret2);
});

Route::get('/esig', function() {
    $account = Account::find(Auth::user()->account_id);
    if (empty($account)) {
        die("Session expired!");
    }
    //$ret = eSignature::get_url($account);
    $ret = echoSig::get_url(
        'dealer_agreement.pdf',
        'dealer-agreement',
        $account->name,
        $account->address1 . ' ' . $account->address2,
        $account->city . ', ' . $account->state . ' ' . $account->zip,
        $account->email
    );
    if (!empty($ret['msg'])) {
        die($ret["msg"]);
    }
    //var_dump($url);
    //echo "<script>";
    //echo "window.open('" . $ret['url'] . "', '_blank');";
    //echo "</script>";

    return redirect($ret['url']);
});

Route::get('/esig/completed/{user_id}', function(\Illuminate\Http\Request $request, $user_id) {

    $doc_type = empty($request->doc_type) ? 'FILE_DEALER_AGREEMENT' : $request->doc_type;

    \App\Lib\Helper::log('### esig complete start ###', [
        'doc_type' => $doc_type
    ]);

    if ($request->eventType == 'ESIGNED') {
        //$ret = eSignature::download_doc($envelope_id);
        $ret = echoSig::download_document($request->widgetKey);
        if (empty($ret['msg'])) {

            $user = User::find($user_id);
            if (!empty($user)) {

                if ($doc_type == 'FILE_ATT_AGREEMENT'){
                    $file = AccountFileAtt::where('account_id', $user->account_id)->where('type', $doc_type)->first();
                    if(empty($file)) {
                        $file = new AccountFileAtt();
                    }
                }else{
                    $file = AccountFile::where('account_id', $user->account_id)->where('type', $doc_type)->first();
                    if(empty($file)) {
                        $file = new AccountFile;
                    }
                }

                $file->type = $doc_type;
                $file->account_id = $user->account_id;
                $file->file_name = $doc_type . '_signed.pdf';
                $file->data = base64_encode($ret['signed_pdf']);
                $file->signed = 'Y';
                $file->created_by = $user->user_id;
                $file->cdate = Carbon::now();
                $file->save();

                echo "<script>";
                echo "alert('Thank you for sign!');";
                echo "window.close();";
                echo "</script>";
                exit;
            }
        }

        echo "<script>";
        echo "alert('" . $ret["msg"] . "');";
        echo "window.close();";
        echo "</script>";
        exit;
    } else if ($request->eventType == 'SIGNATURE_REQUESTED') {
        $user = User::find($user_id);
        if (!empty($user)) {
            if ($doc_type == 'FILE_ATT_AGREEMENT'){
                $file = AccountFileAtt::where('account_id', $user->account_id)->where('type', $doc_type)->first();
                if (empty($file)) {
                    $file = new AccountFileAtt();
                }
            }else {
                $file = AccountFile::where('account_id', $user->account_id)->where('type', $doc_type)->first();
                if (empty($file)) {
                    $file = new AccountFile;
                }
            }

            $file->type = $doc_type;
            $file->account_id = $user->account_id;
            $file->file_name = $doc_type . '_requested.pdf';
            $file->signed = 'N';
            $file->created_by = $user->user_id;
            $file->cdate = Carbon::now();
            $file->save();
        }
    }

    echo "event: " . $request->event;
});

Route::get('/create-test-account', function() {

    /*$account = new Account;
    $account->name = 'Perfect Mobile';
    $account->path = '100000';
    $account->address1 = '2160 N Central Rd';
    $account->address2 = '203F';
    $account->city = 'Fort Lee';
    $account->state = 'NJ';
    $account->zip = '07024';
    $account->office_number = '6788625954';
    $account->contact = 'Yong K. Jun';
    $account->email = 'it@perfectmobileinc.com';
    $account->status = 'A';
    $account->created_by = 'yongj';
    $account->cdate = Carbon::now();
    $account->save();*/

//    //$user = new User;
//    $user = User::find('yongj');
//    //$user->user_id = 'yongj';
//    $user->name = 'Yong K. Jun';
//    $user->email = 'it@perfectmobileinc.com';
//    $user->account_id = 100000;
//    $user->password = bcrypt('Jyk5183!!');
//    $user->status = 'A';
//    $user->created_at = Carbon::now();
//    $user->save();
});

Route::get('/test', function() {
//    $date = Carbon::parse('2017-09-12 23:59:59');
//    dd($date);
//    $account_id = 100141;
//    $account = Account::find($account_id);
//    $ending_balance = -323.64;
//    echo 'no_ach:' . $account->no_ach . '<br/>';
//    echo 'min_ach_amt:' . $account->min_ach_amt . '<br/>';
//    if ( isset($account) &&
//        ($account->no_ach != 'Y') &&
//        ( ($account->min_ach_amt == 0) || ($account->min_ach_amt >  abs($ending_balance) ) )  ) {
//        echo 'If  logic';
//    }else {
//        echo 'Else  logic';
//    }

});

Route::get('/api/test', function() {
    //$ret = App\Lib\h2o::activateGSMafcode('1000006', 'W40', '1476017741', '201', 'Fort Lee');
    //$ret = App\Lib\h2o_rtr::recharge('WH2OU', '5512425274', 30, 1000009);
    //$ret = App\Lib\h2o::getAccountDetail(time(), '5512024068');
    //$ret = App\Lib\h2o::getMDNPortability(time(), 'W40', '8453277909');
    /*$ret = App\Lib\h2o::createMDNPort(
        time(), 'W40', '8453277909', '1398',
        '800 Park Ave PH2F', 'Fort Lee', 'NJ', '07024', 'Yong K Jun',
        'jyk2000@gmail.com', '6788625954', '18550', '4119',
        '014087001391391', '89014102278444322022', Request::ip(), '8453277909', 'Black Wireless', 'N'
    );*/

    /*
    $ref_id = 'UX9WB5Z6XNXELLU2OAJ9HWYK8';
    $ret = App\Lib\reup::get_activation_status($ref_id);
*/

    $ach = ACHPosting::find(100001);
    $ret = Mail::to('jun@jjonbp.com')->send(new ACHBounced($ach));
    echo "<pre>";

    echo "APP_ENV: " . getenv('APP_ENV') . "<br/>";
    echo 'hello<br/>' ;

    //$ret = \App\Lib\reup::get_carriers();
    //$ret = \App\Lib\reup::get_plans(1);

    $sim = '89014103279155826309';
    $esn = '';
    $carrier_id = '53';
    $plan_id = '604';
    $phone_type = '4g';
    $first_name = 'Yong';
    $last_name = 'Jun';
    $address1 = '65 Fairview St';
    $address2 = '1A';
    $city = 'Palisades Park';
    $state = 'NJ';
    $zip = '07650';
    $npa = '201';
    $email = 'jyk2000@gmail.com';

    /*$ret = \App\Lib\reup::activation(
        $sim, $esn, $carrier_id, $plan_id, $phone_type,
        $first_name, $last_name, $address1, $address2,
        $city, $state, $zip, $npa, $email
    );
    */
    //$reference_id = 'YHJ3EUXGZ7J98VXUNPMTMT5X5';
    //$ret = \App\Lib\reup::get_activation_status($reference_id);
    //$ret = \App\Lib\DollarPhone::pin(time(), '30084223', 29.95, 0);

    /*$mdn = '6087096512';
    $product_id = 'WROKS';
    $ret = \App\Lib\reup::get_mdn_info($product_id, $mdn);*/

    var_dump($ret);
    //echo "API_URL:" . App\Lib\lyca::$api_url . "<br/>";
    //$ret = App\Lib\epay::get_pin(time(), '111', 25);
    //$ret = App\Lib\epay::rtr(time(), '0843788031329', '1112223333', 20, 0);
    //$ret = App\Lib\emida::rtr(time(), '8947019', '8624523967', 19, 1);
    //$ret = App\Lib\emida::login();

    //$ret = App\Lib\DollarPhone::rtr(time(),'30027940', '525553760009', 5);
    //var_dump($ret);

    //$day_of_week = date('N', strtotime('Monday'));
    //echo ($day_of_week);

    //$ret = simplexml_load_string($ret->asXML(), null, LIBXML_NOCDATA);
    //print_r($ret->asXML());

    //$ret = new SimpleXMLElement($ret->asXML(), LIBXML_NOCDATA);
    //print_r($ret);

    //$res = $body[0]->xpath('//GetTransTypeListResponse');
    //print_r($res);
    //dd($ret->GetTransTypeListResponse->__toString());
    //$ret = App\Lib\lyca::activateUsimPortinBundle(time(), '8919601000161399981', '07024', '1059', 59, 'jyk2000@gmail.com', '5512024589', 'aaaa', '1234');
    //$xml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:header="http://www.plintron.com/header/"><soapenv:Header><header:ERROR_CODE>0</header:ERROR_CODE><header:ERROR_DESC>Success</header:ERROR_DESC></soapenv:Header><soapenv:Body><ACTIVATE_USIM_PORTIN_BUNDLE_RESPONSE><ALLOCATED_MSISDN>12246596137</ALLOCATED_MSISDN><PORTIN_REFERENCE_NUMBER/></ACTIVATE_USIM_PORTIN_BUNDLE_RESPONSE></soapenv:Body></soapenv:Envelope>';
    //$ret = simplexml_load_string($xml);

    //$ret = App\Lib\lyca::getPortinDetail(time(), '5512024589');
    //$ret = App\Lib\lyca::modifyPortin(time(),'MNPPI0000665288', '8919601000161399981', '07024', '5512024589', 'aaaa', '1234');
    //$ret = App\Lib\lyca::cancelPortIn(time(), 'MNPPI0000665288', 5512024589);
    //var_dump($ret);

    //echo substr('14433193242', 1);

    //var_dump($ret->xpath('//soapenv:Body')[0]->ACTIVATE_USIM_PORTIN_BUNDLE_RESPONSE->ALLOCATED_MSISDN);
    //echo $ret->xpath('//soapenv:Body')[0]->ACTIVATE_USIM_PORTIN_BUNDLE_RESPONSE->ALLOCATED_MSISDN->__toString();
    //$body = $ret->children('soapenv', true)->Body[0];
    //$res = $body->ACTIVATE_USIM_PORTIN_BUNDLE_RESPONSE;

    //var_dump($body->asXML());
    //$min = $body->ALLOCATED_MSISDN->__toString();
    //$ref_no = $body->PORTIN_REFERENCE_NUMBER->__toString();
    //\App\Lib\lyca::test(time());

    //echo 'MIN: ' . $min . '<br/>';
    //echo 'REF_NO: ' . $ref_no . '<br/>';
});

Route::get('/mail-test', function() {
    $ret = App\Lib\Helper::send_mail('jun@jjonbp.com', 'test', 'test<br/>body');
    echo $ret;
});

Route::get('500', function()
{
    abort(500);
});

Route::get('/phpinfo', function() {

    echo phpinfo();
});

Route::get('/apply_dealer_approval/{id}', function($id) {

    $account = Account::find($id);
    if (empty($account)) {
        die("Wrong URL Access!");
    }else {
        $status = $account->status;
        $note = $account->notes;

        if ($status == 'B' && (strpos($note, 'From Become a dealer') !== false)) {
            $account->status = 'A';
            $account->mdate = Carbon::now();
            $account->save();

            App\Lib\Helper::send_mail('ops@softpayplus.com ', '[Notification] Sub-Agent was Clicked a activation link', $account->id.'<br/>Sub-Agent was Clicked a activation link');

            return view('errors.clicked');

        } else {
            echo "Wrong URL Access";
        }
    }
});

Route::get('/check_list', function()
{
    return view('emails.users.check-list');
});

Route::get('/how-to-use-portal', function()
{
    return view('emails.users.how-to-use-portal');
});

//Route::get('/tresp', function () {
//    sleep(120);
//
//    echo '<br><br>replied in /tresp route<br><br>';
//});
//Route::get('/treq', function () {
//
//    echo 'starting request ...<br/>';
//    $ret = emida::ttest();
//    echo 'ending request ';
//});