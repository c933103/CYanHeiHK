# About the “adjusted Font BBox” version

Starting from 1.002, you can choose to download the “normal” version and the new “adjusted Font BBox” version (experimental) on the CYanHeiHK release page. The fonts in these packages are identical, with an exception of the value of a font metadata called “Font BBox”. 

“Font BBox” is a bounding box that can accommodate the largest possible glyph in a font. According to the [Adobe Type-1 specification](https://partners.adobe.com/public/developer/en/font/T1_SPEC.PDF), Font BBox values are used by computer programs for glyph clipping and caching. It is exceptionally large in Source Han Sans (and the derived CYanHeiHK) because of some very wide or tall glyphs. Among those, CID \1346, \1347, \63028, \63029 are wider than usual, and \1438, \1439, \65152, \65153 are taller than usual. Here is how the tall glyphs look like:

![Tall glyphs](images/tall-glyphs.png?raw=true "Tall glyphs")

Glyph \15798 is provided for comparison. \1438 and \1439 are used in Japanese, while \65152, \65153 are full-width dash (破折號) for vertical layout that spans two and three characters respectively.  
 
It is usually the tall glyphs (resulting in a tall Font BBox) that cause application compatibility issues. A large Font BBox can cause problem when application uses it for layout purpose (it shouldn't). For instance, in Adobe Illustrator, the selectable region of the text layer will extend to the bottom of the Font BBox when the normal version is used: 
 
![Illustrator issue with large Font BBox](images/illustrator-fontbbox.png?raw=true "Illustrator issue with large Font BBox")
  
This affects layer selection (very easy to mis-select the text layer when clicked on the supposed to be empty region) and bottom alignment, and cause inconveniences.

The special build works around this issue by modifying the Font BBox to a value as if those taller-than-usual glyphs (\1438, \1439, \65152, \65153) don't exist, so that applications depend on it will behave more correctly.

Note that this means that the Font BBox value no longer complies with the specification. Thus, **be warned that this is considered a temporary “hack” but not a ultimate “fix” to the problem. Also note that you may experience unexpected behaviors when those tall glyphs are accessed**. I am providing this special build because I use it personally and believe it may be useful to someone else, but use it with care. My suggestion is NOT to use this special build for (a) vertical typesetting, (b) PDF export, or (c) printing. But it should be fine for (a) rasterized computer graphics (images saved to PNG/JPG, video production), (b) subtitle overlay (which is normally horizontal), or (c) embedding in website or app. Use it at your own risk.

References:
 - https://github.com/adobe-fonts/source-han-sans/issues/88
 - http://tama-san.com/illustrator-boundingbox/ (Japanese website)