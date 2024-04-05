# ludvig
ludvig is a PHP-based tool for creating JPEG/PNG composites with text and images

ludvig is a PHP script invoked like this:
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
when this is invoked with the following URL:
```
ludvig.php?file=input.l&firstname=world
```
the following will be shown in the browser:

![output from input.l](https://github.com/kjepo/ludvig/blob/main/input-output.jpg)

As you can see from the sample file above, each line in a ludvig file is a command (unless it starts with a `#` (which is a comment) or is blank).

Each line consists of the actual command, like `text=` or `image=`, followed by an argument, like `"hello"` and then an optional list of options, like `color=` or `fontsize=`.  Options usually retain their values from one line to another, so you can write
```
text="Hello {$firstname}", fontsize=36
text="How are you?"
```
and the second `text` command is also rendered with the same font size.  Certain options however have their values reset: these are maximum width for text, bounding box, opacity and border for images.

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
```
image="photo.jpg", bbox=(10% 10% 90% 90%)
```
```
image="signature.png", bbox=(100 100 1000 500), align=top, opacity=50, border=black
```
The `image` command places a JPEG or PNG image inside a bounding box (x<sub>0</sub> y<sub>0</sub> x<sub>1</sub> y<sub>1</sub>) - by default centered - but the `align` option allows you to specify `left`, `right`, `top` or `bottom`.  Also by default, the opacity is 100 and the image is rendered without a border.  During debugging, a border can however be useful to see the actual bounding box.

