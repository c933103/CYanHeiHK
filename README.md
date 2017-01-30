CYanHeiHK (昭源甄黑體)
====================

[Click here for Chinese version 中文版請按此](README.zh.md)  

## Table of Contents

  * [Background](#background)
  * [Differences to the original product](#differences-to-the-original-product)
  * [Scope of review process](#scope-of-review-process)
  * [Available weights](#supported-weights)
  * [About this repository](#about-this-repository)
  * [Using the command line tools](#using-the-command-line-tools)
  * **[Download](#download)**
  * [Important notes](#important-notes)
  * [Disclaimer](#disclaimer)

CYanHeiHK is an OpenType font based on Source Han Sans (SHS) from Adobe and Google (it is called Noto Sans CJK when distributed by Google). A number of glyphs have been modified so that it is more suitable for use in Hong Kong.

## Background

In Unicode, a codepoint can be represented by different glyphs in different regions due to [Han Unification](https://en.wikipedia.org/wiki/Han_unification). Currently, Source Han Sans comes with four variants: Simplified Chinese, Traditional Chinese (following Taiwan's glyph standard), Japanese and Korean. The Traditional Chinese variant follows the glyph standard specified by Taiwan's Ministry of Education. Hong Kong, also using Traditional Chinese characters , has its own character standard specified by the Hong Kong Education Bureau that is different from Taiwan's. 

That said, note that most of the printed materials in Hong Kong doesn't 100% comply to the standard. This is because developing a font product fulfilling the standard is voluntary, but not enforced. Font vendors have the freedom to choose whether to adopt the standard forms in their typeface products. For certain characters, people may actually be more familiar to the non-standard glyphs that are widely adopted in printed materials. They are mostly shapes used in early desktop publishings inherited from movable types.

CYanHeiHK can serve as an interim solution before the release of the new Source Han Sans variant that adheres to Hong Kong's upcoming glyph standard. However, CYanHeiHK doesn't aim to be a typeface that is 100% HK standard compliant. Instead, it takes reference from common commercial font products and tries to strike a balance between the “standard” and “conventional” forms to make it more appealing to general users.

## Differences to the original product

Currently, CYanHeiHK is based on the Traditional Chinese (Taiwan) variant of Source Han Sans (version 1.004). 

The following image serves as an introduction to the font in Chinese, which demonstrates some differences to its base:

![Font intro](doc/images/intro.png?raw=true "About this font, in Chinese")

And here is a summary: 

1. Some glyphs are modified to conform to HK standard.
2. Some glyphs are modified to use the more conventional form.
2. Glyphs having certain components are modified to their traditional forms as I believe they are more suitable for Gothic style. Example of affected components are 艹, 女, 雨, ⺼.
3. Some glyphs are tuned so that they appear better in Regular weight.
4. The 辶 component is redesigned. 
5. Sizes of some full-width punctuations (，。、：；！？) are adjusted.	
6. Lastly, appearances of the half-width, proportional digit “1” and letter “g” are changed. The bottom horizontal line of “1” has been removed, and “g” is changed to single-storey form.    

## Development

Two specificiations exist when talking about Hong Kong's character standard, namely the *List of Graphemes of Commonly-used Chinese Characters 常用字字形表* published by the Hong Kong Education Bureau, and the *Reference Guide on Hong Kong Character Glyphs 香港電腦漢字字形參考指引* published by the Office of the Government Chief Information Officer (OGCIO) . There are discrepancies between the two standards. This project mainly uses the former.

This project uses the TW variant of SHS as the working base since it is the closest to the HK standard. Characters are reviewed and adjustments are made where necessary. The language specific version of SHS-TW covers around 44,600 ideographs, and around 17,600 characters (covered in Big5+HKSCS character set) are adjusted to comply with Taiwan's MoE standard. Characters beyond the Big5+HKSCS range can still be accessed, just that they are not guaranteed to meet Taiwan's glyph standard.

As this is a personal project, it would be impractical for me to go through all characters in the original font and fix every glyph. Even the number of characters in the Big5+HKSCS scope is too large for me. So the scope of work is defined as follow:

1. Characters covered by Big5 and HKSCS charsets will be the maximum supported range. The list of characters is extracted from the `Unihan_OtherMappings.txt` file in the [Unihan database](http://www.unicode.org/Public/UCD/latest/ucd/). From the file, there are 17,642 candidates in total (13,063 in Big5 plus 4,579 in HKSCS).
2. 4,805 characters listed in *常用字字形表 (List of Graphemes of Commonly-used Chinese Characters)* are reviewed and modified. The character list is extracted from EDB's [*Lexical Lists for Chinese Learning in Hong Kong* (香港小學學習字詞表)  website](http://www.edbchinese.hk/lexlist_ch/index.htm). The list is supposed to contain most (if not all) of the frequently used characters in Hong Kong. As expected, all characters are included by either Big5 or HKSCS.
3. 5,224 characters listed in IICORE with Hong Kong source identifier (H1A - H1F) are also reviewed and fixed. The source file can be found [here](http://www.unicode.org/L2/L2010/10375-02n4153-files/IICORE.txt). Many overlap with *香港小學學習字詞表*, so there are just 456 additional candidates to be processed. Also worth noting is that there are characters in *香港小學學習字詞表* not covered by IICORE, so it cannot be seen as a subset of IICORE.
4. IICORE characters with Taiwan, Macau and Japan source identifiers are reviewed. A fix will be applied when remapping is possible.
5. Other characters are evaluated individually, and are modified if considered suitable for HK.

There are actually two ways to “fix” a glyph so that it complies to the font's standard. This includes amending the shapes of the glyphs with some tools, or changing the mapping information of the codepoint. The latter is possible because SHS is a multi locales typeface, so in some occasions the alternate glyph in other langauge is exactly what we need for CYanHeiHK. In this case, we just need to point the codepoint to the desired glyph ID.

Not all characters are treated equal. Most efforts are spent on the frequently used characters. It is less likely for non-frequently used characters to receive an amendment treatment.

In this project, remapping is preferred over amendment. This means that the font will try to reuse existing glyphs when possible, even when certain component is not exactly the same as that is used in the TW version. For instance, the followings components are different between variants, but may be ignored by this project:

![Region level variants](doc/images/region-level-variants.png?raw=true "Region-level variants ignored in this font")

The above subtle discrepancies are considered not significant enough for a whole new glyph. This is especially true for non-frequently used characters, when the option is either to leave them as-is or remap them to those closer to the desired glyphs.

The font still includes all characters covered by the original Source Han Sans TC, just that the unreviewed characters are not guaranteed to meet the font's glyph standard. For instance, although you can access 縎 (Big5: EAD3) in this font, its 骨 component does not adhere to Hong Kong's glyph standard. As (a) there is no alternate glyph matching our standard so a remap is not possible, and (b) the character is rarely used, it is left unmodified. The situation shall improve in the future with the release of SHS-TCHK so that this project can be updated to use it as the working base.

## Available weights

Light, Regular and Bold version of the font are provided.

(For a codepoint, when a glyph needs to be modified, the amendment has to be done independently on each weight. There is simply not enough manpower to support all weights.)

## About this repository

This repository does not only include the font files, but also the script and data files that I use to help create such product. Feel free to build it yourself, or modify it to suit your need.

## Using the command line tools

See [COMMANDS.md](doc/COMMANDS.md) for details.  

## Download

Visit the [releases](https://github.com/tamcy/CYanHeiHK/releases) page to download the fonts. Currently two desktop builds and one web font build are available for download.

### Desktop fonts

Two variants of the font pacakge are available for desktop use. Note that the font names are exactly the same, so you cannot install both.
  
* `CYanHeiHK_{version}.7z` is the “normal version” of the OpenType (.OTF) font files. You're probably looking for this.
* `CYanHeiHK_{version}_adjusted_fontbbox.7z` is the “adjusted Font BBox version”, provided to work around some edge cases caused by the features inherited from Source Han Sans. [Click here for more information](doc/FONTBBOX-ADJUSTED-VERSION.md). Only use it when you understand what it does.
 
The zip file contains the installable fonts, the license file, and a *changes.html* file which you can use to examine the changed glyphs after installing the fonts. Source Han Sans TW also needs to be installed for the reference glyph to display correctly.

### Subsetted fonts

`CYanHeiHK_{version}_subset.7z` is a special build that contains a subsetted version of CYanHeiHK (~8,410 codepoints), with most OpenType features removed. The package contains fonts in WOFF and WOFF2 formats which can be used for the web. Most modern browsers should support either one or both. Also, *hinted* and *unhinted* version are provided for each format and each weight. In addition, subsetted .ttf files are also available which can be used for e.g. embedding in mobile apps.

Detail of subset coverage is as below: 

For Chinese character glyphs,
   * characters listed in IICORE with Hong Kong, Macau, Taiwan, or Japan source identifier are included by default but will be excluded if rarely used. For Japan source, only those overlapping the Big5 or HKSCS code range are included by default. I try to be conservative here, around 370 characters are removed in the selection process. 
   * most characters revised (either remapped or modified) for Hong Kong's needs in this project are included;
   * some additional characters (mostly HKSCS characters like 㗅, 喆) are manually selected to include in the web font.  

For non-Chinese character glyphs,
   * ASCII, punctuation marks, full-width characters, Hiragana and Katakana symbols are included, except for the "no-latin" vairant.

## Important notes

* The original language specific OTFs contain glyphs of the non-default language, so that users can access them using the same font resource through the ‘locl’ GSUB feature in OpenType. This feature still exists in CYanHeiHK, but should not be relied on because the HK glyphs are developed by selecting the region specific glyphs closest to the desired form and modify them when necessary. I plan to remove this ‘locl’ feature in the future.   

## Disclaimer

The CYanHeiHK font and its tools are free for use. The author shall not be responsible or liable for any loss or damage of any kind caused by using this product. For license information of the font, see [LICENSE.txt](LICENSE.txt).