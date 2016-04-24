<?php

namespace App\Command\Workset;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Initializes a workset.
 *
 * This commands will import selection result to the database
 */
class InitializeCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('workset:init')
            ->setDescription('Initializes a workset by importing the selection result to the database')
            ->addArgument('workset_id', InputArgument::REQUIRED, 'Workset ID')
            ->addArgument('selection_file', InputArgument::OPTIONAL, 'Selection result file', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Initialize workset');

        $worksetId = $input->getArgument('workset_id');
        $resultFile = $input->getArgument('selection_file');

        $worksetSelectionDir = $this->getParameter('data_dir') . DIRECTORY_SEPARATOR . 'ws_select_result';
        $defaultResultFile = $worksetSelectionDir . DIRECTORY_SEPARATOR . $worksetId . '.txt';

        $io->text('Workset ID: ' . $worksetId);

        if (!$resultFile) {
            $resultFile = $defaultResultFile;
            $io->text('Result file (DEFAULT): ' . $resultFile);
        } else {
            $io->text('Result file: ' . $resultFile);
        }

        if (!file_exists($resultFile)) {
            throw new InvalidArgumentException('Unable to locate the selection result file: ' . $resultFile);
        }

        $io->text('');

        if ($resultFile != $defaultResultFile) {
            if (!is_dir($worksetSelectionDir)) {
                mkdir($worksetSelectionDir, 0755, true);
            }
            copy($resultFile, $defaultResultFile);
            $io->text('Selection result file copied to data directory, full path: ' . $defaultResultFile);
        }

        $data = file($resultFile);

        $localeMap = [
            'C' => 'cn',
            'J' => 'jp',
            'K' => 'kr',
            'T' => 'tw',
        ];

        $io->text('Removing old workset result');

        $database = $this->getCharacterDatabase();
        $database->deleteAll('process', 'workset = ' . $worksetId);

        $sqls = [];

        $io->text('Reading selection result data');

        foreach ($data as $line) {
            list($codepoint, $tag, $action1, $action2) = explode("\t", $line);

            $category = strtolower(substr($tag, 0, 1));

            if (strpos($action1, '@') !== false) {
                list($action1, $hex) = explode('@', $action1);
                $remapCodepoint = hexdec($hex);
            } else {
                $remapCodepoint = $codepoint;
            }

            if ($action1 == '*') {
                continue;
            }

            switch ($action1) {
                case 'C':
                case 'J':
                case 'K':
                    $newCid = $this->findCid($remapCodepoint, $localeMap[$action1]);
                    if ($action2 == 'F') {
                        // Remap: Yes, Replacement: Yes
                        $action = self::SELECTION_ACTION_OPTIMIZE;
                        $export = true;
                    } else {
                        // Remap: Yes, Replacement: No
                        $action = self::SELECTION_ACTION_REMAP;
                        $export = false;
                    }
                    break;
                case 'F':
                    // Remap: No, Replacement: Yes
                    $action = self::SELECTION_ACTION_OPTIMIZE;
                    $newCid = $this->findCid($remapCodepoint, 'tw');
                    $export = true;
                    break;
                case 'D':
                case 'DC':
                case 'DJ':
                case 'DK':
                    // Remap: Yes, Replacement: Yes
                    $locale = (strlen($action1) > 1) ? substr($action1, 1) : 'T';
                    $action = self::SELECTION_ACTION_DESIGN;
                    $newCid = $this->findCid($remapCodepoint, $localeMap[$locale]);
                    $export = true;
                    break;
                default:
                    throw new \Exception("Unknown tag $tag");
            }

            $sql = sprintf("INSERT INTO process (codepoint, workset, category, action, new_cid, export) 
                      VALUES (%d, %d, '%s', '%s', %s, %d)",
                $codepoint, $worksetId, $category, $action, $newCid, (int)$export
            );
            $sqls[$codepoint] = $sql;
        }

        $io->text('Importing selection result');
        $io->progressStart(count($sqls));

        $conn = $database->getConnection();
        $conn->beginTransaction();
        foreach ($sqls as $sql) {
            $conn->exec($sql);
            $io->progressAdvance();
        }
        $conn->commit();
        $io->progressFinish();

        $io->success('Done. Now you may run workset:create to create files that needs to be processed.');
    }

    private function findCid($codepoint, $locale)
    {
        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->query(sprintf('SELECT cid_%s as cid FROM cmap WHERE codepoint = %s', $locale, $codepoint));
        foreach ($stmt as $row) {
            if (!$row['cid']) {
                throw new \Exception('Incorrect CID ' . $row['cid']);
            }

            return $row['cid'];
        }

        throw new \Exception(sprintf('Unable to locale CID of codepoint %d!', $codepoint));
    }
}
