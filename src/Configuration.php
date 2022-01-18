<?php

namespace Tideways\Shopware6Benchmarking;

class Configuration
{
    private string $name = '';

    public function __construct(
        public ScenarioConfiguration $scenario,
        public ShopwareConfiguration $shopware,
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

        return new self(
            scenario: new ScenarioConfiguration(...$payload['scenario']),
            shopware: new ShopwareConfiguration(...$payload['shopware']),
            filename: $filename,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }
}