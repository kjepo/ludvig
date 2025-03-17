<?php

/**
  *  LUDVIG: A PHP class for creating JPEG/PNG composites
  *
  *  (C) Kjell Post, kjell@irstafoto.se
  *
  *  Ludvig objects are created in one of two ways:
  *
  *  * $ludvig = Ludvig("foo.jpg"); // opens foo.jpg as a new image
  *  * $ludvig = Ludvig(width: 800, height: 500, background: "blue", dpi: 300); // creates a blank image
  *
  *  The "background" parameter is optional and defaults to white. Also, dpi is optional and defaults to 300.
  *
  *  Ludvig objects understands the following methods:
  *
  *  * $ludvig->image("fie.jpg", bbox: [x0,y0,x1,y1], align: Ludvig::ALIGN_CENTER, opacity: 50, border: true)
  *
  *  where bbox is the bounding box, by default ["0%", "0%", "100%", "100%"]
  *        align is one of: ALIGN_CENTER (default), ALIGN_TOP, ALIGN_BOTTOM, ALIGN_LEFT, ALIGN_RIGHT
  *        opacity is 0-100 (default 100)
  *        border is either true/false (default false)
  *
  *  * $ludvig->text("Fie foo fum", align: Ludvig::ALIGN_CENTER, x: "50%", y: 0, 
  *                 font: "Courier", fontsize: 36, textcolor: "red", maxwidth: "90%", linespc: 1.45)
  *
  *  where align is one of: ALIGN_CENTER (default), ALIGN_LEFT, ALIGN_RIGHT
  *        x/y is the start coordinate
  *        font is name of a TTF or OTF font which is found by looking recursively in the current directory
  *        fontsize is by default 2% of document's height
  *        textcolor is by default black
  *        maxwidth specifies maximum width of text: if it's too wide the fontsize is shrunk
  *        linespc is the line spacing (default 1.45)
  *
  *  * $ludvig->poly([x0, y0, x1, y1, ...], border: "black", fill: "gray", thickness: 1)
  *
  *  where (x0,y0), (x1,y1), ... are the corner points of the polygon
  *        border is by default black
  *        fill color is by default gray
  *        thickness is by default 1px
  *
  *  *  $ludvig->output("output.jpg")
  *
  *  If a filename (JPEG or PNG) is specified, the output is written to that.
  *  If no filename is specified, the image is served to the browser.
  *  This method returns a HTML <img> link, which can be used to display a preview of the result.
  *
  *  Normally, measurements can be absolute numbers (pixels), or some unit:
  *
  *    "20%" means 20% of current width/height
  *    "20 cm" means 20 cm
  *    "20 in" means 20 in
  *    "20 mm" means 20 mm
  *
  *  Note that % can not be used when creating an image and that in/cm/mm are based off the document's dpi.
  *
  *  Colors can be either pre-defined names like "white", "black", etc (see list of 147 names below)
  *  or 6 hex digits, optionally prefixed with "#": for instance "000000" (or "#000000") is black.
  *
  *  To do:
  *    allow three hex digits for color
  *    poly_border_color and poly_fill_color as state variables?                
  *    $ludvig->line(x0,y0,x1,y1,color,thickness)
  *    $ludvig->circle(x0,y0,r,border,fill,thickness)
  */

//ini_set('display_errors', 'On');
//ini_set('max_execution_time', 600);

class Ludvig {
    public $doc;
    public $dpi;
    public $width, $height;
    public $font;
    public $fontsize;
    public $align;
    public $textcolor;
    public $maxwidth;
    public $linespc;
    public $x0, $y0, $x1, $y1;
    public $xp, $yp;

    const JPEG_EXT = array('jpg', 'JPG', 'jpeg', 'JPEG');
    const PNG_EXT = array('png', 'PNG');
    const DEFAULT_FONT = "GoNotoCurrent";
    const FONT_EXT = "/^.*\.(ttf|TTF|otf|OTF)$/";

    const ALIGN_CENTER = 1;
    const ALIGN_TOP = 2;
    const ALIGN_BOTTOM = 3;
    const ALIGN_LEFT = 4;
    const ALIGN_RIGHT = 5;

    const DEFAULT_LINESPC = 1.45;

    private function dmp($data) {
        echo "<pre>" . print_r($data, true) . "</pre>";
    }
    
    private function console($data) {
        $output = print_r($data, true);
        echo "<script>console.log('Debug: " . $output . "' );</script>";
    }

    private function debug() {
        $this->dmp("width={$this->width}, height={$this->height}, dpi={$this->dpi}");
        $this->dmp("x0={$this->x0},y0={$this->y0},x1={$this->x1},y1={$this->y1}");
    }

    private function color_name_to_hex($color_name) {
        // standard 147 HTML color names
        $colors = array(
            'aliceblue'=>'F0F8FF',
            'antiquewhite'=>'FAEBD7',
            'aqua'=>'00FFFF',
            'aquamarine'=>'7FFFD4',
            'azure'=>'F0FFFF',
            'beige'=>'F5F5DC',
            'bisque'=>'FFE4C4',
            'black'=>'000000',
            'blanchedalmond '=>'FFEBCD',
            'blue'=>'0000FF',
            'blueviolet'=>'8A2BE2',
            'brown'=>'A52A2A',
            'burlywood'=>'DEB887',
            'cadetblue'=>'5F9EA0',
            'chartreuse'=>'7FFF00',
            'chocolate'=>'D2691E',
            'coral'=>'FF7F50',
            'cornflowerblue'=>'6495ED',
            'cornsilk'=>'FFF8DC',
            'crimson'=>'DC143C',
            'cyan'=>'00FFFF',
            'darkblue'=>'00008B',
            'darkcyan'=>'008B8B',
            'darkgoldenrod'=>'B8860B',
            'darkgray'=>'A9A9A9',
            'darkgreen'=>'006400',
            'darkgrey'=>'A9A9A9',
            'darkkhaki'=>'BDB76B',
            'darkmagenta'=>'8B008B',
            'darkolivegreen'=>'556B2F',
            'darkorange'=>'FF8C00',
            'darkorchid'=>'9932CC',
            'darkred'=>'8B0000',
            'darksalmon'=>'E9967A',
            'darkseagreen'=>'8FBC8F',
            'darkslateblue'=>'483D8B',
            'darkslategray'=>'2F4F4F',
            'darkslategrey'=>'2F4F4F',
            'darkturquoise'=>'00CED1',
            'darkviolet'=>'9400D3',
            'deeppink'=>'FF1493',
            'deepskyblue'=>'00BFFF',
            'dimgray'=>'696969',
            'dimgrey'=>'696969',
            'dodgerblue'=>'1E90FF',
            'firebrick'=>'B22222',
            'floralwhite'=>'FFFAF0',
            'forestgreen'=>'228B22',
            'fuchsia'=>'FF00FF',
            'gainsboro'=>'DCDCDC',
            'ghostwhite'=>'F8F8FF',
            'gold'=>'FFD700',
            'goldenrod'=>'DAA520',
            'gray'=>'808080',
            'green'=>'008000',
            'greenyellow'=>'ADFF2F',
            'grey'=>'808080',
            'honeydew'=>'F0FFF0',
            'hotpink'=>'FF69B4',
            'indianred'=>'CD5C5C',
            'indigo'=>'4B0082',
            'ivory'=>'FFFFF0',
            'khaki'=>'F0E68C',
            'lavender'=>'E6E6FA',
            'lavenderblush'=>'FFF0F5',
            'lawngreen'=>'7CFC00',
            'lemonchiffon'=>'FFFACD',
            'lightblue'=>'ADD8E6',
            'lightcoral'=>'F08080',
            'lightcyan'=>'E0FFFF',
            'lightgoldenrodyellow'=>'FAFAD2',
            'lightgray'=>'D3D3D3',
            'lightgreen'=>'90EE90',
            'lightgrey'=>'D3D3D3',
            'lightpink'=>'FFB6C1',
            'lightsalmon'=>'FFA07A',
            'lightseagreen'=>'20B2AA',
            'lightskyblue'=>'87CEFA',
            'lightslategray'=>'778899',
            'lightslategrey'=>'778899',
            'lightsteelblue'=>'B0C4DE',
            'lightyellow'=>'FFFFE0',
            'lime'=>'00FF00',
            'limegreen'=>'32CD32',
            'linen'=>'FAF0E6',
            'magenta'=>'FF00FF',
            'maroon'=>'800000',
            'mediumaquamarine'=>'66CDAA',
            'mediumblue'=>'0000CD',
            'mediumorchid'=>'BA55D3',
            'mediumpurple'=>'9370D0',
            'mediumseagreen'=>'3CB371',
            'mediumslateblue'=>'7B68EE',
            'mediumspringgreen'=>'00FA9A',
            'mediumturquoise'=>'48D1CC',
            'mediumvioletred'=>'C71585',
            'midnightblue'=>'191970',
            'mintcream'=>'F5FFFA',
            'mistyrose'=>'FFE4E1',
            'moccasin'=>'FFE4B5',
            'navajowhite'=>'FFDEAD',
            'navy'=>'000080',
            'oldlace'=>'FDF5E6',
            'olive'=>'808000',
            'olivedrab'=>'6B8E23',
            'orange'=>'FFA500',
            'orangered'=>'FF4500',
            'orchid'=>'DA70D6',
            'palegoldenrod'=>'EEE8AA',
            'palegreen'=>'98FB98',
            'paleturquoise'=>'AFEEEE',
            'palevioletred'=>'DB7093',
            'papayawhip'=>'FFEFD5',
            'peachpuff'=>'FFDAB9',
            'peru'=>'CD853F',
            'pink'=>'FFC0CB',
            'plum'=>'DDA0DD',
            'powderblue'=>'B0E0E6',
            'purple'=>'800080',
            'red'=>'FF0000',
            'rosybrown'=>'BC8F8F',
            'royalblue'=>'4169E1',
            'saddlebrown'=>'8B4513',
            'salmon'=>'FA8072',
            'sandybrown'=>'F4A460',
            'seagreen'=>'2E8B57',
            'seashell'=>'FFF5EE',
            'sienna'=>'A0522D',
            'silver'=>'C0C0C0',
            'skyblue'=>'87CEEB',
            'slateblue'=>'6A5ACD',
            'slategray'=>'708090',
            'slategrey'=>'708090',
            'snow'=>'FFFAFA',
            'springgreen'=>'00FF7F',
            'steelblue'=>'4682B4',
            'tan'=>'D2B48C',
            'teal'=>'008080',
            'thistle'=>'D8BFD8',
            'tomato'=>'FF6347',
            'turquoise'=>'40E0D0',
            'violet'=>'EE82EE',
            'wheat'=>'F5DEB3',
            'white'=>'FFFFFF',
            'whitesmoke'=>'F5F5F5',
            'yellow'=>'FFFF00',
            'yellowgreen'=>'9ACD32');

        $color_name = strtolower($color_name);
        if (isset($colors[$color_name])) 
            return ('#' . $colors[$color_name]);
        else
            return null;
    }

    public static function abort($s, $line) {
        die("{$s}, line {$line} in " . __FILE__);
    }

    // convert various units to pixels, e.g.,
    //   4711, _      => 4711
    //   "20in", _    => 20*dpi
    //   "20%", $size => 0.2*$size
    // it is an error to use % if $size == null

    private function parseNumber($val, $siz) {
        if (str_contains($val, "%")) {
            if ($this->width > 0)
                return str_replace("%", "", $val) * $siz / 100;
            else
                self::abort("Trying to use {$val} when image width is not defined", __LINE__);
        } else if (str_contains($val, "in")) {
            return str_replace("in", "", $val) * $this->dpi;
        } else if (str_contains($val, "mm")) {
            return str_replace("mm", "", $val) * $this->dpi / 25.4;
        } else if (str_contains($val, "cm")) {
            return str_replace("cm", "", $val) * $this->dpi / 2.54;
        } else
            return $val;
    }


    private function parseHorizontalNumber($val) {
        return $this->parseNumber($val, $this->width);
    }

    private function parseVerticalNumber($val) {
        return $this->parseNumber($val, $this->height);
    }

    private function parseColor($s) {
        $rgb = $this->color_name_to_hex($s); // try HTML names first
        if (!$rgb)                    // if null, interpret $s as "#" plus 6 hex characters
            $rgb = $s;
        if (preg_match("/([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})/", $rgb, $ret)) {
            $red = hexdec($ret[1]);
            $green = hexdec($ret[2]);
            $blue = hexdec($ret[3]);
        } else
            return null;            // neither HTML nor hexcode 
        return imagecolorexact($this->doc, $red, $green, $blue );
    }

    private function reset() {
        $this->width = ImageSX($this->doc);
        $this->height = ImageSY($this->doc);
        $this->x0 = 0;
        $this->y0 = 0;
        $this->x1 = $this->width;         // default bbox is entire document
        $this->y1 = $this->height;
        $this->maxwidth = PHP_INT_MAX;
        $this->textcolor = $this->parseColor("000000");
        $this->font = self::scanfont(Ludvig::DEFAULT_FONT);
        $this->fontsize = $this->parseVerticalNumber("2%");
        $this->align = Ludvig::ALIGN_CENTER;
        $this->xp = $this->width/2;
        $this->yp = $this->height/2;
        $this->linespc = Ludvig::DEFAULT_LINESPC;
    }

    // params: image resource id, opacity in percentage (eg. 80)
    private static function filter_opacity(&$img, $opacity) { 
        if (!isset($opacity) || $opacity >= 100)
            return false;

        $opacity /= 100;
        $w = imagesx($img);
        $h = imagesy($img);

        // turn alpha blending off
        imagealphablending($img, false);

        // find the most opaque pixel in the image (the one with the smallest alpha value)
        $minalpha = 127;
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $alpha = (imagecolorat($img, $x, $y) >> 24) & 0xFF;
                if ($alpha < $minalpha) {
                    $minalpha = $alpha;
                }
            }
        }

        // loop through image pixels and modify alpha for each
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                // get current alpha value (represents the TANSPARENCY!)
                $colorxy = imagecolorat($img, $x, $y);
                $alpha = ($colorxy >> 24) & 0xFF;
                // calculate new alpha
                if ($minalpha !== 127)
                    $alpha = 127 + 127 * $opacity * ($alpha - 127) / (127 - $minalpha);
                else
                    $alpha += 127 * $opacity;
                // get the color index with new alpha
                $alphacolorxy = imagecolorallocatealpha($img, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
                // set pixel with the new color + opacity
                if (!imagesetpixel($img, $x, $y, $alphacolorxy))
                    return false;
            }
        }

        return true;
    }

    // place $img in bounding box (x0,y0) -- (x1,y1)
    // $img is GDImage element to be placed inside bounding box
    // $align is one of ALIGN_CENTER, etc
    // $opacity is 0..100
    // $border is either true/false and draws bounding box border (not $img)
    // this function returns the final height/width of $img
    private function bbox($img, $align, $opacity, $border) {
        $imgw = ImageSX($img);
        $imgh = ImageSY($img);
        if ($imgw/$imgh < ($this->x1 - $this->x0)/($this->y1 - $this->y0)) {
            $dst_height = $this->y1 - $this->y0;
            $dst_width = ($imgw / $imgh) * $dst_height;
            $dst_x = $this->x0 + ($this->x1 - $this->x0 - $dst_width)/2;
            if ($align == Ludvig::ALIGN_LEFT)
                $dst_x = $this->x0;
            if ($align == Ludvig::ALIGN_RIGHT)
                $dst_x = $this->x1 - $dst_width;
            $dst_y = $this->y0;
        } else {
            $dst_width = $this->x1 - $this->x0;
            $dst_height = ($imgh / $imgw) * $dst_width;
            $dst_y = $this->y0 + ($this->y1 - $this->y0 - $dst_height)/2;
            if ($align == Ludvig::ALIGN_TOP)
                $dst_y = $this->y0;
            if ($align == Ludvig::ALIGN_BOTTOM)
                $dst_y = $this->y1 - $dst_height;
            $dst_x = $this->x0;
        }

        self::filter_opacity($img, $opacity);
        imagecopyresampled($this->doc, $img, $dst_x, $dst_y, 0, 0, $dst_width, $dst_height, $imgw, $imgh);

        if ($border)
            imagerectangle($this->doc, $this->x0, $this->y0, $this->x1, $this->y1, $border);

        return array($dst_width, $dst_height);
    }
    
    private static function file_exists($fname) {
        if (!file_exists($fname))
            self::abort("Can't find file {$fname}", __LINE__);
        return $fname;
    }

    private static function rsearch($folder, $pattern) {
        $dir = new RecursiveDirectoryIterator($folder);
        $ite = new RecursiveIteratorIterator($dir);
        $files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
        $fileList = array();
        foreach($files as $file)
            $fileList[] = $file[0];
        return $fileList;
    }

    private static function scanfont($font) {
        $fontfiles = self::rsearch(dirname(__FILE__), Ludvig::FONT_EXT);
        foreach ($fontfiles as $fontfile) {
            $fname = pathinfo($fontfile, PATHINFO_FILENAME);
            if ($fname == $font)
                return $fontfile;
        }
        self::abort("Can't find font file for {$font}", __LINE__);
    }    

    private static function textWidth($fontsize, $font, $text) {
        $text_bound = imageftbbox($fontsize, 0, $font, $text);
        $lower_right_x = $text_bound[2];
        $lower_left_x =  $text_bound[0]; 
        return $lower_right_x - $lower_left_x; 
    }

    private static function textHeight($fontsize, $font, $text) {
        $text_bound = imageftbbox($fontsize, 0, $font, $text);
        $lower_right_y = $text_bound[3];
        $upper_right_y = $text_bound[5];
        return $lower_right_y - $upper_right_y;
    }

    function placeCenterText($text) {
        $textw = self::textWidth($this->fontsize, $this->font, $text);
        $fontsize = $this->fontsize;
        while ($textw > $this->maxwidth && fontsize > 8) {
            $fontsize = $fontsize/1.1;
            $textw = self::textWidth($fontsize, $this->font, $text);	      
        }
        $xp = $this->xp - $textw/2;
        ImageTTFText($this->doc, $fontsize, 0, $xp, $this->yp,
                     $this->textcolor, $this->font, $text);
        $this->maxwidth = PHP_INT_MAX; // reset
        return $this->fontsize;
    }

    function placeLeftText($text) {
        $textw = self::textWidth($this->fontsize, $this->font, $text);
        $texth = self::textHeight($this->fontsize, $this->font, $text);
        $fontsize = $this->fontsize;
        while ($textw > $this->maxwidth && $fontsize > 8) {
            $fontsize = $fontsize/1.1;
            $textw = self::textWidth($fontsize, $this->font, $text);	      
            $texth = self::textHeight($this->fontsize, $this->font, $text);
        }
        $xp = $this->xp;
        $yp = $this->yp;
        ImageTTFText($this->doc, $fontsize, 0, $xp, $yp,
                     $this->textcolor, $this->font, $text);
        $this->maxwidth = PHP_INT_MAX; // reset
        return $this->fontsize;
    }

    function placeRightText($text) {
        $textw = self::textWidth($this->fontsize, $this->font, $text);
        $fontsize = $this->fontsize;
        while ($textw > $this->maxwidth && $fontsize > 8) {
            $fontsize = $fontsize/1.1;
            $textw = self::textWidth($fontsize, $this->font, $text);	      
        }
        $xp = $this->xp - $textw;
        ImageTTFText($this->doc, $fontsize, 0, $xp, $this->yp,
                     $this->textcolor, $this->font, $text);
        $this->maxwidth = PHP_INT_MAX; // reset
        return $this->fontsize;
    }

    // Start of public methods:

    public function image($fname,
                          $bbox = null,
                          $align = Ludvig::ALIGN_CENTER,
                          $opacity = 100,
                          $border = false) {
        $fname = self::file_exists($fname);
        $ext = pathinfo($fname, PATHINFO_EXTENSION);
        if (in_array($ext, Ludvig::JPEG_EXT))
            $img = imagecreatefromjpeg($fname);
        else if (in_array($ext, Ludvig::PNG_EXT))
            $img = imagecreatefrompng($fname);
        else
            self::abort("Sorry, don't know how to open {$fname}", __LINE__);
        if ($bbox) {
            $this->x0 = $this->parseHorizontalNumber($bbox[0]);
            $this->y0 = $this->parseVerticalNumber($bbox[1]);
            $this->x1 = $this->parseHorizontalNumber($bbox[2]);
            $this->y1 = $this->parseVerticalNumber($bbox[3]);
        }
        list($w, $h) = $this->bbox($img, $align, $opacity, $border);
        $this->x0 += $w;
        $this->x1 += $w;
        if ($this->x1 > $this->width) {
            $this->x0 = 0;
            $this->x1 = $w;
            $this->y0 += $h;
            $this->y1 += $h;
        }
    }

    public function poly($points,
                         $border = "black",
                         $fill = "gray",
                         $thickness = 1) {
        $p = array();
        foreach ($points as $i => $point)
            if ($i % 2)
                $p[] = $this->parseVerticalNumber($point);
            else
                $p[] = $this->parseHorizontalNumber($point);
        $poly_border_color = self::parseColor($border);
        $poly_fill_color = self::parseColor($fill);
        imageantialias($this->doc, true);
        imagesetthickness($this->doc, $thickness);
        if ($fill)
            imagefilledpolygon($this->doc, $p, count($p)/2, $poly_fill_color);
        if ($border) {
            imagepolygon($this->doc, $p, count($p)/2, $poly_border_color);
            for ($i = 0; $i < count($p); $i += 2)
                imagefilledellipse($this->doc, $p[$i], $p[$i+1],
                                   $thickness - 1, $thickness - 1, $poly_border_color);
        }
    }

    public function text($text, $align = null, $x = null, $y = null, $font = null, $fontsize = null,
                         $textcolor = null, $maxwidth = null, $linespc = null) {
        if ($align)
            $this->align = $align;
        if ($x)
            $this->xp = $this->parseHorizontalNumber($x);
        if ($y)
            $this->yp = $this->parseVerticalNumber($y);
        if ($font)
            $this->font = self::scanfont($font);
        if ($fontsize)
            $this->fontsize = $this->parseVerticalNumber($fontsize);
        if ($textcolor)
            $this->textcolor = self::parseColor($textcolor);
        if ($maxwidth)
            $this->maxwidth = $this->parseHorizontalNumber($maxwidth);
        if ($linespc)
            $this->linespc = $this->parseVerticalNumber($linespc);

        if ($this->align == Ludvig::ALIGN_CENTER)
            $this->yp += $this->linespc * $this->placeCenterText($text);
        elseif ($this->align == Ludvig::ALIGN_LEFT)
            $this->yp += $this->linespc * $this->placeLeftText($text);
        elseif ($this->align == Ludvig::ALIGN_RIGHT)
            $this->yp += $this->linespc * $this->placeRightText($text);
        else
            self::abort("text align was not center, left or right", __LINE__);
    }
                         
    public function __construct($file = null, $width = null, $height = null, $background = "ffffff", $dpi = 300) {
        if ($file)
            $this->doc = imagecreatefromjpeg($file);
        elseif ($width && $height) {
            $this->dpi = $dpi;
            $width = $this->parseHorizontalNumber($width);
            $height = $this->parseVerticalNumber($height);
            $this->doc = @imagecreatetruecolor ($width, $height)
                       or self::abort("Cannot Initialize new GD image stream", __LINE__);
            imageresolution($this->doc, $dpi);
            imagefill($this->doc, 0, 0, $this->parseColor($background));
        } else {
            self::abort("constructor called without file or width/height", __LINE__);
        }
        $this->reset();
    }

    function __destruct() {
        if ($this->doc)
            imagedestroy($this->doc);
    }

    public function output($fname = null, $quality = 100) {
        if ($fname) {           // output to file
          $ext = pathinfo($fname, PATHINFO_EXTENSION);
          if (in_array($ext, array('jpg', 'JPG', 'jpeg', 'JPEG')))
              imagejpeg($this->doc, $fname, $quality);
          else if (in_array($ext, array('png', 'PNG')))
              imagepng($this->doc, $fname);
          else
              self::abort("Sorry, can only save to JPEG and PNG and you provided {$fname}", __LINE__);
          // return "<a target='_blank' href='{$fname}'>{$fname}</a>";
          return "<img src='{$fname}' height=100 border=1 style='margin: 1em;' />";
        } else {
            // output to browser, caller needs to do
            // header('Content-Type: image/jpeg');
            imagejpeg($this->doc, null, $quality);
        }
    }
}

?>
