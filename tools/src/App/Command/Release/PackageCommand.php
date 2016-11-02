<?php

namespace App\Command\Release;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PackageCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('release:package')
            ->setDescription('Package a build for release')
            ->addArgument('filename_prefix', InputArgument::REQUIRED, 'The filename prefix');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Package a build for release');

        $prefix = $input->getArgument('filename_prefix');
        $projectDir = realpath(__DIR__ . '/../../../../../');
        $buildDir = $this->getParameter('build_dir');
        $sevenZipBin = $this->getParameter('7zip_bin');

        $io = new SymfonyStyle($input, $output);

        $includeProjectFiles = ['CHANGELOG.md', 'README.md', 'LICENSE.txt'];
        $buildDirFileSets = [
            'Normal version' => [
                'filename' => 'CYanHeiHK_%prefix%.7z',
                'files' => function () {
                    return ['changes.html', 'CYanHeiHK-Bold.otf', 'CYanHeiHK-Light.otf', 'CYanHeiHK-Regular.otf'];
                },
            ],
            'FontBBox adjusted version' => [
                'filename' => 'CYanHeiHK_%prefix%_adjusted_fontbbox.7z',
                'files' => function () {
                    return ['changes.html', 'CYanHeiHK-Bold-AdjustedFontBBox.otf', 'CYanHeiHK-Light-AdjustedFontBBox.otf', 'CYanHeiHK-Regular-AdjustedFontBBox.otf'];
                },
            ],
            'Webfont' => [
                'filename' => 'CYanHeiHK_%prefix%_subset.7z',
                'files' => function () {

                    $files = ['webfont_demo.html'];

                    foreach (['Bold', 'Light', 'Regular'] as $weight) {
                        foreach (['hinted', 'unhinted'] as $hinted) {
                            foreach (['all', 'nolatin'] as $subset) {
                                $files[] = sprintf('CYanHei-TCHK-%s-%s-%s.ttf',
                                    $weight,
                                    $subset,
                                    $hinted
                                );
                            }

                            foreach (['woff', 'woff2'] as $extension) {
                                $files[] = sprintf('CYanHei-TCHK-%s-%s.%s',
                                    $weight,
                                    $hinted,
                                    $extension
                                );
                            }
                        }
                    }

                    return $files;
                },
            ],
        ];

        foreach ($buildDirFileSets as $name => $fileSet) {
            $io->section('Packaging ' . $name . ' files');
            $files = [];
            try {
                foreach ($includeProjectFiles as $filename) {
                    $path = $projectDir . DIRECTORY_SEPARATOR . $filename;
                    $io->comment('Adding ' . $path);
                    if (!file_exists($path)) {
                        throw new \Exception('Expected file ' . $path . ' not found!');
                    }
                    $files[] = $path;
                }
                // 1. Ensure file exist
                foreach ($fileSet['files']() as $filename) {
                    $path = $buildDir . DIRECTORY_SEPARATOR . $filename;
                    $io->comment('Adding ' . $path);
                    if (!file_exists($path)) {
                        throw new \Exception('Expected file ' . $path . ' not found!');
                    }
                    $files[] = $path;
                }
            } catch (\Exception $e) {
                $io->error('Error encountered: ' . $e->getMessage());
                $io->error('Skip packaging this file set');
                continue;
            }

            $filelistFile = tempnam(sys_get_temp_dir(), '');
            file_put_contents($filelistFile, implode("\n", $files));
            $archiveFilename = str_replace('%prefix%', $prefix, $fileSet['filename']);
            @unlink($archiveFilename);
            $cmd = '"' . $sevenZipBin . '" a -mx=6 -t7z ' . $buildDir . DIRECTORY_SEPARATOR . $archiveFilename . ' @' . $filelistFile;
            $this->runExternalCommand($io, $cmd);
        }

        $io->success('Done');
    }
}
