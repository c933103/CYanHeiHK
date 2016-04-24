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
        $worksetDir = $this->getWorksetDir(1);
        $buildDir = $this->getParameter('build_dir');

        $cmapFilePath = $buildDir . DIRECTORY_SEPARATOR . 'cmap';
        if (!file_exists($cmapFilePath)) {
            throw new \Exception('Unable to find CMap file: ' . $cmapFilePath);
        }

        foreach ($this->getActionableWeights($input->getOption('weight')) as $weight) {
            $io->section('Building font for ' . $weight . ' weight');
            $this->buildFont($io, $shsDir, $worksetDir, $buildDir, $weight);
        }
    }

    private function buildFont(SymfonyStyle $io,
                               $shsDirRoot,
                               $worksetDirRoot,
                               $buildDirRoot,
                               $weight)
    {
        $fontInfoDir = $this->getAppDataDir() . '/fontinfo';

        $wNewFontInfoDir = $fontInfoDir . '/' . $weight;
        $wBuildDir = $buildDirRoot . '/' . $weight;
        $wWorksetDir = $worksetDirRoot . '/' . $weight;
        $wShsFontDir = $shsDirRoot . '/' . $weight . '/OTC';

        @mkdir($wBuildDir, 0755, true);

        $io->text('Merging modified glyphs into a single ps file');

        // 1. Merge new glyphs into single ps
        $this->runExternalCommand($io, sprintf('%s -cid %s %s %s',
            $this->getAfdkoCommand('mergeFonts'),
            $wNewFontInfoDir . '/cidfontinfo.OTC.TC',
            $wBuildDir . '/merged.ps ',
            //
            $wWorksetDir . '/punc.map ' . $wWorksetDir . '/punc.pfa ' .
            $wWorksetDir . '/s.map ' . $wWorksetDir . '/s.pfa ' .
            $wWorksetDir . '/a.map ' . $wWorksetDir . '/a.pfa ' .
            $wWorksetDir . '/o.map ' . $wWorksetDir . '/o.pfa '
        ));

        $io->text('Replacing original font data with the generated new glyphs');

        // 2. Merge original cidfont.ps.OTC.TC with new glyph ps generated in previous step.
        $this->runExternalCommand($io, sprintf('%s %s %s %s',
            $this->getAfdkoCommand('mergeFonts'),
            $wBuildDir . '/all.ps',
            $wBuildDir . '/merged.ps',
            $wShsFontDir . '/cidfont.ps.OTC.TC'
        ));

        $otfPath = $buildDirRoot . DIRECTORY_SEPARATOR . 'CYanHeiHK-' . $weight . '.otf';
        $io->text('Build final OTF');
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
