<?php

/**
  *  LUDVIG: A PHP-based tool to create JPEG/PNG composites from a text file with commands.
  *
  *  (C) Kjell Post, kjell@irstafoto.se
  *
  *  LUDVIG reads a file with lines like this:
  *
  *  template="filename.jpg"
  *      loads a template
  *  image="filename.jpg", bbox=(x0 y0 x1 y1), align=top
  *      place an image inside a bounding box
  *  text="text", align={left,center,right}, x=50%, y=20, font=Arial, fontsize=112, maxwidth=50%
  *      text can contain {$X} which is replaced by the PHP parameter &X=
  *  poly="10% 10%  100% 10%  100% 100%  10% 100%  10% 10%", border=black, fill=#d0d0d0, thickness=2
  *      creates a polygon with an optional fill color, border color and thickness
  *  var="value"
  *      creates a variable which can later be used as {$var}
  *  output="output.jpg"
  *      writes the current document to a file 
  *
  */

ini_set('display_errors', 'On');
ini_set('max_execution_time', 600);

$var = []; 		      // holds variable names and $_REQUEST parameters

$settings = array(
    "width" => 1920,
    "height" => 1200,
    "DPI" => 300,
    "linespc" => 1.5,
    "font" => "./GoNotoCurrent.ttf",
    "align" => "center",
    "maxwidth" => PHP_INT_MAX,
    "bbox_align" => "",
    "opacity" => 100,
    "color" => -1,
    "xp" => -1,
    "yp" => -1,
    "fontsize" => -1,
    "x0" => -1,
    "y0" => -1,
    "x1" => -1,
    "y1" => -1,
);

file_put_contents("ludvig-00.txt", "Log file for ludvig:\n\n");
function debug($s) {
    file_put_contents("ludvig-00.txt", $s . "\n", FILE_APPEND);
}

function err($msg) {
    echo("{$msg}<br>");
    return 0;
}

function trimQuotes($s) {
    return substr($s, 1, -1);
}

function substitute($s, $params) {
    global $var;
    foreach ($params as $param => $val)
        $s = str_replace("{\${$param}}", $val, $s);        
    return $s;
}

function resetSettings($doc) {
    global $settings;
    $settings["color"] = parseColor($doc, "black");
    $settings["poly-border-color"] = parseColor($doc, "black");
    $settings["poly-border-thickness"] = 1;
    $settings["poly-fill-color"] = null;

    $w = ImageSX($doc);
    $h = ImageSY($doc);
    $settings["width"] = $w;
    $settings["height"] = $h;
    list($settings["xp"], $rest) = parseNumCoordinate($w, "50%");
    list($settings["yp"], $rest) = parseNumCoordinate($h, "50%");
    list($settings["fontsize"], $rest) = parseNumCoordinate($h, "2%");
    list($settings["x0"], $rest) = parseNumCoordinate($w, "0");
    list($settings["y0"], $rest) = parseNumCoordinate($h, "0");
    list($settings["x1"], $rest) = parseNumCoordinate($w, "100%"); 
    list($settings["y1"], $rest) = parseNumCoordinate($h, "100%");
}    

function newDocument($w, $h, $bg) {
    global $settings;
    $doc = @imagecreatetruecolor ($w, $h)
         or die('Cannot Initialize new GD image stream');
    imageresolution($doc, $settings["DPI"]);
    imagefill($doc, 0, 0, parseColor($doc, $bg));
    resetSettings($doc);
    return $doc;
}

// params: image resource id, opacity in percentage (eg. 80)
function filter_opacity(&$img, $opacity) { 
    if (!isset($opacity) || $opacity >= 100)
        return false;

    $opacity /= 100;

    //get image width and height
    $w = imagesx($img);
    $h = imagesy($img);

    //turn alpha blending off
    imagealphablending($img, false);

    //find the most opaque pixel in the image (the one with the smallest alpha value)
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
            if ($minalpha !== 127) {
                $alpha = 127 + 127 * $opacity * ($alpha - 127) / (127 - $minalpha);
            } else {
                $alpha += 127 * $opacity;
            }
            // get the color index with new alpha
            $alphacolorxy = imagecolorallocatealpha($img, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
            // set pixel with the new color + opacity
            if (!imagesetpixel($img, $x, $y, $alphacolorxy))
                return false;
        }
    }

    return true;
}

function rsearch($folder, $pattern) {
    $dir = new RecursiveDirectoryIterator($folder);
    $ite = new RecursiveIteratorIterator($dir);
    $files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
    $fileList = array();
    foreach($files as $file)
        $fileList[] = $file[0];
    return $fileList;
}

function scanfont($font) {
    $fontfiles = rsearch(".", "/^.*\.(ttf|TTF|otf|OTF)$/");
    foreach ($fontfiles as $fontfile) {
        $fname = pathinfo($fontfile, PATHINFO_FILENAME);
        if ($fname == $font)
            return $fontfile;
    }
    die("Can't find font file for {$font}");
}    

function color_name_to_hex($color_name) {
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

function parseColor($doc, $s) {
    $rgb = color_name_to_hex($s); // try HTML names first
    if (!$rgb)                    // if null, interpret $s as "#" plus 6 hex characters
        $rgb = $s;
    if (preg_match("/([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})/", $rgb, $ret)) {
        $red = hexdec( $ret[1] );
        $green = hexdec( $ret[2] );
        $blue = hexdec( $ret[3] );
    } else
        return null;            // neither HTML nor hexcode 
    return imagecolorexact($doc, $red, $green, $blue );
}
    
function parseNumCoordinate($size, $s) {
    list($first, $rest) = explode(" ", "{$s} ", 2);
    $rest = trim($rest);
    if (str_contains($first, "%"))
        return [str_replace("%", "", $first) * $size / 100, $rest];
    else
        return [$first, $rest];
}

function kpexists($fname) {
  if (!file_exists($fname))
    die("Can't find file {$fname}");
  return $fname;
}

function textWidth($font_size, $font, $text) {
  $text_bound = imageftbbox($font_size, 0, $font, $text);
  $lower_left_x =  $text_bound[0]; 
  $lower_left_y =  $text_bound[1];
  $lower_right_x = $text_bound[2];
  $lower_right_y = $text_bound[3];
  $upper_right_x = $text_bound[4];
  $upper_right_y = $text_bound[5];
  $upper_left_x =  $text_bound[6];
  $upper_left_y =  $text_bound[7];

  // get text width and text height
  $text_width =  $lower_right_x - $lower_left_x; //or  $upper_right_x - $upper_left_x
  return $text_width;
}

function textHeight($font_size, $font, $text) {
  $text_bound = imageftbbox($font_size, 0, $font, $text);
  $lower_left_x =  $text_bound[0]; 
  $lower_left_y =  $text_bound[1];
  $lower_right_x = $text_bound[2];
  $lower_right_y = $text_bound[3];
  $upper_right_x = $text_bound[4];
  $upper_right_y = $text_bound[5];
  $upper_left_x =  $text_bound[6];
  $upper_left_y =  $text_bound[7];

  // get text width and text height
  $text_height =  $lower_right_y - $upper_right_y;
  return $text_height;
}

function placeLeftText($doc, $text, $settings) {
    $font = $settings["font"];
    $font_size = $settings["fontsize"];
    $color = $settings["color"];
    $sx = $settings["xp"];
    $sy = $settings["yp"];
    $maxw = $settings["maxwidth"];
    $docw = $settings["width"];
    $doch = $settings["height"];

    $textw = textWidth($font_size, $font, $text);
    $texth = textHeight($font_size, $font, $text);
    while ($textw > $maxw && $font_size > 8) {
        $font_size = $font_size/1.1;
        $textw = textWidth($font_size, $font, $text);	      
    }
    ImageTTFText($doc, $font_size, 0, round($sx), round($sy), $color, $font, $text);
    return $settings["linespc"] * $font_size;
}

function placeRightText($doc, $text, $settings) {
    $font = $settings["font"];
    $font_size = $settings["fontsize"];
    $color = $settings["color"];
    $sx = $settings["xp"];
    $sy = $settings["yp"];
    $maxw = $settings["maxwidth"];
    $docw = $settings["width"];
    $doch = $settings["height"];

    $textw = textWidth($font_size, $font, $text);
    $texth = textHeight($font_size, $font, $text);
    while ($textw > $maxw && $font_size > 8) {
        $font_size = $font_size/1.1;
        $textw = textWidth($font_size, $font, $text);	      
    }
    $sx = $sx - $textw;
    ImageTTFText($doc, $font_size, 0, $sx, $sy, $color, $font, $text);
    return $settings["linespc"] * $font_size;
}

function placeCenterText($doc, $text, $settings) {
    $font = $settings["font"];
    $font_size = $settings["fontsize"];
    $color = $settings["color"];
    $sx = $settings["xp"];
    $sy = $settings["yp"];
    $maxw = $settings["maxwidth"];
    $docw = $settings["width"];
    $doch = $settings["height"];

    $textw = textWidth($font_size, $font, $text);
    $texth = textHeight($font_size, $font, $text);
    while ($textw > $maxw && $font_size > 8) {
        $font_size = $font_size/1.1;
        $textw = textWidth($font_size, $font, $text);	      
    }
    $sx = $sx - $textw/2;
    file_put_contents("ludvig-00.txt", "font_size = {$font_size}, textw = {$textw}, docw = {$docw}, sx = {$sx}\n", FILE_APPEND);
    ImageTTFText($doc, $font_size, 0, $sx, $sy, $color, $font, $text);
    return $settings["linespc"] * $font_size;
}

function placeText($text, $font_size, $y, $font, $black, $doc) {
  $docw = $settings["width"];
  $textw = textWidth($font_size, $font, $text);
  $sx = ($docw - $textw) / 2;
  ImageTTFText($doc, $font_size, 0, $sx, $y, $black, $font, $text);
}

function placeTextBold($text, $font_size, $y, $font, $black, $doc) {
  $docw = $settings["width"];
  $textw = textWidth($font_size, $font, $text);
  $sx = ($docw - $textw) / 2;
  ImageTTFText($doc, $font_size, 0, $sx, $y, $black, $font, $text);
  ImageTTFText($doc, $font_size, 0, $sx, $y-1, $black, $font, $text);
  ImageTTFText($doc, $font_size, 0, $sx, $y+1, $black, $font, $text);
  ImageTTFText($doc, $font_size, 0, $sx-1, $y, $black, $font, $text);
  ImageTTFText($doc, $font_size, 0, $sx+1, $y, $black, $font, $text);
}

// place $jpeg image in bounding box (x0,y0) -- (x1,y1)
// $doc is GDImage destination image resource
// $jpeg is GDImage element to be placed inside bounding box
function bbox($doc, $jpeg, $settings) {
    $jpegw = ImageSX($jpeg);
    $jpegh = ImageSY($jpeg);

    $x0 = $settings["x0"];
    $y0 = $settings["y0"];
    $x1 = $settings["x1"];
    $y1 = $settings["y1"];
    $align = $settings["bbox_align"];
    $opacity = $settings["opacity"];
    $border = $settings["border"];
    
    if ($jpegw/$jpegh < ($x1-$x0)/($y1-$y0)) {
        $dst_height = $y1 - $y0;
        $dst_width = ($jpegw / $jpegh) * $dst_height;
        $dst_x = $x0 + ($x1 - $x0 - $dst_width)/2;
        if ($align == "left")
            $dst_x = $x0;
        if ($align == "right")
            $dst_x = $x1 - $dst_width;
        $dst_y = $y0;
    } else {
        $dst_width = $x1 - $x0;
        $dst_height = ($jpegh / $jpegw) * $dst_width;
        $dst_y = $y0 + ($y1 - $y0 - $dst_height)/2;
        if ($align == "top")
            $dst_y = $y0;
        if ($align == "bottom")
            $dst_y = $y1 - $dst_height;
        $dst_x = $x0;
    }

    filter_opacity($jpeg, $opacity);
    imagecopyresampled($doc, $jpeg, $dst_x, $dst_y, 0, 0, $dst_width, $dst_height, $jpegw, $jpegh);

    if (!is_null($border)) {
        imagerectangle($doc, $x0, $y0, $x1, $y1, $border);
    }
}

function mkcomposite($file) {
  global $settings, $_REQUEST;

  if (!file_exists($file)) {
      echo "Can't open {$file}";
      return 0;
  }

  foreach ($_REQUEST as $key => $value)
      $var[$key] = $value;

  $doc = newDocument($settings["width"], $settings["height"], "#ffffff");
  $lines = file_get_contents($file);
  $lines = preg_split("/\r\n|\n|\r/", $lines);
  $lineno = 0;

  foreach ($lines as $linenr => $line) {

      // certain settings are reset after each line:
      $settings["bbox_align"] = "";
      $settings["maxwidth"] = PHP_INT_MAX;
      $settings["opacity"] = 100;
      $settings["border"] = null;

      $lineno = $lineno + 1;
      if (!$line || substr(trim($line), 0, 1) == "#")
          continue;

      // $line is e.g.
      //
      //     text="fie foo fum", y=20%, font=Arial, fontsize=48
      //     ---- -------------  ------------------------------
      //      ^         ^                       ^
      //     cmd       arg                   options
      // 

      $c0 = strpos($line, "=");
      if (!$c0)
          return err("Syntax error on line {$lineno} -- missing '='");
      $key = trim(substr($line, 0, $c0));
      $rest = substr($line, $c0+1);
      // echo "<pre>1: rest={$rest}</pre>";
      $c1 = strpos($line, '"');
      if (!$c1)
          return err("Syntax error on line {$lineno} -- missing '\"'");
      $rest = substr($rest, 1);
      // echo "<pre>rest={$rest}</pre>";
      $arg = "";
      for ($i = 0; $i < strlen($rest); $i++) {
          if ($rest[$i] == '"')
              break;
          if ($rest[$i] == '\\')
              $i++;
          $arg .= $rest[$i];
      }
      // echo "<pre>arg={$arg}</pre>";
      $options = substr($rest, $i);
      $c2 = strpos($options, ",");
      if ($c2)
          $options = trim(substr($options, $c2+1));
      else
          $options = "";

      debug("line: key={$key}     arg={$arg}     options={$options}");

      if (str_contains($options, ","))
          $options = explode(",", $options);
      else if ($options)
          $options = [ $options ];
      else
          $options = [ ];

      switch ($key) {
      case "template":
          $arg = substitute($arg, $var);
          if (preg_match("/([0-9]+)x([0-9]+)/", $arg, $ret)) {
              $doc = newDocument($ret[1], $ret[2], "#ffffff");
              resetSettings($doc);
          } else {
              $doc = imagecreatefromjpeg(kpexists($arg));
              resetSettings($doc);
          }

          foreach ($options as $option) {
              if (!$option)
                  continue;
              list($optkey, $optval) = explode("=", $option);
              switch (trim($optkey)) {
              case "bg":
                  $bgColor = parseColor($doc, $optval);
                  imagefill($doc, 0, 0, $bgColor);
                  break;
              default:
                  return err("Unknown option: {$option} on line {$lineno}");
              }
          }
                  

          break;
      case "output":
          $fname = substitute($arg, $var);
          $ext = pathinfo($fname, PATHINFO_EXTENSION);
          if (in_array($ext, array('jpg', 'JPG', 'jpeg', 'JPEG')))
              imagejpeg($doc, $fname, 100);
          else if (in_array($ext, array('png', 'PNG')))
              imagepng($doc, $fname);
          else
              echo "Sorry, can only save to JPEG and PNG and you provided {$fname} on line {$lineno}";
          imagedestroy($doc);
          return 0;
          break;
      case "text":
          $txt = substitute($arg, $var);
          foreach ($options as $option) {
              if (!$option)
                  continue;
              list($optkey, $optval) = explode("=", $option);
              switch (trim($optkey)) {
              case "align":
                  $settings["align"] = trim($optval);
                  break;
              case "x":
                  $optval = substitute($optval, $var);
                  list($settings["xp"], $rest) = parseNumCoordinate($settings["width"], trim($optval));
                  break;
              case "y":
                  $optval = substitute($optval, $var);                  
                  list($settings["yp"], $rest) = parseNumCoordinate($settings["height"], trim($optval));
                  break;
              case "font":
                  $optval = substitute($optval, $var);
                  $settings["font"] = scanfont(trim($optval));
                  break;
              case "fontsize":
                  // $fontsize = trim($optval);
                  $optval = substitute($optval, $var);
                  list($settings["fontsize"], $rest) = parseNumCoordinate($settings["height"], trim($optval));
                  break;
              case "color":
                  $settings["color"] = parseColor($doc, trim($optval));
                  break;
              case "maxwidth":
                  list($settings["maxwidth"], $rest) = parseNumCoordinate($settings["width"], trim($optval));
                  break;
              case "linespc":
                  list($settings["linespc"], $rest) = parseNumCoordinate($settings["height"], trim($optval));
                  break;
              default:
                  return err("Unknown option: {$option} on line {$lineno}<br>");
              }
          }

          if ($settings["align"] == "center")
              $settings["yp"] += 1.2 * placeCenterText($doc, $txt, $settings);
          else if ($settings["align"] == "left")
              $settings["yp"] += 1.2 * placeLeftText($doc, $txt, $settings);
          else if ($settings["align"] == "right")
              $settings["yp"] += 1.2 * placeRightText($doc, $txt, $settings);
          break;

      case "image":
          $fname = kpexists(substitute($arg, $var));
          $ext = pathinfo($fname, PATHINFO_EXTENSION);
          if (in_array($ext, array('jpg', 'JPG', 'jpeg', 'JPEG')))
              $img = imagecreatefromjpeg($fname);
          else if (in_array($ext, array('png', 'PNG')))
              $img = imagecreatefrompng($fname);
          else
              return err("Sorry, don't know how to open {$fname} on line {$lineno}");

          foreach ($options as $option) {
              list($optkey, $optval) = explode("=", $option);
              switch (trim($optkey)) {
              case "align":
                  $settings["bbox_align"] = trim($optval);
                  break;
              case "opacity":
                  $settings["opacity"] = trim($optval);
                  break;
              case "bbox":
                  $optval = trimQuotes(trim($optval));
                  list($settings["x0"], $optval) = parseNumCoordinate($settings["width"], $optval);
                  list($settings["y0"], $optval) = parseNumCoordinate($settings["height"], $optval);
                  list($settings["x1"], $optval) = parseNumCoordinate($settings["width"], $optval);
                  list($settings["y1"], $optval) = parseNumCoordinate($settings["height"], $optval);
                  break;
              case "border":
                  $settings["border"] = parseColor($doc, trim($optval));                  
                  break;
              default:
                  return err("Unknown option: {$option} on line {$lineno}");
              }
          }
          // bbox($doc, $img, $settings["x0"], $settings["y0"], $settings["x1"], $settings["y1"], $settings["bbox_align"], $settings["opacity"], $settings["border"]);
          bbox($doc, $img, $settings);
          $dx = $settings["x1"] - $settings["x0"];
          $settings["x0"] += $dx;
          $settings["x1"] += $dx;
          break;

      case "poly":
          $corners = [];
          $i = 0;
          while (trim($arg)) {
              list($corners[$i++], $arg) = parseNumCoordinate($settings["width"], $arg);
              list($corners[$i++], $arg) = parseNumCoordinate($settings["height"], $arg);
          }

          if ($i % 2)
              return err("Missing y-coordinate on line {$lineno}");

          foreach ($options as $option) {
              list($optkey, $optval) = explode("=", $option);
              switch (trim($optkey)) {
              case "fill":
                  $settings["poly-fill-color"] = parseColor($doc, trim($optval));
                  break;
              case "border":
                  $settings["poly-border-color"] = parseColor($doc, trim($optval));
                  break;
              case "thickness":
                  $settings["poly-border-thickness"] = trim($optval);
                  break;
              default:
                  return err("Unknown option: {$option} on line {$lineno}");
              }
          }

          ImageAntiAlias($doc, true);
          $t = $settings["poly-border-thickness"];
          imagesetthickness($doc, $t);
          if (!is_null($settings["poly-fill-color"]))
              imagefilledpolygon($doc, $corners, count($corners)/2, $settings["poly-fill-color"]);
          if (!is_null($settings["poly-border-color"])) {
              imagepolygon($doc, $corners, count($corners)/2, $settings["poly-border-color"]);
              for ($i = 0; $i < count($corners); $i += 2)
                  imagefilledellipse($doc, $corners[$i], $corners[$i+1], $t - 1, $t - 1, $settings["poly-border-color"]);
          }
          break;

      default:
          $var[$key] = $arg;
          debug("variables:\n" . print_r($var, true));
      }

  }
  return $doc;
}

function ludvig($file) {
    $doc = mkcomposite($file);
    if ($doc) {
        header('Content-Type: image/jpeg');
        imagejpeg($doc, null, 100);
    }
}

if (isset($_REQUEST['file']))
    ludvig($_REQUEST['file']);
?>
