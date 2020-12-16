<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\NewsAccountId;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Model\News;
use App\Model\NewsAccountType;
use App\Model\Account;
use Auth;
use Validator;
use DB;
use Maatwebsite\Excel\Facades\Excel;
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/3/17
 * Time: 4:40 PM
 */
class NewsController extends Controller
{
    public function show(Request $request) {


        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $login_account = Account::find(Auth::user()->account_id);

        $query = News::join('users', 'users.user_id', 'news.created_by')
            ->join('accounts', 'accounts.id', 'users.account_id')
            ->where('accounts.path', 'like', $login_account->path . '%')
            ->where('news.status', '<>', 'D');

        if (!empty($request->pdate)) {
            $query = $query->where('news.sdate', '<=', $request->pdate);
            $query = $query->where('news.edate', '>=', $request->pdate);
        }

        $sdate_c = null;
        if (!empty($request->sdate_c)) {
            $sdate_c = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate_c . ' 00:00:00');
            $query = $query->where('news.cdate', '>=', $sdate_c);
        }
        $edate_c = null;
        if (!empty($request->edate_c)) {
            $edate_c = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate_c . ' 23:59:59');
            $query = $query->where('news.cdate', '<', $edate_c);
        }

        $sdate_m = null;
        if (!empty($request->sdate_m)) {
            $sdate_m = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate_m . ' 00:00:00');
            $query = $query->where('news.mdate', '>=', $sdate_m);
        }
        $edate_m = null;
        if (!empty($request->edate_m)) {
            $edate_m = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate_m . ' 23:59:59');
            $query = $query->where('news.mdate', '<', $edate_m);
        }

        if (!empty($request->account_type)) {
            $not_exists_check_array = ['M', 'D', 'S'];
            foreach ($request->account_type as $o) {
                $query = $query->whereRaw('exists (select * from news_account_type where news_id = news.id and account_type = ?)', $o);
                $key = array_search($o, $not_exists_check_array);
                //$not_exists_check_array = array_splice($not_exists_check_array, $key, 1);
                unset($not_exists_check_array[$key]);
            }

            if (is_array($not_exists_check_array)) {
                foreach ($not_exists_check_array as $o) {
                    $query = $query->whereRaw('not exists (select * from news_account_type where news_id = news.id and account_type = ?)', $o);
                }
            }

        }

        $status = $request->status;
        if (!empty($status)) {
            if ($status == 'NE') {
                $query = $query->whereRaw("news.edate > curdate()");
            } else if ($status == 'OG') {
                $query = $query->whereRaw("(news.edate > DATE_ADD(CURDATE(), INTERVAL -1 DAY)) and news.status = 'A'");
            } else {
                $query = $query->where('news.status', $status);
            }
        }

        $account_types = [];
        switch ($login_account->type) {
            case 'L':
                $account_types = [
                    ['code' => 'M', 'name' => 'Master'],
                    ['code' => 'D', 'name' => 'Distributor'],
                    ['code' => 'S', 'name' => 'Sub-Agent'],
                ];
                break;
            case 'M':
                $account_types = [
                    ['code' => 'D', 'name' => 'Distributor'],
                    ['code' => 'S', 'name' => 'Sub-Agent'],
                ];
                break;
            case 'D':
                $account_types = [
                    ['code' => 'S', 'name' => 'Sub-Agent'],
                ];
                break;
        }

        if (!empty($request->type)) {
            $query = $query->where('news.type', $request->type);
        }

        if (!empty($request->product)) {
            $query = $query->where('news.product', $request->product);
        }

        if (!empty($request->subject)) {
            $query = $query->whereRaw("
            ((lower(news.subject) like '%" . str_replace("&nbsp;", '', strtolower($request->subject)). "%' and news.type in ('N', 'A', 'I', 'T') )
            or
            (lower(news.body) like '%" . str_replace("&nbsp;", '',strtolower($request->subject)). "%' and news.type not in ('N', 'A', 'I', 'T') ) )
            ");
        }

        if (!empty($request->news_id)) {
            $query = $query->where('news.id', $request->news_id);
        }

        if (!empty($request->invi_subject)) {
            $query = $query->whereRaw(" lower(news.invi_subject) like '%" . strtolower($request->invi_subject). "%' ");
        }

        if(!empty($request->acct_included)){

            $login_account = Account::find($request->acct_included);
            if(empty($login_account)) {
                return redirect('/admin/settings/news');
            }

            if($request->check_included == 'Y'){
                $query = $query->whereRaw(" 
                '". $login_account->type ."' in (
                    select account_type
                      from news_account_type t
                     where t.news_id = news.id 
                )
                and
                ( IfNull(news.include_account_ids ,'') = ''
                    or exists (
                         select news_id 
                           from news_account_id b , accounts c 
                          where news.id = b.news_id
                            and b.type ='I'
                            and b.account_id = c.id  
                            and '" . $login_account->path . "' like concat( c.path , '%') 
                            ) 
                )
                and ( IfNull(news.exclude_account_ids ,'') = ''
                    or not exists (
                         select news_id 
                           from news_account_id b , accounts c 
                          where news.id = b.news_id
                            and b.type ='E'
                            and b.account_id = c.id  
                            and '" . $login_account->path . "' like concat( c.path , '%') 
                            )
                    )         
                ");
            }else{
                $query = $query->where('include_account_ids', 'Y');
                $ids = NewsAccountId::select('news_id')->where('type', 'I')->where('account_id', $request->acct_included)->get();
                $query = $query->whereIn('news.id', $ids);
            }
        }

        if(!empty($request->acct_excluded)){
            $login_account = Account::find($request->acct_excluded);
            if(empty($login_account)) {
                return redirect('/admin/settings/news');
            }

            $query = $query->where('exclude_account_ids', 'Y');
            $ids = NewsAccountId::select('news_id')->where('type', 'E')->where('account_id', $request->acct_excluded)->get();
            $query = $query->whereIn('news.id', $ids);
        }

        $query = $query->select('news.*');
        $query = $query->orderBy('news.id', 'desc');
        $order_by = empty($request->order_by) ? 'news.id desc' : $request->order_by;

        switch ($order_by) {
            case 'news.id desc':
                $query = $query->orderBy('news.id', 'desc');
                break;
            case 'news.cdate asc':
                $query = $query->orderBy('news.cdate', 'asc');
                break;
            case 'news.cdate desc':
                $query = $query->orderBy('news.cdate', 'desc');
                break;
        }

        if ($request->excel == 'Y') {
            $data = $query->get();
            Excel::create('news', function($excel) use($data) {
                $excel->sheet('reports', function($sheet) use($data) {
                    $reports = [];
                    foreach ($data as $o) {
                        $reports[] = [
                            'ID' => $o->id,
                            'Type' => News::getTypeNameAttribute($o->type),
                            'Product' => $o->product,
                            'From' => $o->sdate,
                            'To' => $o->edate,
                            'Included.Account' => Helper::get_included_account_id($o->id),
                            'Excluded.Account' => Helper::get_excluded_account_id($o->id),
                            'Target.Type' => Helper::get_news_target_type($o->id),
                            'Status' => News::getStatusName($o->status),
                            'Created.By' => $o->created_by,
                            'Created.At' => $o->cdate,
                            'Modified.By' => $o->modified_by,
                            'Modified.At' => $o->mdate,
                            'Void' => $o->status == '',
                            'Sorting' => $o->sorting,
                            'Invisible.Subject' => $o->invi_subject
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $news = $query->paginate(20, ['*'], 'page', $request->page);

        return view('admin.settings.news', [
            'pdate' => $request->pdate,
            'sdate_c' => $sdate_c,
            'edate_c' => $edate_c,
            'sdate_m' => $sdate_m,
            'edate_m' => $edate_m,
            'news' => $news,
            'show_detail' => empty($request->id) ? 'N' : 'Y',
            'id' => $request->id,
            'account_type' => $request->account_type,
            'status' => $status,
            'account_types' => $account_types,
            'type' => $request->type,
            'product' => $request->product,
            'subject' => $request->subject,
            'news_id' => $request->news_id,
            'invi_subject' => $request->invi_subject,
            'acct_included' => $request->acct_included,
            'check_included' => $request->check_included,
            'acct_excluded' => $request->acct_excluded,
            'check_excluded' => $request->check_excluded,
            'order_by' => $request->order_by,
            'page' => $news->currentPage()
        ]);
    }

    public function add(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'type' => 'required',
                'sdate' => 'required|date',
                'edate' => 'required|date',
                // 'account_id' => 'nullable|regex:/\d{6}$/',
                //'account_type' => 'nullable|in:,M,D,A,S',
                'subject' => 'required_if:type,N',
                'product' => 'required_if:type,P,R,N',
                'body' => 'required',
                'status' => 'required'
            ], [
                'product.required_if' => 'Please select product'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $login_account = Account::find(Auth::user()->account_id);
            $account = Account::where('id', $request->account_id)
                ->where('path', 'like', $login_account->path . '%')
                ->first();

            if (!empty($request->account_id) && empty($account)) {
                return response()->json([
                    'msg' => 'Target account ID is invalid or not within your account hierarchy'
                ]);
            }

            $news = new News;
            $news->type = $request->type;
            $news->product = $request->product;
            $news->sdate = Carbon::createFromFormat('Y-m-d', $request->sdate);
            $news->edate = Carbon::createFromFormat('Y-m-d', $request->edate);

            if (!empty($request->include_account_ids)) {
                $news->include_account_ids = 'Y';
            }else{
                $news->include_account_ids = null;
            }
            if (!empty($request->exclude_account_ids)) {
                $news->exclude_account_ids = 'Y';
            }else{
                $news->exclude_account_ids = null;
            }

            $news->subject = $request->subject;
            $news->invi_subject = $request->invi_subject;
            $news->body = $request->body;
            $news->status = $request->status;
            $news->sorting = $request->sorting;
            $news->scroll = $request->scroll;
            $news->created_by = Auth::user()->user_id;
            $news->cdate = Carbon::now();
            $news->save();

            NewsAccountId::where('news_id', $news->id)->delete();

            if(!empty($request->include_account_ids)){
                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->include_account_ids) as $line){
                    $nai = new NewsAccountId;
                    $nai->news_id       = $news->id;
                    $nai->type          = 'I';
                    $nai->account_id    = $line;
                    $nai->cdate         = Carbon::now();
                    $nai->save();
                }
            }
            if (!empty($request->exclude_account_ids)) {
                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->exclude_account_ids) as $line){
                    $nai = new NewsAccountId;
                    $nai->news_id       = $news->id;
                    $nai->type          = 'E';
                    $nai->account_id    = $line;
                    $nai->cdate         = Carbon::now();
                    $nai->save();
                }
            }

            NewsAccountType::where('news_id', $news->id)->delete();

            if (is_array($request->account_type)) {
                foreach ($request->account_type as $o) {
                    $news_account_type = new NewsAccountType;
                    $news_account_type->news_id = $news->id;
                    $news_account_type->account_type = $o;
                    $news_account_type->save();
                }
            }

            return response()->json([
                'msg' => '',
                'id' => $news->id
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function copy_add(Request $request) {

        try {

            $news = News::where('id', $request->id)->first();

            $new_news = new News();
            $new_news->type = $news->type;
            $new_news->product = $news->product;
            $new_news->sdate = $news->sdate;
            $new_news->edate = $news->edate;

            $new_news->account_type = $news->account_type;

            $new_news->include_account_ids = $news->include_account_ids;
            $new_news->exclude_account_ids = $news->exclude_account_ids;

            $new_news->subject = $news->subject;
            $new_news->invi_subject = $news->invi_subject;
            $new_news->body = $news->body;
            $new_news->status = $news->status;
            $new_news->scroll = $news->scroll;
            $new_news->created_by = Auth::user()->user_id;
            $new_news->cdate = Carbon::now();
            $new_news->sorting = $news->sorting;

            $new_news->save();

            if(!empty($request->include_account_ids)){
                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->include_account_ids) as $line){
                    $nai = new NewsAccountId;
                    $nai->news_id       = $new_news->id;
                    $nai->type          = 'I';
                    $nai->account_id    = $line;
                    $nai->cdate         = Carbon::now();
                    $nai->save();
                }
            }
            if (!empty($request->exclude_account_ids)) {
                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->exclude_account_ids) as $line){
                    $nai = new NewsAccountId;
                    $nai->news_id       = $new_news->id;
                    $nai->type          = 'E';
                    $nai->account_id    = $line;
                    $nai->cdate         = Carbon::now();
                    $nai->save();
                }
            }

            if (is_array($request->account_type)) {
                foreach ($request->account_type as $o) {
                    $news_account_type = new NewsAccountType;
                    $news_account_type->news_id = $new_news->id;
                    $news_account_type->account_type = $o;
                    $news_account_type->save();
                }
            }

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function remove(Request $request) {

        try {

            $news = News::where('id', $request->id)->first();

            $news->status = 'D';
            $news->modified_by = Auth::user()->user_id;
            $news->mdate = Carbon::now();

            $news->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required',
                'type' => 'required',
                'sdate' => 'required|date',
                'edate' => 'required|date',
                //'account_type' => 'nullable|in:,M,D,A,S',
                'subject' => 'required_if:type,N',
                'product' => 'required_if:type,P,R,N',
                'body' => 'required',
                'status' => 'required'
            ], [
                'product.required_if' => 'Please select product'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $login_account = Account::find(Auth::user()->account_id);
            $account = Account::where('id', $request->account_id)
                ->where('path', 'like', $login_account->path . '%')
                ->first();

            if (!empty($request->account_id) && empty($account)) {
                return response()->json([
                    'msg' => 'Target account ID is invalid or not within your account hierarchy'
                ]);
            }

            $news = News::find($request->id);
            if (empty($news)) {
                return [
                    'msg' => 'Invalid news ID provided'
                ];
            }

            $news->type = $request->type;
            $news->product = $request->product;
            $news->sdate = Carbon::createFromFormat('Y-m-d', $request->sdate);
            $news->edate = Carbon::createFromFormat('Y-m-d', $request->edate);

            if (!empty($request->include_account_ids)) {

                NewsAccountId::where('news_id', $news->id)->where('type', 'I')->delete();
                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->include_account_ids) as $line){
                    $nai = new NewsAccountId;
                    $nai->news_id       = $news->id;
                    $nai->type          = 'I';
                    $nai->account_id    = $line;
                    $nai->cdate         = Carbon::now();
                    $nai->save();
                }
                $news->include_account_ids = 'Y';
            }else{
                NewsAccountId::where('news_id', $news->id)->where('type', 'I')->delete();
                $news->include_account_ids = null;
            }

            if (!empty($request->exclude_account_ids)) {
                NewsAccountId::where('news_id', $news->id)->where('type','E')->delete();
                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->exclude_account_ids) as $line){
                    $nai = new NewsAccountId;
                    $nai->news_id       = $news->id;
                    $nai->type          = 'E';
                    $nai->account_id    = $line;
                    $nai->cdate         = Carbon::now();
                    $nai->save();
                }
                $news->exclude_account_ids = 'Y';
            }else{
                NewsAccountId::where('news_id', $news->id)->where('type', 'E')->delete();
                $news->exclude_account_ids = null;
            }

            $news->subject = $request->subject;
            $news->body = $request->body;
            $news->invi_subject = $request->invi_subject;
            $news->status = $request->status;
            $news->sorting = $request->sorting;
            $news->scroll = $request->scroll;
            $news->modified_by = Auth::user()->user_id;
            $news->mdate = Carbon::now();
            $news->save();

            NewsAccountType::where('news_id', $news->id)->delete();

            if (is_array($request->account_type)) {
                foreach ($request->account_type as $o) {

                    $news_account_type = new NewsAccountType;
                    $news_account_type->news_id = $news->id;
                    $news_account_type->account_type = $o;
                    $news_account_type->save();
                }
            }

            return response()->json([
                'msg' => '',
                'id' => $news->id
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function detail(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $news = News::find($request->id);

            if (empty($news)) {
                return [
                    'msg' => 'Invalid news ID provided'
                ];
            }

            $news->account_types = NewsAccountType::where('news_id', $news->id)->get();
            $news->account_ids_i = NewsAccountId::where('news_id', $news->id)->where('type', 'I')->get();
            $news->account_ids_e = NewsAccountId::where('news_id', $news->id)->where('type', 'E')->get();

            if ($news->type == "N") {
                $url = $request->root() . "/admin/news#" . $news->id;
                $url_s = $request->root() . "/sub-agent/news#" . $news->id;
            } elseif ($news->type == "A") {
                $url = $request->root() . "/admin/advertise#" . $news->id;
                $url_s = $request->root() . "/sub-agent/advertise#" . $news->id;
            } elseif ($news->type == "I") {
                $url = $request->root() . "/admin/digital#" . $news->id;
                $url_s = $request->root() . "/sub-agent/digital#" . $news->id;
            } elseif ($news->type == 'T') {
                $url = $request->root() . "/admin/task#" . $news->id;
                $url_s = $request->root() . "/sub-agent/task#" . $news->id;
            } elseif ($news->type == 'W') {
                $url = $request->root() . "/admin/follow#" . $news->id;
                $url_s = $request->root() . "/sub-agent/follow#" . $news->id;
            } elseif ($news->type == 'U') {
                $url = $request->root() . "/admin/document#" . $news->id;
                $url_s = $request->root() . "/sub-agent/document#" . $news->id;
            } elseif ($news->type == 'C') {
                $url = $request->root() . "/admin/communication#" . $news->id;
                $url_s = $request->root() . "/sub-agent/communication#" . $news->id;
            }else{
                $url = '';
                $url_s = '';
            }

            return response()->json([
                'msg' => '',
                'data' => $news,
                'url' => $url,
                'url_s' => $url_s
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function void(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $news = News::find($request->id);
            if (empty($news)) {
                return [
                    'msg' => 'Invalid news ID provided'
                ];
            }

            $news->status = 'V';
            $news->modified_by = Auth::user()->user_id;
            $news->mdate = Carbon::now();
            $news->save();

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