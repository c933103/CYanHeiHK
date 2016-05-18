<?php

namespace App\Command\Characters;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExportCandidateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('chardata:export-candidates')
            ->setDescription('Exporter candidates for selection')
            ->addArgument('target_path', InputArgument::REQUIRED, 'Target path for exported content');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Export character data for selection');
        $target = $input->getArgument('target_path');
        $io = new SymfonyStyle($input, $output);

        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->query('SELECT * FROM chardata ORDER BY codepoint');
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $lines = [];
        $lines[] = implode("\t", [
            'Codepoint',
            'Big5',
            'HKSCS',
            'HKPW',
            'IICORE_HK',
            'IICORE_TW',
            'IICORE_JP',
            'IICORE_MO',
            'JP',
            'KR',
            'CN',
            '',
            'TW',
            'HK',
            '',
            '',
            '',
            '',
            'Category',
            'Action1',
            'Action2',
        ]);
        $io->progressStart(count($items));
        foreach ($items as $idx => $item) {
            $io->progressAdvance();
            $char = mb_convert_encoding(pack('N', $item['codepoint']), 'UTF-8', 'UCS-4BE');

            $a = [
                $item['codepoint'],
                $item['big5'],
                $item['hkscs'],
                $item['hk_common'],
                $item['iicore_hk'],
                $item['iicore_tw'],
                $item['iicore_jp'],
                $item['iicore_mo'],
                $char,
                $char,
                $char,
                '',
                $char,
                $char,
                $char,
                $char,
                $char,
                $char,
                '',
            ];

            $lines[] = implode("\t", $a);
        }
        $io->progressFinish();

        file_put_contents($target, implode("\n", $lines));

        $io->success('Done');
    }
}
