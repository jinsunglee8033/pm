<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Carrier extends Model
{
    protected $table = 'carrier';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'name';

    public $incrementing = false;

    public static function get_logo_img_link($carrier){

        $result = '';

        switch($carrier) {
            case "AT&T":
                $result = '/img/rtr/AT&T.jpg';
                break;
            case "FreeUP":
            case "FREEUP":
                $result = '/img/rtr/FreeUP.jpg';
                break;
            case "GEN MOBILE":
                $result = '/img/rtr/GEN Mobile.jpg';
                break;
            case "H2O":
                $result = '/img/rtr/H2O.jpg';
                break;
            case "LIBERTY MOBILE":
                $result = '/img/rtr/libertyMobile.png';
                break;
            case "Lyca":
                $result = '/img/rtr/Lyca.jpg';
                break;
            case "PARTS":
                $result = '';
                break;
            case "SOFTPAYPLUS":
                $result = '';
                break;
            case "Tracfone":
                $result = '/img/rtr/Tracfone.jpg';
                break;
            case "VIBE":
                $result = '';
                break;
            case "Verizon":
            case "VERIZON":
                $result = '/img/rtr/Verizon.jpg';
                break;
            case "XFINITY":
                $result = '/img/rtr/Xfinity.png';
                break;
            default:
                $result = 'blank';
        }
        if($result == ''){
            $result = 'blank';
        }
        return $result;
    }
}
