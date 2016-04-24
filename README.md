# CYanHeiHK (昭源甄黑體)

CYanHeiHK is an OpenType font. It is based on Source Han Sans (SHS) from Adobe and Google (it is called Noto Sans CJK when distributed by Google), with glyphs modified to better suit Hong Kong people's needs.

## Background

The author sees the following issues in the current Source Han Sans release:

1. In Unicode, the same codepoint can result in a variation of glyph appearance, due to the differences in regional standards. Currently, Source Han Sans supports four different favours in terms of regional variations, which are Simplified Chinese (PRC), Traditional Chinese (Taiwan), Japanese and Korean. So far, there is no Traditional Chinese version released targeting Hong Kong's character standard (which usually refer to List of Graphemes of Commonly-used Chinese Characters 常用字字形表 released by the Hong Kong Education Bureau in 2007).
2. For pratical reason, a non-standard glyph is sometimes more preferred than the standard one. The happens  when users are already familiar to a glyph long before the standard was born, and then the authority picked another glyph as the standard. 

A SHS version targeting Hong Kong is currently planned, which will essentially resolve the first issue. However, the release date of SHS-HK is still yet to be announced, and such a release won't solve all problems. In particular, while it is good to have a release fully compliant to HK's glyph standard, it may not be always preferred by the HK community given the discrepancy between the "standard" and "conventional" appearance of some characters.

CYanHeiHK may not fit everyone's needs, but it can at least give users another choice.

## Differences to the original product

Here is a summary of differences between CYanHeiHK and its work base, Source Han Sans TW:

1. Some glyphs are modified to conform to HK standard, and some glyphs are modified to use the more conventional glyphs.
2. Some glyphs are modified to their traditional forms as they are more suitable for Gothic style. Example of  affected components are 艹, 女, 雨, ⺼, 竹 (on top).
3. Some glyphs are optimized so that they appear better in Regular weight.
4. A small number of glyphs are modified to suit HK standard even though the same character conforming HK standard is encoded in different codepoints. For this part, only glyphs of the following two components are affected: (1) 兌 → 兑, thus 說 → 説, 悅 → 悦; (2) 𥁕 → 昷, thus 溫 → 温. HK uses the latter glyphs, but most people are accustomed to typing the former glyphs in PC through most input methods for historical reaons.
5. The 辶 component is redesigned. 
6. SHS is a multiple-master (MM) font. The lightest (ExtraLight) and the heaviest (Heavy) versions are produced by human, while intermediate weights (Thin/Normal/Regular/Medium/Bold) are interpolated by computer software. Normal/Weight are the most frequently used weights, but due to their interpolated nature, some strokes look somewhat inferior. Some glyphs are tuned 
7. The size of some full-width punctuations (，。、：；！？) are adjusted.

Note that the font name "CYanHeiHK" doesn't imply a full compliance to Hong Kong's glyph standard, nut the author is confident that it is better suited to be used in Hong Kong.

## Supported weights

I plan to release a version three weights, which are Light, Regular and Bold. In the current preview, only Regular and Bold are released.

## What's included in this repository

This repository does not only include the final font product, but also the script and data files that I use to help me create such product. Feel free to build the font yourself if you don't agree with my philosophy, or fork and modify the font to suit your need.

## Using the tools

### Prerequisites

To use the tools, the following files must be present in your working machine. 

* [Source Han Sans](https://github.com/adobe-fonts/source-han-sans) project files. The modified glyphs will be merged to the original file set.
* [Adobe Font Development Kit for OpenType (AFDKO)](https://github.com/adobe-fonts/source-han-sans). `tx` and `mergeFonts` are used by this project.
* Optionally, [FontForge](https://fontforge.github.io/en-US/) is required to run FontForge related command.
* The scripts are written in [PHP](http://php.net/).
* [Composer](https://getcomposer.org/download/), a package management system for PHP, is required to install the project dependencies.

### Installation

1. First, clone this repo: `git clone `
2. Go to the `tools/` directory, and run `composer install` to install the dependencies.
3. Open `tools/app/parameters.php.dist` file, fill it with meaning values, then save it as `tools/app/parameters.php`.

Now you can run `php console` to see a list of the available commands.

### Usage

(TBD)

### Disclaimer

The CYanHeiHK font and its tools are free for use. The author shall not be responsible or liable for any loss or damage of any kind caused by using this product.
