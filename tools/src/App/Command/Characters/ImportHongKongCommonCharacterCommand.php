<?php

namespace App\Command\Characters;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportHongKongCommonCharacterCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('chardata:import-hk-common-chars')
            ->setDescription('Imports characters that are common in Hong Kong');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Common Hong Kong characters');
        $path = $this->getAppDataDir() . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'hk_common_chars.txt';
        $io->comment('File path: ' . $path);

        $data = file($path);
        $io->progressStart(count($data));
        $total = 0;

        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->prepare('UPDATE chardata SET hk_common = ? WHERE codepoint = ?');
        $conn->beginTransaction();
        foreach ($data as $line) {

            $line = trim($line);
            $chars = explode(' ', $line);
            foreach ($chars as $char) {
                if (!$char) {
                    continue;
                }

                ++$total;

                $uchar = mb_convert_encoding($char, "UCS-4BE", 'UTF-8');
                $val = unpack("N", $uchar);
                $codepoint = $val[1];

                $stmt->execute([$total, $codepoint]);
            }

            $io->progressAdvance();
        }
        $conn->commit();
        $io->progressFinish();

        $io->success($total . ' characters imported');
    }
}
