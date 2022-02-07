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

namespace Tideways\Shopware6Benchmarking\Services;

use DOMDocument;
use Tideways\Shopware6Benchmarking\Configuration;

class RegisterJsonUpdater
{
    public function update(Configuration $configuration, bool $refreshIfExists = false): void
    {
        if (file_exists($configuration->getDataDirectory() . '/register.json') && $refreshIfExists === false) {
            return;
        }

        $content = file_get_contents($configuration->scenario->host . '/account/register');

        $registerDom = new DOMDocument();
        @$registerDom->loadHTML($content);

        $select = $registerDom->getElementById('personalSalutation');
        $firstOption = $select->firstElementChild->nextElementSibling;

        $salutationId = $firstOption->getAttribute('value');

        $select = $registerDom->getElementById('billingAddressAddressCountry');
        $firstOption = $select->firstElementChild->nextElementSibling;

        $countryId = $firstOption->getAttribute('value');

        file_put_contents($configuration->getDataDirectory() . '/register.json', json_encode([
            'countryId' => $countryId,
            'salutationId' => $salutationId,
        ], JSON_PRETTY_PRINT));
    }
}