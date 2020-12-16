<?php

namespace App\Lib;

use App\Http\Controllers\Boom\ActivateController;
use App\Model\Account;
use App\Model\AccountActivationLimit;
use App\Model\AccountAuthority;
use App\Model\AccountStoreType;
use App\Model\ActivationController;
use App\Model\Billing;
use App\Model\News;
use App\Model\NewsAccountId;
use App\Model\NewsAccountType;
use App\Model\SpiffTrans;
use App\Model\StockSim;
use App\Model\Transaction;
use App\Model\VRProductPrice;
use App\Model\VRRequest;
use App\Model\VRRequestProduct;
use App\Model\VRProduct;
use App\Model\Promotion;
use App\Model\ConsignmentVendor;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Log;
use Illuminate\Support\Facades\Request;
use App\Http\Controllers\ATT\RechargeController as ATT;
use App\Http\Controllers\FreeUP\RechargeController as FreeUP;
use App\Http\Controllers\GEN\RechargeController as Gen;
use App\Http\Controllers\Boom\RechargeController as Boom;
use App\Http\Controllers\GEN\ActivateController as GenActivation;
use App\Http\Controllers\FreeUP\ActivateController as FreeUPActivation;
use App\Http\Controllers\Boom\ActivateController as BoomActivation;


/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/21/17
 * Time: 5:40 PM
 */
class Helper
{

    public static function is_login_as() {
        $login_as_user = Session::get('login-as-user');

        return empty($login_as_user) ? false : true;
    }

    public static function get_hierarchy_img($type) {
        $color = '';
        switch ($type) {
            case 'L':
                $type = 'R';
                $color = 'red';
                break;
            case 'M':
                $color = 'blue';
                break;
            case 'D':
                $color = 'orange';
                break;
            case 'A':
                $color = 'green';
                break;
            case 'S':
                $color = 'brown';
                break;

        }

        $icon = '';
        //$icon .= '<div style="background-color:red; width: width:50px;">';
        //$icon .= '<span style="margin-top: 0px; background-color:' . $color . '; width:18px; margin-left:' . $level * 20 . 'px; height:18px; margin-right:10px; float:left; color:white; text-align:center; line-height: 20px;">' . $type . '</span';
        $icon .= '<div style="display:inline-block; margin-top: 0px; background-color:' . $color . '; width:18px; margin-left: 5px; height:18px; margin-right:5px; color:white; text-align:center; line-height: 20px;">' . $type . '</div>';
        //$icon .= '</div>';

        //$img .= $icon;

        return $icon;
    }

    public static function get_sub_cnt_by_parent_id($parent_id) {
        $account = Account::where('path', 'like', '%'. $parent_id . '%')->where('type', 'S')->get();
        return $account->count();
    }

    public static function get_account_name_html($user_id) {
        $user = User::find($user_id);

        $html = self::get_parent_name_html($user->account_id);

        $account = Account::find($user->account_id);
        $icon = self::get_hierarchy_img($account->type);
        $account_html = "<span>" . $icon . "</span>" . $account->name . ' (' . $account->id . ')';

        return $html . $account_html;
    }

    public static function get_account_name_html_by_id($id) {
        $html = self::get_parent_name_html($id);

        $account = Account::find($id);
        $icon = self::get_hierarchy_img($account->type);
        $account_html = "<span>" . $icon . "</span>" . $account->name . ' (' . $account->id . ')';

        return $html . $account_html;
    }

    public static function get_parent_name_html($id) {
        $account = Account::find($id);
        if (empty($account)) {
            return '';
        }

        $parent = Account::find($account->parent_id);
        if (empty($parent) || $parent->type == 'L') {
            return '';
        }

        $icon = self::get_hierarchy_img($parent->type);
        $html = "<span>" . $icon . "</span><span data-toggle='tooltip' data-placement='top' title='" . $parent->name . " - " .  $parent->id . "'>" . $parent->id . "</span>";
        $html = self::get_parent_name_html($parent->id) . $html;

        return $html;
    }

    public static function get_parent_id_in_collection($collection, $id, $is_account_id = false) {
        foreach ($collection as $o) {
            if (!$is_account_id && $o->id == $id) {
                return $id;
            } else if ($is_account_id && $o->account_id == $id) {
                return $id;
            }
        }

        return '';
    }

    public static function send_mail($to, $subject, $body) {

        try {

            // Send email notification
            /*$transport = Swift_SmtpTransport::newInstance(
                \Config::get('mail.host'),
                \Config::get('mail.port'),
                \Config::get('mail.encryption'))
                ->setUsername(\Config::get('mail.username'))
                ->setPassword(\Config::get('mail.password'))
                ->setStreamOptions(['ssl' => \Config::get('mail.ssloptions')]);

            $mailer = Swift_Mailer::newInstance($transport);
            \Mail::setSwiftMailer($mailer);*/

            \Mail::raw($body, function($message) use ($to, $subject, $body) {
                $message->to($to);
                $message->subject($subject);
                $message->setBody($body, 'text/html');
                //$message->bcc('it@jjonbp.com');
            });

            if (\Mail::failures()) {
                return 'Failed to send message';
            }
        } catch (\Exception $ex) {
            return $ex->getMessage() . ': ' . $ex->getCode();
        }

        return '';

    }

    public static function get_image_type($url) {
        $type = exif_imagetype($url);
        switch ($type) {
            case IMAGETYPE_GIF:
                return 'GIF';
            case IMAGETYPE_JPEG:
                return 'JPEG';
            case IMAGETYPE_PNG:
                return 'PNG';
            case IMAGETYPE_SWF:
                return 'SWF';
            case IMAGETYPE_PSD:
                return 'PSD';
            case IMAGETYPE_BMP:
                return 'BMP';
            case IMAGETYPE_TIFF_II:
                return 'TIFF_II';
            case IMAGETYPE_TIFF_MM:
                return 'TIFF_MM';
            case IMAGETYPE_JPC:
                return 'JPC';
            case IMAGETYPE_JP2:
                return 'JP2';
            case IMAGETYPE_JPX:
                return 'JPX';
            case IMAGETYPE_JB2:
                return 'JB2';
            case IMAGETYPE_SWC:
                return 'SWC';
            case IMAGETYPE_IFF:
                return 'IFF';
            case IMAGETYPE_WBMP:
                return 'WBMP';
            case IMAGETYPE_XBM:
                return 'XBM';
            case IMAGETYPE_ICO:
                return 'ICO';
        }
    }

    public static function image_disable_interlacing($filepath) {
        $im = self::image_create($filepath);
        imageinterlace($im, false);
        self::image_save($im, $filepath);
        imagedestroy($im);
    }

    public static function image_save($im, $filepath) {
        $type = exif_imagetype($filepath); // [] if you don't have exif you could use getImageSize()
        $allowedTypes = array(
            1,  // [] gif
            2,  // [] jpg
            3,  // [] png
            6   // [] bmp
        );
        if (!in_array($type, $allowedTypes)) {
            return false;
        }

        switch ($type) {
            case 1 :
                imagegif($im, $filepath);
                break;
            case 2 :
                imagejpeg($im, $filepath);
                break;
            case 3 :
                imagepng($im, $filepath);
                break;
            case 6 :
                image2wbmp($im, $filepath);
                break;
        }
    }

    public static function image_create($filepath) {
        $type = exif_imagetype($filepath); // [] if you don't have exif you could use getImageSize()
        $allowedTypes = array(
            1,  // [] gif
            2,  // [] jpg
            3,  // [] png
            6   // [] bmp
        );
        if (!in_array($type, $allowedTypes)) {
            return false;
        }
        switch ($type) {
            case 1 :
                $im = imageCreateFromGif($filepath);
                break;
            case 2 :
                $im = imageCreateFromJpeg($filepath);
                break;
            case 3 :
                $im = imageCreateFromPng($filepath);
                break;
            case 6 :
                $im = imageCreateFromBmp($filepath);
                break;
        }
        return $im;
    }

    public static function get_promotion($product) {
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
            and a.type = 'P'
            and a.sdate <= curdate()
            and a.edate >= curdate()
            and a.product = :product
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
        ", [
            'login_path' => $login_account->path,
            'login_path2' => $login_account->path,
            'login_account_type' => $login_account->type,
            'product' => $product
        ]);

        $headline = '';

        foreach ($news as $o) {
            $headline .= '<div class="reset-this" style="display:inline-block; margin-left:100px;vertical-align:top;">';
            $headline .= $o->body;
            $headline .= '</div>';
        }

        if (empty($headline)) {
            //$headline = 'No promotion at this point for ' . $product;
        }

        return $headline;
    }

    public static function get_reminder($product) {
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
            and a.type = 'R'
            and a.sdate <= curdate()
            and a.edate >= curdate()
            and a.product = :product
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
        ", [
            'login_path' => $login_account->path,
            'login_path2' => $login_account->path,
            'login_account_type' => $login_account->type,
            'product' => $product
        ]);

        $headline = '';

        foreach ($news as $o) {
            $headline .= '<div class="reset-this" style="display:inline-block; margin-left:100px;">';
            $headline .= $o->body;
            $headline .= '</div>';
        }

        if (empty($headline)) {
            //$headline = 'No reminder at this point for ' . $product ;
        }

        return $headline;
    }

    public static function over_activation($product) {
        $login_account = Account::find(Auth::user()->account_id);

        $news = DB::select(" 
        select a.* 
            from news a 
            where :login_account_type in (
                    select account_type
                    from news_account_type
                    where news_id = a.id
                )

            and a.status = 'A'
            and a.type = 'O'
            and a.product = :product
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
        ", [
            'login_path' => $login_account->path,
            'login_path2' => $login_account->path,
            'login_account_type' => $login_account->type,
            'product' => $product
        ]);

        $headline = '';

        foreach ($news as $o) {
            $headline .= '<div class="reset-this" style="display:inline-block; margin-left:100px;">';
            $headline .= $o->body;
            $headline .= '</div>';
        }

        if (empty($headline)) {
            //$headline = 'No reminder at this point for ' . $product ;
        }

        return $headline;
    }

    public static function get_reminder_refill() {
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
            and a.type = 'F'
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
        ", [
            'login_path' => $login_account->path,
            'login_path2' => $login_account->path,
            'login_account_type' => $login_account->type
        ]);

        $headline = '';

        foreach ($news as $o) {
            $headline .= '<div class="reset-this" style="display:inline-block; margin-left:100px;">';
            $headline .= $o->body;
            $headline .= '</div>';
        }

        if (empty($headline)) {
            //$headline = 'No reminder at this point for ' . $product ;
        }

        return $headline;
    }

    public static function get_reminder_pin() {
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
            and a.type = 'G'
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
        ", [
            'login_path' => $login_account->path,
            'login_path2' => $login_account->path,
            'login_account_type' => $login_account->type
        ]);

        $headline = '';

        foreach ($news as $o) {
            $headline .= '<div class="reset-this" style="display:inline-block; margin-left:100px;">';
            $headline .= $o->body;
            $headline .= '</div>';
        }

        if (empty($headline)) {
            //$headline = 'No reminder at this point for ' . $product ;
        }

        return $headline;
    }

    public static function get_static_headline() {
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
            and a.type = 'S'
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
        ", [
            'login_path' => $login_account->path,
            'login_path2' => $login_account->path,
            'login_account_type' => $login_account->type
        ]);

        $static_headline = '';

        foreach ($news as $o) {
            if($o->scroll == 'Y'){
                $static_headline .= '<marquee behavior="scroll" direction="left" onmouseover="this.stop();"onmouseout="this.start();">';
                $static_headline .= '<div class="reset-this" style="display:inline-block; margin-left:150px;">';
                $static_headline .= $o->body;
                $static_headline .= '</div>';
                $static_headline .= '</marquee>';
            }elseif($o->scroll == 'N' || $o->scroll == ''){
                $static_headline .= '<div class="reset-this" style="display:inline-block; margin-left:3px;">';
                $static_headline .= $o->body;
                $static_headline .= '</div>';
            }
        }

//        foreach ($news as $o) {
//            $static_headline .= '<div class="reset-this" style="display:inline-block; margin-left:3px;">';
//            $static_headline .= $o->body;
//            $static_headline .= '</div>';
//        }

        if (empty($static_headline)) {
            $static_headline = '';
        }

        return $static_headline;
    }

    public static function get_headline() {
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
            and a.type = 'H'
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

        $headline = '';

        foreach ($news as $o) {
            if($o->scroll == 'Y'){
                $headline .= '<marquee behavior="scroll" direction="left" onmouseover="this.stop();"onmouseout="this.start();">';
                $headline .= '<div class="reset-this" style="display:inline-block; margin-left:150px;">';
                $headline .= $o->body;
                $headline .= '</div>';
                $headline .= '</marquee>';
            }elseif($o->scroll == 'N' || $o->scroll == ''){
                $headline .= '<div class="reset-this" style="display:inline-block; margin-left:3px;">';
                $headline .= $o->body;
                $headline .= '</div>';
            }
        }

//        foreach ($news as $o) {
//            $headline .= '<div class="reset-this" style="display:inline-block; margin-left:150px;">';
//            $headline .= $o->body;
//            $headline .= '</div>';
//        }

        if (empty($headline)) {
            $headline .= '<marquee behavior="scroll" direction="left" onmouseover="this.stop();"onmouseout="this.start();">';
            $headline .= '<div class="reset-this" style="display:inline-block; margin-left:150px;">';
            $headline .= 'Welcome to SoftPayPlus.com.';
            $headline .= '</div>';
            $headline .= '</marquee>';
        }

        return $headline;
    }

    public static function get_headline_only_sub() {
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
            and a.type = 'D'
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

        $headline = '';

        foreach ($news as $o) {
            if($o->scroll == 'Y'){
                $headline .= '<marquee behavior="scroll" direction="left" onmouseover="this.stop();"onmouseout="this.start();">';
                $headline .= '<div class="reset-this" style="display:inline-block; margin-left:150px;">';
                $headline .= $o->body;
                $headline .= '</div>';
                $headline .= '</marquee>';
            }elseif($o->scroll == 'N' || $o->scroll == ''){
                $headline .= '<div class="reset-this" style="display:inline-block; margin-left:150px;">';
                $headline .= $o->body;
                $headline .= '</div>';
            }
        }

//        if (empty($headline)) {
//            $headline = 'Welcome to SoftPayPlus.com.';
//        }

        return $headline;
    }

    public static function get_news_marketplace() {
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
            and a.type = 'M'
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
        ", [
            'login_path' => $login_account->path,
            'login_path2' => $login_account->path,
            'login_account_type' => $login_account->type
        ]);

        $headline = '';

        foreach ($news as $o) {
            $headline .= '<div class="reset-this" style="display:inline-block; margin-left:150px;">';
            $headline .= $o->body;
            $headline .= '</div>';
        }

        if (empty($headline)) {
            $headline = 'Welcome to SoftPayPlus.com.';
        }

        return $headline;
    }

    public static function log($ident, $msg = '') {
        Log::info($ident, [
            'PATH' => Request::path(),
            'MSG' => str_replace("\n", "", var_export($msg, true)),
            'UNIQUE_ID' => isset($_SERVER['UNIQUE_ID']) ? $_SERVER['UNIQUE_ID'] : ''
        ]);
    }

    public static function show_error($column) {

        $errors = Session::get('errors', new \Illuminate\Support\MessageBag);

        $html =  '<strong><span class="help-block">';
        $html .= '  ' . $errors->has($column) ? (!empty($errors->first($column)) ? $errors->first($column) : '') : '' . '';
        $html .= '</span></strong>';

        return $html;
    }

    public static function mask_pin($pin) {
        $length = strlen($pin);
        $head = substr($pin, 0, 2);
        $trail = substr($pin, -2);

        $mask = str_pad('', $length - 4, '*', STR_PAD_LEFT);

        return $head . $mask . $trail;
    }

    public static function update_balance() {
        try {

            $balance = PaymentProcessor::get_limit_string(Auth::user()->account_id);
            Session::put('sub-agent-balance', $balance);

        } catch (\Exception $ex) {
            $msg = ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Helper::update_balance() error', $msg);
        }
    }

    public static function get_last_week_billing() {
        try {

            $user = Auth::user();
            if (empty($user)) {
                throw new \Exception('Your session has been expired. Please login again.');
            }

            $bill = Billing::where('account_id', $user->account_id)
                ->where('bill_date', Carbon::today()->startOfWeek())
                ->first();

            if (empty($bill)) {
                return 0;
            }

            Helper::log('#### Last Week Billing ###', [
                'account_id' => $user->account_id,
                'ending_balance' => $bill->ending_balance,
                'bill' => $bill
            ]);

            return $bill->ending_balance;

        } catch (\Exception $ex) {
            $msg = ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Helper::get_balance() error', $msg);
            return 0;
        }
    }

    public static function get_balance() {
        try {

            $balance = Session::get('sub-agent-balance');
            if (!isset($balance) || true) {
                $balance = PaymentProcessor::get_limit_string(Auth::user()->account_id);
                Session::put('sub-agent-balance', $balance);
            }

            return $balance;

        } catch (\Exception $ex) {
            $msg = ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Helper::get_balance() error', $msg);
            return 0;
        }
    }

    public static function get_consignment_balance() {
        try {
            $user = Auth::user();
            if (empty($user)) {
                return 0;
            }

            $account_id = $user->account_id;
            $balance = Session::get('consignment-balance-' . $account_id);
            if (!isset($balance) || true) {
                $balance = ConsignmentProcessor::get_balance($account_id);
                Session::put('consignment-balance-' . $account_id, $balance);
            }

            return $balance;

        } catch (\Exception $ex) {
            $msg = ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Helper::get_consignment_balance() error', $msg);
            return 0;
        }
    }

    public static function get_consignment_vendor_balance() {
        try {
            $user = Auth::user();
            if (empty($user)) {
                return 0;
            }

            $account_id = $user->account_id;
            $balance = Session::get('consignment-balance-' . $account_id);
            if (!isset($balance) || true) {
                $balance = ConsignmentVendor::get_balance($account_id);
                if ($balance != 0) {
                    Session::put('consignment-vendor-balance-' . $account_id, $balance);
                }
            }

            return $balance;

        } catch (\Exception $ex) {
            $msg = ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Helper::get_consignment_vendor_balance() error', $msg);
            return 0;
        }
    }

    public static function arrayPaginator($array, $request)
    {
        $page = Input::get('page', 1);
        $perPage = 10;
        $offset = ($page * $perPage) - $perPage;

        return new LengthAwarePaginator(array_slice($array, $offset, $perPage, true), count($array), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]);
    }

    public static function arrayPaginator_20($array, $request)
    {
        $page = Input::get('page', 1);
        $perPage = 20;
        $offset = ($page * $perPage) - $perPage;

        return new LengthAwarePaginator(array_slice($array, $offset, $perPage, true), count($array), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]);
    }

    public static function addPromotion($vr_id) {

        try {
            $m_commission = 0;
            $d_commission = 0;

            $vr = VRRequest::find($vr_id);

            if (empty($vr)) {
                return 'Invalid VR ID provided';
            }

            $account_id = $vr->account_id;
            $account_info = Account::find($account_id);

            $acct_type = $account_info->type;

            if($acct_type == 'S') {

                ## Get vr request product info
                $vrprod = VRRequestProduct::where('vr_id', $vr_id)->get();

                if (!empty($vrprod)) {
                    foreach ($vrprod as $o) {
                        $ret = VRProductPrice::get_price_by_account($account_id,$o->prod_id);

                        if(!empty($ret)){
                            $m_commission += $ret->m_commission * $o->qty;
                            $d_commission += $ret->d_commission * $o->qty;
                        }else{
                            $prod = VRProduct::find($o->prod_id);
                            if (!empty($prod)) {
                                $m_commission += $prod->master_commission * $o->qty;
                                $d_commission += $prod->distributor_commission * $o->qty;
                            }
                        }
                    }
                }

                $acct = Account::find($vr->account_id);
                if (empty($acct)) {
                    return 'Invalid Account ID provided';
                }

                ## Add commission in the promotion table

                # master commission
                if ($m_commission > 0) {
                    $prmt_master = new Promotion();
                    $prmt_master->type = 'C';
                    $prmt_master->category_id = 1; // V.R. Product Order Commission
                    $prmt_master->account_id = $acct->master_id; // master id
                    $prmt_master->amount = $m_commission;
                    $prmt_master->created_by = Auth::user()->user_id;
                    $prmt_master->cdate = Carbon::now();
                    $prmt_master->save();

                    $ret = VRRequestProduct::where('vr_id', $vr_id)->update([
                        'master_promotion_id' => $prmt_master->id
                    ]);

                    if ($ret < 1) {
                        Helper::send_mail('it@jjonbp.com', '[PM][' . env('APP_ENV') . '] Failed to update V.R promotion IDs', $vr_id);
                    }
                }

                # distributor commission
                $distributor = Account::where('id', $acct->parent_id)->where('type', 'D')->first();
                if ($d_commission > 0) {
                    $prmt_distributor = new Promotion();
                    $prmt_distributor->type = 'C';
                    $prmt_distributor->category_id = 1; // V.R. Product Order Commission
                    $prmt_distributor->account_id = !empty($distributor) ? $distributor->id : $acct->master_id; //distributor id
                    $prmt_distributor->amount = $d_commission;
                    $prmt_distributor->created_by = Auth::user()->user_id;
                    $prmt_distributor->cdate = Carbon::now();
                    $prmt_distributor->save();

                    $ret = VRRequestProduct::where('vr_id', $vr_id)->update([
                        'distributor_promotion_id' => $prmt_distributor->id
                    ]);

                    if ($ret < 1) {
                        Helper::send_mail('it@jjonbp.com', '[PM][' . env('APP_ENV') . '] Failed to update V.R promotion IDs', $vr_id);
                    }
                }
            }elseif ($acct_type == 'D'){

                ## Get vr request product info
                $vrprod = VRRequestProduct::where('vr_id', $vr_id)->get();

                if (!empty($vrprod)) {
                    foreach ($vrprod as $o) {
                        $ret = VRProductPrice::get_price_by_account($account_id,$o->prod_id);

                        if(!empty($ret)){
                            $m_commission += $ret->m_commission * $o->qty;
                        }else{
                            $prod = VRProduct::find($o->prod_id);
                            if (!empty($prod)) {
                                $m_commission += $prod->master_commission * $o->qty;
                            }
                        }
                    }
                }

                $acct = Account::find($vr->account_id);
                if (empty($acct)) {
                    return 'Invalid Account ID provided';
                }

                ## Add commission in the promotion table

                # master commission
                if ($m_commission > 0) {
                    $prmt_master = new Promotion();
                    $prmt_master->type = 'C';
                    $prmt_master->category_id = 1; // V.R. Product Order Commission
                    $prmt_master->account_id = $acct->master_id; // master id
                    $prmt_master->amount = $m_commission;
                    $prmt_master->created_by = Auth::user()->user_id;
                    $prmt_master->cdate = Carbon::now();
                    $prmt_master->save();

                    $ret = VRRequestProduct::where('vr_id', $vr_id)->update([
                        'master_promotion_id' => $prmt_master->id
                    ]);

                    if ($ret < 1) {
                        Helper::send_mail('it@jjonbp.com', '[PM][' . env('APP_ENV') . '] Failed to update V.R promotion IDs', $vr_id);
                    }
                }
            }

            return '';

        } catch (\Exception $ex) {

            return $ex->getMessage() . ' [' . $ex->getCode() . ']';
        }

    }

    public static function get_file_types() {
        return [
            'FILE_STORE_FRONT',
            'FILE_STORE_INSIDE',
            'FILE_W_9',
            'FILE_PR_SALES_TAX',
            'FILE_USUC',
            'FILE_TAX_ID',
            'FILE_BUSINESS_CERTIFICATION',
            'FILE_DEALER_AGREEMENT',
            'FILE_DRIVER_LICENSE',
            'FILE_VOID_CHECK',
            'FILE_ACH_DOC',
            'FILE_BANK_REFERENCE',
            'FILE_H2O_DEALER_FORM',
            'FILE_H2O_ACH',
            'FILE_ATT_AGREEMENT',
            'FILE_ATT_DRIVER_LICENSE',
            'FILE_ATT_BUSINESS_CERTIFICATION',
            'FILE_ATT_VOID_CHECK'
        ];
    }

    public static function check_threshold_activations_by_account($intime = 5) {
        $accounts = DB::select(" 
            select account_id
              from `transaction`
             where action in ('Activation', 'Port-In')
               and status <> 'F'
               and cdate >= :now - interval :intime minute
             group by account_id
        ", [
            'now' => Carbon::now(),
            'intime' => $intime
        ]);

        if (empty($accounts) || count($accounts) < 1) return null;

        $account_ids = '100000';
        foreach ($accounts as $a) {
            $account_ids .= ',' . $a->account_id;
        }

        $activations = DB::select("
            select 
                t.account_id,
                sum(case when t.min_pass < 60 then p_qty else 0 end) hourly_preload,
                sum(case when t.min_pass < 60 * 24 then p_qty else 0 end) daily_preload,
                sum(case when t.min_pass < 60 * 24 * 7 then p_qty else 0 end) weekly_preload,
                sum(case when t.min_pass < 60 * 24 * 30 then p_qty else 0 end) monthly_preload,
                sum(case when t.min_pass < 60 then r_qty else 0 end) hourly_regular,
                sum(case when t.min_pass < 60 * 24 then r_qty else 0 end) daily_regular,
                sum(case when t.min_pass < 60 * 24 * 7 then r_qty else 0 end) weekly_regular,
                sum(case when t.min_pass < 60 * 24 * 30 then r_qty else 0 end) monthly_regular,
                sum(case when t.min_pass < 60 then b_qty else 0 end) hourly_byos,
                sum(case when t.min_pass < 60 * 24 then b_qty else 0 end) daily_byos,
                sum(case when t.min_pass < 60 * 24 * 7 then b_qty else 0 end) weekly_byos,
                sum(case when t.min_pass < 60 * 24 * 30 then b_qty else 0 end) monthly_byos
              from
            (
            select 
                t.account_id, 
                case when s.is_byos = 'Y' then 1 else 0 end b_qty, 
                case when IfNull(s.type,'') != 'P' then 1 else 0 end r_qty, 
                case when s.type = 'P' then 1 else 0 end p_qty, 
                t.cdate, 
                TIMESTAMPDIFF(MINUTE,t.cdate, '" . Carbon::now() . "') as min_pass
              from transaction t left join stock_sim s on s.sim_serial = t.sim and s.product = t.product_id
             where t.action in ('Activation', 'Port-In')
               and t.status <> 'F'
               and t.account_id in (" . $account_ids . ")
            ) t
            group by t.account_id
        ");

        return $activations;
    }

    public static function check_threshold_limit_by_account($account_id) {

        $activations = DB::select("
            select 
                t.account_id,
                sum(case when t.min_pass < 60 then p_qty else 0 end) hourly_preload,
                sum(case when t.min_pass < 60 * 24 then p_qty else 0 end) daily_preload,
                sum(case when t.min_pass < 60 * 24 * 7 then p_qty else 0 end) weekly_preload,
                sum(case when t.min_pass < 60 * 24 * 30 then p_qty else 0 end) monthly_preload,
                sum(case when t.min_pass < 60 then r_qty else 0 end) hourly_regular,
                sum(case when t.min_pass < 60 * 24 then r_qty else 0 end) daily_regular,
                sum(case when t.min_pass < 60 * 24 * 7 then r_qty else 0 end) weekly_regular,
                sum(case when t.min_pass < 60 * 24 * 30 then r_qty else 0 end) monthly_regular,
                sum(case when t.min_pass < 60 then b_qty else 0 end) hourly_byos,
                sum(case when t.min_pass < 60 * 24 then b_qty else 0 end) daily_byos,
                sum(case when t.min_pass < 60 * 24 * 7 then b_qty else 0 end) weekly_byos,
                sum(case when t.min_pass < 60 * 24 * 30 then b_qty else 0 end) monthly_byos
              from
            (
            select 
                t.account_id, 
                case when s.is_byos = 'Y' then 1 else 0 end b_qty, 
                case when s.type <> 'P' then 1 else 0 end r_qty, 
                case when s.type = 'P' then 1 else 0 end p_qty, 
                t.cdate, 
                TIMESTAMPDIFF(MINUTE,t.cdate, '" . Carbon::now() . "') as min_pass
              from stock_sim s
              join transaction t on s.sim_serial = t.sim and s.product = t.product_id
             where t.action in ('Activation', 'Port-In')
               and t.status <> 'F'
               and t.account_id = :account_id
            ) t
             group by t.account_id
        ", [
            'account_id' => $account_id
        ]);

        if (empty($activations) || count($activations) == 0) {
            return [
              'code' => '0'
            ];
        }

        $activation = $activations[0];

        $activation_limit = AccountActivationLimit::where('account_id', $account_id)->first();
        if (empty($activation_limit)) {
            $activation_limit = AccountActivationLimit::where('account_id', 100000)->first();
        }

        ## Regular
        if ($activation->hourly_regular >= $activation_limit->hourly_regular) {
            $msg = '[Regular] - Recent one hour activations ' . $activation->hourly_regular . ' over ' . $activation_limit->hourly_regular;
        } else if ($activation->daily_regular >= $activation_limit->daily_regular) {
            $msg = '[Regular] - Recent one day activations ' . $activation->daily_regular . ' over ' . $activation_limit->daily_regular;
        } else if ($activation->weekly_regular >= $activation_limit->weekly_regular) {
            $msg = '[Regular] - Recent one week activations ' . $activation->weekly_regular . ' over ' . $activation_limit->weekly_regular;
        } else if ($activation->monthly_regular >= $activation_limit->monthly_regular) {
            $msg = '[Regular] - Recent one month activations ' . $activation->monthly_regular . ' over ' . $activation_limit->monthly_regular;
        } else if ($activation->hourly_byos >= $activation_limit->hourly_byos) {
            $msg = '[BYOS] - Recent one hour activations ' . $activation->hourly_byos . ' over ' . $activation_limit->hourly_byos;
        } else if ($activation->daily_byos >= $activation_limit->daily_byos) {
            $msg = '[BYOS] - Recent one day activations ' . $activation->daily_byos . ' over ' . $activation_limit->daily_byos;
        } else if ($activation->weekly_byos >= $activation_limit->weekly_byos) {
            $msg = '[BYOS] - Recent one week activations ' . $activation->weekly_byos . ' over ' . $activation_limit->weekly_byos;
        } else if ($activation->monthly_byos >= $activation_limit->monthly_byos) {
            $msg = '[BYOS] - Recent one month activations ' . $activation->monthly_byos . ' over ' . $activation_limit->monthly_byos;
        } else if ($activation->hourly_preload >= $activation_limit->hourly_preload) {
            $msg = '[Preload] - Recent one hour activations ' . $activation->hourly_preload . ' over ' . $activation_limit->hourly_preload;
        } else if ($activation->daily_preload >= $activation_limit->daily_preload) {
            $msg = '[Preload] - Recent one day activations ' . $activation->daily_preload . ' over ' . $activation_limit->daily_preload;
        } else if ($activation->weekly_preload >= $activation_limit->weekly_preload) {
            $msg = '[Preload] - Recent one week activations ' . $activation->weekly_preload . ' over ' . $activation_limit->weekly_preload;
        } else if ($activation->monthly_preload >= $activation_limit->monthly_preload) {
            $msg = '[Preload] - Recent one month activations ' . $activation->monthly_preload . ' over ' . $activation_limit->monthly_preload;
        }


        if (!empty($msg)) {
            helper::send_mail(
              'it@perfectmobileinc.com',
              '[PM][' . getenv('APP_ENV') . '] Threshold of Activations',
              'Account : (' . $account_id . ') ' . $msg
            );

            helper::send_mail(
              'tom@perfectmobileinc.com',
              '[PM][' . getenv('APP_ENV') . '] Threshold of Activations',
              'Account : (' . $account_id . ') ' . $msg
            );

            return [
              'code' => '-1',
              'msg' => $msg
            ];
        } else {
            return [
              'code' => '0'
            ];
        }

    }

    public static function check_att_tid($account) {
        if (!empty($account->att_tid)) return $account->att_tid;

//        $tid = \App\Model\ATTTID::where('state', $account->state)->orderBy('used_qty', 'asc')->first();
//        if (empty($tid)) return null;
//        return $tid->code;

        $account = Account::whereRaw('att_tid is not null')->orderBy(DB::raw('RAND()'))->first();
        if (empty($account)) return null;

        return $account->att_tid;
    }

    public static function get_att_tid($account) {
//        if (!empty($account->att_tid)) return $account->att_tid;

        return self::check_att_tid($account);

//        $tid = \App\Model\ATTTID::where('state', $account->state)->orderBy('used_qty', 'asc')->first();
//        if (empty($tid)) return null;
//
//        $tid->used_qty = $tid->used_qty + 1;
//        $tid->update();
//
//        return $tid->code;
    }

    public static function has_att_batch_authority() {
        $authority = Auth::user()->authority;

        if (empty($authority)) {
            return false;
        }

        if ($authority->auth_batch_rtr == 'Y' || $authority->auth_batch_sim_swap == 'Y' || $authority->auth_batch_plan_change == 'Y') {
            return true;
        } 

        return false;
    }

    public static function has_ecommerce_sim() {
        $sim_obj = StockSim::where('c_store_id', Auth::user()->account_id)->first();
        if (empty($sim_obj)) return false;

        return true;
    }

    public static function get_byos_activation_count($account_id, $sdate, $edate) {
        $count = Transaction::join('stock_sim', 'transaction.sim', '=', 'stock_sim.sim_serial')
            ->where('transaction.account_id', $account_id)
            ->whereIn('transaction.action', ['Activation', 'Port-In'])
            ->whereRaw("transaction.status not in ('V', 'F')")
            ->where('stock_sim.is_byos', 'Y')
            ->where('cdate', '>=', $sdate)
            ->where('cdate', '<=', $edate)
            ->count();

        if (empty($count)) return 0;

        return $count;
    }

    public static function get_activation_count_by_special_terms($account_id, $sdate, $edate, $terms) {
        $count = SpiffTrans::where('account_id', $account_id)
          ->where("type", 'S')
          ->whereRaw("special_id is not null")
          ->where('special_terms', $terms)
          ->where('cdate', '>=', $sdate)
          ->where('cdate', '<=', $edate)
          ->count();

        if (empty($count)) return 0;

        return $count;
    }

    public static function get_activation_count_by_special_id($account_id, $special_id) {
        $count = SpiffTrans::where('account_id', $account_id)
            ->where("type", 'S')
            ->where('special_id', $special_id)
            ->count();

        if (empty($count)) return 0;

        return $count;
    }

    public static function process_after_pay($invoice_number){

        $trans = Transaction::where('invoice_number', $invoice_number)->first();
        $product_id = $trans->product_id;

        if ($trans->action == 'RTR') {
            if ($product_id === 'WATTR') {
                ATT::process_after_pay($invoice_number);
            } elseif ($product_id === 'WFRUPR') {
                FreeUP::process_after_pay($invoice_number);
            } elseif ($product_id === 'WGENR' || $product_id === 'WGENOR' || $product_id === 'WGENTR' || $product_id === 'WGENTOR') {
                Gen::process_after_pay($invoice_number);
            } elseif ($product_id === 'WBMBAR' || $product_id === 'WBMRAR' || $product_id === 'WBMPAR' ||
                $product_id === 'WBMBA' || $product_id === 'WBMRA' || $product_id === 'WBMPA') {
                Boom::process_after_pay($invoice_number);
            }
        } else {
            if ($product_id == 'WGENA' || $product_id == 'WGENTA' || $product_id == 'WGENOA' || $product_id == 'WGENTOA') {
                GenActivation::process_after_pay($invoice_number);
            } elseif ($product_id == 'WFRUPA') {
                FreeUPActivation::process_after_pay($invoice_number);
            } elseif ($product_id == 'WBMBA') {
                BoomActivation::process_after_pay($invoice_number);
            }
        }

        return;
    }

    public static function has_activation_controller_auth($account_id, $carrier) {
        $ac = ActivationController::where('account_id', $account_id)->where('carrier', $carrier)->first();
        if (empty($ac)) {
            return false;
        }

        return true;
    }

    public static function insert_location_info($account_id, $address) {

        $name = urlencode($address);

        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$name&key=AIzaSyB7VSJl8gpGBqhHyBn53lGqDhSshrq50AY";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response);

        if($data->status == 'OK'){

            $results = $data->results;

            $lat = $results[0]->geometry->location->lat;
            $lng = $results[0]->geometry->location->lng;

            $account = Account::where('id', $account_id)->first();
            $account->lat = $lat;
            $account->lng = $lng;

            $account->update();
        }
    }

    public static function generate_code($length) {

        //$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@$%&";
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

        $referral_code = '';
        for ($i = 0; $i < $length; $i++) {
            $referral_code .= $chars{mt_rand(0, strlen($chars)-1)};
        }
        return $referral_code;
    }

    public static function get_news_target_type($news_id) {
        $news = NewsAccountType::where('news_id', $news_id)->get();
        $targets = '';
        if($news){
            foreach ($news as $n) {
                $targets .= $n->account_type . ' ';
            }
        }
        return $targets;
    }

    public static function get_included_account_id($news_id) {
        $news = NewsAccountId::where('news_id', $news_id)->where('type', 'I')->get();
        $accounts = '';
        if($news){
            foreach ($news as $n) {
                $accounts .= $n->account_id . ' ';
            }
        }
        return $accounts;
    }

    public static function get_excluded_account_id($news_id) {
        $news = NewsAccountId::where('news_id', $news_id)->where('type', 'E')->get();
        $accounts = '';
        if($news){
            foreach ($news as $n) {
                $accounts .= $n->account_id . ' ';
            }
        }
        return $accounts;
    }

    public static function check_parents_product($account, $product) {
        $acct = Account::where('id', $account)->first();

        $p_acct_id = $acct->parent_id;
        $m_acct_id = $acct->master_id;

        $p_acct = Account::where('id', $p_acct_id)->first();
        $m_acct = Account::where('id', $m_acct_id)->first();

        switch ($product) {
            case 'WATTA':
            case 'WATTPVA':
            case 'WATTDO':
                if($p_acct->act_att == 'Y' && $m_acct->act_att == 'Y') {
                    return 'Y';
                }else{
                    return 'N';
                }
                break;
            case 'WFRUPA':
                if($p_acct->act_freeup == 'Y' && $m_acct->act_freeup == 'Y') {
                    return 'Y';
                }else{
                    return 'N';
                }
                break;
            case 'WGENA':
            case 'WGENOA':
            case 'WGENTA':
            case 'WGENTOA':
                if($p_acct->act_gen == 'Y' && $m_acct->act_gen == 'Y') {
                    return 'Y';
                }else{
                    return 'N';
                }
                break;
            case 'WLYCA':
                if($p_acct->act_lyca == 'Y' && $m_acct->act_lyca == 'Y') {
                    return 'Y';
                }else{
                    return 'N';
                }
                break;
            case 'WH2OM':
            case 'WH2OB':
            case 'WH2OP':
            case 'WEZM':
            case 'WEZP':
                if($p_acct->act_h2o == 'Y' && $m_acct->act_h2o == 'Y') {
                    return 'Y';
                }else{
                    return 'N';
                }
                break;
            case 'WLBTA':
                if($p_acct->act_liberty == 'Y' || $m_acct->act_liberty == 'Y'){
                    return 'Y';
                }else{
                    return 'N';
                }
                break;
            case 'WBMBA':
            case 'WBMRA':
            case 'WBMPA':
                if($p_acct->act_boom == 'Y' && $m_acct->act_boom == 'Y') {
                    return 'Y';
                }else{
                    return 'N';
                }
                break;
            default:
                break;
        }
        return 'N';
    }

    public static function get_min_month($obj, $account, $column_name){ // sim_obj or esn_obj

        if(!empty($obj) && !empty($obj->rtr_month)){
            $allowed_months = explode('|', empty($obj->rtr_month) ? '1|2|3|4|5|6|7|8|9|10|11|12' : $obj->rtr_month);
        }else{
            if(!empty($account->$column_name)){
                $min_month = $account->$column_name;
            }else{
                $p_id = $account->parent_id;
                $m_id = $account->master_id;
                $r_id = 100000;
                if($p_id == $m_id){ // M -> S
                    $m_account = Account::where('id', $m_id)->first();
                    $min_month = $m_account->$column_name;
                }else{  // D -> S
                    $d_account = Account::where('id', $p_id)->first();
                    $min_month = $d_account->$column_name;
                    if(empty($min_month)){
                        $m_account = Account::where('id', $m_id)->first();
                        $min_month = $m_account->$column_name;
                    }
                }
                if(empty($min_month)){ // none setting on all accounts, check Root
                    $r_account = Account::where('id', $r_id)->first();
                    $min_month = $r_account->$column_name;
                    if(empty($min_month)) {
                        $min_month = 1;
                    }
                }
            }

            $allowed_months = array();
            for($i = $min_month; $i <= 6; $i++) {
                $allowed_months[] = $i;
            }
        }
        return $allowed_months;
    }

    public static function check_account_spiff_template($account, $column_name){
        if($account->$column_name == 'Y'){
            $hold_spiff = 'Y';
        }else{
            $p_id = $account->parent_id;
            $m_id = $account->master_id;
            if($p_id == $m_id){ // M -> S
                $m_account = Account::where('id', $m_id)->first();
                $hold_spiff = $m_account->$column_name;
            }else{  // M -> D -> S
                $d_account = Account::where('id', $p_id)->first();
                $hold_spiff = $d_account->$column_name;
                if(empty($hold_spiff) || $hold_spiff != 'Y'){
                    $m_account = Account::where('id', $m_id)->first();
                    $hold_spiff = $m_account->$column_name;
                }
            }
        }
        return ($hold_spiff == 'Y') ? true : false;
    }

    public static function get_hold_spiff_by_product($product_id){

        switch ($product_id) {
            case 'WATTA':
            case 'WATTPVA':
            case 'WATTDO':
                return 'att_hold_spiff';
                break;
            case 'WFRUPA':
                return 'freeup_hold_spiff';
                break;
            case 'WGENA':
            case 'WGENOA':
            case 'WGENTA':
            case 'WGENTOA':
                return 'gen_hold_spiff';
                break;
            case 'WLYCA':
                return 'lyca_hold_spiff';
                break;
            case 'WH2OM':
            case 'WH2OB':
            case 'WH2OP':
            case 'WEZM':
            case 'WEZP':
                return 'lyca_hold_spiff';
                break;
            case 'WLBTA':
                return 'liberty_hold_spiff';
                break;
            case 'WBMRA':
            case 'WBMPA':
            case 'WBMPOA':
            case 'WBMBA':
                return 'boom_hold_spiff';
                break;
            default:
                return '';
                break;
        }
    }
}














