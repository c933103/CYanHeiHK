<?php

namespace App\Command\Font;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildTtfCommand extends ContainerAwareCommand
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    protected function configure()
    {
        $this
            ->setName('font:build-ttf')
            ->setDescription('Converts built OTF font to TTF. Must run after font:build-otf.')
            ->addOption('weight', 'w', InputOption::VALUE_REQUIRED, 'Specify the weight to act upon', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $buildDir = $this->getParameter('build_dir');

        foreach ($this->getActionableWeights($input->getOption('weight')) as $weight) {

            $this->io->section('Building TTF font for ' . $weight . ' weight');

            $otfFontPath = $buildDir . DIRECTORY_SEPARATOR . 'CYanHeiHK-' . $weight . '-AdjustedFontBBox.otf';
            if (!file_exists($otfFontPath)) {
                throw new \Exception('Unable to find OTF file: ' . $otfFontPath);
            }

            $otfJsonFilePath = $buildDir . DIRECTORY_SEPARATOR . 'CYanHeiHK-' . $weight . '.otf.json';
            $ttfJsonFilePath = $buildDir . DIRECTORY_SEPARATOR . 'CYanHeiHK-' . $weight . '.ttf.json';
            $ttfFontPath = $buildDir . DIRECTORY_SEPARATOR . 'CYanHeiHK-' . $weight . '-AdjustedFontBBox.ttf';

            // Dump font
            $this->io->writeln('Dumping font data...');
            $this->runExternalCommand($this->io, sprintf('%s %s -o %s',
                $this->getParameter('otfccdump_bin'),
                $otfFontPath,
                $otfJsonFilePath
            ));

            // Converts OTF curve format to TTF
            $this->io->writeln('Converting font data...');
            $this->runExternalCommand($this->io, sprintf('%s < %s > %s',
                $this->getParameter('otfccc2q_bin'),
                $otfJsonFilePath, $ttfJsonFilePath
            ));

            // Builds TTF
            $this->io->writeln('Building TTF font...');
            $this->runExternalCommand($this->io, sprintf('%s %s -o %s',
                $this->getParameter('otfccbuild_bin'),
                $ttfJsonFilePath,
                $ttfFontPath
            ));

            unlink($otfJsonFilePath);
            unlink($ttfJsonFilePath);
        }
    }
}
