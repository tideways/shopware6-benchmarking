<?php

namespace Tideways\Shopware6Benchmarking;

class Configuration
{
    private string $name = '';

    public function __construct(
        public ScenarioConfiguration $scenario,
        public ShopwareConfiguration $shopware,
        public TidewaysConfiguration $tideways,
        string $filename
    ) {
        $this->name = pathinfo($filename, PATHINFO_FILENAME);
    }

    public static function fromFile(string $filename): Configuration
    {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException("Given filename $filename does not exist.");
        }

        $data = file_get_contents($filename);

        $payload = json_decode($data, JSON_THROW_ON_ERROR);
        $tideways = $payload['tideways'] ?? ['apiToken' => ''];

        return new self(
            scenario: new ScenarioConfiguration(...$payload['scenario']),
            shopware: new ShopwareConfiguration(...$payload['shopware']),
            tideways: new TidewaysConfiguration(...$tideways),
            filename: $filename,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDataDirectory() : string
    {
        $home = $_SERVER['HOME'] ?? throw new \LogicException("No home directory found in environment");
        $dataDir = $home . "/.swbench/" . $this->getName();

        @mkdir($dataDir, 0777, true);

        return $dataDir;
    }
}