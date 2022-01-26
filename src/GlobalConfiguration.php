<?php

namespace Tideways\Shopware6Benchmarking;

class GlobalConfiguration
{
    public function __construct(
        public ExecutionMode $executionMode,
    ) {}

    public static function getGlobalDirectory() : string
    {
        $home = $_SERVER['HOME'] ?? throw new \LogicException("No home directory found in environment");

        $dataDir = $home . "/.swbench/";

        @mkdir($dataDir, 0777, true);

        return $dataDir;
    }

    public static function createFromGlobalDirectory() : self
    {
        $file = self::getGlobalDirectory() . '/config.json';

        $defaults = ['executionMode' => 'docker'];
        $vars = [];

        if (file_exists($file)) {
            $vars = json_decode(file_get_contents($file), true, JSON_THROW_ON_ERROR);
        }

        $vars = array_merge($defaults, $vars);

        $vars['executionMode'] = ExecutionMode::from($vars['executionMode']);

        return new self(...$vars);
    }

    public function save() : void
    {
        $file = self::getGlobalDirectory() . '/config.json';

        file_put_contents($file, json_encode($this, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
    }
}