<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/22/17
 * Time: 2:40 PM
 */

namespace App\Http\Controllers\SubAgent;

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

class AdvertiseController extends Controller
{
    public function show(Request $request) {
        $login_account = Account::find(Auth::user()->account_id);

        //$news = News::where('status', 'A')
        # 1. date
        #   - today should be between sdate and edate
        #   - or sdate & edate are emtpy
        #
        # 2. path
        #   - target account's path should include login account's path
        #
        # 3. account type
        #   - target account type should be empty or match with login account's type

        $news = '';
        $news = DB::select("
        select a.* 
            from news a 
            where 
                :login_account_type in (
                    select account_type
                    from news_account_type
                    where news_id = a.id
                )
            and a.status = 'A'
            and a.type = 'A'
            and a.sdate <= curdate()
            and a.edate >= curdate()
            and ( IfNull(a.include_account_ids ,'') = ''
                  or exists (
                             select news_id 
                              from news_account_id b , accounts c 
                            where a.id = b.news_id
                            and b.type ='I'
                             and b.account_id = c.id  
                             and :login_path like concat( c.path , '%') 
                          )
                  )         
            and ( IfNull(a.exclude_account_ids ,'') = ''
                  or not exists (
                             select news_id 
                              from news_account_id b , accounts c 
                            where a.id = b.news_id
                            and b.type ='E'
                             and b.account_id = c.id  
                             and :login_path2 like concat( c.path , '%') 
                          )
                  ) 
            order by a.sorting asc, a.id desc
        ", [
            'login_path' => $login_account->path,
            'login_path2' => $login_account->path,
            'login_account_type' => $login_account->type
        ]);

        return view('sub-agent.advertise', [
            'news' => $news
        ]);
    }

}