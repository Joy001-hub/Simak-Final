<?php
/**
 * PHP Minifier for Laravel Projects
 * 
 * This script minifies PHP files by:
 * 1. Removing all comments
 * 2. Removing unnecessary whitespace
 * 3. Encoding string literals to hex (optional)
 * 
 * Safe for Laravel - does NOT change class/method/variable names
 * 
 * Usage: php minify-php.php <source_dir> <target_dir> [--encode-strings]
 */

if ($argc < 3) {
    echo "Usage: php minify-php.php <source_dir> <target_dir> [--encode-strings]\n";
    exit(1);
}

$sourceDir = rtrim($argv[1], '/\\');
$targetDir = rtrim($argv[2], '/\\');
$encodeStrings = in_array('--encode-strings', $argv);

// Directories to skip (don't minify)
$skipDirs = [
    'vendor',
    'node_modules',
    'storage',
    'bootstrap/cache',
    'dist',
    'build',
    'tests',
    '.git',
    'public/build',
];

// Files to skip (don't minify)
$skipFiles = [
    'artisan',
];

// Directories where we should NOT encode strings (config values matter)
$noStringEncodeDirs = [
    'config',
    'routes',
    'database/migrations',
    'database/seeders',
];

$stats = ['processed' => 0, 'skipped' => 0, 'copied' => 0, 'errors' => 0];

function shouldSkip($path, $skipList, $baseDir)
{
    $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path);
    $relativePath = str_replace('\\', '/', $relativePath);

    foreach ($skipList as $skip) {
        if (strpos($relativePath, $skip) === 0 || strpos($relativePath, '/' . $skip) !== false) {
            return true;
        }
    }
    return false;
}

function shouldSkipStringEncode($path, $noEncodeDirs, $baseDir)
{
    $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path);
    $relativePath = str_replace('\\', '/', $relativePath);

    foreach ($noEncodeDirs as $dir) {
        if (strpos($relativePath, $dir) === 0) {
            return true;
        }
    }
    return false;
}

function minifyPhp($code, $encodeStrings = false, $skipStringEncode = false)
{
    $tokens = token_get_all($code);
    $output = '';
    $prevType = null;
    $prevIsWord = false;

    $tokenCount = count($tokens);

    for ($i = 0; $i < $tokenCount; $i++) {
        $token = $tokens[$i];

        if (is_array($token)) {
            list($type, $value, $line) = $token;

            // Skip comments
            if (in_array($type, [T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            // Handle whitespace
            if ($type === T_WHITESPACE) {
                // Check if we need space between previous and next token
                $nextType = null;
                $nextIsWord = false;

                for ($j = $i + 1; $j < $tokenCount; $j++) {
                    $next = $tokens[$j];
                    if (is_array($next)) {
                        if (!in_array($next[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                            $nextType = $next[0];
                            $nextIsWord = isWordToken($next[0]);
                            break;
                        }
                    } else {
                        break;
                    }
                }

                // Add space if both sides are "word" tokens
                if ($prevIsWord && $nextIsWord) {
                    $output .= ' ';
                }
                // Also add space after certain keywords regardless
                elseif (needsSpaceAfter($prevType) && $nextType !== null) {
                    $output .= ' ';
                }

                continue;
            }

            // Encode string literals (if enabled and not in skip directories)
            if ($encodeStrings && !$skipStringEncode && $type === T_CONSTANT_ENCAPSED_STRING) {
                $value = encodeString($value);
            }

            $output .= $value;
            $prevType = $type;
            $prevIsWord = isWordToken($type);
        } else {
            $output .= $token;
            $prevType = null;
            $prevIsWord = false;
        }
    }

    return $output;
}

function isWordToken($type)
{
    // Tokens that represent "words" that need spaces between them
    $wordTokens = [
        T_STRING,
        T_VARIABLE,
        T_LNUMBER,
        T_DNUMBER,
        T_NAME_QUALIFIED,
        T_NAME_FULLY_QUALIFIED,
        T_NAME_RELATIVE,
        T_ABSTRACT,
        T_AS,
        T_BREAK,
        T_CALLABLE,
        T_CASE,
        T_CATCH,
        T_CLASS,
        T_CLONE,
        T_CONST,
        T_CONTINUE,
        T_DECLARE,
        T_DEFAULT,
        T_DO,
        T_ECHO,
        T_ELSE,
        T_ELSEIF,
        T_EXTENDS,
        T_FINAL,
        T_FOR,
        T_FOREACH,
        T_FUNCTION,
        T_GLOBAL,
        T_GOTO,
        T_IF,
        T_IMPLEMENTS,
        T_INCLUDE,
        T_INCLUDE_ONCE,
        T_INSTANCEOF,
        T_INSTEADOF,
        T_INTERFACE,
        T_MATCH,
        T_NAMESPACE,
        T_NEW,
        T_PRIVATE,
        T_PROTECTED,
        T_PUBLIC,
        T_READONLY,
        T_REQUIRE,
        T_REQUIRE_ONCE,
        T_RETURN,
        T_STATIC,
        T_SWITCH,
        T_THROW,
        T_TRAIT,
        T_TRY,
        T_USE,
        T_VAR,
        T_WHILE,
        T_YIELD,
        T_YIELD_FROM,
        T_FN,
        T_ARRAY,
        T_PRINT,
    ];

    // Add T_ENUM if exists (PHP 8.1+)
    if (defined('T_ENUM')) {
        $wordTokens[] = T_ENUM;
    }

    return in_array($type, $wordTokens);
}

function needsSpaceAfter($prevType)
{
    if ($prevType === null)
        return false;

    // Keywords that ALWAYS need space after them
    $mustHaveSpace = [
        T_ABSTRACT,
        T_AS,
        T_CASE,
        T_CATCH,
        T_CLASS,
        T_CLONE,
        T_CONST,
        T_DECLARE,
        T_ECHO,
        T_ELSE,
        T_ELSEIF,
        T_EXTENDS,
        T_FINAL,
        T_FOR,
        T_FOREACH,
        T_FUNCTION,
        T_GLOBAL,
        T_GOTO,
        T_IF,
        T_IMPLEMENTS,
        T_INCLUDE,
        T_INCLUDE_ONCE,
        T_INSTANCEOF,
        T_INSTEADOF,
        T_INTERFACE,
        T_MATCH,
        T_NAMESPACE,
        T_NEW,
        T_PRIVATE,
        T_PROTECTED,
        T_PUBLIC,
        T_READONLY,
        T_REQUIRE,
        T_REQUIRE_ONCE,
        T_RETURN,
        T_STATIC,
        T_SWITCH,
        T_THROW,
        T_TRAIT,
        T_TRY,
        T_USE,
        T_VAR,
        T_WHILE,
        T_YIELD,
        T_YIELD_FROM,
        T_FN,
        T_PRINT,
    ];

    if (defined('T_ENUM')) {
        $mustHaveSpace[] = T_ENUM;
    }

    return in_array($prevType, $mustHaveSpace);
}

function needsSpace($prevType)
{
    if ($prevType === null)
        return false;

    // Keywords that need space after them
    $needsSpace = [
        T_ABSTRACT,
        T_AS,
        T_BREAK,
        T_CALLABLE,
        T_CASE,
        T_CATCH,
        T_CLASS,
        T_CLONE,
        T_CONST,
        T_CONTINUE,
        T_DECLARE,
        T_DEFAULT,
        T_DO,
        T_ECHO,
        T_ELSE,
        T_ELSEIF,
        T_EXTENDS,
        T_FINAL,
        T_FOR,
        T_FOREACH,
        T_FUNCTION,
        T_GLOBAL,
        T_GOTO,
        T_IF,
        T_IMPLEMENTS,
        T_INCLUDE,
        T_INCLUDE_ONCE,
        T_INSTANCEOF,
        T_INSTEADOF,
        T_INTERFACE,
        T_MATCH,
        T_NAMESPACE,
        T_NEW,
        T_PRIVATE,
        T_PROTECTED,
        T_PUBLIC,
        T_READONLY,
        T_REQUIRE,
        T_REQUIRE_ONCE,
        T_RETURN,
        T_STATIC,
        T_SWITCH,
        T_THROW,
        T_TRAIT,
        T_TRY,
        T_USE,
        T_VAR,
        T_WHILE,
        T_YIELD,
        T_YIELD_FROM,
        T_FN,
        T_ENUM,
        T_STRING,
        T_VARIABLE,
        T_LNUMBER,
        T_DNUMBER,
    ];

    return in_array($prevType, $needsSpace);
}

function encodeString($str)
{
    // Get the quote character
    $quote = $str[0];
    $inner = substr($str, 1, -1);

    // Skip if string contains variables (double quotes with $)
    if ($quote === '"' && strpos($inner, '$') !== false) {
        return $str;
    }

    // Skip very short strings or strings with special chars
    if (strlen($inner) < 4 || preg_match('/[\x00-\x1f]/', $inner)) {
        return $str;
    }

    // Skip strings that look like class names, paths, or SQL
    if (preg_match('/^(App\\\\|Illuminate\\\\|Native\\\\|SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP)/i', $inner)) {
        return $str;
    }

    // Convert to hex representation
    $hex = '';
    for ($i = 0; $i < strlen($inner); $i++) {
        $hex .= '\\x' . dechex(ord($inner[$i]));
    }

    return '"' . $hex . '"';
}

function processDirectory($source, $target, $baseSource)
{
    global $skipDirs, $skipFiles, $noStringEncodeDirs, $encodeStrings, $stats;

    if (!is_dir($target)) {
        mkdir($target, 0755, true);
    }

    $items = scandir($source);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..')
            continue;

        $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
        $targetPath = $target . DIRECTORY_SEPARATOR . $item;

        // Skip directories
        if (is_dir($sourcePath)) {
            if (shouldSkip($sourcePath, $skipDirs, $baseSource)) {
                echo "  [SKIP DIR] $item\n";
                $stats['skipped']++;
                continue;
            }
            processDirectory($sourcePath, $targetPath, $baseSource);
            continue;
        }

        // Skip specific files
        if (in_array($item, $skipFiles)) {
            echo "  [SKIP FILE] $item\n";
            copy($sourcePath, $targetPath);
            $stats['skipped']++;
            continue;
        }

        // Only minify PHP files
        if (pathinfo($item, PATHINFO_EXTENSION) !== 'php') {
            copy($sourcePath, $targetPath);
            $stats['copied']++;
            continue;
        }

        // Check if in skip directories
        if (shouldSkip($sourcePath, $skipDirs, $baseSource)) {
            copy($sourcePath, $targetPath);
            $stats['skipped']++;
            continue;
        }

        try {
            $code = file_get_contents($sourcePath);
            $skipStringEncode = shouldSkipStringEncode($sourcePath, $noStringEncodeDirs, $baseSource);
            $minified = minifyPhp($code, $encodeStrings, $skipStringEncode);
            file_put_contents($targetPath, $minified);

            $originalSize = strlen($code);
            $minifiedSize = strlen($minified);
            $reduction = round((1 - $minifiedSize / $originalSize) * 100);

            $relativePath = str_replace($baseSource . DIRECTORY_SEPARATOR, '', $sourcePath);
            echo "  [MINIFIED] $relativePath (-{$reduction}%)\n";
            $stats['processed']++;
        } catch (Exception $e) {
            echo "  [ERROR] $item: " . $e->getMessage() . "\n";
            copy($sourcePath, $targetPath);
            $stats['errors']++;
        }
    }
}

// Main execution
echo "\n";
echo "========================================\n";
echo "  PHP Minifier for Laravel\n";
echo "========================================\n";
echo "\n";
echo "Source: $sourceDir\n";
echo "Target: $targetDir\n";
echo "Encode Strings: " . ($encodeStrings ? "YES" : "NO") . "\n";
echo "\n";
echo "Processing...\n";
echo "\n";

if (!is_dir($sourceDir)) {
    echo "ERROR: Source directory does not exist!\n";
    exit(1);
}

processDirectory($sourceDir, $targetDir, $sourceDir);

echo "\n";
echo "========================================\n";
echo "  Summary\n";
echo "========================================\n";
echo "  Processed: {$stats['processed']} files\n";
echo "  Skipped:   {$stats['skipped']} files/dirs\n";
echo "  Copied:    {$stats['copied']} files\n";
echo "  Errors:    {$stats['errors']} files\n";
echo "\n";
echo "Done! Minified files are in: $targetDir\n";
echo "\n";
