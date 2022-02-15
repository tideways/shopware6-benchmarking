<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

class BenchmarkReport
{
    /**
     * @var array<PageReport>
     */
    public array $pages = [];

    public static function createShopware6BenchmarkReport(): self
    {
        $report = new self();
        $report->pages['overall'] = new PageReport(slug: 'overall', label: 'Overall');
        $report->pages['product-detail-page'] = new PageReport('product-detail-page', 'Product Details Page', 'Shopware\Storefront\Controller\ProductController::index');
        $report->pages['listing-page'] = new PageReport('listing-page', 'Category Page', 'Shopware\Storefront\Controller\NavigationController::index');
        $report->pages['listing-widget-filtered'] = new PageReport('listing-widget-filtered', 'Category Filter (Ajax)', 'Shopware\Storefront\Controller\CmsController::category');
        $report->pages['add-to-cart'] = new PageReport('add-to-cart', 'Add Product to Cart', 'Shopware\Storefront\Controller\CartLineItemController::addLineItems');
        $report->pages['cart-page'] = new PageReport('cart-page', 'Cart Page', 'Shopware\Storefront\Controller\CheckoutController::cartPage');
        $report->pages['cart-widget'] = new PageReport('cart-widget', 'Cart Info Widget (Ajax)', 'Shopware\Storefront\Controller\CheckoutController::info');
        $report->pages['homepage'] = new PageReport('homepage', 'Homepage', 'Shopware\Storefront\Controller\NavigationController::home');
        $report->pages['search'] = new PageReport('search', 'Search Page', 'Shopware\Storefront\Controller\SearchController::search');
        $report->pages['search-suggest'] = new PageReport('search-suggest', 'Search Suggest (Ajax)', 'Shopware\Storefront\Controller\SearchController::ajax');
        $report->pages['register'] = new PageReport('register', 'Register Account', 'Shopware\Storefront\Controller\RegisterController::register');
        $report->pages['register-page'] = new PageReport('register-page', 'Register Page', 'Shopware\Storefront\Controller\RegisterController::accountRegisterPage');
        $report->pages['checkout-register-page'] = new PageReport('checkout-register-page', 'Checkout Register Page', 'Shopware\Storefront\Controller\RegisterController::checkoutRegisterPage');
        $report->pages['login'] = new PageReport('login', 'Login', 'Shopware\Storefront\Controller\AuthController::login');
        $report->pages['order'] = new PageReport('order', 'Order', 'Shopware\Storefront\Controller\CheckoutController::order');
        $report->pages['confirm-page'] = new PageReport('confirm-page', 'Checkout Confirm Page', 'Shopware\Storefront\Controller\CheckoutController::confirmPage');
        $report->pages['account-profile-page'] = new PageReport('account-profile-page', 'Account Profile Page', 'Shopware\Storefront\Controller\AccountProfileController::index');
        $report->pages['checkout-finish-page'] = new PageReport('checkout-finish-page', 'Checkout Finish Page', 'Shopware\Storefront\Controller\CheckoutController::finishPage');

        return $report;
    }
}
