<?php

/**
 * Apog Extension Generator (CLI Only)
 */
if (PHP_SAPI !== 'cli') exit("This script must be run from the command line.\n");

if ($argc < 2) {
    echo "ℹ️  Usage:\n";
    echo "  php bin/generator.php core [--force]\n";
    echo "  php bin/generator.php shipping <code> \"Name\" [--force]\n";
    echo "  php bin/generator.php payment <code> \"Name\" [--force]\n";
    echo "  php bin/generator.php total <code> \"Name\" [binding_payment_code] [--force]\n";
    exit(1);
}

/**
 * Parse CLI arguments into:
 * - $args  (positional arguments)
 * - $flags (options like --force)
 *
 * This allows flexible ordering of flags without breaking argument positions.
 */
$rawArgs = $argv;
array_shift($rawArgs); // remove script name

$flags = array_filter($rawArgs, fn($arg) => str_starts_with($arg, '--'));
$args  = array_values(array_filter($rawArgs, fn($arg) => !str_starts_with($arg, '--')));

$force = in_array('--force', $flags, true);

$type           = $args[0] ?? null;
$rawModuleCode  = $args[1] ?? null;
$moduleCode     = normalizeCode($rawModuleCode);
$moduleName     = isset($args[2]) ? trim($args[2]) : null;
$bindingPaymentInput = $args[3] ?? null;
$bindingPayment      = normalizeBindingPayment($bindingPaymentInput);

if ('core' !== $type) {
    if (empty($moduleCode) || strlen($moduleCode) < 3) {
        outAndExit("❌ Module code must be at least 3 characters.");
    }

    if (trim($moduleName) === '') {
        outAndExit("❌ Module name cannot be empty.");
    }
}

$className = generateClassName($moduleCode);

$baseDir = dirname(__DIR__) . '/';
$srcDir  = $baseDir . 'src/';

/**
 * Recursively deletes a directory and all its contents.
 *
 * @param string $dir Absolute path to directory
 *
 * @return void
 */
function deleteDir($dir) {
    if (!is_dir($dir)) return;

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        $file->isDir() ? rmdir($file) : unlink($file);
    }

    rmdir($dir);
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
function normalizeCode(?string $code): string {
    $code = (string) $code;
    $code = trim($code);
    $code = strtolower($code);
    $code = str_replace(['-', ' '], '_', $code);
    $code = preg_replace('/[^a-z0-9_]/', '', $code); // remove anything weird
    $code = preg_replace('/_+/', '_', $code); // collapse multiple underscores
    return trim($code, '_');
}

/**
 * Normalizes a binding payment code for Apog modules.
 *
 * Rules:
 * - Converts empty or whitespace-only values to null
 * - Ensures the value is prefixed with "apog_"
 * - Leaves already-prefixed values unchanged
 *
 * Examples:
 *   "cod"        → "apog_cod"
 *   "apog_cod"   → "apog_cod"
 *   ""           → null
 *   null         → null
 *
 * @param string|null $bindingPayment Raw binding input from CLI
 *
 * @return string|null Normalized binding payment code or null if not provided
 */
function normalizeBindingPayment(?string $bindingPayment): ?string {
    if ($bindingPayment === null) {
        return null;
    }

    $bindingPayment = trim($bindingPayment);

    if ($bindingPayment === '') {
        return null;
    }

    if (!str_starts_with($bindingPayment, 'apog_')) {
        $bindingPayment = 'apog_' . $bindingPayment;
    }

    return $bindingPayment;
}

/**
 * Converts a module code into a PascalCase class name.
 *
 * Example:
 *   apog_shipping → ApogShipping
 *
 * @param string $code Normalized module code
 *
 * @return string Class name
 */
function generateClassName(string $code): string {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $code)));
}

/**
 * Generates files from template directory into destination.
 *
 * Features:
 * - Recursive copy
 * - Variable replacement in file contents and paths
 * - Removes `.tpl` extension from output files
 *
 * @param string $src Source template directory
 * @param string $dst Destination directory
 * @param array $vars Key-value replacements (e.g., {{module_code}} → value)
 * @param bool $force Whether to overwrite existing directory
 *
 * @return int $fileCount Number of files generated
 */
function generate($src, $dst, $vars = [], $force = false) {
    if (!is_dir($src)) {
        out("❌ Error: Source template directory not found: $src");
        return 0;
    }

    if (is_dir($dst)) {
        if (!$force) {
            out("⚠️  Warning: Module already exists: $dst");
            out("    👉 Use --force to overwrite");
            return 0;
        }

        out("⚠️  Warning: Target already exists — overwriting due to --force");
        deleteDir($dst);
    }
    
    if (!is_dir($dst) && !mkdir($dst, 0755, true)) {
        out("❌ Error: Failed to create directory: $dst", 1);
        return 0;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $fileCount = 0;

    foreach ($iterator as $item) {
        $relativePath = ltrim(substr($item->getPathname(), strlen($src)), DIRECTORY_SEPARATOR);

        // Replace variables in the path (filenames/folders)
        $targetRelativePath = str_replace(array_keys($vars), array_values($vars), $relativePath);

        // Remove .tpl extension from destination filename
        if (str_ends_with($targetRelativePath, '.tpl')) {
            $targetRelativePath = substr($targetRelativePath, 0, -4);
        }

        $dstPath = rtrim($dst, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $targetRelativePath;

        if ($item->isDir()) {
            if (!is_dir($dstPath)) mkdir($dstPath, 0755, true);
        } else {
            $content = file_get_contents($item->getRealPath());
            if (!empty($vars)) {
                $content = str_replace(array_keys($vars), array_values($vars), $content);
            }

            if (file_put_contents($dstPath, $content) === false) {
                outAndExit("❌ Error: Failed to write file: $dstPath", 1);
            }

            $fileCount++;
        }
    }

    return $fileCount;
}

/**
 * Prints a formatted header block for the build process.
 *
 * Displays module name, source path, and output path
 * in a consistent CLI-friendly format.
 *
 * @param array $config Configuration array with:
 *  - string 'type'   Module type
 *  - string 'name'   Module name
 *  - string 'code'   Module code
 *  - string 'target' Output generated folder path
 *
 * @return void
 */
function printHeader(array $config): void {
    out("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    out("⚙️  Generating - {$config['type']} - {$config['name']} - Module");
    out("📦 Name : {$config['name']}");
    out("🔑 Code : {$config['code']}");
    out("📁 Path : {$config['target']}");
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
        out("❌ Error          : No files were generated. Check the source directory and template variables.");
    } else {
        out("✅ Success        : Module generated");
    }

    out("📂 Location       : {$config['target']}");
    out("📄 Files generated: {$config['fileCount']}");
    out("⏱️  Completed in   : {$config['elapsedTime']}s");
    out("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
}

switch ($type) {

    case 'core':

        $moduleName = 'Apog Core';
        $moduleCode = 'apog_core';
        $target = $srcDir . $moduleCode;

        $config = [
            'type'  => 'core',
            'name'  => $moduleName,
            'code'  => $moduleCode,
            'target' => $target,
        ];

        $startTime = microtime(true);

        printHeader($config);

        $fileCount = generate($baseDir . 'core', $target, [], $force);

        $elapsedTimeInSeconds = round(microtime(true) - $startTime, 3);

        $config['fileCount'] = $fileCount;
        $config['elapsedTime'] = $elapsedTimeInSeconds;

        printFooter($config);
        break;

    case 'shipping':
        if (count($args) < 3) {
            outAndExit("ℹ️  Usage: php bin/generator.php shipping <code> \"Name\" [--force]");
        }

        $target    = $srcDir . "apog_shipping_{$moduleCode}";

        $vars = [
            '{{ext_type}}'     => 'shipping',
            '{{module_code}}'  => $moduleCode,
            '{{module_name}}'  => $moduleName,
            '{{ClassName}}'    => $className,
        ];

        $config = [
            'type'  => 'shipping',
            'name'  => $moduleName,
            'code'  => $moduleCode,
            'target' => $target,
        ];

        $startTime = microtime(true);

        printHeader($config);

        $fileCount = generate($baseDir . 'generators/templates/shipping', $target, $vars, $force);

        $elapsedTimeInSeconds = round(microtime(true) - $startTime, 3);

        $config['fileCount'] = $fileCount;
        $config['elapsedTime'] = $elapsedTimeInSeconds;

        printFooter($config);
        break;

    case 'payment':
        if (count($args) < 3) {
            outAndExit("ℹ️  Usage: php bin/generator.php payment <code> \"Name\" [--force]");
        }

        $target    = $srcDir . "apog_payment_{$moduleCode}";

        $vars = [
            '{{ext_type}}'     => 'payment',
            '{{module_code}}'  => $moduleCode,
            '{{module_name}}'  => $moduleName,
            '{{ClassName}}'    => $className,
        ];

        $config = [
            'type'   => 'payment',
            'name'   => $moduleName,
            'code'   => $moduleCode,
            'target' => $target,
        ];

        $startTime = microtime(true);

        printHeader($config);

        $fileCount = generate(
            $baseDir . 'generators/templates/payment',
            $target,
            $vars,
            $force
        );

        $elapsedTimeInSeconds = round(microtime(true) - $startTime, 3);

        $config['fileCount'] = $fileCount;
        $config['elapsedTime'] = $elapsedTimeInSeconds;

        printFooter($config);
        break;

    case 'total':
        if (count($args) < 3) {
            outAndExit("ℹ️  Usage: php bin/generator.php total <code> \"Name\" [--force]");
        }

        $target    = $srcDir . "apog_total_{$moduleCode}";

        /**
         * Optional binding input (future-proof)
         * example:
         * php generator.php total cod_fee "COD Fee" cod
         */
        out("🔗 Binding : " . ($bindingPayment ?? 'none'));

        if ($bindingPayment !== null && !preg_match('/^apog_[a-z0-9_]+$/', $bindingPayment)) {
            outAndExit("❌ Invalid binding payment format. Expected: apog_<code>");
        }

        $vars = [
            '{{ext_type}}'            => 'total',
            '{{module_code}}'         => $moduleCode,
            '{{module_name}}'         => $moduleName,
            '{{ClassName}}'           => $className,
            '{{binding_payment_code}}' => $bindingPayment !== null
                ? "'" . $bindingPayment . "'"
                : 'null',
        ];

        $config = [
            'type'   => 'total',
            'name'   => $moduleName,
            'code'   => $moduleCode,
            'target' => $target,
        ];

        $startTime = microtime(true);

        printHeader($config);

        $fileCount = generate(
            $baseDir . 'generators/templates/total',
            $target,
            $vars,
            $force
        );

        $elapsedTimeInSeconds = round(microtime(true) - $startTime, 3);

        $config['fileCount'] = $fileCount;
        $config['elapsedTime'] = $elapsedTimeInSeconds;

        printFooter($config);

        break;
    default:
        outAndExit("❌ Error: Unknown type '$type'. Use 'core', 'shipping', 'payment', or 'total'.");
}
