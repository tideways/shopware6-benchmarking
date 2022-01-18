<?php

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