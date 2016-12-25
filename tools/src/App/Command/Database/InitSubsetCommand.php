<?php

namespace App\Command\Database;

use App\Command\ContainerAwareCommand;
use App\Data\Database;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitSubsetCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('db:init-subset')
            ->setDescription('Imports the code points to be included in the subset into the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Import subset code point data into the database');

        $records = $this->prepareSubsetCodepointFile($io);
        $this->importSubsetData($io, $records);

        $io->success('Done.');
    }

    private function prepareSubsetCodepointFile(SymfonyStyle $io)
    {
        $io->section('Preparing subset data');

        $stmt = $this->getCharacterDatabase()->getConnection()->query(
            'SELECT c.codepoint, d.hk_common, d.iicore_hk, d.iicore_tw, d.iicore_jp, d.iicore_mo, c.cid_tw AS cid, p.new_cid 
             FROM cmap c
             LEFT JOIN chardata d ON c.codepoint = d.codepoint 
             LEFT JOIN process p ON c.codepoint = p.codepoint 
             ORDER BY c.codepoint',
            \PDO::FETCH_ASSOC);

        $records = [];

        $rows = $stmt->fetchAll();
        $total = count($rows);
        $io->progressStart($total);

        $keepInSubset = parse_ini_file($this->getAppDataDir() . '/fixtures/subset_includes.txt');

        $extraRanges = [];
        foreach (['noncjk', 'cjk'] as $category) {
            foreach ($keepInSubset[$category . '_range'] as $range) {
                list($from, $to) = explode('..', $range);
                $extraRanges[$category][] = [hexdec($from), hexdec($to)];
            }
        }

        $excludeCodepoints = [];
        $extraCodepoints = [];

        foreach ($keepInSubset['codepoint'] as $codepoint) {
            if (strpos($codepoint, 'U+') === 0) {
                $codepoint = hexdec(substr($codepoint, 2));
            }
            $extraCodepoints[$codepoint] = true;
        }

        foreach ($keepInSubset['exclude_codepoint'] as $codepoint) {
            if (strpos($codepoint, 'U+') === 0) {
                $codepoint = hexdec(substr($codepoint, 2));
            }
            $excludeCodepoints[$codepoint] = true;
        }

        foreach ($rows as $idx => $row) {
            $codepoint = $row['codepoint'];

            $subsetCategory = false;

            if (isset($excludeCodepoints[$codepoint])) {
                $io->progressAdvance();
                continue;
            }

            if (isset($extraCodepoints[$codepoint])
                || $row['hk_common']
                || $row['iicore_hk']
                || $row['iicore_tw']
                || $row['iicore_jp']
                || $row['iicore_mo']
                || $row['new_cid']
            ) {
                $subsetCategory = Database::SUBSET_CATEGORY_CJK;
            } else {
                foreach (['noncjk', 'cjk'] as $category) {
                    foreach ($extraRanges[$category] as $range) {
                        if ($codepoint >= $range[0] && $codepoint <= $range[1]) {
                            $subsetCategory = $category == 'cjk' ? Database::SUBSET_CATEGORY_CJK : Database::SUBSET_CATEGORY_NON_CJK;
                        }
                    }
                }
            }

            if ($subsetCategory) {
                $records[] = [
                    'codepoint' => $codepoint,
                    'hex_cp' => dechex($codepoint),
                    'category' => $subsetCategory,
                ];
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        return $records;
    }

    private function importSubsetData(SymfonyStyle $io, array $records)
    {
        $io->section('Importing data into the database');

        $database = $this->getCharacterDatabase();

        $conn = $database->getConnection();
        $conn->beginTransaction();
        $conn->exec('DELETE FROM subset');
        $stmt = $conn->prepare('INSERT INTO subset (codepoint, hex_cp, category) VALUES (?, ?, ?)');

        $io->progressStart(count($records));
        foreach ($records as $record) {
            $stmt->execute([$record['codepoint'], $record['hex_cp'], $record['category']]);
            $io->progressAdvance();
        }

        $io->progressFinish();
        $conn->commit();
    }
}
