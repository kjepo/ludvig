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

[https://github.com/kjepo/ludvig/blob/main/input-output.jpg]

