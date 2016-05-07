<?php

namespace App\Command;

use App\Data\Database;
use Pimple\Container;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

abstract class ContainerAwareCommand extends BaseCommand
{
    const SELECTION_ACTION_REMAP = 'remap';
    const SELECTION_ACTION_OPTIMIZE = 'optimize';
    const SELECTION_ACTION_DESIGN = 'design';

    const SELECTION_CATEGORY_STANDARD = 's';
    const SELECTION_CATEGORY_OPTIMIZE = 'o';
    const SELECTION_CATEGORY_AESTHETIC = 'a';

    /**
     * @var Container
     */
    private $container;

    public function __construct($container, $name = null)
    {
        parent::__construct($name);
        $this->container = $container;
    }

    protected function getAppDataDir()
    {
        return realpath(__DIR__ . '/../../../data');
    }

    protected function getAppWorkspaceDir()
    {
        return $this->getParameter('workspace_dir');
    }

    protected function getWorksetDir($i)
    {
        return $this->getAppWorkspaceDir() . DIRECTORY_SEPARATOR . 'set' . $i;
    }

    protected function getParameter($key)
    {
        return $this->container['parameters'][$key];
    }

    /**
     * @return Database
     */
    protected function getCharacterDatabase()
    {
        return $this->container['db'];
    }

    protected function getImportedWorksetIds()
    {
        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->query('SELECT DISTINCT workset FROM process WHERE workset > 0 ORDER BY workset');
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[] = (int)$row[0];
        }

        return $result;
    }

    protected function runExternalCommand(SymfonyStyle $io, $cmd)
    {
        if ($io->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $io->comment($cmd);
        }

        $process = new Process($cmd);
        $process->run(function ($type, $buffer) use ($io) {
            $io->text($buffer);
        });
    }

    protected function getAfdkoCommand($command)
    {
        return $this->getParameter('afdko_bin_dir') . DIRECTORY_SEPARATOR . $command;
    }

    protected function runSubCommand($command, InputInterface $input, OutputInterface $output)
    {
        $cmd = $this->getApplication()->get($command);
        $cmd->run($input, $output);
    }

    protected function getActionableWeights($suppliedWeights)
    {
        $defaultWeights = $this->getParameter('weights');
        if (!$suppliedWeights) {
            return $defaultWeights;
        }

        if (!is_array($suppliedWeights)) {
            $suppliedWeights = explode(',', $suppliedWeights);
        }

        if ($diff = array_diff($suppliedWeights, $defaultWeights)) {
            throw new InvalidArgumentException('Unsupported weight: ' . implode(', ', $diff));
        }

        return $suppliedWeights;
    }

    protected function getSourceHanSansPsFilePath($weight)
    {
        return $this->getParameter('shs_dir')
        . DIRECTORY_SEPARATOR . $weight
        . DIRECTORY_SEPARATOR . 'OTC'
        . DIRECTORY_SEPARATOR . 'cidfont.ps.OTC.TC';
    }
}
