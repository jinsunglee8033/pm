<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/24/17
 * Time: 11:48 AM
 */

namespace App\Http\Controllers\Admin\Settings;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\Account;
use App\Model\ATTBatchFeeBase;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\Product;
use App\Model\SpiffSetup;
use App\Model\SpiffSetupSpecial;
use App\Model\SpiffTemplate;
use App\Model\SpiffTemplateOwner;
use App\Model\State;
use App\Model\StoreType;
use App\Model\Vendor;
use App\Model\VRProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class AccountSpiffSetupController extends Controller
{

    public function show(Request $request) {

        $accounts = Account::LeftJoin('spiff_template', 'accounts.spiff_template', '=', 'spiff_template.id')->where('accounts.status', 'A');

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

        if (!empty($request->emails)) {
            if($request->emails_except == 'Y'){
                $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                $con = sprintf(" '%s' ", implode("', '", $emails));
                $con = strtolower($con);
                $accounts = $accounts->whereRaw(" ( lower(IfNull(accounts.email,'')) not in ($con) and lower(IfNull(accounts.email2,'')) not in ($con) ) ");
            } else {
                $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                $con = sprintf(" '%s' ", implode("', '", $emails));
                $con = strtolower($con);
                $accounts = $accounts->whereRaw(" ( lower(IfNull(accounts.email,'')) in ($con) or lower(IfNull(accounts.email2,'')) in ($con) ) ");
            }
        }

        if (!empty($request->id)) {
            $target_account = Account::find($request->id);
            if ($request->include_sub_account_id == 'Y') {
                $accounts = $accounts->where('accounts.path', 'like', $target_account->path . '%');
            } else {
                $accounts = $accounts->where('accounts.id', $request->id);
            }
        }

        if (!empty($request->ids)) {
            if($request->ids_except == 'Y'){
                $ids = preg_split('/[\ \r\n\,]+/', $request->ids);
                $accounts = $accounts->whereNotIn('accounts.id', $ids);
            }else {
                $ids = preg_split('/[\ \r\n\,]+/', $request->ids);
                $accounts = $accounts->whereIn('accounts.id', $ids);
            }
        }

        if (!empty($request->user_name)) {
            $accounts = $accounts->whereRaw("accounts.id in (select account_id from users where lower(name) like '%" . strtolower($request->user_name) . "%')");
        }

        if (!empty($request->created_sdate)) {
//            $accounts = $accounts->whereRaw('cast(cdate as date) >= \'' . $request->created_sdate . '\'');
            $accounts = $accounts->where('accounts.cdate', '>=', Carbon::parse($request->created_sdate . ' 00:00:00'));
        }

        if (!empty($request->created_edate)) {
//            $accounts = $accounts->whereRaw('cast(cdate as date) <= \'' . $request->created_edate . '\'');
            $accounts = $accounts->where('accounts.cdate', '<=', Carbon::parse($request->created_edate . ' 23:59:59'));
        }

        if (!empty($request->spiff_template)) {
            $accounts = $accounts->where('accounts.spiff_template', $request->spiff_template);
        }

        if (!empty($request->allow_boom)) {
            $accounts = $accounts->where('accounts.act_boom', 'Y');
        }
        if (!empty($request->allow_freeup)) {
            $accounts = $accounts->where('accounts.act_freeup', 'Y');
        }
        if (!empty($request->allow_gen)) {
            $accounts = $accounts->where('accounts.act_gen', 'Y');
        }
        if (!empty($request->allow_h2o)) {
            $accounts = $accounts->where('accounts.act_h2o', 'Y');
        }
        if (!empty($request->allow_liberty)) {
            $accounts = $accounts->where('accounts.act_liberty', 'Y');
        }
        if (!empty($request->allow_lyca)) {
            $accounts = $accounts->where('accounts.act_lyca', 'Y');
        }

        if (!empty($request->hs_boom)) {
            $accounts = $accounts->where('accounts.boom_hold_spiff', 'Y');
        }
        if (!empty($request->hs_freeup)) {
            $accounts = $accounts->where('accounts.freeup_hold_spiff', 'Y');
        }
        if (!empty($request->hs_gen)) {
            $accounts = $accounts->where('accounts.gen_hold_spiff', 'Y');
        }
        if (!empty($request->hs_h2o)) {
            $accounts = $accounts->where('accounts.h2o_hold_spiff', 'Y');
        }
        if (!empty($request->hs_liberty)) {
            $accounts = $accounts->where('accounts.liberty_hold_spiff', 'Y');
        }
        if (!empty($request->hs_lyca)) {
            $accounts = $accounts->where('accounts.lyca_hold_spiff', 'Y');
        }

        if (!empty($request->m_boom)) {
            $accounts = $accounts->where('accounts.boom_min_month', '>=', $request->m_boom);
        }
        if (!empty($request->m_freeup)) {
            $accounts = $accounts->where('accounts.freeup_min_month', '>=', $request->m_freeup);
        }
        if (!empty($request->m_gen)) {
            $accounts = $accounts->where('accounts.gen_min_month', '>=', $request->m_gen);
        }
        if (!empty($request->m_h2o)) {
            $accounts = $accounts->where('accounts.h2o_min_month', '>=', $request->m_h2o);
        }
        if (!empty($request->m_liberty)) {
            $accounts = $accounts->where('accounts.liberty_min_month', '>=', $request->m_liberty);
        }
        if (!empty($request->m_lyca)) {
            $accounts = $accounts->where('accounts.lyca_min_month', '>=', $request->m_lyca);
        }



        $user = Auth::user();
        $user_account = Account::find($user->account_id);
        $path = $user_account->path;

        $accounts = $accounts->whereRaw('accounts.path like \'' . $path . '%\'');


        if ($request->excel == 'Y' && Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system'])) {
            $data = $accounts->select("accounts.*", "spiff_template.template as template")->get();
            Excel::create('accounts', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $parent = Account::find($a->parent_id);

                        $reports[] = [
                            'Parent #' => $parent->id,
                            'Parent Name' => $parent->name,
                            'SPP Account #' => $a->id,
                            'Account Name' => $a->name,
                            'Status' => $a->status,
                            'Spiff.Template' => $a->template,
                            'Spiff.Template.ID' => $a->spiff_template,
                            'Boom.act' => $a->act_boom,
                            'Boom.Min.Month' => $a->boom_min_month,
                            'Boom.Hold.Spiff' => $a->boom_hold_spiff,
                            'Freeup.act' => $a->act_freeup,
                            'Freeup.Min.Month' => $a->freeup_min_month,
                            'Freeup.Hold.Spiff' => $a->freeup_hold_spiff,
                            'Gen.act' => $a->act_gen,
                            'Gen.Min.Month' => $a->gen_min_month,
                            'Gen.Hold.Spiff' => $a->gen_hold_spiff,
                            'H2O.act' => $a->act_h2o,
                            'H2O.Min.Month' => $a->h2o_min_month,
                            'H2O.Hold.Spiff' => $a->h2o_hold_spiff,
                            'Liberty.act' => $a->act_liberty,
                            'Liberty.Min.Month' => $a->liberty_min_month,
                            'Liberty.Hold.Spiff' => $a->liberty_hold_spiff,
                            'Lyca.act' => $a->act_lyca,
                            'Lyca.Min.Month' => $a->lyca_min_month,
                            'Lyca.Hold.Spiff' => $a->lyca_hold_spiff
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $accounts = $accounts->select("accounts.*", "spiff_template.template as template")->paginate(100);

        $states = State::orderBy('name', 'asc')->get();

        $d_spiff_templates = SpiffTemplate::where('account_type', 'D')->orderBy('template')->get();
        $s_spiff_templates = SpiffTemplate::where('account_type', 'S')->orderBy('template')->get();

        $vr_carriers = VRProduct::whereNotNull('carrier')->where('carrier', '<>', '')->groupBy('carrier')->get([
            'carrier'
        ]);

        foreach ($vr_carriers as $v) {
            $v->carrier_key = $v->carrier;
            $v->carrier_key = str_replace('&', '', $v->carrier);
            $v->carrier_key = str_replace(' ', '', $v->carrier_key);
        }

        $carriers = Carrier::where('has_activation', 'Y')->get();
        $attbatchfeebase = ATTBatchFeeBase::where('type', 'B')->first();
        $attbatchfeetiers = ATTBatchFeeBase::where('type', 'T')->get();
        $spiff_templates = SpiffTemplate::orderByRaw(' case when account_type = "M" then 1
                                                        when account_type = "D" then 2
                                                        when account_type = "S" then 3
                                                        end asc, template asc')->get();

        return view('admin.settings.account-spiff-setup', [
            'accounts' => $accounts,
            'states' => $states,
            'types' => $this->get_types_filter(),
            'type' => $request->type,
            'name' => $request->name,
            'status' => $request->status,
            'emails' => $request->emails,
            'emails_except' => $request->emails_except,
            'user_id' => $request->user_id,
            'id' => $request->id,
            'ids' => $request->ids,
            'ids_except' => $request->ids_except,
            'user_name' => $request->user_name,
            'include_sub_account' => $request->include_sub_account,
            'include_sub_account_name' => $request->include_sub_account_name,
            'include_sub_account_id' => $request->include_sub_account_id,
            'd_spiff_templates' => $d_spiff_templates,
            's_spiff_templates' => $s_spiff_templates,
            'carriers' => $carriers,
            'attbatchfeebase' => $attbatchfeebase,
            'attbatchfeetiers' => $attbatchfeetiers,
            'spiff_templates' => $spiff_templates,
            'spiff_template' => $request->spiff_template,
            'allow_boom' => $request->allow_boom,
            'allow_freeup' => $request->allow_freeup,
            'allow_gen' => $request->allow_gen,
            'allow_h2o' => $request->allow_h2o,
            'allow_liberty' => $request->allow_liberty,
            'allow_lyca' => $request->allow_lyca,
            'hs_boom' => $request->hs_boom,
            'hs_freeup' => $request->hs_freeup,
            'hs_gen' => $request->hs_gen,
            'hs_h2o' => $request->hs_h2o,
            'hs_liberty' => $request->hs_liberty,
            'hs_lyca' => $request->hs_lyca,
            'm_boom' => $request->m_boom,
            'm_freeup' => $request->m_freeup,
            'm_gen' => $request->m_gen,
            'm_h2o' => $request->m_h2o,
            'm_liberty' => $request->m_liberty,
            'm_lyca' => $request->m_lyca
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

    public function update_dis_temps(Request $request) {
        try {

            $accounts = Account::where('path', 'like', "%$request->account_id%")
                ->where('type', 'D')
                ->where('spiff_template', $request->dis_temp_from)
                ->update(['spiff_template' => $request->dis_temp_to, 'mdate' => Carbon::now()]);

            return response()->json([
                'msg' => '',
                'count' => count($accounts)
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update_sub_temps(Request $request) {
        try {

            $accounts = Account::where('path', 'like', "%$request->account_id%")
                ->where('type', 'S')
                ->where('spiff_template', $request->sub_temp_from)
                ->update(['spiff_template' => $request->sub_temp_to, 'mdate' => Carbon::now()]);

            return response()->json([
                'msg' => '',
                'count' => count($accounts)
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function all_active(Request $request) {
        try {

            if($request->act_product == 'AT&T'){
                $act = 'act_att';
            }elseif($request->act_product == 'Boom Mobile'){
                $act = 'act_boom';
            }elseif($request->act_product == 'FreeUP'){
                $act = 'act_freeup';
            }elseif($request->act_product == 'GEN Mobile'){
                $act = 'act_gen';
            }elseif($request->act_product == 'H2O'){
                $act = 'act_h2o';
            }elseif($request->act_product == 'Liberty Mobile'){
                $act = 'act_liberty';
            }elseif($request->act_product == 'Lyca'){
                $act = 'act_lyca';
            }

            $accounts = Account::where('path', 'like', "%$request->account_id%")
                ->update([$act => 'Y', 'mdate' => Carbon::now()]);

            return response()->json([
                'msg' => '',
                'count' => count($accounts)
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function all_inactive(Request $request) {
        try {

            if($request->act_product == 'AT&T'){
                $act = 'act_att';
            }elseif($request->act_product == 'Boom Mobile'){
                $act = 'act_boom';
            }elseif($request->act_product == 'FreeUP'){
                $act = 'act_freeup';
            }elseif($request->act_product == 'GEN Mobile'){
                $act = 'act_gen';
            }elseif($request->act_product == 'H2O'){
                $act = 'act_h2o';
            }elseif($request->act_product == 'Liberty Mobile'){
                $act = 'act_liberty';
            }elseif($request->act_product == 'Lyca'){
                $act = 'act_lyca';
            }

            $accounts = Account::where('path', 'like', "%$request->account_id%")
                ->update([$act => '', 'mdate' => Carbon::now()]);

            return response()->json([
                'msg' => '',
                'count' => count($accounts)
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function add_template(Request $request) {
        if (!empty($request->template_name) && !empty($request->account_type)) {
            $template = SpiffTemplate::find($request->template_id);

            if (empty($template)) {
                $template = new SpiffTemplate();
                $template->account_id = Auth::user()->account_id;
                $template->account_type = $request->account_type;
                $template->template = $request->template_name;
                $template->cdate = Carbon::now();
                $template->save();
            } else {
                $template->template = $request->template_name;
                $template->mdate = Carbon::now();
                $template->update();
            }

            SpiffTemplateOwner::where('template_id', $template->id)->delete();
            if (!empty($request->master_ids)) {
                $owner_ids = explode(',', $request->master_ids);
                if (!empty($owner_ids) && count($owner_ids) > 0) {
                    foreach ($owner_ids as $oid) {
                        $owner = new SpiffTemplateOwner();
                        $owner->template_id = $template->id;
                        $owner->account_id  = $oid;
                        $owner->save();
                    }
                }
            }

            return redirect('/admin/settings/spiff-setup?account_type=' . $request->account_type . '&template=' . $template->id);
        }

        return redirect('/admin/settings/spiff-setup?account_type=' . $request->account_type);
    }

    public function spiff_template(Request $request) {

        $spiff_templates = SpiffTemplate::whereRaw('id in (select template_id from spiff_template_owner where account_id = ' . $request->account_id . ')')
            ->get([
                'id',
                'account_type'
            ]);

        $dis_spiff_templates = SpiffTemplate::whereRaw('account_type = "D" and id not in (select template_id from spiff_template_owner where account_id = ' . $request->account_id . ')')
            ->get([
                'id',
                'account_type'
            ]);

        $sub_spiff_templates = SpiffTemplate::whereRaw('account_type = "S" and id not in (select template_id from spiff_template_owner where account_id = ' . $request->account_id . ')')
            ->get([
                'id',
                'account_type'
            ]);

        $count = empty($spiff_templates) ? 0 : count($spiff_templates);
        $d_count = empty($dis_spiff_templates) ? 0 : count($dis_spiff_templates);
        $s_count = empty($sub_spiff_templates) ? 0 : count($sub_spiff_templates);

        return response()->json([
            'count' => $count,
            'spiff_templates' => $spiff_templates,
            'd_count' => $d_count,
            'dis_spiff_templates' => $dis_spiff_templates,
            's_count' => $s_count,
            'sub_spiff_templates' => $sub_spiff_templates
        ]);
    }
}