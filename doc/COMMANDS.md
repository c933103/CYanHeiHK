# Using the command line tools

This page only shows the steps to set up the command line tools to build CYanHeiHK using the new mapping file and updated glyphs included in this repository. Documentation of creating those mapping and glyph files from Source Han Sans TW is under construction.

## Prerequisites

To use the tools, the following files must be present in your working machine: 

* [Source Han Sans](https://github.com/adobe-fonts/source-han-sans) project files. The modified glyphs will be merged to the original file set.
* [Adobe Font Development Kit for OpenType (AFDKO)](https://github.com/adobe-fonts/source-han-sans). This project uses `autohint`, `tx` and `mergeFonts`.
* [FontTools](https://github.com/behdad/fonttools). The tool `pyftsubset` is required for building web font. Also needed is the [Brotli Python extension](https://github.com/google/brotli) to build WOFF2 font. 
* Optionally, [FontForge](https://fontforge.github.io/en-US/) is required to run FontForge related command.
* The scripts are written in [PHP](http://php.net/), so it has to be installed first.
* [Composer](https://getcomposer.org/download/), a package management system for PHP, is required to install the project dependencies.

## Setting up

1. Clone this repo: `git clone https://github.com/tamcy/CYanHeiHK.git`
2. Go to the `tools/` directory, and run `composer install` to install the dependencies.
3. Open `tools/app/parameters.php.dist` file, change the various paths to meaningful values, then save it as `tools/app/parameters.php`.

Now you can run `php console` to see a list of the available commands.

## Building the font

### Step 1: Data initialization  

Run the following commands to initialize everything:

```php
php console db:init
php console chardata:init
php console workset:init data/fixtures/worksets.txt
```

### Step 2: Build font

The font can be built using the following command: 

```php
php console font:build -s
```

The built font will be written to the `build_dir` directory specified in the configuration file. Also generated are PDFs of the changed glyphs. In addition, there will be an HTML file named `changes.html`, which shows all remapped and modified glyphs is also produced in the build directory. Open this file in the browser to examine the changes after installing the built fonts.