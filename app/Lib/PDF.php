<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 4/5/17
 * Time: 3:08 PM
 */

namespace App\Lib;

use FPDI;

class PDF extends FPDI {
    const DPI = 96;
    const MM_IN_INCH = 25.4;
    const A4_HEIGHT = 297;
    const A4_WIDTH = 210;
    // tweak these values (in pixels)
    const MAX_WIDTH = 700;
    const MAX_HEIGHT = 300;
    function pixelsToMM($val) {
        return $val * self::MM_IN_INCH / self::DPI;
    }
    function resizeToFit($imgFilename) {
        list($width, $height) = getimagesize($imgFilename);
        $widthScale = self::MAX_WIDTH / $width;
        $heightScale = self::MAX_HEIGHT / $height;
        $scale = min($widthScale, $heightScale);
        return array(
            round($this->pixelsToMM($scale * $width)),
            round($this->pixelsToMM($scale * $height))
        );
    }
    function centreImage($img, $y, $type) {
        list($width, $height) = $this->resizeToFit($img);
        // you will probably want to swap the width/height
        // around depending on the page's orientation
        $this->Image(
            $img,
            (self::A4_WIDTH - $width) / 2,
            #(self::A4_WIDTH - $height) / 2,
            $y,
            $width,
            $height,
            $type
        );
    }
}