# CYanHeiHK (昭源甄黑體)

CYanHeiHK is an OpenType font based on Source Han Sans (SHS) from Adobe and Google (it is called Noto Sans CJK when distributed by Google). A number of glyphs have been modified so that it is more suited for use in Hong Kong.

## Background

The following issues are observed in the current Source Han Sans release:

1. In Unicode, the same codepoint can result in a variation of glyph appearance due to (Han Unification)[https://en.wikipedia.org/wiki/Han_unification]. Currently, Source Han Sans supports four different favours in terms of language dependent glyphs, which are Simplified Chinese (PRC), Traditional Chinese (Taiwan), Japanese and Korean. So far, there is no Traditional Chinese version targeting Hong Kong's character standard (which *usually* refers to List of Graphemes of Commonly-used Chinese Characters 常用字字形表 released by the Hong Kong Education Bureau).
2. For pratical reason, a non-standard glyph is sometimes more preferred than a standard one. The happens when users are already familiar to a glyph long before the standard was born, but the authority picked an unfamiliar representation as the standard. 

A SHS version targeting Hong Kong is currently planned, which will essentially resolve the first issue. However, the release date of SHS-HK is still yet to be announced, and such a release won't solve all problems. In particular, while it is good to have a release fully compliant to HK's glyph standard, it may not be always preferred by the HK community given the discrepancy between the “standard” and “conventional” appearance of some characters.

## Differences to the original product

Here is a summary of differences between CYanHeiHK and its work base, Source Han Sans TW:

1. Some glyphs are modified to conform to HK standard, and some glyphs are modified to use the more conventional form.
2. Some glyphs are modified to their traditional forms as they are more suitable for Gothic style. Example of  affected components are 艹, 女, 雨, ⺼, 竹 (on top, like 簡,箸).
3. Some glyphs are optimized so that they appear better in Regular weight.
4. A small number of glyphs are modified to suit HK standard even though the the same character conforming HK standard is already encoded in different codepoints. For this part, only glyphs of the following two components are affected: (1) 兌 → 兑, thus 說 → 説, 悅 → 悦; (2) 𥁕 → 昷, thus 溫 → 温. HK uses the latter glyphs, but most people are accustomed to typing the former glyphs in PC through most input methods for historical reasons.
5. The 辶 component is redesigned. 
6. A small number of glyphs is optimized in Regular weight. 
7. The size of some full-width punctuations (，。、：；！？) are adjusted.

## Supported weights

Light, Regular and Bold version of the font are provided.

## What's included in this repository

This repository does not only include the final font product, but also the script and data files that I use to help me create such product. Feel free to build the font yourself if you don't agree with my philosophy, or fork and modify the font to suit your need.

## Using the tools

See (COMMANDS.md)[doc/COMMANDS.md] for details.  

### Disclaimer

The CYanHeiHK font and its tools are free for use. The author shall not be responsible or liable for any loss or damage of any kind caused by using this product.
