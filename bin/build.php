<?php
/**
 * Apog Extension Packager (CLI Only)
 */
if (PHP_SAPI !== 'cli') exit("This script must be run from the command line.\n");
if (!class_exists('ZipArchive')) exit("Error: PHP ZipArchive extension is required.\n");

if ($argc < 2) {
    echo "Usage:\n";
    echo "  php bin/build.php core\n";
    echo "  php bin/build.php shipping <code>\n";
    exit(1);
}

$type = $argv[1];

$baseDir = dirname(__DIR__) . '/';
$srcDir  = $baseDir . 'src/';
$distDir = $baseDir . 'dist/';

if (!is_dir($distDir)) mkdir($distDir, 0755, true);

/**
 * Package a directory to Zip (contents only, not the root folder)
 */
function buildPackage($source, $zipPath) {
    if (!is_dir($source)) exit("❌ Error: Source folder not found: $source\n");
    if (!is_dir($source . '/upload')) exit("❌ Error: Missing 'upload/' folder in $source\n");

    if (file_exists($zipPath)) {
        unlink($zipPath);
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        exit("❌ Error: Could not create zip file at $zipPath\n");
    }


    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        $filePath = $file->getRealPath();

        // Relative path inside zip
        $relativePath = substr($filePath, strlen($source) + 1);

        $zip->addFile($filePath, $relativePath);
    }

    $zip->close();
    echo "📦 Package created: " . basename($zipPath) . "\n";
}

/**
 * Validate module structure
 */
function validateModule($path) {
    if (!is_dir($path)) {
        exit("Error: Module not found: $path\n");
    }

    if (!file_exists($path . '/install.xml')) {
        exit("Error: Missing install.xml in $path\n");
    }

    if (!is_dir($path . '/upload')) {
        exit("Error: Missing upload/ folder in $path\n");
    }
}

switch ($type) {

    case 'core':
        $moduleName = 'apog_core';
        $source = $srcDir . $moduleName;

        validateModule($source);

        $zipFile = $distDir . $moduleName . '.ocmod.zip';

        echo "Building core...\n";
        echo "→ Source: $source\n";
        echo "→ Output: $zipFile\n";

        buildPackage($source, $zipFile);

        echo "✔ Core package created\n";
        break;

    case 'shipping':
        if ($argc < 3) {
            exit("Usage: php build.php shipping <code>\n");
        }

        $code = strtolower($argv[2]);
        $moduleName = "apog_shipping_$code";
        $source = $srcDir . $moduleName;

        validateModule($source);

        $zipFile = $distDir . $moduleName . '.ocmod.zip';

        echo "Building shipping module: $code\n";
        echo "→ Source: $source\n";
        echo "→ Output: $zipFile\n";

        buildPackage($source, $zipFile);

        echo "✔ Module package created\n";
        break;

    default:
        exit("Unknown type: $type\n");
}
