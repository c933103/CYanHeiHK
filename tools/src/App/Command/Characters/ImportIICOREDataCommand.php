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
            ->setDescription('Imports IICORE data (only categories of HK)');
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
        $stmt = $conn->prepare('UPDATE chardata SET iicore_category = ? WHERE codepoint = ?');
        $conn->beginTransaction();
        foreach ($data as $lineNo => $line) {

            if ($lineNo < 11) {
                continue;
            }

            $codepoint = hexdec(substr($line, 0, 5));
            $hkCategory = trim(substr($line, 14, 3));
            if (!$hkCategory) {
                continue;
            }
            $total++;
            $stmt->execute([$hkCategory, $codepoint]);

            $io->progressAdvance();
        }
        
        $conn->commit();
        $io->progressFinish();

        $io->success($total . ' characters imported');
    }
}
