# ludvig
ludvig is a PHP class for creating JPEG/PNG composites with text and images.

*Note:* an earlier version of Ludvig, which supported text input, is still available
as `ludvig.php`.

Here is a very simple PHP script using Ludvig:
```
  require_once('ludvig-class.php');
  header('Content-Type: image/jpeg');
  $doc = new Ludvig(width: 1200, height: 800, background: "lightblue");
  $doc->text("Hello world");
  $doc->output();
```
When this is invoked the following will be shown in the browser:

![output](https://github.com/kjepo/ludvig/blob/main/input-output.jpg)

## Initialising

The constructor for Ludvig can be used in two ways.
### Initialising with an existing image file 
```
$doc = Ludvig("foo.jpg"); // open JPEG file
$doc = Ludvig("fie.png"); // open PNG file
```
This creates a new document, with the given file (JPEG or PNG) as a background.

### Initialising with a blank document
Alternatively,
```
$doc = Ludvig(width: 800, height: 500, background: "white", dpi: 300);
```
creates a blank document, where `width` and `height` are required parameters,
whereas `background` and `dpi` are optional and defaults to the above values.

The width/height can also be, e.g., "20 cm", "50 mm" or "10 in".
Generally speaking, any dimension in Ludvig can be an absolute number (interpreted as pixels),
or a number followed by one of the units `cm`, `mm`, `in`, or `%`.
But for initialising, `%` can not be used as `%` is assumed to be a percentage of the document's
width/height which does not make sense when creating the document.
Obviously, the `dpi` parameter is used to calculate the document's dimension when `cm`, `mm` or `in` are used.

Once the document has been created, the instance variables `$doc->width`, `$doc->height` and `$doc->dpi` are available.

## Methods
Ludvig object understands the following methods:

- `image`

```
$doc->image("fie.jpg",
             bbox: [$x0, $y0, $x1, $y1],
             align: Ludvig::ALIGN_CENTER,
             opacity: 100,
             border: false)
```

This places the JPEG (or PNG) file on top of the document, as specified by the bounding box
with the northwest corner ($x1, $y0) and the southeast corner ($x1, $y1).
If not specified, the bounding box is assumed to be the entire document.
The image can further be aligned in five different ways, as specified by the `align` parameter:
`Ludvig::ALIGN_CENTER` (default),
`Ludvig::ALIGN_TOP`,
`Ludvig::ALIGN_BOTTOM`,
`Ludvig::ALIGN_LEFT`,
or
`Ludvig::ALIGN_RIGHT`.
The `opacity` is a value 0-100 (default 100 - no transparency) while `border`, when true,
draws a simple border around the bounding box (not the image) which is useful for debugging.

Successive calls to `image` places each image left-to-right on the document when no bounding box is specified.

- `text`

```
$ludvig->text("Fie foo fum",
               align: Ludvig::ALIGN_CENTER,
               x: "50%", y: "50%",
               font: "GoNotoCurrent",
               fontsize: "2%",
               textcolor: "black",
               maxwidth: PHP_INT_MAX,
               linespc: 1.45);
```
Only the first argument (the text itself) is required.  The following parameters have default values as shown.

`align` can be either `Ludvig::ALIGN_CENTER` (default), `Ludvig::ALIGN_LEFT`, or `Ludvig::ALIGN_RIGHT`.

`x` and `y` are the coordinates for the baseline, which (as explained earlier) can be, e.g., "50%" or "1 cm".

`font` is searched for recursively in the directory where `ludvig-class.php` is stored.  It should be a TTF or OTF file.
There is a default font called `GoNotoCurrent`, declared as a constant in Ludvig. I highly recommend downloading this
font as it supports many different alphabets.

`fontsize` is the fontsize, which also can be an absolute number or a number with a unit.

`textcolor` is either a name, like "blue", or a hex value like `"#ffd700"` (which happens to be gold).

`maxwidth` defines the maximum width for the string.  If the text is wider than this, the fontsize is shrunk until it fits.

`linespc` is the line spacing so that successive calls to `text` do not have to specify the `y` coordinate.

All of these parameters (except `x` and `y`) retain their values from one call to the next,
thus obviating the need to specify, e.g., the font or fontsize for every call.

- `poly`

```
$doc->poly([$x0, $y0, $x1, $y1, ...], border: "black", fill: "gray", thickness: 1);
```

draws a filled polygon where the first argument specifies the corners.
The last corner doesn't have to be the same as the first.
The parameters `border`, `fill` and `thickness` are optional with default values as shown.

- `output`

```
$doc->output()
```
or,
```
$doc->output("output.jpg")
```
outputs the document, either to the browser (when no argument is given), or to a JPEG or PNG file.
If you output to the browser, it is recommended that you first issue a header:
```
header('Content-Type: image/jpeg');
```
This method returns a HTML `<img>` text which can be echo'ed to display a preview of the result.

# A larger example

```
require_once('ludvig-class.php');
$doc = new Ludvig(width: 1400, height: 1200, background: "a3a992");
$f="Futura-CondensedLight";
// corners of the polygon
$x0="15%"; $y0="40%"; $x1="85%"; $y1="57%";
$doc->text("Interested in Photography?", font: $f, textcolor: "black", y: "7%", fontsize: 56);
$doc->poly([$x0, $y0, $x1, $y0, $x1, $y1, $x0, $y1], fill: "lightgray", thickness: 2);
$doc->text("Visit your local camera store in Nässjö", y: "50%");
$doc->image("../img/sponsorer/hegethorns.png", bbox: ["80%", "90%", "99%", "99%"], align: Ludvig::ALIGN_BOTTOM);
echo $doc->output("../tmp/test-8.jpg");
```

![Output](https://github.com/kjepo/ludvig/blob/main/hegethorns.jpeg)

# Questions

This software is released "as is".  If you're interested in having features added, you can contact me at `kjell@irstafoto.se`.

