# ludvig
ludvig is a PHP-based tool for creating JPEG/PNG composites with text and images

ludvig is a PHP script and is invoked like this:
```
ludvig.php?file=input.l&var1=...&var2=...
```
where `input.l` is the ludvig file.  Optionally, you can supply URL parameters ("query strings") `&var=...` and these variables can be used in the ludvig file where they will be replaced by their respective value.

For instance, here is a very simple ludvig script:
```
# this is the file input.l
template="1200x800", bg=lightblue
text="Hello {$firstname}"
```
When this is invoked with the following URL:
```
ludvig.php?file=input.l&firstname=world
```
the following will be shown in the browser:

![output from input.l](https://github.com/kjepo/ludvig/blob/main/input-output.jpg)

As you can see from the sample file above, each line in a ludvig file is a command (unless it starts with a `#` which is a comment, or is blank).

Each line consists of the actual command, like `text=` or `image=`, followed by an argument, like `"hello"` and then an optional list of options, like `color=` or `fontsize=`.  Options usually retain their values from one line to another, so you can write
```
text="Hello {$firstname}", fontsize=36
text="How are you?"
```
and the second `text` command is also rendered with the same font size.  Certain options however have their values reset: these are maximum width for text, bounding box, opacity and border for images.

## variables
Example:
```
fs="36"
text="Hello", fontsize={$fs}
```
A variable can be defined and used later, as long as it isn't called `text`, `image`, etc.
These variables live in the same space as the variables defined in the URL.

## template command
Examples:
```
template="1200x800", bg=lightblue
```
```
template="background.jpg"
```
```
template="background.png"
```
This is usually the first line, as it creates a document of a certain size, either via a specified dimension (and an optional background color), or by loading a JPEG or PNG file.
A color is either one of the 147 standard HTML names like `brown`, `crimson` or `darkgray`, or a hex value like `#ffd700` (which happens to be gold).

## image command
Examples:
```
image="photo.jpg", bbox=(10% 10% 90% 90%)
```
```
image="signature.png", bbox=(100 100 1000 500), align=top, opacity=50, border=black
```
The `image` command places a JPEG or PNG image inside a bounding box (x<sub>0</sub> y<sub>0</sub> x<sub>1</sub> y<sub>1</sub>) - by default centered - but the `align` option allows you to specify `left`, `right`, `top` or `bottom`.  Also by default, the opacity is 100 and the image is rendered without a border.  During debugging, a border can however be useful to see the actual bounding box.

## text command
Example:
```
text="Fie foo fum", y=50%, color=gray, fontsize=5%
```
The text may contain, e.g., `{$x}` in which case this is replaced by the value of `x` from the URL,
or if `x` was defined by a variable command (see above).

The `text` command has many options:
- `x=...` and `y=...` sets the x/y coordinate for the text.  The numeric value can either be an absolute number like `100` or a relative value like `50%`.  When it is a relative value, it is measured against the document's width if it's an x coordinate, and against the height if it's a y coordinate.
- `align=` followed by `left`, `center` or `right` aligns the text. By default text is centered.
- `font=` followed by a name like `Courier` or `Arial`.  Ludvig then looks for a file `Courier.ttf` or `Courier.otf` recursively in the same directory.
- `fontsize=` followed by either an absolute value or a percentage, e.g., `2%` in which case the font size is measured against the document's height.
- `color=` followed by a color name, like `brown`, or an RGB-value like `#ffd700`.
- `maxwidth=` followed by a numeric value.  If the text is wider than the value, the font is shrunk until it fits.
- `linespc=` followed by a numeric value, which specifies the distance to the next line.  By default the line spacing is set to 1.5

## poly command
Example:
```
poly="10% 10%  90% 10%  90% 90%  10% 90%", border=blue, fill=pink, thickness=5
```
The `poly` command takes a list of (x,y) coordinates and renders a polygon, by default unfilled and with a 1 px black border but these can be overriden by the `border`, `fill` and `thickness` options.

## output command
Example:
```
output="filename.jpg"
```
```
output="{$name}.png"
```
When the `output` command is executed, the document is saved to the specified file name as either a JPEG or PNG (depending on the file name suffix).  The document is then destroyed.

If the `output` command is not encountered, the document is rendered as a JPEG image in the browser.

# A larger example
```
template="1400x1200", bg=#a3a992
# define the font
f="Futura-CondensedLight"
# corners of the polygon
x0="15%"
y0="40%"
x1="85%"
y1="57%"
text="Interested in Photography?", font={$f}, color=black, y=7%, fontsize=56
poly="{$x0} {$y0} {$x1} {$y0} {$x1} {$y1} {$x0} {$y1} {$x0} {$y0}", fill=lightgray, thickness=2
text="Visit your local camera store in Nässjö", font={$f}, y=50%
image="../img/sponsorer/hegethorns.png", bbox=(80% 90% 99% 99%), align=bottom
```
![Output](https://github.com/kjepo/ludvig/blob/main/hegethorns.jpeg)



