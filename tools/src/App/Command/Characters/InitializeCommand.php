<?php

namespace App\Command\Characters;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('chardata:init')
            ->setDescription('')
            ->addArgument('unihan_mapping_db', InputArgument::REQUIRED, 'Path to unihan_mappings.txt');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->runSubCommand('chardata:import-big5-chars', $input, $output);
        $this->runSubCommand('chardata:import-hk-common-chars', new ArrayInput([]), $output);
    }
}