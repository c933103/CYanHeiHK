CYanHeiHK (昭源甄黑體)
====================

## Table of Contents

  * [Background](#background)
  * [Differences to the original product](#differences-to-the-original-product)
  * [Scope of review process](#scope-of-review-process)
  * [Supported weights](#supported-weights)
  * [About this repository](#about-this-repository)
  * [Using the command line tools](#using-the-command-line-tools)
  * [Download](#download)
  * [Important notes](#important-notes)
  * [Disclaimer](#disclaimer)

CYanHeiHK is an OpenType font based on Source Han Sans (SHS) from Adobe and Google (it is called Noto Sans CJK when distributed by Google). A number of glyphs have been modified so that it is more suitable for use in Hong Kong.

## Background

The following issues are observed in the current Source Han Sans release:

1. In Unicode, a codepoint can be represented by usually slightly different glyphs in different regions due to [Han Unification](https://en.wikipedia.org/wiki/Han_unification). Currently, Source Han Sans supports four different favours in terms of language dependent glyphs, which are Simplified Chinese (PRC), Traditional Chinese (Taiwan), Japanese and Korean. So far there is no Traditional Chinese version targeting Hong Kong's character standard (usually refers to the *List of Graphemes of Commonly-used Chinese Characters 常用字字形表* by the Hong Kong Education Bureau).
2. For practical reason, a non-standard glyph is sometimes more preferred. The happens when users are already familiar to a glyph long before the standard was born, but the authority picked a somewhat unfamiliar shape as the standard.

A SHS version targeting Hong Kong is currently planned, which will essentially resolve the first issue. However, the release date of SHS-HK is still yet to be announced, and such a release won't serve all use cases. In particular, while it is good to have a release adhering Hong Kong's glyph standard, such a release may not be always preferred by the community given the discrepancy between the “standard” and “conventional” appearance of some characters.

## Differences to the original product

The current release of the font is based on the TW variant of Source Han Sans (version 1.004). 

The following image serves as an introduction to the font in Chinese, which demonstrates some differences to its base:

![Font intro](doc/images/intro.png?raw=true "About this font, in Chinese")

And here is a summary: 

1. Some glyphs are modified to conform to HK standard, and some glyphs are modified to use the more conventional form.
2. Some glyphs are modified to their traditional forms as they are more suitable for Gothic style. Example of  affected components are 艹, 女, 雨, ⺼.
3. Some glyphs are optimized so that they appear better in Regular weight.
4. The 辶 component is redesigned. 
5. A small number of glyphs are optimized in Regular weight. 
6. The size of some full-width punctuations (，。、：；！？) are adjusted.
7. A small number of glyphs are modified to comply with HK's standard, even though such a character already exist but in different codepoints. For this part, only glyphs of the following two components are affected: (1) 兌 → 兑, thus 說 → 説, 悅 → 悦; (2) 𥁕 → 昷, thus 溫 → 温. HK uses the latter glyphs, but most people are accustomed to typing the former glyphs in PC due to historical reasons. Because of this, you may not want to use this font in certain cases, like when you are writing an article to discuss the origination of the component 兌 and 兑. 

## Scope of review process

To achieve the goal of this project i.e. making it more suitable for Hong Kong, characters in the font have to be reviewed and adjustments (either by remapping or modification) have to be made where necessary. As this is a personal project in spare time, it would be impractical for me to go through all characters (>40,000 glyphs) in the original font to hunt and fix every non standard-compliant shape. The scope of Source Han Sans TW, where only characters in the Big5 + HKSCS character set are modified to comply with Taiwan's MoE standard, is also too large for me, as it still sums up to more than 18,000 characters. As a result, the following decision was made to restrict the number of glyphs to a reasonable extent:
 
1. All characters in BIG5 and HKSCS are listed as candidates to be reviewed. They are extracted using the `Unihan_OtherMappings.txt` file in the [Unihan database](http://www.unicode.org/Public/UCD/latest/ucd/). Characters out of this range are normally ignored.
2. About 4,800 characters listed in *List of Graphemes of Commonly-used Chinese Characters* (常用字字形表) are actively reviewed and labelled for action. They are taken from EDB's [*Lexical Lists for Chinese Learning in Hong Kong* (香港小學學習字詞表)  website](http://www.edbchinese.hk/lexlist_ch/index.htm). The list contains most of the frequently used characters in Hong Kong.
3. Other Big5 characters will not be actively reviewed. I may handle it when I encounter such character and want to fix it.
4. Characters adopted by HKSCS do not imply that they are frequently accessed in Hong Kong. To save time, all HKSCS characters will be reviewed but only those frequently used (which is up to me to decide) will be labelled for further action.

The font still includes the all characters supported by Source Han Sans, just that they are not guaranteed to meet the HK standard (as defined in this product). For example, although the “骨” component of “骨”, “猾”, “滑” are modified, “傦” is left as is. The situation should improve after Source Han Sans HK is released in the future, which CYanHeiHK will certainly use as the new base version to work upon.

## Supported weights

Light, Regular and Bold version of the font are provided.

## About this repository

This repository does not only include the compiled font files, but also the script and data files that I use to help me create such product. Feel free to build the font yourself if you don't agree with my philosophy, or fork and modify the font to suit your need.

## Using the command line tools

See [COMMANDS.md](doc/COMMANDS.md) for details.  

## Download

To download the fonts instead of building one manually, visit the [releases](https://github.com/tamcy/CYanHeiHK/releases) page. The zip file contains fonts of the three weights, and a *changes.html* file which you can open to examine the changed glyphs after installing the fonts. Source Han Sans TW also needs to be present for the reference glyph to display correctly. 

## Important notes

* Due to the special handling of the “兌” component, there will be two codepoints sharing the same glyph when 兌 is the only distinguishing feature. For instance, 說 (U+8AAA) and 説 (U+8AAC) will both appear as 言+兑 (U+8AAC). As a result, this font is not suitable for situation when the user needs to distinguish these two components. The same is applied to the characters with the “𥁕” component.  
* The original language specific Source Han Sans OTFs contain glyphs of the non-default language, so that users can access them using the same font resource through the ‘locl’ GSUB feature in OpenType. This feature still exists in CYanHeiHK, but should not be used because the HK glyphs are developed by selecting the region specific glyphs closest to the desired form and modify them when necessary.

## Disclaimer

The CYanHeiHK font and its tools are free for use. The author shall not be responsible or liable for any loss or damage of any kind caused by using this product. For license information of the font, see [LICENSE.txt](LICENSE.txt).
