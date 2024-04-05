template="1400x1200", bg=#a3a992
# fonts
f1="Myriad Pro"
f2="Courier New"
# indentation
i1="5%"
i2="7%"
text="LUDVIG", font=Futura-CondensedLight, color=black, y=7%, fontsize=56
text="LUDVIG reads a series of lines from a file:", x={$i1}, y=12%, fontsize=20, align=left, font={$f1}
text=""
text="ludvig.php?file=...&var=...", font={$f2}, x={$i2}
text=""
text="The specified file contains a series of commands like this:", font={$f1}, x={$i1}
text=""
text="varname=\"value\"", font={$f2}, x={$i1}
text="creates a variable varname which can be substituted in various places using {$varname}.", font={$f1}, x={$i2}
text="template=\"1400x1000\", bg=#FFFFFF", font={$f2}, x={$i1}
text="creates a blank document 1400x1000 with (in this example) white background.", font={$f1}, x={$i2}
text="template=\"image.jpg\"", font={$f2}, x={$i1}
text="reads image.jpg as a template.", font={$f1}, x={$i2}
text="text=\"fie foo fum\", x=num, y=num, align=left|center|right, font=name", x={$i1}, font={$f2}
text="  fontsize=num, color=#000000, maxwidth=num, linespc=num", x={$i1}
text="adds text at (x,y), aligned left/center/right with a certain font, size, and color.", font={$f1}, x={$i2}
text="text can contain {\$var} which is replaced by the value of URL parameter &var=..."
text="x/y coordinates and fontsize can be absolute numbers or relative, e.g., 2%"
text="maxwidth makes sure text fits within a certain width, linespc controls line spacing."
text="image=\"file.jpg\", bbox=(40% 40% 60% 60%),", font={$f2}, x={$i1}
text="  align=top|bottom|left|right|center, border=black"
text="places an image inside a bounding box, with an optional border and alignment.", font={$f1}, x={$i2}
text="output=\"file.jpg\"", font={$f2}, x={$i1}
text="writes the document to a file.", font={$f1}, x={$i2}
text="# comment", font={$f2}, x={$i1}
text="a line starting with # is a comment.", font={$f1}, x={$i2}
image="../img/SM-crown.png", bbox=(80% 75% 99% 94%), align=right
text="SM I FOTOGRAFI", font=Cardo-Bold, fontsize=1%, x=89%, y=96%
#output="{$output}"
