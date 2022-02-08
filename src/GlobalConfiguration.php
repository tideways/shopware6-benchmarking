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
            $vars = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
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