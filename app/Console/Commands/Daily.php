<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 1/2/19
 * Time: 9:01 PM
 */

namespace App\Console\Commands;

use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Model\Account;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class Daily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily process ... ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $today = \Carbon\Carbon::yesterday()->format('Y-m-d');
            $retailers = DB::select("
                select *
                  from accounts
                 where id in (
                select account_id
                  from `transaction`
                 where cast(cdate as date) = :today
                 group by account_id
                 )
                   and pay_method = 'C'
            ", [
                'today' => $today
            ]);

            $r_accounts = [];
            $d_accounts = [];
            $m_accounts = [];
            $d_ids = [];
            $m_ids = [];
            foreach ($retailers as $r) {
                if ($r->pay_method == 'C') {
                    $p20 = $r->credit_limit * 0.2;
                    $r->balance = PaymentProcessor::get_limit($r->id);

                    if ($r->balance < $p20) {
                        $r_accounts[] = $r;
                    }
                }

                if ($r->parent_id != $r->master_id) {
                    if (empty($d_ids) || !in_array($r->parent_id, $d_ids)) {
                        $d_ids[] = $r->parent_id;

                        $dist_acct = Account::find($r->parent_id);
                        $p20 = $dist_acct->credit_limit * 0.2;
                        $dist_acct->balance = PaymentProcessor::get_dist_limit($dist_acct->id);

                        if ($dist_acct->balance < $p20) {
                            $d_accounts[] = $dist_acct;
                        }
                    }
                }

                if (empty($m_ids) || !in_array($r->master_id, $m_ids)) {
                    $m_ids[] = $r->master_id;

                    $m_acct = Account::find($r->master_id);
                    $p20 = $m_acct->credit_limit * 0.2;
                    $m_acct->balance = PaymentProcessor::get_master_limit($m_acct->id);

                    if ($m_acct->balance < $p20) {
                        $m_accounts[] = $m_acct;
                    }
                }
            }

            $email_msg = '';
            if (!empty($r_accounts) && count($r_accounts) > 0) {
                $email_msg .= '[Retailers]';
                foreach ($r_accounts as $r) {
                    $email_msg .= '<br> - [' . $r->id . ', ' . $r->name . '] Balance:' . $r->balance . ', Credit Limit:' . $r->credit_limit . ', Used Gross:' . ($r->credit_limit - $r->balance);
                }
            }

            if (!empty($d_accounts) && count($d_accounts) > 0) {
                if (!empty($email_msg)) {
                    $email_msg .= '<br>';
                }
                $email_msg .= '[Distributors]';
                foreach ($d_accounts as $d) {
                    $email_msg .= '<br> - [' . $d->id . ', ' . $d->name . '] Balance:' . $d->balance . ', Credit Limit:' . $d->credit_limit . ', Used Gross:' . ($d->credit_limit - $d->balance);
                }
            }

            if (!empty($m_accounts) && count($m_accounts) > 0) {
                if (!empty($email_msg)) {
                    $email_msg .= '<br>';
                }
                $email_msg .= '[Masters]';
                foreach ($m_accounts as $m) {
                    $email_msg .= '<br> - [' . $m->id . ', ' . $m->name . '] Balance:' . $m->balance . ', Credit Limit:' . $m->credit_limit . ', Used Gross:' . ($m->credit_limit - $m->balance);
                }
            }

            if (!empty($email_msg)) {
                helper::send_mail(
                  'it@perfectmobileinc.com',
                  '[PM][' . getenv('APP_ENV') . '] Credit Limit/Used Gross Amount',
                  $email_msg
                );
            }

        } catch (\Exception $ex) {
            Helper::log('#### EXCEPTION ####', $ex->getTraceAsString());
            $msg = ' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . '] Activation limit Check Process Failure', $msg);
        }


        //Generate summary data for Dashboard.
        try {
            DB::delete("DELETE from dashboard");

            $tx = DB::select("
INSERT INTO dashboard(account_id, carrier, cdate, act_qty, portin_qty, rtr_qty, pin_qty)
SELECT   a.id, d.carrier, date(c.cdate),
                sum(if(c.action = 'Activation', 1, 0)) as act_qty,
                sum(if(c.action = 'Port-In', 1, 0)) as port_qty,
                sum(if(c.action = 'RTR', 1, 0)) as rtr_qty,
                sum(if(c.action = 'PIN', 1, 0)) as pin_qty
FROM accounts a inner join accounts b on b.path like concat('%', a.id,'%') and b.type ='S'
                inner join transaction c on b.id = c.account_id 
                                           and c.cdate >= curdate() - interval 8 day and c.cdate < curdate()
                                           and c.type ='S'
                                           and c.status != 'F'
                                           and c.action in ('Activation', 'Port-In')
                                           and c.void_date is null 
                inner join product d on c.product_id = d.id
WHERE a.type !='S'
AND a.status = 'A'
GROUP BY 1,2,3
UNION all
SELECT   a.id, '' , date(c.cdate),
                sum(if(c.action = 'Activation', 1, 0)) as act_qty,
                sum(if(c.action = 'Port-In', 1, 0)) as port_qty,
                sum(if(c.action = 'RTR', 1, 0)) as rtr_qty,
                sum(if(c.action = 'PIN', 1, 0)) as pin_qty
FROM accounts a inner join accounts b on b.path like concat('%', a.id,'%') and b.type ='S'
                inner join transaction c on b.id = c.account_id 
                                         and c.cdate >= curdate() - interval 8 day and c.cdate < curdate()
                                          and c.type ='S'
                                         and c.status = 'C'
                                         and c.action in ('RTR', 'PIN')
                                         and c.void_date is null 
                inner join product d on c.product_id = d.id
WHERE a.type !='S'
AND a.status = 'A'
GROUP BY 1,2,3
            ", [
            ]);


            DB::delete("DELETE from pm_demo.dashboard");

            $tx = DB::select("
INSERT INTO pm_demo.dashboard(account_id,  carrier, cdate, act_qty, portin_qty, rtr_qty, pin_qty)
SELECT   a.id, d.carrier, date(c.cdate),
                sum(if(c.action = 'Activation', 1, 0)) as act_qty,
                sum(if(c.action = 'Port-In', 1, 0)) as port_qty,
                sum(if(c.action = 'RTR', 1, 0)) as rtr_qty,
                sum(if(c.action = 'PIN', 1, 0)) as pin_qty
FROM pm_demo.accounts a inner join pm_demo.accounts b on b.path like concat('%', a.id,'%') and b.type ='S'
                inner join pm_demo.transaction c on b.id = c.account_id 
                                           and c.cdate >= curdate() - interval 8 day and c.cdate < curdate()
                                           and c.type ='S'
                                           and c.status != 'F'
                                           and c.action in ('Activation', 'Port-In')
                                           and c.void_date is null 
                inner join pm_demo.product d on c.product_id = d.id
WHERE a.type !='S'
AND a.status = 'A'
GROUP BY 1,2,3
UNION all
SELECT   a.id, '' , date(c.cdate),
                sum(if(c.action = 'Activation', 1, 0)) as act_qty,
                sum(if(c.action = 'Port-In', 1, 0)) as port_qty,
                sum(if(c.action = 'RTR', 1, 0)) as rtr_qty,
                sum(if(c.action = 'PIN', 1, 0)) as pin_qty
FROM pm_demo.accounts a inner join pm_demo.accounts b on b.path like concat('%', a.id,'%') and b.type ='S'
                inner join pm_demo.transaction c on b.id = c.account_id 
                                         and c.cdate >= curdate() - interval 8 day and c.cdate < curdate()
                                          and c.type ='S'
                                         and c.status = 'C'
                                         and c.action in ('RTR', 'PIN')
                                         and c.void_date is null 
                inner join pm_demo.product d on c.product_id = d.id
WHERE a.type !='S'
AND a.status = 'A'
GROUP BY 1,2,3
            ", [
            ]);


            helper::send_mail(
                    'it@perfectmobileinc.com',
                    '[PM][' . getenv('APP_ENV') . '] Generated Summary data of Dashboard',
                    'Completed'
            );



            //Clean up vr_product_price.
            $tx=DB::select("
insert into vr_request_deleted(vr_id, cdate)
select distinct a.id, current_timestamp
from vr_request a inner join vr_request_product b on a.id = b.vr_id
                  inner join vr_product_price c on a.account_id = c.account_id and c.expired_date < curdate() and b.prod_id = c.vr_prod_id
where a.status ='CT'
   ", []);

            DB::delete("DELETE from vr_request_product where vr_id in ( select vr_id from vr_request_deleted where cdate >= curdate() ) ");
            DB::delete("DELETE from vr_request where id in ( select vr_id from vr_request_deleted where cdate >= curdate() ) and status ='CT' ");
            DB::delete("DELETE from vr_product_price where expired_date < curdate() ");


            $tx=DB::select("
insert into pm_demo.vr_request_deleted(vr_id, cdate)
select distinct a.id, current_timestamp
from pm_demo.vr_request a inner join pm_demo.vr_request_product b on a.id = b.vr_id
                  inner join pm_demo.vr_product_price c on a.account_id = c.account_id and c.expired_date < curdate() and b.prod_id = c.vr_prod_id
where a.status ='CT'
   ", []);

            DB::delete("DELETE from pm_demo.vr_request_product where vr_id in ( select vr_id from pm_demo.vr_request_deleted where cdate >= curdate() ) ");
            DB::delete("DELETE from pm_demo.vr_request where id in ( select vr_id from pm_demo.vr_request_deleted where cdate >= curdate() ) and status ='CT' ");
            DB::delete("DELETE from pm_demo.vr_product_price where expired_date < curdate() ");


            helper::send_mail(
                'it@perfectmobileinc.com',
                '[PM][' . getenv('APP_ENV') . '] Clean up vr_product_price/vr_request/vr_request_product in CT.',
                'Completed'
            );

        } catch (\Exception $ex) {
            Helper::log('#### EXCEPTION ####', $ex->getTraceAsString());
            $msg = ' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . '] Generating Summary data of Dashboard Failure', $msg);
        }

    }
}
