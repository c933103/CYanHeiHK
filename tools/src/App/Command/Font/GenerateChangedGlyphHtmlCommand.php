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

        $exportGlyphs = [];
        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->query(sprintf('SELECT * FROM cmap c, process p WHERE c.codepoint = p.codepoint ORDER BY p.workset, c.codepoint'));
        foreach ($stmt as $row) {
            $exportGlyphs[$row['codepoint']] = [
                'workset' => $row['workset'],
                'category' => (strtolower($row['tag']) == 'ss') ? 'ss' : $row['category'],
                'action_type' => ($row['export'] == 1) ? 'modified' : 'remapped',
            ];
        }

        $html = [];
        $counter = [];
        foreach (['s', 'ss', 'a', 'o'] as $category) {
            foreach (['remapped', 'modified'] as $action) {
                $html[$category . '_' . $action] = '';
                $counter[$category . '_' . $action] = 0;
            }
        }

        foreach ($exportGlyphs as $codepoint => $data) {
            $char = mb_convert_encoding(pack('N', $codepoint), 'UTF-8', 'UCS-4BE');
            $key = $data['category'] . '_' . $data['action_type'];
            ++$counter[$key];
            $html[$key] .= sprintf(
                '<tr>
<td class="idx">%d</td>
<td class="u">%s</td>
<td class="orig">%s</td>
<td class="new light">%s</td>
<td class="new regular">%s</td>
<td class="new bold">%s</td>
</tr>
',
                $counter[$key], strtoupper(dechex($codepoint)), $char, $char, $char, $char
            );
        }

        $doc = file_get_contents($this->getAppDataDir() . '/html/document.html');
        $tableTpl = file_get_contents($this->getAppDataDir() . '/html/table.html');
        foreach ($html as $categoryKey => $content) {
            $content = str_replace('%rows%', $content, $tableTpl);
            $doc = str_replace(
                ['%' . $categoryKey . '%', '%' . $categoryKey . '_count%'],
                [$content, $counter[$categoryKey]],
                $doc
            );
        }

        file_put_contents($buildDir . '/changes.html', $doc);
    }
}
