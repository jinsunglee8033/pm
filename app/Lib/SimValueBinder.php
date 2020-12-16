<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 1/16/18
 * Time: 11:39 AM
 */

namespace App\Lib;

use PHPExcel_Cell;
use PHPExcel_Cell_DataType;
use PHPExcel_Cell_IValueBinder;
use PHPExcel_Cell_DefaultValueBinder;

class SimValueBinder extends PHPExcel_Cell_DefaultValueBinder implements PHPExcel_Cell_IValueBinder
{
    public function bindValue(PHPExcel_Cell $cell, $value = null)
    {

        if (is_numeric($value))
        {
            $cell->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING2);

            return true;
        }



        // else return default behavior
        return parent::bindValue($cell, $value);
    }
}