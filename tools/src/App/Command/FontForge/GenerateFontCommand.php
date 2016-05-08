<?php

namespace App\Command\FontForge;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
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
            ->addArgument('workset_id', InputArgument::OPTIONAL, 'Limits the workset ID to generate')
            ->addOption('weight', 'w', InputOption::VALUE_REQUIRED, 'Specify the weight to act upon', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('FontForge: Generate .pfa files in workset(s)');

        $ids = $this->getImportedWorksetIds();
        $customWorksetId = $input->getArgument('workset_id');
        if ($customWorksetId) {
            if (array_search($customWorksetId, $ids) !== false) {
                $ids = [$customWorksetId];
            } else {
                throw new InvalidArgumentException('Invalid workset ID.');
            }
        }

        if (!$ids) {
            $io->error('No workset to work on.');
            die;
        }

        $scriptFile = $this->getAppDataDir() . DIRECTORY_SEPARATOR . 'ffscript' . DIRECTORY_SEPARATOR . 'generate.pe';
        if (!is_file($scriptFile)) {
            throw new \Exception('Fontforge script file not found:' . $scriptFile);
        }

        $fontForgeBin = $this->getParameter('fontforge_bin');
        if (!is_file($fontForgeBin)) {
            throw new \Exception('To use this command, "fontforge_bin" must be specified in the parameter file');
        }

        $weights = $this->getActionableWeights($input->getOption('weight'));

        foreach ($ids as $id) {
            $io->block(':::::::::: Workset #' . $id . ' ::::::::::');
            $this->generatePFA($io, $fontForgeBin, $scriptFile, $id, $weights);
        }

        $io->success('Operation complete');
    }

    private function generatePFA(SymfonyStyle $io, $fontForgeBin, $scriptFile, $worksetId, $weights)
    {
        $worksetDir = $this->getWorksetDir($worksetId);

        $io->text('Workset ID: ' . $worksetId);
        $io->text('Workset directory: ' . $worksetDir);
        $io->text('Weights: ' . implode(', ', $weights));

        $categories = ['s', 'a', 'o'];

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
                $outputFile = $dir . DIRECTORY_SEPARATOR . $category . '.pfa';
                if (!file_exists($file)) {
                    $io->text(' - SKIPPED: ' . $category . '.sfd');
                    continue;
                }

                $this->runExternalCommand($io, '"' . $fontForgeBin . '" -script ' . $scriptFile . ' ' . $file);

                if (file_exists($outputFile)) {
                    $content = file_get_contents($outputFile);
                    $content = preg_replace(["{%%CreationDate: .+\n}", "{%%Creator: .+\n}"], '', $content);
                    file_put_contents($outputFile, $content);
                }

                $io->text(' - DONE: ' . $category . '.sfd');
            }
        }
    }
}
