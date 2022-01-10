<?php

use Symfony\Component\Process\Process;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require __DIR__ . '/vendor/autoload.php';

$htmlFilePath = __DIR__ . '/report.html';
$pdfFilePath = __DIR__  . '/report.pdf';
$configFilePath = __DIR__ . '/config.json';

$config = json_decode(file_get_contents($configFilePath), true);

$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/cache/twig',
]);

$reportHtml = $twig->render('report.html.twig', $config);

file_put_contents($htmlFilePath, $reportHtml);

$process = Process::fromShellCommandline(
    sprintf(
        'wkhtmltopdf  ' .
        '-T 20mm ' .
        '-B 20mm ' .
        '-L 15mm ' .
        '-R 15mm ' .
        '--disable-local-file-access --allow ./report/ ' .
        '--encoding "utf8" ' .
        '--minimum-font-size 18 ' .
        '--page-width 23cm ' .
        '--header-spacing 10 ' .
        '--header-center "Shopware Benchmark Scenario - Page [page]" ' .
        '--header-font-size 10 ' .
        '%s %s',
        $htmlFilePath,
        $pdfFilePath
    )
);
$process->run();
$exitCode = $process->getExitCode();
var_dump($process->getErrorOutput());
