<?php

namespace App\Command\Characters;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportIICOREDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('chardata:import-iicore-data')
            ->setDescription('Imports IICORE data with Hong Kong source');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import IICORE data for Hong Kong');
        $path = $this->getAppDataDir() . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'IICORE.txt';
        $io->comment('File path: ' . $path);

        $data = file($path);
        $io->progressStart(count($data));
        $total = 0;

        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->prepare('UPDATE chardata SET iicore_hk = ?, iicore_tw = ?, iicore_jp = ?, iicore_mo = ? WHERE codepoint = ?');
        $conn->beginTransaction();
        foreach ($data as $lineNo => $line) {
            if ($lineNo < 11) {
                continue;
            }

            $rawCodepoint = substr($line, 0, 5);
            $codepoint = hexdec($rawCodepoint);
            $hkCategory = trim(substr($line, 14, 3)) ?: null;
            $twCategory = trim(substr($line, 8, 3)) ?: null;
            $jpCategory = trim(substr($line, 11, 3)) ?: null;
            $moCategory = trim(substr($line, 20, 3)) ?: null;
            if (!$hkCategory && !$twCategory && !$jpCategory && !$moCategory) {
                continue;
            }

            ++$total;
            $stmt->execute([$hkCategory, $twCategory, $jpCategory, $moCategory, $codepoint]);
            if ($stmt->rowCount() < 1) {
                $io->error('WARNING: ' . $rawCodepoint . ' not found in the database');
            }

            $io->progressAdvance();
        }
        $conn->commit();
        $io->progressFinish();

        $io->success($total . ' characters imported');
    }
}
