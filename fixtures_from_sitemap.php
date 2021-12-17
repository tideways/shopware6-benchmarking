<?php

$config = parse_ini_file(__DIR__ . '/.env');

$content = file_get_contents($config['HOST'] . '/sitemap.xml');

$sitemapDom = new DOMDocument();
$sitemapDom->loadXml($content);

$products = [];
$categories = [];
$listingHandle = fopen(__DIR__ . '/fixtures/listing_urls.csv', 'w');
$productHandle = fopen(__DIR__ . '/fixtures/product_urls.csv', 'w');
foreach ($sitemapDom->getElementsByTagName('loc') as $locationNode) {
    $url = $locationNode->nodeValue;

    $locXml = gzdecode(file_get_contents($url));

    $locDom = new DOMDocument();
    $locDom->loadXml($locXml);

    $xpath = new DOMXPath($locDom);

    foreach ($locDom->getElementsByTagName('url') as $urlNode) {
        $location = $urlNode->getElementsByTagName('loc')[0]->nodeValue;
        $changeFrequency = $urlNode->getElementsByTagName('changefreq')[0]->nodeValue;
        $url = substr($location, strlen($config['HOST']));

        if (strlen($url) === 0) {
            $url = '/';
        }

        if ($changeFrequency == 'hourly') {
            fwrite($productHandle, $url . "\n");
        } else if ($changeFrequency == 'daily') {
            fwrite($listingHandle, $url . "\n");
        }
    }
}

fclose($productHandle);
fclose($listingHandle);
