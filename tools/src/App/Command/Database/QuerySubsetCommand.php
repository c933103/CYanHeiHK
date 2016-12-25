<?php

namespace App\Command\Database;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class QuerySubsetCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('db:query-subset')
            ->addArgument('filename', InputArgument::REQUIRED, 'input filename, or the characters if in inline mode')
            ->addOption('inline', 'i', InputOption::VALUE_NONE, 'Inline mode')
            ->setDescription('Checks if the characters are included in the subset.');
    }

    private function fillStringToCodepointArray($s, array &$codepoints)
    {
        $len = mb_strlen(trim($s));
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($s, $i, 1);
            $codepoint = $this->utf8CharToIntCodepoint($char);
            $codepoints[$codepoint] = [
                'codepoint' => $codepoint,
                'char' => $char,
                'included' => 'No',
            ];
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Query CID by codepoint');
        $filename = $input->getArgument('filename');
        $inlineMode = $input->getOption('inline');

        $codepoints = [];

        if ($inlineMode) {
            $this->fillStringToCodepointArray($filename, $codepoints);
        } else {
            foreach (file($filename) as $line) {
                $this->fillStringToCodepointArray($line, $codepoints);
            };
        }

        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->prepare('SELECT * FROM subset WHERE codepoint = ?');
        foreach ($codepoints as $codepoint => &$data) {
             $stmt->execute([$codepoint]);
             $result = $stmt->fetchAll();
             if (count($result) > 0 ) {
                 $data['included'] = 'Yes';
             }
        }

        ksort($codepoints);
        $io->table(['Codepoint', 'Character', 'Included?'], $codepoints);
    }
}
