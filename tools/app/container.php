<?php

use App\Container;

$c = new Container();

$c['parameters'] = include __DIR__ . '/parameters.php';

$c['db'] = function ($c) {
    return new \App\Data\Database($c['parameters']['db']);
};

$c->addContainerAwareCommands([
    'App\\Command\\Database\\InitializeCommand',
    'App\\Command\\Database\\InitSchemaCommand',
    'App\\Command\\Database\\InitCmapCommand',
    'App\\Command\\Database\\QueryCmapCommand',
    'App\\Command\\Characters\\InitializeCommand',
    'App\\Command\\Characters\\ImportBig5CharacterDataCommand',
    'App\\Command\\Characters\\ImportHongKongCommonCharacterCommand',
    'App\\Command\\Characters\\ExportCandidateCommand',
    'App\\Command\\Characters\\ImportIICOREDataCommand',
    'App\\Command\\Characters\\ExportGlyphCommand',
    'App\\Command\\Workset\\InitializeCommand',
    'App\\Command\\Workset\\CreateCommand',
    'App\\Command\\Font\\BuildCommand',
    'App\\Command\\Font\\BuildCmapCommand',
    'App\\Command\\Font\\BuildMergedPsCommand',
    'App\\Command\\Font\\BuildOtfCommand',
    'App\\Command\\Font\\GenerateModifiedGlyphPDFCommand',
    'App\\Command\\Font\\GenerateChangedGlyphHtmlCommand',
    'App\\Command\\FontForge\\GenerateFontCommand',
    'App\\Command\\Release\\PackageCommand',
    'App\\Command\\Font\\BuildSubsetCommand',
]);

$c['application'] = function ($c) {
    $application = new \App\Console\Application();
    $application->addCommands($c->getCommands());

    return $application;
};

return $c;