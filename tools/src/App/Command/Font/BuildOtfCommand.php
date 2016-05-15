<?php

namespace App\Command\Font;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildOtfCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('font:build-otf')
            ->setDescription('Builds OTF font. Must run after font:build-final-cmap.')
            ->addOption('weight', 'w', InputOption::VALUE_REQUIRED, 'Specify the weight to act upon', null)
            ->addOption('fix-fontbbox', null, InputOption::VALUE_NONE, 'If supplied, apply hack to fix the exceptionally large FontBBox');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $fixFontBBox = $input->getOption('fix-fontbbox');

        $shsDir = $this->getParameter('shs_dir');
        $buildDir = $this->getParameter('build_dir');

        $ids = $this->getImportedWorksetIds();
        rsort($ids);
        $cmapFilePath = $buildDir . DIRECTORY_SEPARATOR . 'cmap';
        if (!file_exists($cmapFilePath)) {
            throw new \Exception('Unable to find CMap file: ' . $cmapFilePath);
        }

        foreach ($this->getActionableWeights($input->getOption('weight')) as $weight) {
            $io->section('Building font for ' . $weight . ' weight');
            $this->buildFont($io, $shsDir, $ids, $buildDir, $weight, $fixFontBBox);
        }
    }

    private function buildFont(SymfonyStyle $io,
                               $shsDirRoot,
                               $worksetIds,
                               $buildDirRoot,
                               $weight,
                               $fixFontBBox)
    {
        $fontInfoDir = $this->getAppDataDir() . '/fontinfo';

        $wNewFontInfoDir = $fontInfoDir . '/' . $weight;
        $wBuildDir = $buildDirRoot . '/' . $weight;
        $wShsFontDir = $shsDirRoot . '/' . $weight . '/OTC';

        $mergeFileArgs = [];

        array_unshift($worksetIds, 0);

        foreach ($worksetIds as $worksetId) {
            $wWorksetDir = $this->getWorksetDir($worksetId) . '/' . $weight;

            if ($worksetId == 0) {
                $categories = ['punc', 'digits', 'alpha'];
            } else {
                $categories = ['s', 'a', 'o'];
            }

            foreach ($categories as $category) {
                $mapFile = $wWorksetDir . '/' . $category . '.map';
                $pfaFile = $wWorksetDir . '/' . $category . '.pfa';

                if (file_exists($mapFile) && file_exists($pfaFile)) {
                    $mergeFileArgs[] = $mapFile . ' ' . $pfaFile;
                }
            }
        }

        @mkdir($wBuildDir, 0755, true);

        // 1. Merge new glyphs into single ps
        $io->section('Merging modified glyphs into a single ps file');

        $this->runExternalCommand($io, sprintf('%s -cid %s %s %s',
            $this->getAfdkoCommand('mergeFonts'),
            $wNewFontInfoDir . '/cidfontinfo.OTC.TC',
            $wBuildDir . '/merged.ps ',
            implode(' ', $mergeFileArgs)
        ));

        $io->text('Auto-hinting the merged file');

        $this->runExternalCommand($io, sprintf('%s %s',
            $this->getAfdkoCommand('autohint'),
            $wBuildDir . '/merged.ps '
        ));

        // 2. Merge original cidfont.ps.OTC.TC with new glyph ps generated in previous step.
        $io->section('Replacing original font data with the generated new glyphs');

        $this->runExternalCommand($io, sprintf('%s %s %s %s',
            $this->getAfdkoCommand('mergeFonts'),
            $wBuildDir . ($fixFontBBox ? '/all.ps.tmp' : '/all.ps'),
            $wBuildDir . '/merged.ps',
            $wShsFontDir . '/cidfont.ps.OTC.TC'
        ));

        if ($fixFontBBox) {
            // 3. Fix FontBBox.
            $this->fixFontBBox($io, $wShsFontDir, $wNewFontInfoDir, $wBuildDir, $wBuildDir . '/all.ps.tmp', $wBuildDir . '/all.ps');
        }

        // 4. Fix CFF data
        $io->section('Fixing the CFF table');
        $this->fixCffDefinitionData($io, $wBuildDir . '/all.ps');

        // 5. Merge original cidfont.ps.OTC.TC with new glyph ps generated in previous step.
        $io->section('Building final OTF');

        $otfPath = $buildDirRoot . DIRECTORY_SEPARATOR . 'CYanHeiHK-' . $weight . ($fixFontBBox ? '-AdjustedFontBBox' : '') . '.otf';
        $io->comment($otfPath);

        // 6. Finally, build OTF.
        $this->runExternalCommand($io, sprintf('%s -f %s -ff %s -fi %s -mf %s -r -nS -cs 2 -ch %s -ci %s -o %s',
            $this->getAfdkoCommand('makeotf'),
            $wBuildDir . '/all.ps',
            $wNewFontInfoDir . '/features.OTC.TC',
            $wNewFontInfoDir . '/cidfontinfo.OTC.TC',
            $this->getAppDataDir() . '/fontinfo/FontMenuNameDB',
            $buildDirRoot . '/cmap',
            $shsDirRoot . '/SourceHanSans_TWHK_sequences.txt',
            $otfPath
        ));
    }

    private function fixFontBBox(SymfonyStyle $io,
                                 $wShsFontDir,
                                 $wNewFontInfoDir,
                                 $wBuildDir,
                                 $inputFile,
                                 $outputFile)
    {
        $io->section('Fixing font BBox');

        // Generate a temporary file with ridiculously tall glyphs removed, to get the
        // smaller bbox value. Wide glyphs shouldn't cause issue so isn't removed.
        $bboxFixFile = $wBuildDir . '/bboxfix.ps';
        $this->runExternalCommand($io, sprintf('%s -cid %s %s %s',
            $this->getAfdkoCommand('mergeFonts'),
            $wNewFontInfoDir . '/cidfontinfo.OTC.TC',
            $bboxFixFile,
            sprintf('%1$s/bboxfix.map %1$s/bboxfix.pfa', $this->getWorksetDir(0) . '/Shared')
        ));

        $tmpPsFile = $wBuildDir . '/all_sans_tall_glyphs.ps';

        $this->runExternalCommand($io, sprintf('%s %s %s %s',
            $this->getAfdkoCommand('mergeFonts'),
            $tmpPsFile,
            $wBuildDir . '/bboxfix.ps',
            $wShsFontDir . '/cidfont.ps.OTC.TC'
        ));

        // Determine the correct BBOx, logic ported from
        // https://github.com/adobe-type-tools/perl-scripts/blob/master/Scripts/fix-fontbbox.pl
        $llx = $urx = 500;
        $lly = $ury = 380;

        $logfile = $tmpPsFile . '.afm_log';
        $this->runExternalCommand($io, 'tx -afm ' . $tmpPsFile . '  > ' . $logfile);

        if (!file_exists($logfile)) {
            throw new \Exception('Unexpected file not found: ' . $logfile);
        }

        $originalBbox = null;
        foreach (file($logfile) as $line) {
            if (preg_match('/FontBBox (.+)/', $line, $matches)) {
                $originalBbox = trim($matches[1]);
                $io->text('Original FontBBox: ' . $matches[1]);
            }

            if (preg_match('/^C\s+.+;\s+N\s+.+\s+;\s+B\s+(.+)\s+;/', $line, $matches)) {
                list($a, $b, $c, $d) = explode(' ', $matches[1]);
                if ($a < $llx) {
                    $llx = $a;
                }
                if ($b < $lly) {
                    $lly = $b;
                }
                if ($c > $urx) {
                    $urx = $c;
                }
                if ($d > $ury) {
                    $ury = $d;
                }
            }
        }

        $io->text(" Correct FontBBox: $llx $lly $urx $ury");

        if (!$originalBbox) {
            throw new \Exception('Original FontBBox information not found!');
        }

        $ifp = fopen($inputFile, 'r');
        $ofp = fopen($outputFile, 'w');

        $fontBboxReplaced = false;

        $io->text('');
        $io->text('Writing file with new FontBBox information...');

        while (!feof($ifp)) {
            // very confident that the FontBBox definition exists within the first megabyte of the file
            $content = fread($ifp, 1024 * 1024);

            if (!$fontBboxReplaced) {
                $oldBBox = 'FontBBox {' . $originalBbox . '}';
                $pos = strpos($content, $oldBBox);
                if ($pos !== false) {
                    $newBbox = sprintf('FontBBox {%s %s %s %s}', $llx, $lly, $urx, $ury);
                    $content = substr_replace($content, $newBbox, $pos, strlen($oldBBox));
                    $fontBboxReplaced = true;
                }
            }

            fwrite($ofp, $content);
        }

        fclose($ifp);
        fclose($ofp);

        $io->text('Done.');
    }

    private function fixCffDefinitionData(SymfonyStyle $io, $inputFile)
    {
        $outputFile = $inputFile . '.tmp';

        $ifp = fopen($inputFile, 'r');
        $ofp = fopen($outputFile, 'w');
        $defContentReplaced = false;
        while (!feof($ifp)) {
            $content = fread($ifp, 1024 * 1024);

            if ($defContentReplaced) {
                fwrite($ofp, $content);
            } else {
                $endDefPos = strpos($content, '%%BeginData');
                if ($endDefPos === false) {
                    throw new \Exception('Unable to find expected string %%BeginData');
                }

                $defContent = substr($content, 0, $endDefPos);

                // 1. Replace all occurrences of SourceHanSansTC with CYanHeiHK
                $defContent = str_replace('SourceHanSansTC', 'CYanHeiHK', $defContent);

                // 2. Removes XUID definition
                $defContent = preg_replace("{/XUID \\[.+\\] def\n}", '', $defContent);

                // 3. Replaces copyright notice
                $defContent = preg_replace('{/Notice .+ def}',
                    '/Notice (Copyright 2014-2016 Adobe Systems Incorporated (http://www.adobe.com/).) def',
                    $defContent);

                fwrite($ofp, $defContent);
                fwrite($ofp, substr($content, $endDefPos));
                $defContentReplaced = true;
            }
        }

        fclose($ifp);
        fclose($ofp);

        unlink($inputFile);
        rename($outputFile, $inputFile);
    }
}
