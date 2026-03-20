<?php
declare(strict_types=1);

/**
 * Apog Extension Packager (CLI Only)
 */
if (PHP_SAPI !== 'cli') exit("This script must be run from the command line.\n");
if (!class_exists('ZipArchive')) exit("❌ Error: PHP ZipArchive extension is required.\n");

if ($argc < 2) {
    out("ℹ️  Usage:");
    out("  php bin/build.php core");
    out("  php bin/build.php shipping <code>");
    out("  php bin/build.php --all");
    exit(1);
}

$type = $argv[1];

$all = in_array('--all', $argv, true);

$baseDir = dirname(__DIR__) . '/';
$srcDir  = $baseDir . 'src/';
$distDir = $baseDir . 'dist/';

if (!is_dir($distDir) && !mkdir($distDir, 0755, true)) {
    exit("❌ Error: Failed to create dist directory: $distDir\n");
}

/**
 * Outputs a message to CLI with newline.
 *
 * @param string $message Message to display
 *
 * @return void
 */
function out($message = '') {
    echo $message . PHP_EOL;
}

/**
 * Outputs a message and exits the script.
 *
 * @param string $message Message to display
 * @param int $exit_code Exit status code (default: 0)
 *
 * @return void
 */
function outAndExit($message = '', $exit_code = 0) {
    out($message);
    exit($exit_code);
}

/**
 * Normalizes a module code to a safe format.
 *
 * Rules:
 * - lowercase
 * - spaces and dashes → underscores
 * - removes invalid characters
 * - collapses multiple underscores
 *
 * @param string $code Raw module code
 *
 * @return string Normalized code
 */
function normalizeCode(string $code): string {
    $code = trim($code);
    $code = strtolower($code);
    $code = str_replace(['-', ' '], '_', $code);
    $code = preg_replace('/[^a-z0-9_]/', '', $code); // remove anything weird
    $code = preg_replace('/_+/', '_', $code); // collapse multiple underscores
    return trim($code, '_');
}

/**
 * Builds a ZIP package from a module directory.
 *
 * - Includes only the contents of the source directory (not the root folder)
 * - Preserves directory structure inside the archive
 * - Overwrites existing archive if present (with warning)
 *
 * @param string $source Absolute path to the module source directory
 * @param string $zipPath Absolute path where the ZIP file will be created
 *
 * @return int Number of files successfully added to the archive
 */
function buildPackage($source, $zipPath) {
    if (file_exists($zipPath)) {
        out("⚠️  Warning: Overwriting existing package: " . basename($zipPath));
        unlink($zipPath);
    }

    $fileCount = 0;

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        out("❌ Error: Could not create zip file at $zipPath");
        return 0;
    }


    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        $filePath = $file->getRealPath();

        // Relative path inside zip
        $relativePath = ltrim(substr($filePath, strlen($source)), DIRECTORY_SEPARATOR);

        if (!$zip->addFile($filePath, $relativePath)) {
            out("❌ Error: Failed to add file to archive: $filePath\n");
            $zip->close();
            return 0;
        }
        $fileCount++;
    }

    $zip->close();
    out("📦 Package created: " . basename($zipPath));
    return $fileCount;
}

/**
 * Validates that a module directory has the required OpenCart structure.
 *
 * Required:
 * - path to the module directory
 * - install.xml (OCMOD file)
 * - upload/ directory (files to be copied to store)
 *
 * @param string $path Absolute path to the module directory
 *
 * @return void
 */
function validateModule($path) {
    if (!is_dir($path)) {
        outAndExit("❌ Error: Module not found: $path");
    }

    if (!file_exists($path . '/install.xml')) {
        outAndExit("❌ Error: Missing install.xml in $path");
    }

    if (!is_dir($path . '/upload')) {
        outAndExit("❌ Error: Missing upload/ folder in $path");
    }
}

/**
 * Prints a formatted header block for the build process.
 *
 * Displays module name, source path, and output path
 * in a consistent CLI-friendly format.
 *
 * @param array $config Configuration array with:
 *  - string 'name'   Module name
 *  - string 'source' Source directory path
 *  - string 'output' Output ZIP file path
 *
 * @return void
 */
function printHeader(array $config): void {
    out("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    out("⚙️  Building Module Package");
    out("📦 Name  : {$config['name']}");
    out("📁 Source: {$config['source']}");
    out("📂 Output: {$config['output']}");
    out("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
}

/**
 * Prints a formatted footer block after the build process.
 *
 * Displays:
 * - Success or error status
 * - Output file location
 * - Number of files included
 * - Execution time
 *
 * @param array $config Configuration array with:
 *  - string 'output'      Output ZIP file path
 *  - int    'fileCount'   Number of files added to archive
 *  - float  'elapsedTime' Execution time in seconds
 *
 * @return void
 */
function printFooter(array $config): void {
    out("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    if ($config['fileCount'] === 0) {
        out("❌ Error       : No files were added to the package. Check the source directory.");
    } else {
        out("✅ Success     : Package created");
    }

    out("📂 Location    : {$config['output']}");
    out("📄 Files       : {$config['fileCount']}");
    out("⏱️ Completed in: {$config['elapsedTime']}s");
    out("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
}

/**
 * Scans the src directory and returns all valid module paths.
 *
 * Supports:
 * - apog_core
 * - apog_shipping_*
 *
 * @param string $srcDir Base src directory
 *
 * @return array List of module names
 */
function getAllModules(string $srcDir): array {
    $modules = [];

    if (!is_dir($srcDir)) return $modules;

    foreach (scandir($srcDir) as $item) {
        if ($item === '.' || $item === '..') continue;

        $fullPath = $srcDir . $item;

        if (!is_dir($fullPath)) continue;

        // Only include valid module naming
        if ($item === 'apog_core' || str_starts_with($item, 'apog_shipping_')) {
            $modules[] = $item;
        }
    }

    return $modules;
}

/**
 * Builds a module package by name.
 *
 * @param string $moduleName Module folder name (e.g., apog_core, apog_shipping_acs)
 * @param string $srcDir Base src directory
 * @param string $distDir Output directory
 *
 * @return void
 */
function buildModule(string $moduleName, string $srcDir, string $distDir): void {
    $source = $srcDir . $moduleName;
    $zipPath = $distDir . $moduleName . '.ocmod.zip';

    validateModule($source);

    $config = [
        'name'   => $moduleName,
        'source' => $source,
        'output' => $zipPath,
    ];

    $startTime = microtime(true);

    printHeader($config);

    $fileCount = buildPackage($source, $zipPath);

    $elapsedTime = round(microtime(true) - $startTime, 3);

    $config['fileCount'] = $fileCount;
    $config['elapsedTime'] = $elapsedTime;

    printFooter($config);
}


if ($all) {
    out("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    out("🚀 Building ALL modules");
    out("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    $modules = getAllModules($srcDir);

    if (empty($modules)) {
        outAndExit("❌ Error: No modules found in src directory.");
    }

    foreach ($modules as $moduleName) {
        try {
            buildModule($moduleName, $srcDir, $distDir);
        } catch (Throwable $e) {
            out("❌ Error building {$moduleName}: " . $e->getMessage());
        }
    }

    out("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    out("✅ All modules processed");
    out("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    exit(0);
}

switch ($type) {

    case 'core':
        $moduleName = 'apog_core';

        buildModule($moduleName, $srcDir, $srcDir);
        break;

    case 'shipping':
        if ($argc < 3) {
            outAndExit("ℹ️  Usage: php build.php shipping <code>");
        }

        $rawCode = $argv[2];
        $code = normalizeCode($rawCode);

        if (empty($code) || strlen($code) < 3) {
            outAndExit("❌ Module code must be at least 3 characters.");
        }

        $moduleName = "apog_shipping_$code";
        buildModule($moduleName, $srcDir, $srcDir);
        break;

    default:
        outAndExit("❌ Error: Unknown type '$type'. Use 'core' or 'shipping'.");
}
