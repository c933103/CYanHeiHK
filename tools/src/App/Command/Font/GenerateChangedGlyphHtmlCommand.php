<?php

namespace App\Command\Font;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateChangedGlyphHtmlCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('font:generate-changed-glyph-html')
            ->setDescription('Exports changes of the built font.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $buildDir = $this->getParameter('build_dir');

        $worksets = [['Label' => 'All', 'Id' => -1]];

        $glyphs = [];
        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->query(sprintf('SELECT * FROM cmap c, process p WHERE c.codepoint = p.codepoint ORDER BY p.workset, c.codepoint'));
        foreach ($stmt as $idx => $row) {
            $workset = $row['workset'];
            if (!isset($worksets[$workset])) {
                $worksets[$workset] = ['Id' => $workset, 'Label' => $workset];
            }

            $category = (strtolower($row['tag']) == 'ss') ? 'ss' : $row['category'];
            $actionType = ($row['export'] == 1) ? 'modified' : 'remapped';

            $codepoint = $row['codepoint'];
            $char = mb_convert_encoding(pack('N', $codepoint), 'UTF-8', 'UCS-4BE');

            $glyphs[] = [
                'No' => $idx + 1,
                'Workset' => $workset,
                'Category' => strtoupper($category) . '-' . ucfirst($actionType),
                'U+' => strtoupper(dechex($codepoint)),
                'TW' => $char,
                'Light' => $char,
                'Regular' => $char,
                'Bold' => $char,
            ];
        }

        $doc = file_get_contents($this->getAppDataDir() . '/html/changes.html');
        $doc = str_replace('var worksets = [];', 'var worksets = ' . json_encode(array_values($worksets)) . ';', $doc);
        $doc = str_replace('var data = [];', 'var data = ' . json_encode($glyphs) . ';', $doc);

        file_put_contents($buildDir . '/changes.html', $doc);
    }
}
