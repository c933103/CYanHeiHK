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
            ->addOption('weight', 'w', InputOption::VALUE_REQUIRED, 'Specify the weight to act upon', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

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
            $this->buildFont($io, $shsDir, $ids, $buildDir, $weight);
        }
    }

    private function buildFont(SymfonyStyle $io,
                               $shsDirRoot,
                               $worksetIds,
                               $buildDirRoot,
                               $weight)
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
                $categories = ['punc'];
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

        // 2. Merge original cidfont.ps.OTC.TC with new glyph ps generated in previous step.
        $io->section('Replacing original font data with the generated new glyphs');

        $this->runExternalCommand($io, sprintf('%s %s %s %s',
            $this->getAfdkoCommand('mergeFonts'),
            $wBuildDir . '/all.ps',
            $wBuildDir . '/merged.ps',
            $wShsFontDir . '/cidfont.ps.OTC.TC'
        ));

        // 3. Merge original cidfont.ps.OTC.TC with new glyph ps generated in previous step.
        $io->section('Build final OTF');

        $otfPath = $buildDirRoot . DIRECTORY_SEPARATOR . 'CYanHeiHK-' . $weight . '.otf';
        $io->comment($otfPath);

        // 3. Finally, build OTF.
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

}
