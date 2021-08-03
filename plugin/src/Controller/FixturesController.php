<?php declare(strict_types=1);

namespace Tideways\LoadTesting\Controller;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 * @Acl({"tideways.loadtesting"})
 */
class FixturesController
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @Acl({"tideways.loadtesting"})
     * @Route("/api/_tideways/loadtesting-fixtures", name="api.tideways.loadtesting", methods={"GET"}, requirements={"version"="\d+"})
     */
    public function load(Context $context)
    {
        $listings = $this->connection->fetchFirstColumn("SELECT CONCAT('/', seo_path_info) FROM seo_url WHERE route_name = 'frontend.navigation.page' AND is_deleted = 0");

        $details = $this->connection->fetchFirstColumn("SELECT CONCAT('/', seo_path_info) FROM seo_url  WHERE route_name = 'frontend.detail.page' AND is_deleted = 0");

        $keywords = $this->connection->fetchFirstColumn("SELECT keyword FROM  product_keyword_dictionary LIMIT 5000");

        $numbers = $this->connection->fetchFirstColumn('SELECT product_number FROM product');

        $salutationId = $this->connection->fetchOne('SELECT LOWER(HEX(id)) FROM salutation LIMIT 1');

        $countryId = $this->connection->fetchOne("SELECT LOWER(HEX(country_id)) FROM `country_translation` WHERE `name` = 'Deutschland' LIMIT 1");

        $productIds = $this->connection->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM product LIMIT 5000');

        $currencyId = Defaults::CURRENCY;

        $categoryIds = $this->connection->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM category LIMIT 2000');

        $salesChannelId = $this->connection->fetchOne("SELECT LOWER(HEX(sales_channel_id)) FROM sales_channel_translation WHERE name = 'Storefront' LIMIT 1");

        $taxId = $this->connection->fetchOne("SELECT LOWER(HEX(id)) FROM tax LIMIT 1");

        if (!$salutationId) {
            throw new RuntimeException('No salutation id found');
        }
        if (!$countryId) {
            throw new RuntimeException('Country "deutschland" not found');
        }
        if (empty($keywords)) {
            throw new RuntimeException('No search keywords found');
        }
        if (empty($details)) {
            throw new RuntimeException('No product urls found');
        }
        if (empty($listings)) {
            throw new RuntimeException('No listing urls found');
        }
        if (empty($numbers)) {
            throw new RuntimeException('No product numbers found');
        }
        if (empty($categoryIds)) {
            throw new RuntimeException('No category ids found');
        }
        if (empty($productIds)) {
            throw new RuntimeException('No product ids found');
        }
        if (empty($salesChannelId)) {
            throw new RuntimeException("Sales channel with name 'Storefront' not found");
        }

        return new JsonResponse([
            'listing_urls.csv' => $listings,
            'product_urls.csv' => $details,
            'keywords.csv' => $keywords,
            'register.json' => ['countryId' => $countryId, 'salutationId' => $salutationId],
            'product_numbers.csv' => $numbers,
            'importer.json' => [
                'currencyId' => $currencyId,
                'taxId' => $taxId,
                'salesChannelId' => $salesChannelId,
                'productIds' => $productIds,
                'categoryIds' => $categoryIds
            ],
        ]);
    }
}
