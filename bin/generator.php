<?php
/**
 * Apog Extension Generator (CLI Only)
 */
if (PHP_SAPI !== 'cli') exit("This script must be run from the command line.\n");

if ($argc < 2) {
    echo "Usage:\n";
    echo "  php bin/generator.php core\n";
    echo "  php bin/generator.php shipping <code> \"Friendly Name\"\n";
    exit(1);
}

$type = $argv[1];
$baseDir = dirname(__DIR__) . '/';
$srcDir  = $baseDir . 'src/';

/**
 * Clean and Recursive Copy
 */
function generate($src, $dst, $vars = []) {
    if (!is_dir($src)) exit("Error: Source template directory not found: $src\n");
    
    // Clean target if exists to avoid mixing old and new versions
    if (is_dir($dst)) {
        exec(PHP_OS_FAMILY === 'Windows' ? "rd /s /q " . escapeshellarg($dst) : "rm -rf " . escapeshellarg($dst));
    }
    mkdir($dst, 0755, true);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relativePath = substr($item->getPathname(), strlen($src));
        
        // Replace variables in the path (filenames/folders)
        $targetRelativePath = str_replace(array_keys($vars), array_values($vars), $relativePath);
        
        // Remove .tpl extension from destination filename
        if (str_ends_with($targetRelativePath, '.tpl')) {
            $targetRelativePath = substr($targetRelativePath, 0, -4);
        }
        
        $dstPath = $dst . $targetRelativePath;

        if ($item->isDir()) {
            if (!is_dir($dstPath)) mkdir($dstPath, 0755, true);
        } else {
            $content = file_get_contents($item->getRealPath());
            if (!empty($vars)) {
                $content = str_replace(array_keys($vars), array_values($vars), $content);
            }
            file_put_contents($dstPath, $content);
        }
    }
}

switch ($type) {

    case 'core':
        $target = $srcDir . 'apog_core';

        if (is_dir($target)) {
            exit("Error: Target already exists: $target\n");
        }

        echo "⚙️  Generating Apog Core...\n";
        generate($baseDir . 'core', $target);
        echo "✅ Done! Core generated at $target\n";
        break;

    case 'shipping':
        if ($argc < 4) {
            exit("Usage: php bin/generator.php shipping <code> \"Name\"\n");
        }

        $code = strtolower($argv[2]);
        $name = $argv[3];
        $className = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $code)));

        $vars = [
            '{{ext_type}}'     => 'shipping',
            '{{module_code}}'  => $code,
            '{{module_name}}'  => $name,
            '{{ClassName}}'    => $className,
        ];

        $target = $srcDir . "apog_shipping_$code";

        if (is_dir($target)) {
            exit("Error: Target already exists: $target\n");
        }

        echo "⚙️  Generating Shipping: $name ($code)...\n";
        generate($baseDir . 'generators/templates/shipping', $target, $vars);
        echo "✅ Done! Module generated at: $target\n";
        break;

    default:
        exit("❌ Error: Unknown type '$type'. Use 'core' or 'shipping'.\n");
}
