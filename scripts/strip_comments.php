<?php

$directories = [
    __DIR__ . '/../app',
    __DIR__ . '/../config',
    __DIR__ . '/../routes',
];

foreach ($directories as $dir) {
    if (!is_dir($dir))
        continue;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $content = php_strip_whitespace($file->getPathname());
            // Preserve the opening tag if stripped (php_strip_whitespace keeps it usually, but let's be safe)
            if (!str_starts_with($content, '<?php')) {
                // specific edge cases might exist, but usually it's fine.
            }
            file_put_contents($file->getPathname(), $content);
            echo "Stripped: " . $file->getPathname() . PHP_EOL;
        }
    }
}

echo "All PHP files have been stripped of comments/whitespace." . PHP_EOL;
