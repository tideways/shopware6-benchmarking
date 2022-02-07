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

class SitemapFixturesDownloader
{
    public function download(Configuration $configuration, bool $refreshIfExists = false)
    {
        if (file_exists($configuration->getDataDirectory() . '/listing_urls.csv') &&
            file_exists($configuration->getDataDirectory() . '/product_urls.csv') &&
            $refreshIfExists === false) {
            return;
        }

        $content = file_get_contents($configuration->scenario->host . '/sitemap.xml');

        $sitemapDom = new DOMDocument();
        $sitemapDom->loadXml($content);

        $listingHandle = fopen($configuration->getDataDirectory() . '/listing_urls.csv', 'w');
        $productHandle = fopen($configuration->getDataDirectory() . '/product_urls.csv', 'w');

        $counts = [
            'listings' => 0,
            'products' => 0,
        ];

        foreach ($sitemapDom->getElementsByTagName('loc') as $locationNode) {
            $url = $locationNode->nodeValue;

            $locXml = gzdecode(file_get_contents($url));

            $locDom = new DOMDocument();
            $locDom->loadXml($locXml);

            foreach ($locDom->getElementsByTagName('url') as $urlNode) {
                $location = $urlNode->getElementsByTagName('loc')[0]->nodeValue;
                $changeFrequency = $urlNode->getElementsByTagName('changefreq')[0]->nodeValue;
                $url = substr($location, strlen($configuration->scenario->host));

                if (strlen($url) === 0) {
                    $url = '/';
                }

                if ($changeFrequency == 'hourly') {
                    fwrite($productHandle, $url . "\n");
                    $counts['products']++;
                } else if ($changeFrequency == 'daily') {
                    fwrite($listingHandle, $url . "\n");
                    $counts['listings']++;
                }
            }
        }

        file_put_contents($configuration->getDataDirectory() . '/counts.json', json_encode($counts));

        fclose($productHandle);
        fclose($listingHandle);
    }

    public function isCachedSitemapEmpty(Configuration $configuration) : bool
    {
        if (file_exists($configuration->getDataDirectory() . '/listing_urls.csv') &&
            count(file($configuration->getDataDirectory() . '/listing_urls.csv')) < 2) {
            return true;
        }

        if (file_exists($configuration->getDataDirectory() . '/product_urls.csv') &&
            count(file($configuration->getDataDirectory() . '/product_urls.csv')) < 2) {
            return true;
        }

        return false;
    }
}