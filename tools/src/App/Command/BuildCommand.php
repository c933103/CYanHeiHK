<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:build')
            ->setDescription('Build processed font')
            ->addArgument('workset_id', InputArgument::OPTIONAL, 'The workset ID', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $worksetId = $input->getArgument('workset_id');

        $shsDir = $this->getParameter('shs_dir');
        $worksetDir = $this->getWorksetDir($worksetId);
        $buildDir = $this->getParameter('build_dir');
        $weights = $this->getParameter('weights');

        $fontInfoDir = $this->getAppDataDir() . '/fontinfo';

        // Step 1. Generate new CMAP file (base on UniSourceHanSansTW-UTF32-H)

        $io->section('Generating new CMap file');

        $content = $this->generateNewCmap($io);
        $content = str_replace('{DATA}', $content, file_get_contents($this->getAppDataDir() . '/fontinfo/UniSourceHanSansTW-UTF32-H.tpl'));
        @mkdir($buildDir, 0755, true);
        file_put_contents($buildDir . '/cmap', $content);

        // Step 2. For each weight...
        foreach ($weights as $weight) {

            $io->section('Working on ' . $weight . ' weight');

            $wNewFontInfoDir = $fontInfoDir . '/' . $weight;
            $wBuildDir = $buildDir . '/' . $weight;
            $wWorksetDir = $worksetDir . '/' . $weight;
            $wShsFontDir = $shsDir . '/' . $weight . '/OTC';

            @mkdir($wBuildDir, 0755, true);

            $io->text('Merging modified glyphs into single ps file');

            // 2a. Merge new glyphs into single ps
            $this->runExternalCommand($io, sprintf('%s -cid %s %s %s',
                $this->getAfdkoCommand('mergeFonts'),
                $this->getAppDataDir() . '/fontinfo/' . $weight . '/cidfontinfo.OTC.TC',
                $wBuildDir . '/merged.ps ',
                $wWorksetDir . '/s.map ' . $wWorksetDir . '/s.pfa ' .
                $wWorksetDir . '/a.map ' . $wWorksetDir . '/a.pfa ' .
                $wWorksetDir . '/o.map ' . $wWorksetDir . '/o.pfa '
            ));

            $io->text('Replace original font data with the generated new glyphs');

            // 2b. Merge original cidfont.ps.OTC.TC with new glyph ps generated in last step.
            $this->runExternalCommand($io, sprintf('%s %s %s %s',
                $this->getAfdkoCommand('mergeFonts'),
                $wBuildDir . '/all.ps',
                $wBuildDir . '/merged.ps',
                $wShsFontDir . '/cidfont.ps.OTC.TC'
            ));

            $io->text('Build final OTC');
            // 2c. Finally, build OTC.
            $this->runExternalCommand($io, sprintf('%s -f %s -ff %s -fi %s -r -nS -cs 2 -ch %s -ci %s',
                $this->getAfdkoCommand('makeotf'),
                $wBuildDir . '/all.ps',
                $wNewFontInfoDir . '/features.OTC.TC',
                $wNewFontInfoDir . '/cidfontinfo.OTC.TC',
                $buildDir . '/cmap',
                $shsDir . '/SourceHanSans_TWHK_sequences.txt'
            ));
        }
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

                $cmap[$codepointRangeStart]['count']++;
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
        $this->writeLines($s, 'begincidchar', 'endcidchar', $charLines);
        $this->writeLines($s, 'begincidrange', 'endcidrange', $rangeLines);

        $io->success('Done');

        return $s;
    }

    private function writeLines(&$s, $begin, $end, $input)
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
