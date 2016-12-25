<?php

namespace App\Command\Font;

use App\Command\ContainerAwareCommand;
use App\Data\Database;
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
                $hinting = false;
                $io->text(sprintf(' Flavor: TTF / %s / %s',
                    $subset == 'all' ? 'With latin characters' : 'Without latin characters',
                    $hinting ? 'Hinted' : 'Unhinted'
                ));

                $outputFilePrefix = sprintf($buildDir . DIRECTORY_SEPARATOR . 'CYanHei-TCHK-' . $weight . '-' . '%s',
                    $subset == 'all' ? 'all' : 'nolatin'
                );

                $produceTTF($subsetFilePath[$subset], $hinting, $weight, $outputFilePrefix);

                $io->newLine();
            }
        }
    }

    private function prepareSubsetCodepointFile(SymfonyStyle $io)
    {
        $io->section('Preparing font subsetting files');

        $lines = [
            'cjkonly' => [],
            'all' => [],
        ];

        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->prepare('SELECT * FROM subset');
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $item) {
            if ($item['category'] == Database::SUBSET_CATEGORY_CJK) {
                $lines['cjkonly'][] = $item['hex_cp'];
            }
            $lines['all'][] = $item['hex_cp'];
            $lines['demopage'][] = $item['codepoint'];
        }

        $buildDir = $this->getParameter('build_dir');
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
