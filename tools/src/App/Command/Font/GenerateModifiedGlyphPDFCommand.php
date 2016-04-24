<?php

namespace App\Command\Font;

use App\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateModifiedGlyphPDFCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('font:generate-modified-glyph-pdf')
            ->setDescription('Generates PDFs of modified glyphs. Must run after font:build-otf.')
            ->addOption('weight', 'w', InputOption::VALUE_REQUIRED, 'Specify the weight to act upon', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $buildDir = $this->getParameter('build_dir');
        $weights = $this->getActionableWeights($input->getOption('weight'));

        $io->title('Export PDFs of modified glyphs');

        $afdkoBinDir = $this->getParameter('afdko_bin_dir');
        $exportGlyphs = [];
        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->query(sprintf('SELECT * FROM cmap c, process p WHERE c.codepoint = p.codepoint AND p.export = 1 ORDER BY c.codepoint'));
        foreach ($stmt as $row) {
            $exportGlyphs[$row['category']][$row['new_cid']] = true;
        }

        foreach ($weights as $weight) {
            $io->section($weight);

            foreach ($exportGlyphs as $category => $cids) {
                // PDF file
                $targetFile = $buildDir . DIRECTORY_SEPARATOR . $weight . DIRECTORY_SEPARATOR . $category . '_modified.pdf';
                $io->text(' - PDF file (' . $targetFile . ')');
                $cmd = sprintf('%s/tx -pdf -g %s %s %s',
                    $afdkoBinDir,
                    implode(',', array_keys($cids)),
                    $buildDir . '/' . $weight . '/all.ps',
                    $targetFile);

                $this->runExternalCommand($io, $cmd);
            }
        }
    }
}
