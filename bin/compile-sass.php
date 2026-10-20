<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;

$rootDir = dirname(__DIR__);
$scssFile = $rootDir . '/public/assets/scss/style.scss';
$cssOutputDir = $rootDir . '/public/assets/css';
$cssOutputFile = $cssOutputDir . '/style.css';

if (!is_dir($cssOutputDir)) {
    mkdir($cssOutputDir, 0755, true);
}

try {
    echo "Compilando SASS desde: {$scssFile}...\n";

    $compiler = new Compiler();
    $compiler->setOutputStyle(OutputStyle::EXPANDED);
    $compiler->setImportPaths([dirname($scssFile)]);

    $scssSource = file_get_contents($scssFile);
    $result = $compiler->compileString($scssSource);

    file_put_contents($cssOutputFile, $result->getCss());

    echo "¡Compilación exitosa! Archivo generado en: {$cssOutputFile}\n";
} catch (\Throwable $e) {
    echo "Error al compilar SCSS: " . $e->getMessage() . "\n";
    exit(1);
}
