<?php

namespace App\Command\Font;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildCmapCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('font:build-final-cmap')
            ->setDescription('Build final CMap');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $buildDir = $this->getParameter('build_dir');

        // Generate new CMAP file (base on UniSourceHanSansTW-UTF32-H)
        $io->section('Generating new CMap file');

        $content = $this->generateNewCmap($io);
        $content = str_replace('{DATA}', $content, file_get_contents($this->getAppDataDir() . '/fontinfo/UniSourceHanSansTW-UTF32-H.tpl'));
        @mkdir($buildDir, 0755, true);
        file_put_contents($buildDir . '/cmap', $content);
    }

    private function generateNewCmap(SymfonyStyle $io)
    {
        // Build cmap table
        $cmap = [];

        $stmt = $this->getCharacterDatabase()->getConnection()->query('SELECT c.codepoint, c.cid_tw AS cid, p.new_cid FROM cmap c LEFT JOIN process p ON c.codepoint = p.codepoint ORDER BY c.codepoint', \PDO::FETCH_ASSOC);

        $lastCodepoint = -1;
        $lastCid = -1;
        $codepointRangeStart = false;
        $rows = $stmt->fetchAll();
        $total = count($rows);
        $io->progressStart($total);
        foreach ($rows as $idx => $row) {
            $codepoint = $row['codepoint'];
            $cid = $row['new_cid'] ?: $row['cid'];

            if ($codepoint - $lastCodepoint == 1 && $cid - $lastCid == 1) {
                if (!$codepointRangeStart) {
                    $codepointRangeStart = $lastCodepoint;
                }

                ++$cmap[$codepointRangeStart]['count'];
            } else {
                $codepointRangeStart = false;
                $cmap[$codepoint] = [
                    'count' => 1,
                    'cid' => $cid,
                ];
            }

            $lastCodepoint = $codepoint;
            $lastCid = $cid;

            $io->progressAdvance();
        }

        $io->progressFinish();

        //
        $io->text('Producing CMap content');
        $charLines = [];
        $rangeLines = [];
        foreach ($cmap as $codepoint => $data) {
            if ($data['count'] == 1) {
                // Char mode
                $charLines[] = sprintf('<%08s> %d', dechex($codepoint), $data['cid']);
            } else {
                // Range mode
                $rangeLines[] = sprintf('<%08s> <%08s> %d', dechex($codepoint), dechex($codepoint + $data['count'] - 1), $data['cid']);
            }
        }

        // Write cmap file
        $s = '';
        $this->writeLinesInChunk($s, 'begincidchar', 'endcidchar', $charLines);
        $this->writeLinesInChunk($s, 'begincidrange', 'endcidrange', $rangeLines);

        $io->success('Done');

        return $s;
    }

    private function writeLinesInChunk(&$s, $begin, $end, $input)
    {
        foreach (array_chunk($input, 100) as $lines) {
            $s .= count($lines) . ' ' . $begin . "\n";
            foreach ($lines as $line) {
                $s .= $line . "\n";
            }
            $s .= $end . "\n";
            $s .= "\n";
        }
    }
}
