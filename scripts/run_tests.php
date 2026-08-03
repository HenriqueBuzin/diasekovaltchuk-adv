<?php

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

/** @param list<string> $command */
function run(array $command): void
{
    $display = implode(' ', $command);
    echo "\n> {$display}\n";
    passthru($display, $status);
    if ($status !== 0) {
        exit($status);
    }
}

run(['composer', '--working-dir=backend', 'audit']);
run(['composer', '--working-dir=backend', 'format:check']);
run(['composer', '--working-dir=backend', 'analyse']);

if (extension_loaded('xdebug') || extension_loaded('pcov')) {
    putenv('XDEBUG_MODE=coverage');
    run([
        'php',
        '-d',
        'memory_limit=1G',
        'backend/vendor/bin/phpunit',
        '--configuration=backend/phpunit.xml',
        '--coverage-clover=backend/coverage.xml',
    ]);
    run(['php', 'backend/scripts/check-coverage.php', 'backend/coverage.xml']);
} else {
    run(['docker', 'build', '--target', 'backend-quality', '--tag', 'diasekovaltchuk-adv:backend-quality', '.']);
}

run(['npm', 'audit', '--audit-level=high']);
run(['npm', 'run', 'format:frontend:check']);
run(['npm', 'run', 'lint:frontend']);
run(['npm', 'run', 'typecheck:frontend']);
run(['npm', 'run', 'test:frontend']);
run(['npm', 'run', 'test:e2e']);

echo "\nTodos os gates de qualidade passaram.\n";
