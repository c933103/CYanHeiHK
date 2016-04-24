<?php

namespace App\Command\Database;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitSchemaCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('db:init-schema')
            ->setDescription('Resets the character mapping database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Reset character mapping database');

        $this->getCharacterDatabase()->reinitialize();

        $io->success('Done.');
    }
}
