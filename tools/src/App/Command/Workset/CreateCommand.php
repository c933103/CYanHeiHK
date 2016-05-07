<?php

namespace App\Command\Workset;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('workset:create')
            ->setDescription('Exports workset files to the workspace directory')
            ->setHelp(<<<EOT
For every weight and category, this command creates the following files in the workspace directory:

1. [weight]/[category].pfa, which contains the glyphs that needs to be adjusted.
2. [weight]/[category]_ref.pfa, which contains the glyphs of different regions of the adjusting codepoints.
3. [weight]/[category].pdf containing an overview of the to-be-adjusted glyphs.
4. [weight]/[category].map for use by mergefonts.
 
[category] can be either "s" (standardization), "a" (aesthetic replacement) or "o" (optimization).  
EOT
            )
            ->addArgument('workset_id', InputArgument::OPTIONAL, 'Limit the workset ID to act upon (default to all)')
            ->addOption('weight', 'w', InputOption::VALUE_REQUIRED, 'Specify the weight to act upon', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Create workset files');

        $afdkoBinDir = $this->getParameter('afdko_bin_dir');
        $weights = $this->getActionableWeights($input->getOption('weight'));

        $ids = $this->getImportedWorksetIds();
        $customWorksetId = $input->getArgument('workset_id');
        if ($customWorksetId) {
            if (array_search($customWorksetId, $ids) !== false) {
                $ids = [$customWorksetId];
            } else {
                throw new InvalidArgumentException('Invalid workset ID.');
            }
        }

        if (!$ids) {
            $io->error('No workset to create.');
            die;
        }

        foreach ($ids as $id) {
            $io->block(':::::::::: Workset #' . $id . ' ::::::::::');
            $this->createWorksetFiles($io, $afdkoBinDir, $id, $weights);
        }

        $io->success('Operation complete');
    }

    private function createPunctuationFiles(SymfonyStyle $io, $afdkoBinDir, $shsPsFile, $worksetDir, $weight)
    {
        $cids = [58992, 59017, 59018, 59022, 63030, 63031, 63033];
        $pfaFile = $worksetDir . DIRECTORY_SEPARATOR . $weight . DIRECTORY_SEPARATOR . 'punc.pfa';
        $mappingFile = $worksetDir . DIRECTORY_SEPARATOR . $weight . DIRECTORY_SEPARATOR . 'punc.map';

        $io->text(' - Producing PFA file');
        $cmd = sprintf('%s/tx -t1 -decid -g %s %s %s',
            $afdkoBinDir,
            implode(',', $cids),
            $shsPsFile,
            $pfaFile);

        $this->runExternalCommand($io, $cmd);

        // MAP file
        $io->text(' - Mergefont property file (' . $mappingFile . ')');

        $s = "mergeFonts SourceHanSansTC-$weight-Dingbats 1\n0	.notdef\n";
        foreach ($cids as $cid) {
            $s .= sprintf("%s\tcid%s\n", $cid, $cid);
        }

        file_put_contents($mappingFile, $s);
    }

    private function createWorksetFiles(SymfonyStyle $io, $afdkoBinDir, $worksetId, $weights)
    {
        $worksetDir = $this->getWorksetDir($worksetId);

        $io->text('AFDKO tools directory: ' . $afdkoBinDir);
        $io->text('Workset ID: ' . $worksetId);
        $io->text('Workset directory: ' . $worksetDir);
        $io->text('Weights: ' . implode(', ', $weights));

        $exportGlyphs = [];
        $referenceGlyphs = [];

        $io->text('Reading selection result');

        $conn = $this->getCharacterDatabase()->getConnection();
        $stmt = $conn->query(sprintf('SELECT * FROM cmap c, process p WHERE p.workset = ' . $worksetId . ' AND  c.codepoint = p.codepoint AND p.export = 1 ORDER BY c.codepoint'));
        foreach ($stmt as $row) {
            $exportGlyphs[$row['category']][$row['new_cid']] = true;
            foreach (['cn', 'jp', 'kr', 'tw'] as $region) {
                $referenceGlyphs[$row['category']][$row['cid_' . $region]] = true;
            }
        }

        $categoryText = [
            self::SELECTION_CATEGORY_STANDARD => 'standardization',
            self::SELECTION_CATEGORY_OPTIMIZE => 'optimization',
            self::SELECTION_CATEGORY_AESTHETIC => 'aesthetic replacement',
        ];

        foreach ($weights as $weight) {
            $io->section('Exporting glyphs for ' . $weight . ' weight');
            @mkdir($worksetDir . '/' . $weight, 0755, true);

            $shsPsFile = $this->getSourceHanSansPsFilePath($weight);

            if ($worksetId == 1) {
                $this->createPunctuationFiles($io, $afdkoBinDir, $shsPsFile, $worksetDir, $weight);
            }

            foreach ($exportGlyphs as $category => $cids) {
                $io->text('Exporting ' . $categoryText[$category] . ' glyphs');

                // PDF file
                $targetFile = $worksetDir . DIRECTORY_SEPARATOR . $weight . DIRECTORY_SEPARATOR . $category . '.pdf';
                $io->text(' - PDF file (' . $targetFile . ')');
                $cmd = sprintf('%s/tx -pdf -g %s %s %s',
                    $afdkoBinDir,
                    implode(',', array_keys($cids)),
                    $shsPsFile,
                    $targetFile);

                $this->runExternalCommand($io, $cmd);

                // PFA file

                $targetFile = $worksetDir . DIRECTORY_SEPARATOR . $weight . DIRECTORY_SEPARATOR . $category . '.pfa';
                $io->text(' - Producing PFA file');
                $cmd = sprintf('%s/tx -t1 -decid -g %s %s %s',
                    $afdkoBinDir,
                    implode(',', array_keys($cids)),
                    $shsPsFile,
                    $targetFile);

                $this->runExternalCommand($io, $cmd);

                // MAP file

                $mappingFile = $worksetDir . DIRECTORY_SEPARATOR . $weight . DIRECTORY_SEPARATOR . $category . '.map';
                $io->text(' - Mergefont property file (' . $mappingFile . ')');

                $s = "mergeFonts SourceHanSansTC-$weight-Ideographs\n0	.notdef\n";
                foreach ($cids as $cid => $_) {
                    $s .= sprintf("%s cid%s\n", $cid, $cid);
                }

                file_put_contents($mappingFile, $s);

                // REFERENCE PFA file
                $targetFile = $worksetDir . DIRECTORY_SEPARATOR . $weight . DIRECTORY_SEPARATOR . $category . '_ref.pfa';
                $io->text(' - Reference glyphs (' . $targetFile . ')');

                $cmd = sprintf('%s/tx -t1 -decid -g %s %s %s',
                    $afdkoBinDir,
                    implode(',', array_keys($referenceGlyphs[$category])),
                    $shsPsFile,
                    $targetFile);

                $this->runExternalCommand($io, $cmd);
            }
        }
    }
}
