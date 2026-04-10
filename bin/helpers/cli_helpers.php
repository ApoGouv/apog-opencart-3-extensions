<?php

// ---------------------
// CLI Parsing
// ---------------------

/**
 * Parses CLI arguments in --key=value format.
 *
 * Example:
 *   --type=shipping --code=cod
 *   --type=shipping --code=cod --name="COD"
 *
 * @param array $argv
 * @return array
 */
function parseNamedArgs(array $argv): array {
    $params = [];

    foreach ($argv as $arg) {
        if (!str_starts_with($arg, '--')) continue;

        $arg = substr($arg, 2);

        if (str_contains($arg, '=')) {
            [$key, $value] = explode('=', $arg, 2);
            $params[strtolower($key)] = $value;
        } else {
            // flag (e.g. --force, --all)
            $params[strtolower($arg)] = true;
        }
    }

    return $params;
}


// ---------------------
// Output
// ---------------------

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
 * Prints a CLI section separator.
 */
function cliSeparator(): void {
    out("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
}

/**
 * Prints a CLI formatted block.
 *
 * @param string[] $lines Lines to display inside the block
 * @param bool $withSeparator Whether to wrap with separators
 */
function cliPrintBlock(array $lines, bool $withSeparator = true): void {
    if ($withSeparator) {
        cliSeparator();
    }

    foreach ($lines as $line) {
        out($line);
    }

    if ($withSeparator) {
        cliSeparator();
    }
}


// ---------------------
// Normalization
// ---------------------

/**
 * Normalizes a module code to a safe format.
 *
 * Rules:
 * - lowercase
 * - spaces and dashes to underscores
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

// ---------------------
// Validation
// ---------------------


/**
 * Validates a module code.
 *
 * The code must be a non-empty string with a minimum length of 3 characters.
 *
 * @param string $moduleCode Normalized module code to validate
 *
 * @throws InvalidArgumentException If the code fails validation checks
 *
 * @return void
 */
function validateCode(string $moduleCode): void {
    if (!is_string($moduleCode) || $moduleCode === '' || strlen($moduleCode) < 3) {
        throw new InvalidArgumentException("Module code must be at least 3 characters.");
    }
}

