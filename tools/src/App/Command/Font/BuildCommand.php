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
            ->setDescription('Shortcut to run font:build-final-cmap, font:build-otf, and font:generate-modified-glyph-pdf')
            ->addOption('weight', 'w', InputOption::VALUE_REQUIRED, 'Specify the weight to act upon', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ffOptions = new ArrayInput(['workset_id' => 1]);
        if ($input->getOption('weight')) {
            $ffOptions['--weight'] = $input->getOption('weight');
        }

        $this->runSubCommand('ff:generate', $ffOptions, $output);
        $this->runSubCommand('font:build-final-cmap', new ArrayInput([]), $output);
        $this->runSubCommand('font:build-otf', $input, $output);
        $this->runSubCommand('font:generate-modified-glyph-pdf', $input, $output);
    }
}