<?php

namespace App\Model;

use App\Lib\Helper;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class News extends Model
{
    protected $table = 'news';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public static function getTypeNameAttribute($type) {
//        $type = $this->attributes['type'];
        switch ($type) {
            case 'N':
                return 'News';
            case 'S':
                return 'Static Headline';
            case 'D':
                return 'Static Headline (2nd)';
            case 'H':
                return 'Headline';
            case 'P':
                return 'Promotion';
            case 'R':
                return 'Reminder';
            case 'F':
                return 'Reminder (Refill Section)';
            case 'G':
                return 'Reminder (Pin Section)';
            case 'O':
                return 'Over Activation';
            case 'I':
                return 'Digital e-Marketing';
            case 'A':
                return 'Advertise';
            case 'T':
                return 'Task';
            case 'W':
                return 'Follow-Ups';
            case 'U':
                return 'Documents';
            case 'C':
                return 'Communications';
            default:
                return $type;
        }
    }

    public static function getStatusNameAttribute($status) {

        $html = '';
//        if (!is_null($this->edate) && $this->edate < Carbon::today()->toDateString()) {
//            $html .= '<span style="color:red;font-weight:bold;">Expired</span>';
//        } else if (!$this->hasExpired && $this->status == 'A') {
//            $html .= '<span style="color:green;font-weight:bold;">In Progress</span>';
//        }

        switch ($status) {
            case 'A':
                $html .= (empty($html) ? '' : ' - ') . '<span style="color:green;">Active</span>';
                break;
            case 'H':
                $html .= (empty($html) ? '' : ' - ') . '<span style="color:orange;">On Hold</span>';
                break;
            case 'C':
                $html .= (empty($html) ? '' : ' - ') . '<span style="color:red;">Closed</span>';
                break;
            case 'V':
                $html .= (empty($html) ? '' : ' - ') . '<span style="color:red;">Voided</span>';
                break;
            case 'E':
                $html .= (empty($html) ? '' : ' - ') . '<span style="color:red;font-weight:bold;">Expired</span>';
                break;
            default:
                $html .= (empty($html) ? '' : ' - ') . $status;
                break;
        }

        return $html;
    }


    public static function getStatusName($status) {
        $name = '';
        switch ($status) {
            case 'A':
                $name = 'Active';
                break;
            case 'H':
                $name = 'On Hold';
                break;
            case 'C':
                $name = 'Closed';
                break;
            case 'V':
                $name = 'Voided';
                break;
            case 'E':
                $name = 'Expired';
                break;
            default:
                $name = $status;
                break;
        }

        return $name;
    }

    public function getHasExpiredAttribute() {
        if (!is_null($this->edate) && $this->edate < Carbon::today()->toDateString()) {
            return true;
        }

        return false;
    }

    public function getAccountTypeNameAttribute() {
        switch ($this->account_type) {
            case 'L':
                return 'Root';
            case 'M':
                return 'Master';
            case 'D':
                return 'Distributor';
            case 'A':
                return 'Agent';
            case 'S':
                return 'Sub-Agent';
            default:
                return $this->account_type;
        }
    }

    public static function getAccountTypesByNews($new_id) {

        $types = NewsAccountType::where('news_id', $new_id)
            ->orderByRaw(' case when account_type = "L" then 1
                                when account_type = "M" then 2
                                when account_type = "D" then 3
                                when account_type = "S" then 4 
                                else 5 end asc ')->get();

        $icon = '';
        foreach ($types as $t){
            $type = $t->account_type;
            $icon .= Helper::get_hierarchy_img($type);
        }

        return $icon;
    }
}
