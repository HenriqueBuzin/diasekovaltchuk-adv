<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

$path = $argv[1] ?? '';
if ($path === '' || ! is_file($path)) {
    fwrite(STDERR, "Arquivo Clover não encontrado.\n");
    exit(1);
}

$document = new DOMDocument;
if (! $document->load($path)) {
    fwrite(STDERR, "Relatório Clover inválido.\n");
    exit(1);
}

$xpath = new DOMXPath($document);
$metrics = $xpath->query('/coverage/project/metrics')->item(0);
if (! $metrics instanceof DOMElement) {
    fwrite(STDERR, "Métricas de cobertura ausentes.\n");
    exit(1);
}

$classNodes = $xpath->query('/coverage/project/package/file/class/metrics');
$classTotal = $classNodes->length;
$classCovered = 0;
foreach ($classNodes as $classMetrics) {
    if (! $classMetrics instanceof DOMElement) {
        continue;
    }
    if ($classMetrics->getAttribute('methods') === $classMetrics->getAttribute('coveredmethods')
        && $classMetrics->getAttribute('statements') === $classMetrics->getAttribute('coveredstatements')) {
        $classCovered++;
    }
}

$targets = [
    'linhas' => ['statements', 'coveredstatements'],
    'funções' => ['methods', 'coveredmethods'],
];

$failed = $classCovered !== $classTotal;
printf(
    "Cobertura de classes: %d/%d (%.2f%%)\n",
    $classCovered,
    $classTotal,
    $classTotal === 0 ? 100.0 : ($classCovered / $classTotal) * 100,
);
foreach ($targets as $label => [$totalName, $coveredName]) {
    $total = (int) $metrics->getAttribute($totalName);
    $covered = (int) $metrics->getAttribute($coveredName);
    $percent = $total === 0 ? 100.0 : ($covered / $total) * 100;
    printf("Cobertura de %s: %d/%d (%.2f%%)\n", $label, $covered, $total, $percent);
    if ($covered !== $total) {
        $failed = true;
    }
}

exit($failed ? 1 : 0);
