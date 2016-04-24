<?php

namespace App\Command\FontForge;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateFontCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ff:generate')
            ->setDescription('Generate .pfa from .sfd files using FontForge')
            ->addArgument('workset_id', InputArgument::REQUIRED, 'Workset ID')
            ->addOption('weight', 'w', InputOption::VALUE_REQUIRED, 'Specify the weight to act upon', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('FontForge: Generate .pfa files in a workset');

        $scriptFile = $this->getAppDataDir() . DIRECTORY_SEPARATOR . 'ffscript' . DIRECTORY_SEPARATOR . 'generate.pe';
        if (!is_file($scriptFile)) {
            throw new \Exception('Fontforge script file not found:' . $scriptFile);
        }

        $fontForgeBin = $this->getParameter('fontforge_bin');
        if (!is_file($fontForgeBin)) {
            throw new \Exception('To use this command, "fontforge_bin" must be specified in the parameter file');
        }

        $worksetId = $input->getArgument('workset_id');
        $worksetDir = $this->getWorksetDir($worksetId);

        $weights = $this->getActionableWeights($input->getOption('weight'));

        $io->text('Workset ID: ' . $worksetId);
        $io->text('Workset directory: ' . $worksetDir);
        $io->text('Weights: ' . implode(', ', $weights));

        $categories = ['s', 'a', 'o'];
        if ($worksetId == 1) {
            $categories[] = 'punc';
        }

        foreach ($weights as $weight) {
            $io->section('Exporting glyphs for ' . $weight . ' weight');

            $dir = $worksetDir . DIRECTORY_SEPARATOR . $weight;

            $io->text('Directory: ' . $dir);

            if (!is_dir($dir)) {
                $io->warning('Directory not found, skipped');
                continue;
            }

            foreach ($categories as $category) {
                $file = $dir . DIRECTORY_SEPARATOR . $category . '.sfd';
                if (!file_exists($file)) {
                    $io->text(' - SKIPPED: ' . $category . '.sfd');
                    continue;
                }

                $this->runExternalCommand($io, '"' . $fontForgeBin . '" -script ' . $scriptFile . ' ' . $file);
                $io->text(' - DONE: ' . $category . '.sfd');
            }
        }

        $io->success('Operation complete');
    }
}
