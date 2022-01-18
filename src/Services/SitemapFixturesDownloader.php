<?php

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
}