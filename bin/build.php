<?php
declare(strict_types=1);

/**
 * Apog Extension Packager (CLI Only)
 */
if (PHP_SAPI !== 'cli') exit("This script must be run from the command line.\n");
if (!class_exists('ZipArchive')) exit("❌ Error: PHP ZipArchive extension is required.\n");

require_once __DIR__ . '/helpers/cli_helpers.php';

const HELP_TEXT = <<<TXT
Apog Extension Builder

Usage:
  php bin/build.php --type=core
  php bin/build.php --type=shipping --code=<code>
  php bin/build.php --type=payment  --code=<code>
  php bin/build.php --type=total    --code=<code>
  php bin/build.php --all

Options:
  --type     Module type: core, shipping, payment, total
  --code     Module code (required except core)
  --all      Build all modules
  --help     Show this help message

Examples:
  php bin/build.php --type=core
  php bin/build.php --type=payment --code=cod
  php bin/build.php --all
TXT;

enum ModuleType: string
{
    case CORE = 'core';
    case SHIPPING = 'shipping';
    case PAYMENT = 'payment';
    case TOTAL = 'total';
}

/**
 * -----------------------------
 * CLI ENTRY POINT
 * -----------------------------
 */
$params = parseNamedArgs($argv);

if (!empty($params['help'])) {
    outAndExit(HELP_TEXT, 0);
}

$type       = $params['type'] ?? null;
$moduleCode = normalizeCode($params['code'] ?? null);
$all        = !empty($params['all']);

$typeEnum = is_string($type) && $type !== ''
    ? ModuleType::tryFrom($type)
    : null;

$baseDir = dirname(__DIR__) . '/';
$srcDir  = $baseDir . 'src/';
$distDir = $baseDir . 'dist/';

if (!is_dir($distDir) && !mkdir($distDir, 0755, true)) {
    outAndExit("❌ Error: Failed to create dist directory: $distDir", 1);
}

/**
 * Validate input
 */
if ($all && (!empty($typeEnum) || !empty($moduleCode))) {
    out("❌ Error: --all cannot be combined with --type or --code.");
    outAndExit(HELP_TEXT, 1);
}

if (!$all && !$typeEnum) {
    out("❌ Error: Missing module --type.");
    outAndExit(HELP_TEXT, 1);
}

if ($typeEnum !== ModuleType::CORE && !$all) {
    try {
        validateCode($moduleCode);
    } catch (Throwable $e) {
        outAndExit("❌ " . $e->getMessage(), 1);
    }
}

/**
 * Execute
 */
if ($all) {
    runBuildAll($srcDir, $distDir);
    exit(0);
}

$moduleName = resolveModuleName($typeEnum, $moduleCode);
runBuild($moduleName, $srcDir, $distDir);


/**
 * Resolves the module folder name based on type and optional code.
 *
 * Generates the expected module directory name used in the src/ folder.
 * For non-core modules, the code is appended to the module type.
 *
 * Examples:
 * - core            → apog_core
 * - shipping + acs  → apog_shipping_acs
 * - payment + cod   → apog_payment_cod
 *
 * @param ModuleType $type Module type enum
 * @param string|null $code Module code (required for non-core types)
 *
 * @return string Resolved module folder name
 */
function resolveModuleName(ModuleType $type, ?string $code): string {
    return match ($type) {
        ModuleType::CORE     => 'apog_core',
        ModuleType::SHIPPING => "apog_shipping_$code",
        ModuleType::PAYMENT  => "apog_payment_$code",
        ModuleType::TOTAL    => "apog_total_$code",
    };
}

/**
 * Builds all valid modules found in the src directory.
 *
 * - Scans for supported module naming patterns
 * - Executes build process for each module
 * - Continues execution even if individual module builds fail
 *
 * Outputs a summary block upon completion.
 *
 * @param string $srcDir Absolute path to the source modules directory
 * @param string $distDir Absolute path to the output (dist) directory
 *
 * @return void
 */
function runBuildAll(string $srcDir, string $distDir): void {
    cliPrintBlock([
        "⚙️  Building all modules"
    ]);

    $modules = getAllModules($srcDir);

    if (empty($modules)) {
        outAndExit("❌ Error: No modules found in src directory.", 1);
    }

    foreach ($modules as $moduleName) {
        try {
            runBuild($moduleName, $srcDir, $distDir);
        } catch (Throwable $e) {
            out("❌ Error building {$moduleName}: " . $e->getMessage());
        }
    }

    cliPrintBlock([
        "✅ All modules processed"
    ]);
}

/**
 * Executes the build process for a single module.
 *
 * Steps:
 * - Validates module structure
 * - Prepares build configuration
 * - Generates ZIP package
 * - Outputs build summary (header + footer)
 *
 * @param string $moduleName Module folder name (e.g., apog_core, apog_payment_cod)
 * @param string $srcDir Absolute path to the source directory
 * @param string $distDir Absolute path to the output (dist) directory
 *
 * @return void
 */
function runBuild(
    string $moduleName,
    string $srcDir,
    string $distDir
): void {
    $source = $srcDir . $moduleName;
    $zipPath = $distDir . $moduleName . '.ocmod.zip';

    validateModule($source);

    $config = [
        'name'             => $moduleName,
        'source'           => $source,
        'output_file_path' => $zipPath,
    ];

    $start = microtime(true);

    printBuildHeader($config);

    $result = buildPackage($source, $zipPath);

    $config['fileCount']   = $result['count'];
    $config['success']     = $result['success'];
    $config['elapsedTime'] = round(microtime(true) - $start, 3);

    printBuildFooter($config);
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
 * @return array{count:int,success:bool} Execution results containing file count and status
 */
function buildPackage(string $source, string $zipPath): array {
    if (file_exists($zipPath)) {
        out("⚠️  Warning: Overwriting existing package: " . basename($zipPath));
        unlink($zipPath);
    }

    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        out("❌ Error: Could not create zip file at $zipPath");
        return ['count' => 0, 'success' => false];
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    $fileCount = 0;

    foreach ($files as $file) {
        $filePath = $file->getRealPath();

        if ($filePath === false) {
            $zip->close();
            outAndExit("❌ Error: Failed to read file path from $source", 1);
        }

        // Relative path inside zip
        $relativePath = ltrim(substr($filePath, strlen($source)), DIRECTORY_SEPARATOR);

        if (!$zip->addFile($filePath, $relativePath)) {
            $zip->close();
            out("❌ Error: Failed to add file: $filePath");
            return ['count' => 0, 'success' => false];
        }

        $fileCount++;
    }

    $zip->close();

    out("📦 Package created: " . basename($zipPath));

    return ['count' => $fileCount, 'success' => true];
}


/**
 * Validates that a module directory has the minimum 
 * required OpenCart installer structure.
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
function validateModule(string $path): void {
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
 * Checks whether a directory name is a valid module name.
 *
 * @param string $name Directory name
 *
 * @return bool
 */
function isValidModuleName(string $name): bool {
    foreach (ModuleType::cases() as $type) {
        if ($type === ModuleType::CORE) {
            if ($name === "apog_{$type->value}") {
                return true;
            }
            continue;
        }

        $prefix = "apog_{$type->value}_";

        if (str_starts_with($name, $prefix)) {
            return true;
        }
    }

    return false;
}

/**
 * Scans the src directory and returns all valid module paths.
 *
 * Supports:
 * - apog_core
 * - apog_shipping_*
 * - apog_payment_*
 * - apog_total_*
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
        if (isValidModuleName($item)) {
            $modules[] = $item;
        }
    }

    return $modules;
}

/**
 * Prints the build header block.
 *
 * Displays package build context in a structured CLI format.
 *
 * @param array $config Configuration array with:
 *  - string 'name'             Module name
 *  - string 'source'           Source directory path
 *  - string 'output_file_path' Output ZIP file path
 *
 * @return void
 */
function printBuildHeader(array $config): void {
    $lines = [
        "⚙️  Building module package: {$config['name']}",
        "📁 Source: {$config['source']}",
        "📂 Output: {$config['output_file_path']}",
    ];

    cliPrintBlock($lines);
}

/**
 * Prints the build footer block after execution.
 *
 * Displays build result summary including:
 * - success or error state
 * - file count
 * - output location
 * - execution time
 *
 * @param array $config Configuration array with:
 *  - string 'output_file_path' Output ZIP file path
 *  - int    'fileCount'   Number of files added to archive
 *  - float  'elapsedTime' Execution time in seconds
 *
 * @return void
 */
function printBuildFooter(array $config): void {
    $lines = [];

    if ($config['fileCount'] === 0) {
        $lines[] = "❌ Error       : No files were added to the package. Check the source directory.";
    } else {
        $lines[] = "✅ Success     : Package created";
    }

    $lines[] = "📂 Location    : {$config['output_file_path']}";
    $lines[] = "📄 Files       : {$config['fileCount']}";
    $lines[] = "⏱️ Completed in: {$config['elapsedTime']}s";

    cliPrintBlock($lines);
}
