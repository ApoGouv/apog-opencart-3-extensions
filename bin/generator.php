<?php
declare(strict_types=1);

/**
 * Apog Extension Generator (CLI Only)
 */
if (PHP_SAPI !== 'cli') exit("This script must be run from the command line.\n");

require_once __DIR__ . '/helpers/cli_helpers.php';

const HELP_TEXT = <<<TXT
Apog Extension Generator

Usage:
  php bin/generator.php --type=core [--force]
  php bin/generator.php --type=shipping --code=<code> --name="Name" [--force]
  php bin/generator.php --type=payment  --code=<code> --name="Name" [--force]
  php bin/generator.php --type=total    --code=<code> --name="Name" [--binding_payment=<code>] [--force]

Options:
  --type              Module type: core, shipping, payment, total
  --code              Module code (required except core)
  --name              Module display name (required except core)
  --binding_payment   Only for total modules (e.g. cod)
  --force             Overwrite existing module
  --help              Show this help message

Examples:
  php bin/generator.php --type=payment --code=cod --name="Cash on Delivery"
  php bin/generator.php --type=total --code=cod_fee --name="COD Fee" --binding_payment=cod
TXT;

const BINDING_PATTERN = '/^apog_[a-z0-9_]+$/';

enum ModuleType: string {
    case CORE = 'core';
    case SHIPPING = 'shipping';
    case PAYMENT = 'payment';
    case TOTAL = 'total';
}

/**
 * MODULE CONFIG
 * Returns the module definition configuration for a given module type.
 *
 * The definition describes how the generator should behave for each module type,
 * including which template to use and which inputs are required or supported.
 *
 * Keys:
 * - template (string): Template directory name used for generation
 * - requires_code (bool): Whether --code is required
 * - requires_name (bool): Whether --name is required
 * - supports_binding (bool): Whether --binding_payment is supported (optional)
 *
 * @param ModuleType $type Module type enum (core, shipping, payment, total)
 *
 * @return array{
 *     template: string,
 *     requires_code: bool,
 *     requires_name: bool,
 *     supports_binding: bool
 * }
 */
function getModuleDefinition(ModuleType $type): array {
    return match ($type) {
        ModuleType::CORE => [
            'template' => 'core',
            'requires_code' => false,
            'requires_name' => false,
            'supports_binding' => false,
        ],

        ModuleType::SHIPPING => [
            'template' => 'shipping',
            'requires_code' => true,
            'requires_name' => true,
            'supports_binding' => false,
        ],

        ModuleType::PAYMENT => [
            'template' => 'payment',
            'requires_code' => true,
            'requires_name' => true,
            'supports_binding' => false,
        ],

        ModuleType::TOTAL => [
            'template' => 'total',
            'requires_code' => true,
            'requires_name' => true,
            'supports_binding' => true,
        ],
    };
}

$params = parseNamedArgs($argv);

if (!empty($params['help'])) {
    outAndExit(HELP_TEXT, 0);
}

$type                = $params['type'] ?? null;
$rawModuleCode       = $params['code'] ?? null;
$moduleCode          = normalizeCode($rawModuleCode);
$moduleName          = isset($params['name']) ? trim($params['name']) : null;
$bindingPaymentInput = $params['binding_payment'] ?? null;
$bindingPayment      = normalizeBindingPayment($bindingPaymentInput);
$force               = !empty($params['force']);

$typeEnum = is_string($type) && $type !== ''
    ? ModuleType::tryFrom($type)
    : null;

if (!$typeEnum) {
    out("❌ Error: Unknown type '$type'");
    outAndExit(HELP_TEXT, 1);
}

if ($typeEnum === ModuleType::CORE) {
    $moduleName      = 'Apog Core';
    $moduleCode      = 'apog_core';
}

$moduleDefinition = getModuleDefinition($typeEnum);

$baseDir = dirname(__DIR__) . '/';
$sourceDir  = $baseDir . 'src/';

/**
 * -----------------------------
 * VALIDATION (CENTRALIZED)
 * -----------------------------
 */
if ($moduleDefinition['requires_code'] && !$moduleCode) {
    outAndExit("❌ Module --code is required.");
}

if ($moduleDefinition['requires_name'] && !$moduleName) {
    outAndExit("❌ Module --name is required.");
}

if ($moduleDefinition['supports_binding'] && $bindingPayment !== null && !preg_match(BINDING_PATTERN, $bindingPayment)) {
    outAndExit("❌ Invalid binding payment format. Expected: apog_<code>");
}

if ($moduleDefinition['requires_code']) {
    try {
        validateCode($moduleCode);
    } catch (\Throwable $e) {
        outAndExit("❌ " . $e->getMessage(), 1);
    }
}

/**
 * -----------------------------
 * CORE RUNNER
 * -----------------------------
 */
runGenerator(
    type: $typeEnum,
    moduleDefinition: $moduleDefinition,
    moduleCode: $moduleCode,
    moduleName: $moduleName,
    bindingPayment: $bindingPayment,
    baseDir: $baseDir,
    sourceDir: $sourceDir,
    force: $force
);

/**
 * Executes the module generation process.
 *
 * Responsibilities:
 * - Resolves template and output directories
 * - Builds template replacement variables
 * - Prints CLI header and footer blocks
 * - Executes file generation
 * - Measures and reports execution time
 *
 * @param ModuleType   $type             Module type enum (core, shipping, payment, total)
 * @param array        $moduleDefinition Module definition config (e.g. ['template' => 'payment'])
 * @param string|null  $moduleCode       Normalized module code (e.g. apog_cod)
 * @param string|null  $moduleName       Module display name
 * @param string|null  $bindingPayment   Optional binding payment code (for total modules)
 * @param string       $baseDir          Base project directory
 * @param string       $sourceDir        Source output directory (e.g. /src/)
 * @param bool         $force            Whether to overwrite existing modules
 *
 * @return void
 */
function runGenerator(
    ModuleType $type,
    array $moduleDefinition,
    ?string $moduleCode,
    ?string $moduleName,
    ?string $bindingPayment,
    string $baseDir,
    string $sourceDir,
    bool $force
): void {

    $templateDir = $baseDir . 'generators/templates/' . $moduleDefinition['template'];
    $templateDir = match ($type) {
        ModuleType::CORE => $baseDir . 'core',
        default          => $baseDir . 'generators/templates/' . $moduleDefinition['template'],
    };

    $outputDir = match ($type) {
        ModuleType::CORE => $sourceDir . $moduleCode,
        default => $sourceDir . 'apog_' . $moduleDefinition['template'] . '_' . $moduleCode,
    };

    $className = generateClassName($moduleCode);

    $replaceVars = buildReplaceVars(
        type: $type,
        moduleCode: $moduleCode,
        moduleName: $moduleName,
        className: $className,
        bindingPayment: $bindingPayment
    );

    $config = [
        'moduleType' => $type->value,
        'name'       => $moduleName,
        'code'       => $moduleCode,
        'outputDir'  => $outputDir,
    ];

    $start = microtime(true);

    printGeneratorHeader($config);

    $result = generate($templateDir, $outputDir, $replaceVars, $force);

    $config['fileCount']   = $result['count'];
    $config['success']     = $result['success'];
    $config['elapsedTime'] = round(microtime(true) - $start, 3);

    printGeneratorFooter($config);
}

/**
 * Builds the template variable replacement map for module generation.
 *
 * Generates the key-value pairs used to replace placeholders in template
 * files and paths (e.g. {{module_code}} → apog_cod).
 *
 * Special handling:
 * - CORE modules return an empty array (no replacements needed)
 * - TOTAL modules include optional binding payment variable
 *
 * @param ModuleType   $type           Module type enum
 * @param string|null  $moduleCode     Normalized module code
 * @param string|null  $moduleName     Module display name
 * @param string       $className      Generated PascalCase class name
 * @param string|null  $bindingPayment Optional binding payment code
 *
 * @return array<string, string> Replacement map (placeholder => value)
 */
function buildReplaceVars(
    ModuleType $type,
    ?string $moduleCode,
    ?string $moduleName,
    string $className,
    ?string $bindingPayment
): array {
    if ($type === ModuleType::CORE) {
        return []; // No variable replacement needed for core
    }

    $vars = [
        '{{ext_type}}'    => $type->value,
        '{{module_code}}' => $moduleCode ?? '',
        '{{module_name}}' => $moduleName ?? '',
        '{{ClassName}}'   => $className,
    ];

    if ($type === ModuleType::TOTAL) {
        $vars['{{binding_payment_code}}'] =
            $bindingPayment !== null
            ? "'" . $bindingPayment . "'"
            : 'null';
    }

    return $vars;
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
    return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $code)));
}

/**
 * FILE GENERATION
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
 * @return array{count: int, success: bool} Execution results containing file count and status
 */
function generate(string $src, string $dst, array $vars = [], bool $force = false): array {
    if (!is_dir($src)) {
        out("❌ Error: Source template directory not found: $src");
        return [
            'count' => 0,
            'success' => false,
        ];
    }

    if (is_dir($dst)) {
        if (!$force) {
            out("⚠️  Warning: Module already exists: $dst");
            out("    👉 Use --force to overwrite");
            return [
                'count' => 0,
                'success' => false,
            ];
        }

        out("⚠️  Warning: Target output directory already exists — overwriting due to --force");

        try {
            deleteDir($dst);
        } catch (Throwable $e) {
            outAndExit("❌ " . $e->getMessage(), 1);
        }
    }

    if (!is_dir($dst) && !mkdir($dst, 0755, true)) {
        outAndExit("❌ Error: Failed to create directory: $dst", 1);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $fileCount = 0;

    foreach ($iterator as $item) {
        $relativePath = substr($item->getPathname(), strlen($src));
        $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);

        $targetPath = resolveTargetPath($dst, $relativePath, $vars);

        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
            continue;
        }

        $content = file_get_contents($item->getRealPath());

        if ($content === false) {
            outAndExit("❌ Failed to read file: " . $item->getRealPath(), 1);
        }

        $content = applyTemplateVars($content, $vars);

        writeFile($targetPath, $content);

        $fileCount++;
    }

    return [
        'count' => $fileCount,
        'success' => true,
    ];
}

/**
 * Resolves the final destination file path for a generated file.
 *
 * Applies template variable replacements to the relative path and
 * removes the `.tpl` extension if present.
 *
 * @param string $dst          Base destination directory
 * @param string $relativePath Relative path from the template source
 * @param array  $vars         Key-value replacements (e.g., {{module_code}} → value)
 *
 * @return string Absolute path to the destination file
 */
function resolveTargetPath(string $dst, string $relativePath, array $vars): string {
    // Replace variables in the path (filenames/folders)
    $relativePath = str_replace(array_keys($vars), array_values($vars), $relativePath);

    // Remove .tpl extension from destination filename
    if (str_ends_with($relativePath, '.tpl')) {
        $relativePath = substr($relativePath, 0, -4);
    }

    return rtrim($dst, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
}

/**
 * Applies template variable replacements to file content.
 *
 * If no variables are provided, the original content is returned unchanged.
 *
 * @param string $content File content
 * @param array  $vars    Key-value replacements (e.g., {{module_code}} → value)
 *
 * @return string Processed content with variables replaced
 */
function applyTemplateVars(string $content, array $vars): string {
    return empty($vars)
        ? $content
        : str_replace(array_keys($vars), array_values($vars), $content);
}

/**
 * Recursively deletes a directory and all its contents.
 *
 * @param string $dir Absolute path to directory
 * 
 * @throws RuntimeException if an unsafe path is provided or deletion fails
 *
 * @return void
 */
function deleteDir(string $dir): void {
    if (!is_dir($dir)) return;

    if ($dir === '' || $dir === '/' || $dir === DIRECTORY_SEPARATOR) {
        throw new RuntimeException("Unsafe directory deletion attempt: $dir");
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        $path = $file->getPathname();

        $file->isDir() ? rmdir($path) : unlink($path);
    }

    rmdir($dir);
}

/**
 * Writes content to a file.
 *
 * Terminates execution if the write operation fails.
 *
 * @param string $path    Absolute file path
 * @param string $content File content to write
 *
 * @return void
 */
function writeFile(string $path, string $content): void {
    $dir = dirname($path);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (file_put_contents($path, $content) === false) {
        outAndExit("❌ Failed to write file: $path", 1);
    }
}

/**
 * Prints the generator header block.
 *
 * Displays module generation context in a structured CLI format.
 *
 * @param array $config Configuration array with:
 *  - string 'moduleType' Module type (e.g. payment, shipping, total)
 *  - string 'name'       Module display name
 *  - string 'code'       Module code (normalized)
 *  - string 'outputDir'  Output directory path
 *
 * @return void
 */
function printGeneratorHeader(array $config): void {
    $lines = [
        "⚙️  Generating {$config['moduleType']} module: {$config['name']}",
        "📦 Name       : {$config['name']}",
        "🔑 Code       : {$config['code']}",
        "📁 Output dir : {$config['outputDir']}",
    ];

    cliPrintBlock($lines);
}

/**
 * Prints the generator footer block after execution.
 *
 * Displays generation result summary including:
 * - success or error state
 * - generated file count
 * - output location
 * - execution time
 *
 * @param array $config Configuration array with:
 *  - string 'outputDir'  Output directory path
 *  - int    'fileCount'   Number of generated files
 *  - float  'elapsedTime' Execution time in seconds
 *
 * @return void
 */
function printGeneratorFooter(array $config): void {
    $lines = [];

    if (!$config['success'] || $config['fileCount'] === 0) {
        $lines[] = "❌ Error          : No files were generated. Check templates and input configuration.";
    } else {
        $lines[] = "✅ Success        : Module generated";
    }

    $lines[] = "📂 Location       : {$config['outputDir']}";
    $lines[] = "📄 Files generated: {$config['fileCount']}";
    $lines[] = "⏱️ Completed in   : {$config['elapsedTime']}s";

    cliPrintBlock($lines);
}
