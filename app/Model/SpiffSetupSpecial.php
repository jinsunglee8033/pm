<?php

namespace App\Model;

use App\Lib\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;



class SpiffSetupSpecial extends Model
{
    protected $table = 'spiff_setup_special';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public static function get_special_spiffs($product_id, $denom, $account_type, $account_id, $sim_obj, $esn_obj, $terms = []) {
        $special_spiffs = Array();

        if (!empty($sim_obj)) {
            if (in_array($sim_obj->special_spiff, ['N'])) {
                return $special_spiffs;
            }
        }
        if (!empty($esn_obj)) {
            if (in_array($esn_obj->special_spiff, ['N'])) {
                return $special_spiffs;
            }
        }

        $terms[] = 'referal';

    	$yesterday = \Carbon\Carbon::yesterday();
    	$tomorrow = \Carbon\Carbon::tomorrow();

    	$specials = SpiffSetupSpecial::where('product_id', $product_id)
    		->where('denom', $denom)
    		->where('account_type', $account_type)
    		->where('period_from', '<', $tomorrow)
    		->where('period_to', '>', $yesterday)
    		->get();

    	if (!empty($specials) && count($specials) > 0) {
    		foreach ($specials as $s) {
    		    $included = false;
    		    $excluded = false;

    		    if (empty(!$s->include)) {
    		        $include_accts = explode(',', $s->include);

    		        $tacct = Account::find($account_id);
    		        while($tacct->id != 100000) {
    		            if (in_array($tacct->id, $include_accts)) {
                            $included = true;
                            break;
                        }

                        $tacct = Account::find($tacct->parent_id);
                    }
                };

                if (empty(!$s->exclude)) {
                    $exclude_accts = explode(',', $s->exclude);

                    $tacct = Account::find($account_id);
                    while($tacct->id != 100000) {
                        if (in_array($tacct->id, $exclude_accts)) {
                            $excluded = true;
                            break;
                        }

                        $tacct = Account::find($tacct->parent_id);
                    }
                };

    			if (
    			     (empty($s->include) || $included) && (empty($s->exclude) || !$excluded)
                ) {
    			    ### CHECK MAX COUNT ###
                    $act_qty = Helper::get_activation_count_by_special_terms($account_id, $s->period_from, $s->period_to, $s->terms);
                    if (!empty($s->maxqty) && $act_qty >= $s->maxqty) {
                        continue;
                    }

    			    if (!empty($sim_obj)) {
                        if ($sim_obj->is_byos == 'Y') {
                            $byod_qty = Helper::get_byos_activation_count($account_id, $s->period_from, $s->period_to);
                            if (empty($s->maxqty) || $s->maxqty > $byod_qty) {
                                $terms[] = 'Byos';
                            }
                        } else {
                            switch ($account_type) {
                                case 'S':
                                    if ($sim_obj->nonbyos_spiff_r !== 'N') $terms[] = 'NonBYOS';
                                    break;
                                case 'D':
                                    if ($sim_obj->nonbyos_spiff_d !== 'N') $terms[] = 'NonBYOS';
                                    break;
                                case 'M':
                                    if ($sim_obj->nonbyos_spiff_m !== 'N') $terms[] = 'NonBYOS';
                                    break;
                            }
                        }
                    }

                    if (!empty($esn_obj) && $esn_obj->is_byod !== 'Y' && $esn_obj->status == 'A') {
    			        switch ($account_type) {
                            case 'S':
                                if ($esn_obj->nonbyod_spiff_r !== 'N') $terms[] = 'NonBYOD';
                                break;
                            case 'D':
                                if ($esn_obj->nonbyod_spiff_d !== 'N') $terms[] = 'NonBYOD';
                                break;
                            case 'M':
                                if ($esn_obj->nonbyod_spiff_m !== 'N') $terms[] = 'NonBYOD';
                                break;
                        }
                    }

                    $include_ids = null;
    			    if (!empty($sim_obj) && !empty($sim_obj->special_spiff_ids)) {
                        $include_ids = explode(',', $sim_obj->special_spiff_ids);
                    }
                    if (!empty($esn_obj) && !empty($esn_obj->special_spiff_ids)) {
                        $include_ids = explode(',', $esn_obj->special_spiff_ids);
                    }

                    if ($s->terms == 'StaticSIM') {
    			        if (!empty($include_ids) && in_array($s->id, $include_ids)) {
                            $special_spiffs[] = [
                                'special_id' => $s->id,
                                'name'  => $s->name,
                                'spiff' => $s->spiff,
                                'terms' => $s->terms,
                                'pay_to' => $s->pay_to,
                                'max_qty' => $s->maxqty,
                                'pay_to_amt' => $s->pay_to_amt
                            ];
                        }

                    } else {
                        if (empty($include_ids) || in_array($s->id, $include_ids)) {
                            if (empty($s->terms)) {
                                $special_spiffs[] = [
                                  'special_id' => $s->id,
                                  'name'  => $s->name,
                                  'spiff' => $s->spiff,
                                  'terms' => $s->terms,
                                  'pay_to' => $s->pay_to,
                                  'max_qty' => $s->maxqty,
                                  'pay_to_amt' => $s->pay_to_amt
                                ];
                            } else {
                                if (in_array($s->terms, $terms)) {
                                    $special_spiffs[] = [
                                      'special_id' => $s->id,
                                      'name'  => $s->name,
                                      'spiff' => $s->spiff,
                                      'terms' => $s->terms,
                                      'pay_to' => $s->pay_to,
                                      'max_qty' => $s->maxqty,
                                      'pay_to_amt' => $s->pay_to_amt
                                    ];
                                }
                            }
                        }
                    }
    			}
    		}
    	}

    	return $special_spiffs;
    }

    public static function give_special_spiff($trans, $phone, $product_id, $denom, $account_type, $account_id, $sim_obj, $esn_obj = null) {
        $terms = array();
        if ($trans->action == 'Port-In') {
            $terms[] = 'Port';
        }

        ### get_special_spiffs($product_id, $denom, $account_type, $account_id, $sim_obj, $esn_obj, $terms = [])
    	$specials = self::get_special_spiffs($product_id, $denom, $account_type, $account_id, $sim_obj, $esn_obj, $terms);

        if (!empty($specials) && count($specials) > 0) {
            foreach ($specials as $ss) {
                $spiff_trans = new SpiffTrans;
                $spiff_trans->trans_id      = $trans->id;
                $spiff_trans->phone         = $phone;
                $spiff_trans->type          = 'S';
                $spiff_trans->account_id    = $account_id;
                $spiff_trans->product_id    = $product_id;
                $spiff_trans->denom         = $denom;
                $spiff_trans->account_type  = $account_type;
                $spiff_trans->spiff_month   = 1;
                $spiff_trans->spiff_amt     = $ss['spiff'];
                $spiff_trans->orig_spiff_amt = 0;
                $spiff_trans->special_id    = $ss['special_id'];
                $spiff_trans->special_terms = $ss['terms'];
                $spiff_trans->created_by    = 'system';
                $spiff_trans->note          = $ss['name'];
                $spiff_trans->cdate         = Carbon::now();
                $spiff_trans->save();

                if ($ss['terms'] == 'referal' && !empty($ss['pay_to']) && !empty($ss['pay_to_amt'])) {
                    $promotion = new Promotion();
                    $promotion->type        = 'C';
                    $promotion->category_id = 4;
                    $promotion->account_id  = $ss['pay_to'];
                    $promotion->amount      = $ss['pay_to_amt'];
                    $promotion->notes       = 'Referal::Account(' . $account_id . ')::Order(' . $trans->id . ')';
                    $promotion->cdate       = Carbon::now();
                    $promotion->created_by  = 'system';
                    $promotion->save();
                }
            }
        }
    }
}
