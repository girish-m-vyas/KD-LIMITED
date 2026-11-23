<?php
declare(strict_types=1);

/**
 * Zero-dependency test runner.
 *
 *   php tests/run.php            run every test
 *   php tests/run.php Csrf       run test classes whose name contains "Csrf"
 *
 * Exit code is 0 only when every test passes.
 */

require __DIR__ . '/../src/autoload.php';
require __DIR__ . '/TestCase.php';

$filter = $argv[1] ?? '';
$files  = array_merge(
    glob(__DIR__ . '/Unit/*Test.php') ?: [],
    glob(__DIR__ . '/Integration/*Test.php') ?: []
);
sort($files);

$passed = 0;
$failed = [];
$start  = microtime(true);

foreach ($files as $file) {
    $before = get_declared_classes();
    require_once $file;
    $classes = array_diff(get_declared_classes(), $before);

    foreach ($classes as $class) {
        if (!is_subclass_of($class, \Tests\TestCase::class)) {
            continue;
        }
        if ($filter !== '' && stripos($class, $filter) === false) {
            continue;
        }
        foreach (get_class_methods($class) as $method) {
            if (!str_starts_with($method, 'test')) {
                continue;
            }
            /** @var \Tests\TestCase $test */
            $test = new $class();
            $label = substr(strrchr($class, '\\') ?: $class, 1) . '::' . $method;
            try {
                $test->setUp();
                $test->$method();
                $test->tearDown();
                $passed++;
                echo '.';
            } catch (\Throwable $e) {
                $failed[] = [$label, $e];
                echo 'F';
            }
        }
    }
}

$elapsed = round(microtime(true) - $start, 2);
echo "\n\n";

foreach ($failed as [$label, $e]) {
    echo "FAIL  {$label}\n      " . get_class($e) . ': ' . $e->getMessage() . "\n";
    $frame = $e->getTrace()[0] ?? null;
    echo '      at ' . $e->getFile() . ':' . $e->getLine() . "\n\n";
}

$total = $passed + count($failed);
echo "{$total} tests, {$passed} passed, " . count($failed) . " failed ({$elapsed}s)\n";
exit($failed ? 1 : 0);
