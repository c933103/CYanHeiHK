CYanHeiHK (昭源甄黑體)
====================

## Table of Contents

  * [Background](#background)
  * [Differences to the original product](#differences-to-the-original-product)
  * [Supported weights](#supported-weights)
  * [About this repository](#about-this-repository)
  * [Using the command line tools](#using-the-command-line-tools)
  * [Download](#download)
  * [Disclaimer](#disclaimer)

CYanHeiHK is an OpenType font based on Source Han Sans (SHS) from Adobe and Google (it is called Noto Sans CJK when distributed by Google). A number of glyphs have been modified so that it is more suitable for use in Hong Kong.

## Background
The following issues are observed in the current Source Han Sans release:

1. In Unicode, a codepoint can be represented by usually slightly different glyphs due to [Han Unification](https://en.wikipedia.org/wiki/Han_unification). Currently, Source Han Sans supports four different favours in terms of language dependent glyphs, which are Simplified Chinese (PRC), Traditional Chinese (Taiwan), Japanese and Korean. So far there is no Traditional Chinese version targeting Hong Kong's character standard (usually refers to the *List of Graphemes of Commonly-used Chinese Characters 常用字字形表* by the Hong Kong Education Bureau).
2. For practical reason, a non-standard glyph is sometimes more preferred than a standard one. The happens when users are already familiar to a glyph long before the standard was born, but the authority picked a somewhat unfamiliar shape as the standard.

A SHS version targeting Hong Kong is currently planned, which will essentially resolve the first issue. However, the release date of SHS-HK is still yet to be announced, and such a release won't serve all user cases. In particular, while it is good to have a release fully compliant to HK's glyph standard, it may not be always preferred by the HK community given the discrepancy between the “standard” and “conventional” appearance of some characters.

## Differences to the original product

The current release of the font is based on the TW variant of Source Han Sans (version 1.004). 

The following image is an introduction to the font in Chinese, which demonstrates some differences to its base:

![Font intro](doc/images/intro.png?raw=true "About this font, in Chinese")

And Here is a summary: 

1. Some glyphs are modified to conform to HK standard, and some glyphs are modified to use the more conventional form.
2. Some glyphs are modified to their traditional forms as they are more suitable for Gothic style. Example of  affected components are 艹, 女, 雨, ⺼.
3. Some glyphs are optimized so that they appear better in Regular weight.
4. The 辶 component is redesigned. 
5. A small number of glyphs are optimized in Regular weight. 
6. The size of some full-width punctuations (，。、：；！？) are adjusted.
7. A small number of glyphs are modified to comply with HK's standard, even though such a character already exist but in different codepoints. For this part, only glyphs of the following two components are affected: (1) 兌 → 兑, thus 說 → 説, 悅 → 悦; (2) 𥁕 → 昷, thus 溫 → 温. HK uses the latter glyphs, but most people are accustomed to typing the former glyphs in PC due to historical reasons. Because of this, you may not want to use this font in certain cases, like when you are writing an article to discuss the origination of the component 兌 and 兑. 

## Supported weights

Light, Regular and Bold version of the font are provided.

## About this repository

This repository does not only include the compiled font files, but also the script and data files that I use to help me create such product. Feel free to build the font yourself if you don't agree with my philosophy, or fork and modify the font to suit your need.

## Using the command line tools

See [COMMANDS.md](doc/COMMANDS.md) for details.  

## Download

To download the fonts instead of building one manually, visit the [releases](https://github.com/tamcy/CYanHeiHK/releases) page. The zip file contains fonts of the three weights, and a *changes.html* file which you can open to examine the changed glyphs after installing the fonts. Source Han Sans TW also needs to be present for the reference glyph to display correctly. 

## Disclaimer

The CYanHeiHK font and its tools are free for use. The author shall not be responsible or liable for any loss or damage of any kind caused by using this product. For license information of the font, see [LICENSE.txt](LICENSE.txt).
