<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/22/17
 * Time: 2:40 PM
 */

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

class NewsController extends Controller
{
    public function show(Request $request) {
        $login_account = Account::find(Auth::user()->account_id);

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
            and a.type = 'N'
            and (
                ifnull(:product, '') = '' or 
                a.product = :product2
            ) 
            and (
                ifnull(:subject, '') = ''
                or 
                (lower(a.subject) like concat('%', :subject2, '%') and a.type = 'N')
                or
                (lower(a.body) like concat('%', :subject3, '%') and a.type not in ('N'))
            ) 
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
            'login_account_type' => $login_account->type,
            'product' => $request->product,
            'product2' => $request->product,
            'subject' => $request->subject,
            'subject2' => strtolower($request->subject),
            'subject3' => strtolower($request->subject)
        ]);

        return view('admin.news', [
            'news' => $news,
            'product' => $request->product,
            'subject' => $request->subject
        ]);
    }

}