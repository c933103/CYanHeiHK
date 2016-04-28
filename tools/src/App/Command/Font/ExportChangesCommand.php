<?php

namespace App\Command\Font;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExportChangesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('font:export-changes-html')
            ->setDescription('Exports changes of the built font.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $shsDir = $this->getParameter('shs_dir');
        $worksetDir = $this->getWorksetDir(1);
        $buildDir = $this->getParameter('build_dir');

        $exportGlyphs = [];
        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->query(sprintf('SELECT * FROM cmap c, process p WHERE c.codepoint = p.codepoint ORDER BY p.workset, c.codepoint'));
        foreach ($stmt as $row) {
            $exportGlyphs[$row['codepoint']] = [
                'workset' => $row['workset'],
                'category' => $row['category'],
                'action_type' => ($row['export'] == 1) ? 'modified' : 'remapped',
            ];
        }

        $html = [];
        $counter = [];
        foreach (['s', 'a', 'o'] as $category) {
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
<td class="idx">%d</td><td class="orig">%s</td><td class="new light">%s</td><td class="new regular">%s</td><td class="new bold">%s</td>
</tr>
',
                $counter[$key], $char, $char, $char, $char
            );
        }

        $tpl = file_get_contents($this->getAppDataDir() . '/html/template.html');
        foreach ($html as $categoryKey => $content) {
            $tpl = str_replace(
                ['%' . $categoryKey . '%', '%' . $categoryKey . '_count%'],
                [$content, $counter[$categoryKey]],
                $tpl
            );
        }

        file_put_contents($buildDir . '/changes.html', $tpl);
    }
}
