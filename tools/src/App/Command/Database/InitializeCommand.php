<?php

namespace App\Command\Database;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('db:init')
            ->setDescription('Runs db:init-schema followed by db:init-cmap and db:init-subset');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->runSubCommand('db:init-schema', $input, $output);
        $this->runSubCommand('db:init-cmap', $input, $output);
        $this->runSubCommand('db:init-subset', $input, $output);
    }
}