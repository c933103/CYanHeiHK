<?php

namespace App\Command\Database;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueryCmapCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('db:query-cmap')
            ->addArgument('codepoint', InputArgument::REQUIRED, 'The codepoint to query')
            ->setDescription('Query CID by codepoint');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Query CID by codepoint');

        $database = $this->getCharacterDatabase();
        $conn = $database->getConnection();
        $stmt = $conn->prepare('SELECT * FROM cmap WHERE codepoint = :codepoint');

        $items = [];
        $codepoints = explode(',', $input->getArgument('codepoint'));
        foreach ($codepoints as $codepoint) {
            if (preg_match('/^U\+?([A-F0-9]+)/i', $codepoint, $matches)) {
                $codepoint = hexdec($matches[1]);
            }
            if (!is_numeric($codepoint)) {
                throw new InvalidArgumentException('Codepoint ' . $codepoint . ' invalid');
            }

            $stmt->execute(['codepoint' => $codepoint]);
            $rows = $stmt->fetchAll();
            if (count($rows)) {
                $row = $rows[0];
                $items[] = [
                    $codepoint,
                    dechex($codepoint),
                    $row['cid_jp'],
                    $row['cid_kr'],
                    $row['cid_cn'],
                    $row['cid_tw'],
                ];
            } else {
                $items[] = [
                    $codepoint,
                    dechex($codepoint),
                    'Not found',
                    'Not found',
                    'Not found',
                    'Not found',
                ];
            }
        }

        $io->table(['Codepoint', 'Unicode', 'CID (JP)', 'CID (KR)', 'CID (CN)', 'CID (TW)'], $items);
    }
}
