import logging
import random
import time

from locust import constant, task, events
from locusthelpers.shopware_user import ShopwareUser

import locust.stats

from locusthelpers.fixtures import getListings, getProductDetails, getRandomWordFromFixture, getRandomWordFromOperatingSystem
from locust_plugins import run_single_user

locust.stats.STATS_AUTORESIZE = False

@events.init_command_line_parser.add_listener
def _(parser):
    parser.add_argument("--tideways-apikey", type=str, env_var="LOCUST_TIDEWAYS_APIKEY", default="", help="The API Key to trigger Tideways callgraph traces with")
    parser.add_argument("--tideways-trace-rate", type=int, env_var="LOCUST_TIDEWAYS_TRACE_RATE", default=1, help="The sample rate for triggering callgraph traces")
    parser.add_argument("--recurring-user-rate", type=int, env_var="LOCUST_RECURRING_USER_RATE", default=50, help="The percentage of users that already have a login and come back")
    parser.add_argument("--filterer-min-filters", type=int, env_var="LOCUST_FILTERER_MIN_FILTERS", default=3, help="Filterer User: Minimum number of filters to apply on a listing page")
    parser.add_argument("--filterer-max-filters", type=int, env_var="LOCUST_FILTERER_MAX_FILTERS", default=5, help="Filterer User: Maximum number of filters to apply on a listing page")
    parser.add_argument("--filterer-visit-product-ratio", type=int, env_var="LOCUST_FILTERER_VISIT_PRODUCT_RATIO", default=10, help="Filterer User: Percentage of times a product is visited after filtering.")
    parser.add_argument("--max-pagination-surfing", type=int, env_var="LOCUST_MAX_PAGINATION_SURFING", default=3, help="Random surfer number of maximum pages they paginate through")

class Purchaser(ShopwareUser):
    weight = 2
    wait_time = constant(10)

    # Visit random product listing page
    # add up to five products to cart
    # do checkout
    @task
    def order(self):
        self.auth.clearCookies()
        # default 50% chance to login or register
        if random.randint(0, 100) <= self.environment.parsed_options.recurring_user_rate:
            self.auth.loginRandomUserFromFixture()
        else:
            self.auth.register()
        url = randomWithNTopPages(listings, 10)
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
            time.sleep(2)
            self.addProductToCart(detailPageUrl)
            time.sleep(2)

        self.checkoutOrder()

class Filterer(ShopwareUser):
    weight = 30
    wait_time = constant(10)

    # Visit random product listing page
    # and apply a filter
    @task
    def filter(self):
        url = random.choice(listings)
        numberOfFiltersToApply = random.randint(self.environment.parsed_options.filterer_min_filters, self.environment.parsed_options.filterer_max_filters)
        response = self.visitProductListingPage(productListingUrl=url)
        time.sleep(1)

        for _ in range(numberOfFiltersToApply):
            ajaxResponse = self.applyRandomFilterOnProductListingPage(response)
            time.sleep(1)
            # In 10 percent of cases, try to visit a few products
            if random.randint(1, 100) <= self.environment.parsed_options.filterer_visit_product_ratio:
                self.visitRandomProductDetailPagesFromListing(ajaxResponse)
                time.sleep(1)

class Searcher(ShopwareUser):
    weight = 20
    wait_time = constant(10)

    # Visit random product listing page
    # and apply a filter
    @task
    def search(self):
        self.visitPage("/")
        response = self.search.search(getRandomWordFromFixture())
        time.sleep(1)
        self.visitRandomProductDetailPagesFromListing(response)

    @task
    def searchAndFilter(self):
        self.visitPage("/")
        response = self.search.search(getRandomWordFromFixture())
        time.sleep(1)
        ajaxResponse = self.applyRandomFilterOnProductListingPage(response)
        time.sleep(1)
        self.visitRandomProductDetailPagesFromListing(ajaxResponse)

    @task
    def searchForWordFromWordlist(self):
        self.visitPage("/")
        response = self.search.search(getRandomWordFromOperatingSystem())
        time.sleep(1)
        self.visitRandomProductDetailPagesFromListing(response)
        time.sleep(1)
        ajaxResponse = self.applyRandomFilterOnProductListingPage(response)
        time.sleep(1)
        self.visitRandomProductDetailPagesFromListing(ajaxResponse)

class PaginationSurfer(ShopwareUser):
    weight = 30
    wait_time = constant(10)

    # Visit a random product listing page and paginate through 1-3 additional pages
    @task()
    def detail_page(self):
        url = randomWithNTopPages(listings, 10)
        self.visitProductListingPageAndUseThePagination(
            url, random.randint(0, self.environment.parsed_options.max_pagination_surfing))

class Registerer(ShopwareUser):
    @task
    def register(self):
        self.auth.clearCookies()
        self.auth.register(writeToFixture=True)

class Surfer(ShopwareUser):
    weight = 30
    wait_time = constant(10)

    def on_start(self):
        self.auth.clearCookies()
        if random.randint(0, 100) <= self.environment.parsed_options.recurring_user_rate:
            self.auth.register()
        else:
            logging.info("Anonymous Surfer starting")

    @task(10)
    def listing_page(self):
        url = randomWithNTopPages(listings, 10)
        self.visitProductListingPageAndRetrieveProductUrls(
            productListingUrl=url)

    @task(4)
    def detail_page(self):
        url = randomWithNTopPages(details, 25)
        self.visitProduct(url)

class FancySurferThatDoesALotOfThings(ShopwareUser):
    weight = 20
    wait_time = constant(10)

    @task
    def browseAroundFromHomepageAndAddToAnonymousCart(self):
        self.auth.clearCookies()
        self.visitHomepage()
        time.sleep(1)

        response = self.visitProductListingPage(randomWithNTopPages(listings, 10))
        time.sleep(1)

        productDetailResponses = self.visitRandomProductDetailPagesFromListing(
            response)
        time.sleep(1)

        if len(productDetailResponses) > 0:
            self.addProductToCart(
                random.choice(productDetailResponses).url
            )

    @task
    def browseAroundFromHomepageAndAddToAnonymousCartAndCheckout(self):
        self.auth.clearCookies()
        self.visitHomepage()
        time.sleep(1)

        response = self.visitProductListingPage(randomWithNTopPages(listings, 10))
        time.sleep(1)

        productDetailResponses = self.visitRandomProductDetailPagesFromListing(
            response)
        time.sleep(1)

        if len(productDetailResponses) > 0:
            self.addProductToCart(
                random.choice(productDetailResponses).url
            )

        time.sleep(1)

        self.auth.registerOrLogin()
        time.sleep(1)

        if len(productDetailResponses) > 0:
            self.checkoutOrder()

    @task
    def authenticatedSearchAfterHomepageAndAddToCartAndCheckout(self):
        self.auth.clearCookies()

        self.auth.registerOrLogin()
        time.sleep(1)

        self.visitHomepage()
        time.sleep(1)

        # in 50% of the cases do a bogus search first
        if random.randint(0, 1) == 0:
            self.search.search(getRandomWordFromOperatingSystem())

        response = self.search.search(getRandomWordFromFixture())
        time.sleep(1)

        ajaxResponse = self.applyRandomFilterOnProductListingPage(response)
        productDetailResponses = self.visitRandomProductDetailPagesFromListing(
            ajaxResponse)

        time.sleep(1)

        if len(productDetailResponses) > 0:
            self.addProductToCart(
                random.choice(productDetailResponses).url
            )
            time.sleep(1)

            self.checkoutOrder()

class DebugUser(ShopwareUser):
    weight = 0

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

def randomWithNTopPages(pages, numTopPages):
    randomPage = random.choice(pages)
    topPage = random.choice(pages[:numTopPages])
    return random.choice([randomPage, topPage])

if __name__ == "__main__":
    DebugUser.host = "https://shopware64.tideways.io"
    run_single_user(DebugUser, include_length=True,
                    include_time=True, include_context=True)
