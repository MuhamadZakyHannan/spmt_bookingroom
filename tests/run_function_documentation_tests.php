<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * Menghentikan audit ketika fungsi bernama belum mempunyai dokumentasi singkat.
 */
function expectFunctionDocumentation(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

/**
 * Mengambil seluruh file PHP produksi yang wajib memiliki dokumentasi fungsi.
 */
function productionPhpFiles(string $root): array
{
    $paths = [$root . '/app', $root . '/api', $root . '/scripts'];
    $files = [$root . '/config.php'];
    foreach ($paths as $path) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }
    sort($files);
    return $files;
}

/**
 * Menentukan apakah token fungsi bernama didahului PHPDoc setelah modifier metode.
 */
function hasFunctionDocblock(array $tokens, int $functionIndex): bool
{
    $ignored = [T_WHITESPACE, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT];
    for ($index = $functionIndex - 1; $index >= 0; $index--) {
        $token = $tokens[$index];
        if (is_string($token)) {
            if (trim($token) === '') continue;
            return false;
        }
        if (in_array($token[0], $ignored, true)) continue;
        return $token[0] === T_DOC_COMMENT;
    }
    return false;
}

/**
 * Mengaudit PHPDoc seluruh fungsi dan metode PHP bernama dalam satu file.
 */
function undocumentedPhpFunctions(string $file, string $root): array
{
    $tokens = token_get_all((string) file_get_contents($file));
    $missing = [];
    foreach ($tokens as $index => $token) {
        if (!is_array($token) || $token[0] !== T_FUNCTION) continue;

        $name = null;
        for ($cursor = $index + 1, $count = count($tokens); $cursor < $count; $cursor++) {
            $candidate = $tokens[$cursor];
            if (is_array($candidate) && $candidate[0] === T_STRING) {
                $name = $candidate[1];
                break;
            }
            if ($candidate === '(') break;
        }
        if ($name !== null && !hasFunctionDocblock($tokens, $index)) {
            $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));
            $missing[] = $relative . ':' . $token[2] . ' ' . $name . '()';
        }
    }
    return $missing;
}

$root = dirname(__DIR__);
$missingPhp = [];
foreach (productionPhpFiles($root) as $file) {
    array_push($missingPhp, ...undocumentedPhpFunctions($file, $root));
}

expectFunctionDocumentation(
    $missingPhp === [],
    $missingPhp === []
        ? 'Seluruh fungsi dan metode PHP produksi memiliki PHPDoc.'
        : "Fungsi PHP tanpa dokumentasi:\n- " . implode("\n- ", $missingPhp)
);

$javascriptFiles = glob($root . '/public/js/*.js') ?: [];
$viewIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/app/views'));
foreach ($viewIterator as $viewFile) {
    if ($viewFile->isFile() && strtolower($viewFile->getExtension()) === 'php') {
        $javascriptFiles[] = $viewFile->getPathname();
    }
}
$missingJs = [];
foreach ($javascriptFiles as $file) {
    $lines = file($file) ?: [];
    foreach ($lines as $index => $line) {
        $functionMatch = preg_match('/^\s*(?:async\s+)?function\s+([A-Za-z_$][\w$]*)\s*\(/', $line, $functionParts);
        $arrowMatch = preg_match('/^\s*(?:const|let|var)\s+([A-Za-z_$][\w$]*)\s*=\s*(?:async\s*)?(?:\([^;]*\)|[A-Za-z_$][\w$]*)\s*=>/', $line, $arrowParts);
        if (!$functionMatch && !$arrowMatch) continue;
        $functionName = $functionMatch ? $functionParts[1] : $arrowParts[1];
        $previous = $index - 1;
        while ($previous >= 0 && trim($lines[$previous]) === '') $previous--;
        if ($previous < 0 || !str_ends_with(trim($lines[$previous]), '*/')) {
            $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));
            $missingJs[] = $relative . ':' . ($index + 1) . ' ' . $functionName . '()';
        }
    }
}

expectFunctionDocumentation(
    $missingJs === [],
    $missingJs === []
        ? 'Seluruh fungsi JavaScript bernama pada aset dan view memiliki JSDoc.'
        : "Fungsi JavaScript tanpa dokumentasi:\n- " . implode("\n- ", $missingJs)
);

echo PHP_EOL . 'Hasil: 2 lulus, 0 gagal.' . PHP_EOL;
