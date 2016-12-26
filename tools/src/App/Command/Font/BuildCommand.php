<?php

namespace App\Command\Font;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('font:build')
            ->setDescription('Shortcut to run ff:generate, font:build-final-cmap, font:build-otf, and font:generate-modified-glyph-pdf in order')
            ->addOption('weight', 'w', InputOption::VALUE_REQUIRED, 'Specify the weight to act upon', null)
            ->addOption('skip-ff-generate', 's', InputOption::VALUE_NONE, 'Do not run ff:generate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $buildArgs = [];
        $ffArgs = [];
        if ($weight = $input->getOption('weight')) {
            $ffArgs['--weight'] = $weight;
            $buildArgs['--weight'] = $weight;
        }

        if (!$input->getOption('skip-ff-generate')) {
            $this->runSubCommand('ff:generate', new ArrayInput($ffArgs), $output);
        }

        $this->runSubCommand('db:init-subset', new ArrayInput([]), $output);
        $this->runSubCommand('font:build-final-cmap', new ArrayInput([]), $output);
        $this->runSubCommand('font:build-merged-ps', new ArrayInput($buildArgs), $output);
        $this->runSubCommand('font:build-otf', new ArrayInput($buildArgs), $output);
        $this->runSubCommand('font:build-subset', new ArrayInput($buildArgs), $output);
        $this->runSubCommand('font:generate-modified-glyph-pdf', new ArrayInput($buildArgs), $output);
        $this->runSubCommand('font:generate-changed-glyph-html', new ArrayInput([]), $output);
    }
}