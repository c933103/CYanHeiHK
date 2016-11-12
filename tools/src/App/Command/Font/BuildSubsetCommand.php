<?php

namespace App\Command\Font;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildSubsetCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('font:build-subset')
            ->setDescription('Builds subset font, including WOFF, WOFF2 and TTF.')
            ->addOption('weight', 'w', InputOption::VALUE_REQUIRED, 'Specify the weight to act upon', null);
    }

    protected function assertFileExists(SymfonyStyle $io, $path, $errorMessage = '')
    {
        if (!file_exists($path)) {
            $io->error(sprintf('Expected file not found: %s', $path));
            if ($errorMessage) {
                $io->error($errorMessage);
            }

            throw new \Exception('File not found.');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Build WOFF fonts');

        $pyftsubsetBin = $this->getParameter('pyftsubset_bin');
        $subsetFilePath = $this->prepareSubsetCodepointFile($io);
        $buildDir = $this->getParameter('build_dir');

        $scriptFile = $this->getAppDataDir() . DIRECTORY_SEPARATOR . 'ffscript' . DIRECTORY_SEPARATOR . 'otf2ttf.pe';
        if (!is_file($scriptFile)) {
            throw new \Exception('Fontforge script file not found:' . $scriptFile);
        }

        $fontForgeBin = $this->getParameter('fontforge_bin');
        if (!is_file($fontForgeBin)) {
            throw new \Exception('To use this command, "fontforge_bin" must be specified in the parameter file');
        }

        foreach ($this->getActionableWeights($input->getOption('weight')) as $weight) {
            $io->section('Creating font files for ' . $weight . ' weight');
            $dirs = $this->getDirConfigForWeight($weight);
            $fontPath = $dirs['build_dir'] . '/CYanHeiHK-TrimmedFeatures.otf';

            $this->assertFileExists($io, $fontPath, 'Please make sure font:build-otf is run successfully.');

            foreach (['woff', 'woff2'] as $flavor) {
                foreach ([true, false] as $hinting) {

                    $outputFile = $buildDir . DIRECTORY_SEPARATOR . 'CYanHei-TCHK-' . $weight . '-' . ($hinting ? 'hinted' : 'unhinted') . '.' . $flavor;

                    $io->text(' Flavor: ' . $flavor);
                    $io->text('Hinting: ' . ($hinting ? 'Yes' : 'No'));
                    $io->text('   File: ' . $outputFile);

                    $this->runExternalCommand($io,
                        sprintf('%s %s --unicodes-file=%s --flavor=%s --drop-tables+=locl,vhea,vmtx %s --output-file=%s',
                            $pyftsubsetBin,
                            $fontPath,
                            $subsetFilePath['all'],
                            $flavor,
                            $hinting ? '--hinting' : '--no-hinting --desubroutinize',
                            $outputFile
                        ));
                    $io->text('         Done, file created');
                    $io->newLine();
                }
            }


            // TTF, with Latin
            $produceTTF = function ($subset, $hinting, $weight, $outputFilePrefix) use ($io, $buildDir, $fontForgeBin, $pyftsubsetBin, $scriptFile, $fontPath) {

                $this->runExternalCommand($io,
                    sprintf('%s %s --unicodes-file=%s --drop-tables+=locl,vhea,vmtx %s --output-file=%s',
                        $pyftsubsetBin,
                        $fontPath,
                        $subset,
                        $hinting ? '' : '--no-hinting --desubroutinize',
                        $outputFilePrefix . '.otf'
                    ));

                $this->runExternalCommand($io, '"' . $fontForgeBin . '" -script ' . $scriptFile . ' ' . $outputFilePrefix . '.otf ' . $weight);
            };

            foreach (['all', 'cjk'] as $subset) {
                foreach ([true, false] as $hinting) {
                    $io->text(sprintf(' Flavor: TTF / %s / %s',
                        $subset == 'all' ? 'With latin characters' : 'Without latin characters',
                        $hinting ? 'Hinted' : 'Unhinted'
                    ));

                    $outputFilePrefix = sprintf($buildDir . DIRECTORY_SEPARATOR . 'CYanHei-TCHK-' . $weight . '-' . '%s-%s',
                        $subset == 'all' ? 'all' : 'nolatin',
                        $hinting ? 'hinted' : 'unhinted'
                    );

                    $produceTTF($subsetFilePath[$subset], $hinting, $weight, $outputFilePrefix);

                    $io->newLine();
                }
            }
        }
    }

    private function prepareSubsetCodepointFile(SymfonyStyle $io)
    {
        $io->section('Preparing font subsetting file');

        $buildDir = $this->getParameter('build_dir');

        $stmt = $this->getCharacterDatabase()->getConnection()->query(
            'SELECT c.codepoint, d.hk_common, d.iicore_hk, d.iicore_tw, d.iicore_jp, d.iicore_mo, c.cid_tw AS cid, p.new_cid 
             FROM cmap c
             LEFT JOIN chardata d ON c.codepoint = d.codepoint 
             LEFT JOIN process p ON c.codepoint = p.codepoint 
             ORDER BY c.codepoint',
            \PDO::FETCH_ASSOC);

        $lines = [
            'cjkonly' => [],
            'all' => [],
        ];

        $rows = $stmt->fetchAll();
        $total = count($rows);
        $io->progressStart($total);

        $keepInSubset = parse_ini_file($this->getAppDataDir() . '/fixtures/subset_includes.txt');

        $extraRanges = [];
        foreach (['noncjk', 'cjk'] as $category) {
            foreach ($keepInSubset[$category . '_range'] as $range) {
                list($from, $to) = explode('..', $range);
                $extraRanges[$category][] = [hexdec($from), hexdec($to)];
            }
        }

        $excludeCodepoints = [];
        $extraCodepoints = [];

        foreach ($keepInSubset['codepoint'] as $codepoint) {
            if (strpos($codepoint, 'U+') === 0) {
                $codepoint = hexdec(substr($codepoint, 2));
            }
            $extraCodepoints[$codepoint] = true;
        }

        foreach ($keepInSubset['exclude_codepoint'] as $codepoint) {
            if (strpos($codepoint, 'U+') === 0) {
                $codepoint = hexdec(substr($codepoint, 2));
            }
            $excludeCodepoints[$codepoint] = true;
        }

        foreach ($rows as $idx => $row) {
            $codepoint = $row['codepoint'];

            $included = false;

            if (isset($excludeCodepoints[$codepoint])) {
                $io->progressAdvance();
                continue;
            }

            if (isset($extraCodepoints[$codepoint])
                || $row['hk_common']
                || $row['iicore_hk']
                || $row['iicore_tw']
                || $row['iicore_jp']
                || $row['iicore_mo']
                || $row['new_cid']
            ) {
                $included = 'cjk';
            } else {
                foreach (['noncjk', 'cjk'] as $category) {
                    foreach ($extraRanges[$category] as $range) {
                        if ($codepoint >= $range[0] && $codepoint <= $range[1]) {
                            $included = $category;
                        }
                    }
                }
            }

            if ($included) {
                if ($included == 'cjk') {
                    $lines['cjkonly'][] = dechex($codepoint);
                }
                $lines['all'][] = dechex($codepoint);
                $lines['demopage'][] = $codepoint;
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        $targetFile = $buildDir . DIRECTORY_SEPARATOR . 'subset_unicodes';
        file_put_contents($targetFile . '_all', implode("\n", $lines['all']));
        file_put_contents($targetFile . '_cjk', implode("\n", $lines['cjkonly']));

        $io->text(sprintf('Done, %d codepoints will be included (%d for CJK only subset)', count($lines['all']), count($lines['cjkonly'])));

        //

        $str = '';
        $codepoints = $lines['demopage'];
        sort($codepoints);
        foreach ($codepoints as $codepoint) {
            $char = mb_convert_encoding(pack('N', $codepoint), 'UTF-8', 'UCS-4BE');
            $str .= sprintf('<div class="col-xs-1" data-codepoint="%d">%s</div>', $codepoint, $char);
        }

        $webfontDemoContent = file_get_contents($this->getAppDataDir() . '/html/webfont_demo.html');
        $webfontDemoContent = str_replace('%content%', $str, $webfontDemoContent);
        file_put_contents($buildDir . '/webfont_demo.html', $webfontDemoContent);

        return [
            'all' => $targetFile . '_all',
            'cjk' => $targetFile . '_cjk',
        ];
    }
}
