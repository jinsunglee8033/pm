<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Account;
use App\Model\Denom;
use App\Model\State;
use App\Model\Transaction;
use Validator;
use Carbon\Carbon;
use Session;
use Auth;
use DB;


/**
 * Created by PhpStorm.
 * User: Jin
 * Date: 4/12/20
 * Time: 12:24 AM
 */
class Dashboard2Controller extends Controller
{

    public function show() {

        $login_account = Account::find(Auth::user()->account_id);

        $carriers =[];
        if($login_account->act_h2o == 'Y'){
            $carriers[]     = 'H2O';
            $h2o_labels     = [];
            $h2o_data1      = [];
            $h2o_data2      = [];
        };
        if($login_account->act_lyca == 'Y'){
            $carriers[]     = 'Lyca';
            $lyca_labels    = [];
            $lyca_data1     = [];
            $lyca_data2     = [];
        };
        if($login_account->act_att == 'Y'){
            $carriers[]     = 'AT&T';
            $att_labels     = [];
            $att_data1      = [];
            $att_data2      = [];
        };
        if($login_account->act_freeup == 'Y'){
            $carriers[]     = 'FreeUP';
            $freeup_labels  = [];
            $freeup_data1   = [];
            $freeup_data2   = [];
        };
        if($login_account->act_gen == 'Y'){
            $carriers[]     = 'GEN Mobile';
            $gen_labels     = [];
            $gen_data1      = [];
            $gen_data2      = [];
        };
        if($login_account->act_liberty == 'Y'){
            $carriers[]     = 'Liberty Mobile';
            $liberty_labels = [];
            $liberty_data1  = [];
            $liberty_data2  = [];
        };
        if($login_account->act_boom == 'Y'){
            $carriers[]     = 'Boom Mobile';
            $boom_labels    = [];
            $boom_data1     = [];
            $boom_data2     = [];
        };

        $all_data = DB::select("
            select DATE_FORMAT(a.cdate, '%m-%d') as cdate,'H2O' as carrier, ifnull(b.act_qty, 0) as act_qty, ifnull(b.portin_qty, 0) as port_qty
            from std_date a left join dashboard b on account_id = :acct_id1 and carrier = 'H2O' and a.cdate = b.cdate
            where a.cdate >= curdate() - interval 8 DAY
            and a.cdate < curdate()
            UNION ALL
            select DATE_FORMAT(a.cdate, '%m-%d') as cdate,'Lyca' as carrier, ifnull(b.act_qty, 0) as act_qty, ifnull(b.portin_qty, 0) as port_qty
            from std_date a left join dashboard b on account_id = :acct_id2 and carrier = 'Lyca' and a.cdate = b.cdate
            where a.cdate >= curdate() - interval 8 DAY
            and a.cdate < curdate()
            UNION ALL
            select DATE_FORMAT(a.cdate, '%m-%d') as cdate,'AT&T' as carrier, ifnull(b.act_qty, 0) as act_qty, ifnull(b.portin_qty, 0) as port_qty
            from std_date a left join dashboard b on account_id = :acct_id3 and carrier = 'AT&T' and a.cdate = b.cdate
            where a.cdate >= curdate() - interval 8 DAY
            and a.cdate < curdate()
            UNION ALL
            select DATE_FORMAT(a.cdate, '%m-%d') as cdate,'FreeUP' as carrier, ifnull(b.act_qty, 0) as act_qty, ifnull(b.portin_qty, 0) as port_qty
            from std_date a left join dashboard b on account_id = :acct_id4 and carrier = 'FreeUP' and a.cdate = b.cdate
            where a.cdate >= curdate() - interval 8 DAY
            and a.cdate < curdate()
            UNION ALL
            select DATE_FORMAT(a.cdate, '%m-%d') as cdate,'GEN Mobile' as carrier, ifnull(b.act_qty, 0) as act_qty, ifnull(b.portin_qty, 0) as port_qty
            from std_date a left join dashboard b on account_id = :acct_id5 and carrier = 'GEN Mobile' and a.cdate = b.cdate
            where a.cdate >= curdate() - interval 8 DAY
            and a.cdate < curdate()
            UNION ALL
            select DATE_FORMAT(a.cdate, '%m-%d') as cdate,'Liberty Mobile' as carrier, ifnull(b.act_qty, 0) as act_qty, ifnull(b.portin_qty, 0) as port_qty
            from std_date a left join dashboard b on account_id = :acct_id6 and carrier = 'Liberty Mobile' and a.cdate = b.cdate
            where a.cdate >= curdate() - interval 8 DAY
            and a.cdate < curdate()
            UNION ALL
            select DATE_FORMAT(a.cdate, '%m-%d') as cdate,'Boom Mobile' as carrier, ifnull(b.act_qty, 0) as act_qty, ifnull(b.portin_qty, 0) as port_qty
            from std_date a left join dashboard b on account_id = :acct_id7 and carrier = 'Boom Mobile' and a.cdate = b.cdate
            where a.cdate >= curdate() - interval 8 DAY
            and a.cdate < curdate()
        ", [
            'acct_id1' => $login_account->id,
            'acct_id2' => $login_account->id,
            'acct_id3' => $login_account->id,
            'acct_id4' => $login_account->id,
            'acct_id5' => $login_account->id,
            'acct_id6' => $login_account->id,
            'acct_id7' => $login_account->id,
        ]);

        if (is_array($all_data)){

            $r = 0;
            $g = 0;
            $b = 255;
            $act_color = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', 0.2)';
            $act_border_color = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', 1)';

            $r = 60;
            $g = 150;
            $b = 106;
            $port_color = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', 0.2)';
            $port_border_color = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', 1)';

            $background_color_act = $act_color;
            $border_color_act = $act_border_color;
            $background_color_port = $port_color;
            $border_color_port = $port_border_color;

            foreach ($all_data as $a) {

                if($a->carrier == 'H2O'){
                    $h2o_labels[] = $a->cdate;
                    $h2o_data1[] = $a->act_qty;
                    $h2o_data2[] = $a->port_qty;
                }
                if($a->carrier == 'Lyca'){
                    $lyca_labels[] = $a->cdate;
                    $lyca_data1[] = $a->act_qty;
                    $lyca_data2[] = $a->port_qty;
                }
                if($a->carrier == 'AT&T'){
                    $att_labels[] = $a->cdate;
                    $att_data1[] = $a->act_qty;
                    $att_data2[] = $a->port_qty;
                }
                if($a->carrier == 'FreeUP'){
                    $freeup_labels[] = $a->cdate;
                    $freeup_data1[] = $a->act_qty;
                    $freeup_data2[] = $a->port_qty;
                }
                if($a->carrier == 'GEN Mobile'){
                    $gen_labels[] = $a->cdate;
                    $gen_data1[] = $a->act_qty;
                    $gen_data2[] = $a->port_qty;
                }
                if($a->carrier == 'Liberty Mobile'){
                    $liberty_labels[] = $a->cdate;
                    $liberty_data1[] = $a->act_qty;
                    $liberty_data2[] = $a->port_qty;
                }
                if($a->carrier == 'Boom Mobile'){
                    $boom_labels[] = $a->cdate;
                    $boom_data1[] = $a->act_qty;
                    $boom_data2[] = $a->port_qty;
                }

            }

            if($login_account->act_h2o == 'Y') {
                $h2o_dataset = new \stdClass();

                $ds1 = new \stdClass();
                $ds1->label = '# of Activation';
                $ds1->data = $h2o_data1;
                $ds1->backgroundColor = $background_color_act;
                $ds1->borderColor = $border_color_act;
                $ds1->borderWidth = 1;

                $ds2 = new \stdClass();
                $ds2->label = '# of Port-In';
                $ds2->data = $h2o_data2;
                $ds2->backgroundColor = $background_color_port;
                $ds2->borderColor = $border_color_port;
                $ds2->borderWidth = 1;

                $h2o_dataset->labels = $h2o_labels;
                $h2o_dataset->datasets = [$ds1, $ds2];
            }

            if($login_account->act_lyca == 'Y') {
                $lyca_dataset = new \stdClass();

                $ds1 = new \stdClass();
                $ds1->label = '# of Activation';
                $ds1->data = $lyca_data1;
                $ds1->backgroundColor = $background_color_act;
                $ds1->borderColor = $border_color_act;
                $ds1->borderWidth = 1;

                $ds2 = new \stdClass();
                $ds2->label = '# of Port-In';
                $ds2->data = $lyca_data2;
                $ds2->backgroundColor = $background_color_port;
                $ds2->borderColor = $border_color_port;
                $ds2->borderWidth = 1;

                $lyca_dataset->labels = $lyca_labels;
                $lyca_dataset->datasets = [$ds1, $ds2];
            }

            if($login_account->act_att == 'Y') {
                $att_dataset = new \stdClass();

                $ds1 = new \stdClass();
                $ds1->label = '# of Activation';
                $ds1->data = $att_data1;
                $ds1->backgroundColor = $background_color_act;
                $ds1->borderColor = $border_color_act;
                $ds1->borderWidth = 1;

                $ds2 = new \stdClass();
                $ds2->label = '# of Port-In';
                $ds2->data = $att_data2;
                $ds2->backgroundColor = $background_color_port;
                $ds2->borderColor = $border_color_port;
                $ds2->borderWidth = 1;

                $att_dataset->labels = $att_labels;
                $att_dataset->datasets = [$ds1, $ds2];
            }

            if($login_account->act_freeup == 'Y') {
                $freeup_dataset = new \stdClass();

                $ds1 = new \stdClass();
                $ds1->label = '# of Activation';
                $ds1->data = $freeup_data1;
                $ds1->backgroundColor = $background_color_act;
                $ds1->borderColor = $border_color_act;
                $ds1->borderWidth = 1;

                $ds2 = new \stdClass();
                $ds2->label = '# of Port-In';
                $ds2->data = $freeup_data2;
                $ds2->backgroundColor = $background_color_port;
                $ds2->borderColor = $border_color_port;
                $ds2->borderWidth = 1;

                $freeup_dataset->labels = $freeup_labels;
                $freeup_dataset->datasets = [$ds1, $ds2];
            }

            if($login_account->act_gen == 'Y') {

                $gen_dataset = new \stdClass();

                $ds1 = new \stdClass();
                $ds1->label = '# of Activation';
                $ds1->data = $gen_data1;
                $ds1->backgroundColor = $background_color_act;
                $ds1->borderColor = $border_color_act;
                $ds1->borderWidth = 1;

                $ds2 = new \stdClass();
                $ds2->label = '# of Port-In';
                $ds2->data = $gen_data2;
                $ds2->backgroundColor = $background_color_port;
                $ds2->borderColor = $border_color_port;
                $ds2->borderWidth = 1;

                $gen_dataset->labels = $gen_labels;
                $gen_dataset->datasets = [$ds1, $ds2];
            }

            if($login_account->act_liberty == 'Y') {
                $liberty_dataset = new \stdClass();

                $ds1 = new \stdClass();
                $ds1->label = '# of Activation';
                $ds1->data = $liberty_data1;
                $ds1->backgroundColor = $background_color_act;
                $ds1->borderColor = $border_color_act;
                $ds1->borderWidth = 1;

                $ds2 = new \stdClass();
                $ds2->label = '# of Port-In';
                $ds2->data = $liberty_data2;
                $ds2->backgroundColor = $background_color_port;
                $ds2->borderColor = $border_color_port;
                $ds2->borderWidth = 1;

                $liberty_dataset->labels = $liberty_labels;
                $liberty_dataset->datasets = [$ds1, $ds2];
            }

            if($login_account->act_boom == 'Y') {
                $boom_dataset = new \stdClass();

                $ds1 = new \stdClass();
                $ds1->label = '# of Activation';
                $ds1->data = $boom_data1;
                $ds1->backgroundColor = $background_color_act;
                $ds1->borderColor = $border_color_act;
                $ds1->borderWidth = 1;

                $ds2 = new \stdClass();
                $ds2->label = '# of Port-In';
                $ds2->data = $boom_data2;
                $ds2->backgroundColor = $background_color_port;
                $ds2->borderColor = $border_color_port;
                $ds2->borderWidth = 1;

                $boom_dataset->labels = $boom_labels;
                $boom_dataset->datasets = [$ds1, $ds2];
            }
        }

        ### RTR / PIN ###
        $rtr_pin_trans = DB::select("
            select a.cdate,'RTR/PIN', ifnull(b.rtr_qty,0) as rtr_qty, ifnull(b.pin_qty,0) as pin_qty
            from std_date a left join dashboard b on account_id = :acct_id and IfNull(carrier,'') = '' and a.cdate = b.cdate
            where a.cdate >= curdate() - interval 8 DAY
            and a.cdate < curdate()
            order by 2,1
        ", [
            'acct_id' => $login_account->id
        ]);

        $rtr_pin_dataset = new \stdClass();
        if (is_array($rtr_pin_trans)) {
            $labels = [];
            $data1 = [];
            $data2 = [];

            $r = 255;
            $g = 0;
            $b = 0;
            $rtr_color = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', 0.2)';
            $rtr_border_color = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', 1)';

            $r = 215;
            $g = 180;
            $b = 20;
            $pin_color = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', 0.2)';
            $pin_border_color = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', 1)';

            foreach ($rtr_pin_trans as $o) {
                $labels[] = $o->cdate;
                $data1[] = $o->rtr_qty;
                $data2[] = $o->pin_qty;

                $background_color_rtr = $rtr_color;
                $border_color_rtr = $rtr_border_color;

                $background_color_pin = $pin_color;
                $border_color_pin = $pin_border_color;
            }

            $ds1 = new \stdClass();
            $ds1->label = '# of RTR';
            $ds1->data = $data1;
            $ds1->backgroundColor = $background_color_rtr;
            $ds1->borderColor = $border_color_rtr;
            $ds1->borderWidth = 1;

            $ds2 = new \stdClass();
            $ds2->label = '# of PIN';
            $ds2->data = $data2;
            $ds2->backgroundColor = $background_color_pin;
            $ds2->borderColor = $border_color_pin;
            $ds2->borderWidth = 1;

            $rtr_pin_dataset->labels = $labels;
            $rtr_pin_dataset->datasets = [$ds1, $ds2];
        }

        return view('admin.dashboard2', [
            'carriers'  => $carriers,
            'h2o'       => json_encode($h2o_dataset),
            'lyca'      => json_encode($lyca_dataset),
            'att'       => json_encode($att_dataset),
            'freeup'    => json_encode($freeup_dataset),
            'gen'       => json_encode($gen_dataset),
            'liberty'   => json_encode($liberty_dataset),
            'boom'      => json_encode($boom_dataset),
            'rtr_pin' => json_encode($rtr_pin_dataset)
        ]);
    }

}