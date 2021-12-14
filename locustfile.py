import logging
import random

from locust import constant, task
from locusthelpers.shopware_user import ShopwareUser

from locusthelpers.fixtures import getListings, getProductDetails, getProductNumbers, getRandomWordFromFixture, getRandomWordFromOperatingSystem
from locust_plugins import run_single_user


class Purchaser(ShopwareUser):
    weight = 10
    wait_time = constant(15)

    # Visit random product listing page
    # add up to five products to cart
    # do checkout
    @task
    def order(self):
        self.auth.clearCookies()
        # 50% chance to login or register
        if random.randint(0, 1) == 0:
            self.auth.loginRandomUserFromFixture()
        else:
            self.auth.register()
        url = random.choice(listings)
        productUrls = self.visitProductListingPageAndRetrieveProductUrls(
            productListingUrl=url
        )

        if len(productUrls) == 0:
            logging.error("No products found on this page")
            return

        # get the maximum number of products to order, either 5 or the number of productUrls
        maxProducts = min(5, len(productUrls))

        for detailPageUrl in random.sample(productUrls, random.randint(1, maxProducts)):
            self.visitProduct(detailPageUrl)
            self.addProductToCart(detailPageUrl)

        self.checkoutOrder()


class Filterer(ShopwareUser):
    # Visit random product listing page
    # and apply a filter
    @task
    def filter(self):
        url = random.choice(listings)
        numberOfFiltersToApply = random.randint(3, 5)
        response = self.visitProductListingPage(productListingUrl=url)

        for _ in range(numberOfFiltersToApply):
            ajaxResponse = self.applyRandomFilterOnProductListingPage(response)
            # In 10 percent of cases, try to visit a few products
            if random.randint(1, 10) == 1:
                self.visitRandomProductDetailPagesFromListing(ajaxResponse)


class Searcher(ShopwareUser):
    # Visit random product listing page
    # and apply a filter
    @task
    def search(self):
        self.visitPage("/")
        response = self.search.search(getRandomWordFromFixture())
        self.visitRandomProductDetailPagesFromListing(response)

    @task
    def searchAndFilter(self):
        self.visitPage("/")
        response = self.search.search(getRandomWordFromFixture())
        ajaxResponse = self.applyRandomFilterOnProductListingPage(response)
        self.visitRandomProductDetailPagesFromListing(ajaxResponse)

    @task
    def searchForWordFromWordlist(self):
        self.visitPage("/")
        response = self.search.search(getRandomWordFromOperatingSystem())
        self.visitRandomProductDetailPagesFromListing(response)
        ajaxResponse = self.applyRandomFilterOnProductListingPage(response)
        self.visitRandomProductDetailPagesFromListing(ajaxResponse)


class PaginationSurfer(ShopwareUser):
    weight = 30
    wait_time = constant(2)

    # Visit a random product listing page and paginate through 1-3 additional pages
    @task()
    def detail_page(self):
        url = random.choice(listings)
        self.visitProductListingPageAndUseThePagination(
            url, random.randint(0, 3))


class Registerer(ShopwareUser):
    @task
    def register(self):
        self.auth.clearCookies()
        self.auth.register(writeToFixture=True)


class Surfer(ShopwareUser):
    weight = 30
    wait_time = constant(2)

    def on_start(self):
        self.auth.clearCookies()
        # Percentage of users that are authenticated
        probability = 0.5
        if bool(random.random() < probability) is True:
            self.auth.register()
        else:
            logging.info("Anonymous Surfer starting")

    @task(10)
    def listing_page(self):
        url = random.choice(listings)
        self.visitProductListingPageAndRetrieveProductUrls(
            productListingUrl=url)

    @task(4)
    def detail_page(self):
        url = random.choice(details)
        self.visitProduct(url)


class FancySurferThatDoesALotOfThings(ShopwareUser):
    @task
    def browseAroundFromHomepageAndAddToAnonymousCart(self):
        self.auth.clearCookies()
        self.visitHomepage()
        response = self.visitProductListingPage(random.choice(listings))
        productDetailResponses = self.visitRandomProductDetailPagesFromListing(
            response)
        if len(productDetailResponses) > 0:
            self.addProductToCart(
                random.choice(productDetailResponses).url
            )

    @task
    def browseAroundFromHomepageAndAddToAnonymousCartAndCheckout(self):
        self.auth.clearCookies()
        self.visitHomepage()
        response = self.visitProductListingPage(random.choice(listings))
        productDetailResponses = self.visitRandomProductDetailPagesFromListing(
            response)
        if len(productDetailResponses) > 0:
            self.addProductToCart(
                random.choice(productDetailResponses).url
            )

        self.auth.registerOrLogin()
        if len(productDetailResponses) > 0:
            self.checkoutOrder()

    @task
    def authenticatedSearchAfterHomepageAndAddToCartAndCheckout(self):
        self.auth.clearCookies()
        self.auth.registerOrLogin()
        self.visitHomepage()

        response = self.search.search(getRandomWordFromFixture())
        ajaxResponse = self.applyRandomFilterOnProductListingPage(response)
        productDetailResponses = self.visitRandomProductDetailPagesFromListing(
            ajaxResponse)

        if len(productDetailResponses) > 0:
            self.addProductToCart(
                random.choice(productDetailResponses).url
            )
            self.checkoutOrder()


class DebugUser(ShopwareUser):
    @task
    def browseAroundFromHomepageAndAddToAnonymousCartAndCheckout(self):
        self.auth.clearCookies()
        self.visitHomepage()

        self.addProductToCart(
            "https://shopware64.tideways.io/Sleek-Iron-Federal-Preserve/3073f9e5e8744ba28b7cb649d3e598aa"
        )
        self.auth.register()

        self.checkoutOrder()


listings = getListings()
details = getProductDetails()
numbers = getProductNumbers()


if __name__ == "__main__":
    DebugUser.host = "https://shopware64.tideways.io"
    run_single_user(DebugUser, include_length=True,
                    include_time=True, include_context=True)
