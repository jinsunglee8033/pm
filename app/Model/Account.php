<?php

namespace App\Model;

use App\Lib\Helper;
use App\User;
use Illuminate\Database\Eloquent\Model;
use DB;
use Log;

class Account extends Model
{
    protected $table = 'accounts';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function type_name() {
        switch ($this->type) {
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
                return $this->type;
        }
    }

    public function status_name() {
        switch ($this->status) {
            case 'A':
                return 'Active';
            case 'B':
                return 'Become Dealer';
            case 'H':
                return 'On-Hold';
            case 'C':
                return 'Closed';
            case 'P':
                return 'Pre-Auth';
            case 'F':
                return 'Failed Payment';
            default:
                return $this->status;
        }
    }

    public function file($type) {
        $file = AccountFile::where('account_id', $this->attributes['id'])
            ->where('type', $type)->first();

        return $file;
    }

    public function file_att($type) {
        $file = AccountFileAtt::where('account_id', $this->attributes['id'])
            ->where('type', $type)->first();

        return $file;
    }

    public function doc_status_name() {
        switch ($this->attributes['doc_status']) {
            case 2:
                return 'Verizon Sent';
            case 3:
                return 'All Ready';
            default:

                if ($this->is_verizon_ready()) {
                    return 'Verizon Ready';
                }

                return '';
        }
    }

    public function is_verizon_ready() {
        $ret = DB::select("
            select if((
                        select count(*)
                        from account_files a
                        where a.account_id = accounts.id
                        and (
                            a.type in (
                                'FILE_STORE_FRONT',
                                'FILE_STORE_INSIDE'
                            ) or 
                            ( a.type = 'FILE_DEALER_AGREEMENT' and a.signed = 'Y')
                        )
                    ) = 3 /*and ifnull(doc_status, '') = ''*/, 'Y', 'N') as verizon_ready
            from accounts
            where id = :id
        ", [
            'id' => $this->attributes['id']
        ]);

        return count($ret) > 0 && $ret[0]->verizon_ready == 'Y';
    }

    public function h2o_doc_status_name() {
        switch ($this->attributes['doc_status_h2o']) {
            case 2:
                return 'H2O Sent';
            case 3:
                return 'All Ready';
            default:

                if ($this->is_h2o_ready()) {
                    return 'H2O Ready';
                }

                return '';
        }
    }

    public function is_h2o_ready() {
        $ret = DB::select("
            select if((
                        select count(*)
                        from account_files a
                        where a.account_id = accounts.id
                        and (
                            ( a.type = 'FILE_H2O_DEALER_FORM' and length(a.data) > 0 )
                            or 
                            (a.type = 'FILE_H2O_ACH' and length(a.data) > 0 )
                        )
                    ) = 2 , 'Y', 'N') as h2o_ready
            from accounts
            where id = :id
        ", [
            'id' => $this->attributes['id']
        ]);

        return count($ret) > 0 && $ret[0]->h2o_ready == 'Y';
    }



    public function att_doc_status_name() {
        switch ($this->attributes['doc_status_att']) {
            case 1:
                return 'ATT Ready';
            case 2:
                return 'ATT Sent';
            case 3:
                return 'ATT All Doc Ready';
            case 4:
                return 'All Partial Sent';
            case 5:
                return 'Pending';
            case 7:
                return 'Denied';
            case 8:
                return 'Call';
            default:
                if ($this->is_att_ready()) {
                    return 'ATT Ready';
                }
                return '';
        }
    }

    public function is_att_ready() {
        $ret = DB::select("
            select if((
                        select count(*)
                        from account_files_att a
                        where a.account_id = accounts.id
                        and ( a.type = 'FILE_ATT_AGREEMENT' and a.data is not null)
                    ) = 1, 'Y', 'N') as att_ready
            from accounts
            where id = :id
        ", [
            'id' => $this->attributes['id']
        ]);

        return count($ret) > 0 && $ret[0]->att_ready == 'Y';
    }

    public function getStateNameAttribute() {
        $state = State::find($this->attributes['state']);
        if (empty($state)) {
            return $this->attributes['state'];
        }

        return $state->name;

    }

    public function getRatePlanNameAttribute() {
        $rate_plan = RatePlan::find($this->attributes['rate_plan_id']);
        if (empty($rate_plan)) {
            return '';
        }

        return $rate_plan->name;
    }

    public function getRatePlanIdAttribute() {
        $rate_plan_id = $this->attributes['rate_plan_id'];
        if (is_null($rate_plan_id)) {
            return '';
        }

        return $rate_plan_id;
    }

    public function getParentAttribute() {
        $parent = Account::find($this->attributes['parent_id']);
        return $parent;
    }

    public function getMasterAttribute() {
        $master = Account::find($this->attributes['master_id']);
        return $master;
    }

    public static function getUserCountByAccount($account_id){
        $count = User::where('account_id', $account_id)->where('status','A')->count();
        return $count;
    }
}
