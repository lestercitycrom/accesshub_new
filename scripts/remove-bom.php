<?php

/**
 * Script to remove BOM (Byte Order Mark) from all PHP files in the project.
 * BOM causes "strict_types declaration must be the very first statement" errors.
 */

$projectRoot = dirname(__DIR__);
$directories = [
    $projectRoot . '/app',
    $projectRoot . '/config',
    $projectRoot . '/database',
    $projectRoot . '/packages',
    $projectRoot . '/routes',
    $projectRoot . '/tests',
];

$extensions = ['php'];
$bomFound = false;
$filesFixed = 0;

function removeBom(string $filePath): bool
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }

    // Check for UTF-8 BOM (EF BB BF)
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
        file_put_contents($filePath, $content);
        return true;
    }

    // Check for UTF-16 LE BOM (FF FE)
    if (substr($content, 0, 2) === "\xFF\xFE") {
        $content = substr($content, 2);
        file_put_contents($filePath, $content);
        return true;
    }

    // Check for UTF-16 BE BOM (FE FF)
    if (substr($content, 0, 2) === "\xFE\xFF") {
        $content = substr($content, 2);
        file_put_contents($filePath, $content);
        return true;
    }

    return false;
}

function scanDirectory(string $dir, array $extensions): array
{
    $files = [];
    if (!is_dir($dir)) {
        return $files;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $extensions)) {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}

echo "Scanning for BOM in PHP files...\n\n";

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        continue;
    }

    $files = scanDirectory($dir, $extensions);
    foreach ($files as $file) {
        if (removeBom($file)) {
            $relativePath = str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $file);
            echo "Fixed: {$relativePath}\n";
            $filesFixed++;
            $bomFound = true;
        }
    }
}

if ($bomFound) {
    echo "\n✓ Removed BOM from {$filesFixed} file(s).\n";
} else {
    echo "✓ No BOM found in PHP files.\n";
}

exit($bomFound ? 0 : 0);
