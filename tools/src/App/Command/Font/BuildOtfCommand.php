<?php

namespace App\Command\Font;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildOtfCommand extends ContainerAwareCommand
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    protected function configure()
    {
        $this
            ->setName('font:build-otf')
            ->setDescription('Builds OTF font. Must run after font:build-merged-ps.')
            ->addOption('weight', 'w', InputOption::VALUE_REQUIRED, 'Specify the weight to act upon', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $buildDir = $this->getParameter('build_dir');

        $cmapFilePath = $buildDir . DIRECTORY_SEPARATOR . 'cmap';
        if (!file_exists($cmapFilePath)) {
            throw new \Exception('Unable to find CMap file: ' . $cmapFilePath);
        }

        foreach ($this->getActionableWeights($input->getOption('weight')) as $weight) {
            $this->prepareFiles($weight);

            $this->io->section('Building OTF font for ' . $weight . ' weight');

            // Variant 1: Normal flavor
            $config = new BuildConfig();
            $config->weight = $weight;
            $config->cmapPath = $buildDir . '/cmap';
            $config->inputFile = 'all.ps';
            $config->featuresFile = 'features.OTC.TC';
            $config->outputPath = $buildDir . DIRECTORY_SEPARATOR . 'CYanHeiHK-' . $weight . '.otf';
            $this->buildFont($config);

            // Variant 2: Font BBox adjusted flavor
            $config->inputFile = 'all_adjusted_fontbbox.ps';
            $config->outputPath = $buildDir . DIRECTORY_SEPARATOR . 'CYanHeiHK-' . $weight . '-AdjustedFontBBox.otf';
            $this->buildFont($config);

            // Variant 3: Trimmed feature for building woff
            $config->featuresFile = 'features_trimmed.OTC.TC';
            $config->outputPath = $buildDir . DIRECTORY_SEPARATOR . $weight . DIRECTORY_SEPARATOR . 'CYanHeiHK-TrimmedFeatures.otf';
            $this->buildFont($config);
        }
    }

    private function prepareFiles($weight)
    {
        $dirs = $this->getDirConfigForWeight($weight);
        $replaces = [
            '%version%' => $this->getParameter('version'),
        ];

        $this->copyFile($dirs['font_info_dir'], $dirs['build_dir'], 'features.OTC.TC', $replaces);
        $this->copyFile($dirs['font_info_dir'], $dirs['build_dir'], 'features_trimmed.OTC.TC', $replaces);
        $this->copyFile($dirs['font_info_dir'], $dirs['build_dir'], 'cidfontinfo.OTC.TC', $replaces);

        $this->copyFile($this->getParameter('shs_dir'), $dirs['build_dir'], 'SourceHanSans_TWHK_sequences.txt');
        $this->copyFile($this->getAppDataDir() . '/fontinfo', $dirs['build_dir'], 'FontMenuNameDB');
    }

    private function buildFont(BuildConfig $config)
    {
        $dirs = $this->getDirConfigForWeight($config->weight);

        $otfPath = $config->outputPath;
        $this->io->comment($otfPath);

        $this->runExternalCommand($this->io, sprintf('%s -f %s -ff %s -fi %s -mf %s -r -nS -cs 2 -ch %s -ci %s -o %s',
            $this->getAfdkoCommand('makeotf'),
            $dirs['build_dir'] . '/' . $config->inputFile,
            $dirs['build_dir'] . '/' . $config->featuresFile,
            $dirs['build_dir'] . '/cidfontinfo.OTC.TC',
            $dirs['build_dir'] . '/FontMenuNameDB',
            $config->cmapPath,
            $dirs['build_dir'] . '/SourceHanSans_TWHK_sequences.txt',
            $config->outputPath
        ));
    }
}

class BuildConfig
{
    public $weight;
    public $inputFile;
    public $outputPath;
    public $cmapPath;
    public $featuresFile;
}
