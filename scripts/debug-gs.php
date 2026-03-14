<?php

/**
 * GS / Binary Debug Script
 * Access: https://pdf.hetdubai.com/scripts/debug-gs.php?secret=het2026debug
 * DELETE or restrict this file after diagnosis.
 */

declare(strict_types=1);

define('DEBUG_SECRET', 'het2026debug');

if (($_GET['secret'] ?? '') !== DEBUG_SECRET) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

use App\Core\Bootstrap;

$config = Bootstrap::init();

echo "=== het PDF Tools - GS Diagnostic ===\n\n";

// PHP info
echo "PHP Version   : " . PHP_VERSION . "\n";
echo "OS            : " . PHP_OS . "\n";
echo "SAPI          : " . PHP_SAPI . "\n";
echo "disable_functions: " . (ini_get('disable_functions') ?: '(none)') . "\n";
echo "proc_open ok  : " . (function_exists('proc_open') ? 'YES' : 'NO') . "\n";
echo "exec ok       : " . (function_exists('exec') ? 'YES' : 'NO') . "\n";
echo "shell_exec ok : " . (function_exists('shell_exec') ? 'YES' : 'NO') . "\n";
echo "\n";

// Paths
$gsPath = (string) $config['binaries']['ghostscript'];
echo "GS bin config : " . $gsPath . "\n";
$gsResolved = trim((string) @shell_exec('command -v ' . escapeshellarg($gsPath) . ' 2>/dev/null'));
echo "GS resolved   : " . ($gsResolved ?: '(not found via command -v)') . "\n";
$gsVersion = trim((string) @shell_exec(escapeshellarg($gsResolved ?: $gsPath) . ' --version 2>&1'));
echo "GS version    : " . ($gsVersion ?: '(empty)') . "\n\n";

// Storage paths
$tempPath = $config['storage']['temp_path'];
echo "Temp path     : " . $tempPath . "\n";
echo "Temp writable : " . (is_writable($tempPath) ? 'YES' : 'NO') . "\n\n";

// Create a minimal test PDF using GS itself (PS -> PDF)
$testDir = $tempPath . DIRECTORY_SEPARATOR . 'debug_' . uniqid('', true);
if (!is_dir($testDir)) {
    mkdir($testDir, 0755, true);
}

$testPsPath  = $testDir . DIRECTORY_SEPARATOR . 'test.ps';
$testPdfPath = $testDir . DIRECTORY_SEPARATOR . 'test.pdf';
$compressOut = $testDir . DIRECTORY_SEPARATOR . 'compressed.pdf';

// Write a tiny PostScript document
file_put_contents($testPsPath, "%!PS\n/Courier findfont 12 scalefont setfont\n72 720 moveto\n(het PDF debug test) show\nshowpage\n");

echo "=== Step 1: Generate test PDF via GS (PS->PDF) ===\n";
$cmd = implode(' ', [
    escapeshellarg($gsResolved ?: $gsPath),
    '-dBATCH -dNOPAUSE -dQUIET',
    '-sDEVICE=pdfwrite',
    '-sOutputFile=' . escapeshellarg($testPdfPath),
    escapeshellarg($testPsPath),
    '2>&1',
]);
echo "CMD: $cmd\n";
$output = [];
$exitCode = -1;
exec($cmd, $output, $exitCode);
echo "Exit : $exitCode\n";
echo "Out  : " . implode("\n       ", $output) . "\n";
echo "PDF created: " . (is_file($testPdfPath) ? 'YES (' . filesize($testPdfPath) . ' bytes)' : 'NO') . "\n\n";

if (!is_file($testPdfPath)) {
    echo "FATAL: Could not generate test PDF. GS execution is broken.\n";
    cleanup($testDir);
    exit;
}

echo "=== Step 2: Compress test PDF via GS ===\n";
$cmd2 = implode(' ', [
    escapeshellarg($gsResolved ?: $gsPath),
    '-sDEVICE=pdfwrite',
    '-dCompatibilityLevel=1.5',
    '-dNOPAUSE', '-dQUIET', '-dBATCH',
    '-dPDFSETTINGS=/screen',
    '-dDownsampleColorImages=true',
    '-dColorImageResolution=96',
    '-sOutputFile=' . escapeshellarg($compressOut),
    escapeshellarg($testPdfPath),
    '2>&1',
]);
echo "CMD: $cmd2\n";
$output2 = [];
$exitCode2 = -1;
exec($cmd2, $output2, $exitCode2);
echo "Exit  : $exitCode2\n";
echo "Out   : " . (implode("\n        ", $output2) ?: '(empty)') . "\n";
echo "Result: " . (is_file($compressOut) ? 'YES (' . filesize($compressOut) . ' bytes)' : 'NO') . "\n\n";

echo "=== Step 3: proc_open test ===\n";
$testCmd = escapeshellarg($gsResolved ?: $gsPath) . ' --version';
$descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = @proc_open($testCmd, $descriptors, $pipes);
if (!is_resource($proc)) {
    echo "proc_open: FAILED (not a resource)\n";
} else {
    fclose($pipes[0]);
    usleep(300000);
    stream_set_blocking($pipes[1], true);
    stream_set_blocking($pipes[2], true);
    $pOut = stream_get_contents($pipes[1]);
    $pErr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $pExit = proc_close($proc);
    echo "proc_open: OK\n";
    echo "Exit  : $pExit\n";
    echo "Stdout: " . (trim((string)$pOut) ?: '(empty)') . "\n";
    echo "Stderr: " . (trim((string)$pErr) ?: '(empty)') . "\n";
}

echo "\n=== Done ===\n";
echo "Test dir: $testDir (will be left for manual inspection)\n";

function cleanup(string $dir): void
{
    // intentionally not deleting so you can inspect
}
