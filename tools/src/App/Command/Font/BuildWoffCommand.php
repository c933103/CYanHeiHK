<?php

namespace App\Command\Font;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildWoffCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('font:build-woff')
            ->setDescription('Builds WOFF font.')
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

        $this->copyFile($this->getAppDataDir() . '/html', $buildDir, 'webfont_demo.html');

        foreach ($this->getActionableWeights($input->getOption('weight')) as $weight) {
            $io->section('Creating font files for ' . $weight . ' weight');
            $dirs = $this->getDirConfigForWeight($weight);
            $fontPath = $dirs['build_dir'] . '/CYanHeiHK-TrimmedFeatures.otf';

            $this->assertFileExists($io, $fontPath, 'Please make sure font:build-otf is run successfully.');

            foreach (['woff', 'woff2'] as $flavor) {
                foreach ([true, false] as $hinting) {

                    $outputFile = $buildDir . DIRECTORY_SEPARATOR . 'CYanHeiHK-' . $weight . '-' . ($hinting ? 'hinted' : 'unhinted') . '.' . $flavor;

                    $io->text(' Flavor: ' . $flavor);
                    $io->text('Hinting: ' . ($hinting ? 'Yes' : 'No'));
                    $io->text('   File: ' . $outputFile);

                    $this->runExternalCommand($io,
                        sprintf('%s %s --unicodes-file=%s --flavor=%s --drop-tables+=locl,vhea,vmtx %s --output-file=%s',
                            $pyftsubsetBin,
                            $fontPath,
                            $subsetFilePath,
                            $flavor,
                            $hinting ? '--hinting' : '--no-hinting --desubroutinize',
                            $outputFile
                        ));
                    $io->text('         Done, file created');
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

        $lines = [];
        $rows = $stmt->fetchAll();
        $total = count($rows);
        $io->progressStart($total);

        $keepInSubset = parse_ini_file($this->getAppDataDir() . '/fixtures/subset_includes.txt');

        $extraRanges = [];
        foreach ($keepInSubset['range'] as $range) {
            list($from, $to) = explode('..', $range);
            $extraRanges[] = [hexdec($from), hexdec($to)];
        }

        $extraCodepoints = [];
        foreach ($keepInSubset['codepoint'] as $codepoint) {
            if (strpos($codepoint, 'U+') === 0) {
                $codepoint = hexdec(substr($codepoint, 2));
            }
            $extraCodepoints[$codepoint] = true;
        }
        
        $count = 0;

        foreach ($rows as $idx => $row) {
            $codepoint = $row['codepoint'];

            $included = false;
            if (isset($extraCodepoints[$codepoint])
                || $row['hk_common']
                || $row['iicore_hk']
                || $row['iicore_tw']
                || $row['iicore_jp']
                || $row['iicore_mo']
                || $row['new_cid']
            ) {
                $included = true;
            } else {
                foreach ($extraRanges as $range) {
                    if ($codepoint >= $range[0] && $codepoint <= $range[1]) {
                        $included = true;
                    }
                }
            }

            if ($included) {
                $lines[] = '#' . $codepoint . ' (' . dechex($codepoint) . ')';
                $lines[] = dechex($codepoint);
                $count++;
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        $targetFile = $buildDir . DIRECTORY_SEPARATOR . 'unicodes';
        file_put_contents($targetFile, implode("\n", $lines));

        $io->text(sprintf('Done, %d codepoints will be included', $count));
        return $targetFile;
    }
}
