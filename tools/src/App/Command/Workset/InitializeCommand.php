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
            ->addArgument('selection_file', InputArgument::REQUIRED, 'Selection result file', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Initialize workset');

        $resultFile = $input->getArgument('selection_file');

        $worksetSelectionDir = $this->getParameter('data_dir') . DIRECTORY_SEPARATOR . 'fixtures';
        $defaultResultFile = $worksetSelectionDir . DIRECTORY_SEPARATOR . 'worksets.txt';

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
        $database->deleteAll('process', null);

        $sqls = [];

        $io->text('Reading selection result data');

        foreach ($data as $lineNo => $line) {
            list($codepoint, $worksetId, $tag, $action1, $action2) = explode("\t", $line);

            $category = strtolower(substr($tag, 0, 1));

            if (strpos($action1, '@') !== false) {
                list($action1, $hex) = explode('@', $action1);
                $remapCodepoint = hexdec($hex);
            } else {
                $remapCodepoint = $codepoint;
            }

            $io->comment(sprintf('%s: %s, %s', $codepoint, $category, $action1));

            if ($action1 == '*') {
                continue;
            }
            $action1 = str_replace('*', '', $action1);
            switch ($action1) {
                case 'C':
                case 'J':
                case 'K':
                case 'T':
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
                    throw new \Exception("Unknown action $action1 at line " . ($lineNo + 1));
            }

            $sql = sprintf("INSERT INTO process (codepoint, workset, tag, category, action, new_cid, export) 
                      VALUES (%d, %d, '%s', '%s', '%s', %s, %d)",
                $codepoint, $worksetId, $tag, $category, $action, $newCid, (int)$export
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
