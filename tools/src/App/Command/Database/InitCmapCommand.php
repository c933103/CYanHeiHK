<?php

namespace App\Command\Database;

use App\Command\ContainerAwareCommand;
use App\Data\Database;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitCmapCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('db:init-cmap')
            ->setDescription('Imports Source Han Sans\'s CMap data into the database');
    }

    private function assertFileExists($directory, $filename)
    {
        if (!file_exists($directory . DIRECTORY_SEPARATOR . $filename)) {
            throw new \Exception(sprintf('%s not found in %s!', $filename, $directory));
        }
    }

    private function getCmapFilename($locale)
    {
        return 'UniSourceHanSans' . strtoupper($locale) . '-UTF32-H';
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Import Source Han Sans\'s CMap data into the database');

        $io->section('Checking Source Han Sans\'s directory');
        $locales = ['CN', 'JP', 'KR', 'TW'];
        $directory = $this->getParameter('shs_dir');

        foreach ($locales as $locale) {
            $filename = $this->getCmapFilename($locale);
            $this->assertFileExists($directory, $filename);
            $io->text($filename . ' found.');
        }

        $io->section('Clearing database table');
        $database = $this->getCharacterDatabase();
        $database->deleteAll('cmap', null);
        $io->text('CMap table cleared.');

        foreach ($locales as $locale) {
            $this->importCmapTable($io, $database, $directory, $locale);
        }

        $io->success('Done.');
    }

    private $importedCodePoints = [];

    private function importCmapTable(SymfonyStyle $io, Database $database, $directory, $locale)
    {
        $io->section(sprintf('Importing CMap for %s', $locale));

        $conn = $database->getConnection();
        $conn->beginTransaction();
        $data = file($directory . '/' . $this->getCmapFilename($locale));
        $total = count($data);
        $i = 0;
        while ($i < $total) {
            if (preg_match('#(\d+) (begincidchar|begincidrange)#', $data[$i], $matches)) {
                for ($j = 0; $j < $matches[1]; ++$j) {
                    ++$i;
                    foreach ($this->parseMappingLine($data[$i]) as $codepoint => $cid) {
                        if (isset($this->importedCodePoints[$codepoint])) {
                            $io->comment(sprintf('^ %04d ==> %d', $codepoint, $cid));
                            // Update
                            $database->getConnection()->exec(
                                sprintf('UPDATE cmap SET cid_%s = %d where codepoint = %d',
                                    strtolower($locale), $cid, $codepoint)
                            );
                        } else {
                            // Insert
                            $io->comment(sprintf('+ %04d ==> %d', $codepoint, $cid));
                            $sql = sprintf('INSERT INTO cmap (codepoint, cid_%s) VALUES (%d, %d)',
                                strtolower($locale), $codepoint, $cid);
                            $database->getConnection()->exec($sql);
                            $this->importedCodePoints[$codepoint] = true;
                        }
                    }
                }
            } else {
                ++$i;
            }
        }

        $conn->commit();
    }

    private function parseMappingLine($s)
    {
        $map = [];
        if (preg_match('#^<([0-9a-f]+)>( <([0-9a-f]+)>)? (\d+)$#', $s, $matches)) {
            $start = hexdec($matches[1]);
            $map[$start] = (int)$matches[4];
            if ($matches[3]) {
                $end = hexdec($matches[3]);
                for ($i = 1, $z = $end - $start + 1; $i < $z; ++$i) {
                    $map[$start + $i] = $matches[4] + $i;
                }
            }
        }

        return $map;
    }
}