<?php

use Symfony\Component\Process\Process;

require __DIR__ . '/vendor/autoload.php';

$htmlFile = __DIR__ . '/report.html';
$pdfFilename = __DIR__  . '/report.pdf';

$process = Process::fromShellCommandline(
    sprintf(
        'wkhtmltopdf  ' .
        '-T 20mm ' .
        '-B 20mm ' .
        '-L 15mm ' .
        '-R 15mm ' .
        '--encoding "utf8" ' .
        '--minimum-font-size 18 ' .
        '--page-width 23cm ' .
        '--header-spacing 10 ' .
        '--header-center "Shopware Benchmark Scenario - Page [page]" ' .
        '--header-font-size 10 ' .
        '%s %s',
        $htmlFile,
        $pdfFilename
    )
);
$process->run();
$exitCode = $process->getExitCode();
var_dump($exitCode);
