<?php
/**
 * SWBench
 * Copyright (C) 2022 Tideways GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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

    public static function createNew(string $title, string $host): Configuration
    {
        return new self(
            scenario: new ScenarioConfiguration(title: $title, duration: "60m", host: $host),
            shopware: new ShopwareConfiguration(),
            tideways: new TidewaysConfiguration(),
            filename: 'empty.json',
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDataDirectory() : string
    {
        $dataDir = GlobalConfiguration::getGlobalDirectory() . "/" . $this->getName();

        @mkdir($dataDir, 0777, true);

        return $dataDir;
    }

    public function write(string $file) : void
    {
        file_put_contents($file, json_encode($this, JSON_PRETTY_PRINT));
    }
}