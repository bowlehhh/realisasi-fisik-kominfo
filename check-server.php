<?php

declare(strict_types=1);

const MINIMUM_PHP_VERSION = '8.3.0';

/**
 * Extensions required by the production dependency lock and current MySQL configuration.
 *
 * @var array<string, string>
 */
const REQUIRED_EXTENSIONS = [
    'ctype' => 'Laravel framework',
    'dom' => 'HTML/CSS inliner used by Laravel mail rendering',
    'fileinfo' => 'filesystem MIME detection',
    'filter' => 'Laravel framework',
    'hash' => 'Laravel framework',
    'iconv' => 'Symfony polyfills',
    'json' => 'Laravel and HTTP dependencies',
    'libxml' => 'DOM support',
    'mbstring' => 'Laravel framework',
    'openssl' => 'Laravel encryption and HTTPS clients',
    'pcre' => 'Laravel framework',
    'pdo' => 'database abstraction',
    'pdo_mysql' => 'MySQL/MariaDB connection configured by this application',
    'session' => 'Laravel framework',
    'tokenizer' => 'Laravel framework',
];

function printSection(string $title): void
{
    echo PHP_EOL.$title.PHP_EOL;
    echo str_repeat('-', strlen($title)).PHP_EOL;
}

function printList(array $values): void
{
    echo $values === [] ? '- none'.PHP_EOL : '- '.implode(PHP_EOL.'- ', $values).PHP_EOL;
}

$phpVersionPasses = version_compare(PHP_VERSION, MINIMUM_PHP_VERSION, '>=');
$loadedExtensions = get_loaded_extensions();
sort($loadedExtensions, SORT_NATURAL | SORT_FLAG_CASE);

$availableRequiredExtensions = [];
$missingExtensions = [];

foreach (REQUIRED_EXTENSIONS as $extension => $reason) {
    if (extension_loaded($extension)) {
        $availableRequiredExtensions[] = $extension;

        continue;
    }

    $missingExtensions[] = sprintf('%s (%s)', $extension, $reason);
}

echo 'Laravel deployment server check'.PHP_EOL;
echo 'PHP server version: '.PHP_VERSION.PHP_EOL;
echo 'PHP binary: '.PHP_BINARY.PHP_EOL;
echo 'Minimum project PHP: '.MINIMUM_PHP_VERSION.PHP_EOL;
echo 'PHP version status: '.($phpVersionPasses ? 'PASS' : 'FAIL').PHP_EOL;

printSection('Required extensions available');
printList($availableRequiredExtensions);

printSection('Required extensions missing');
printList($missingExtensions);

printSection('All loaded PHP extensions');
printList($loadedExtensions);

$passes = $phpVersionPasses && $missingExtensions === [];

echo PHP_EOL.'Overall status: '.($passes ? 'PASS' : 'FAIL').PHP_EOL;

exit($passes ? 0 : 1);
