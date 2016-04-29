<?php

namespace App\Command\Characters;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class ImportBig5CharacterDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('chardata:import-big5-chars')
            ->setDescription('Imports character that falls under the Big5 and HKSCS range');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Big5 and HKSCS character data');

        $mappingDbPath = $this->getAppDataDir() . '/fixtures/Unihan_OtherMappings.txt';
        if (!file_exists($mappingDbPath)) {
            throw new InvalidArgumentException('Unable to find Unihan mapping database file: ' . $mappingDbPath);
        }

        $this->getCharacterDatabase()->deleteAll('chardata', null);

        $chars = [];

        $data = file($mappingDbPath);
        $count = count($data);

        $io->section('Reading mapping data');

        $io->progressStart(count($data));

        foreach ($data as $line) {
            $line = trim($line);
            if (!$line || substr($line, 0, 1) == '#') {
                continue;
            }

            list($unicode, $encoding, $codepoint) = explode("\t", $line);
            if ($encoding != 'kBigFive' && $encoding != 'kHKSCS') {
                continue;
            }

            $id = hexdec(substr($unicode, 2));
            ++$count;

            if (!isset($chars[$id])) {
                $chars[$id] = [
                    'big5' => null,
                    'hkscs' => null,
                ];
            }

            if ($encoding == 'kBigFive') {
                $chars[$id]['big5'] = $codepoint;
            }

            if ($encoding == 'kHKSCS') {
                $chars[$id]['hkscs'] = $codepoint;
            }
            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->section('Writing mapping data to the database');

        $io->progressStart(count($chars));

        $conn = $this->getCharacterDatabase()->getConnection();
        $conn->beginTransaction();
        $stmt = $conn->prepare('INSERT INTO chardata (codepoint, big5, hkscs) VALUES (?, ?, ?)');
        foreach ($chars as $id => $data) {
            $stmt->execute([$id, $data['big5'], $data['hkscs']]);
            $io->progressAdvance();
        }
        $conn->commit();
        $io->progressFinish();
        $io->success('Done');
    }
}
