import logging
import random
import time
import os

from locust import constant, task, events
from locusthelpers.shopware_user import ShopwareUser

import locust.stats

from locusthelpers.fixtures import getListings, getProductDetails, getRandomWordFromFixture, getRandomWordFromOperatingSystem
from locust_plugins import run_single_user
from locust_plugins import jmeter_listener

locust.stats.STATS_AUTORESIZE = False

@events.init_command_line_parser.add_listener
def _(parser):
    parser.add_argument("--tideways-apikey", type=str, env_var="LOCUST_TIDEWAYS_APIKEY", default="", help="The API Key to trigger Tideways callgraph traces with")
    parser.add_argument("--tideways-trace-rate", type=int, env_var="LOCUST_TIDEWAYS_TRACE_RATE", default=1, help="The sample rate for triggering callgraph traces")
    parser.add_argument("--guest-ratio", type=int, env_var="LOCUST_GUEST_RATIO", default=90, help="The percentage of users that browse as guest.")
    parser.add_argument("--accounts-new-ratio", type=int, env_var="LOCUST_ACCOUNTS_NEW_RATIO", default=0, help="The percentage of non-guest users that create a new account instead of logging into an existing.")
    parser.add_argument("--checkout-guest-ratio", type=int, env_var="LOCUST_CHECKOUT_GUEST_RATIO", default=50, help="During checkout, percentage of not logged in users that stay a guest or create a new account")
    parser.add_argument("--checkout-accounts-new-ratio", type=int, env_var="LOCUST_CHECKOUT_ACCOUNTS_NEW_RATIO", default=0, help="The percentage of non-guest checkout users that create a new account instead of logging into an existing.")
    parser.add_argument("--filterer-min-filters", type=int, env_var="LOCUST_FILTERER_MIN_FILTERS", default=3, help="Filterer User: Minimum number of filters to apply on a listing page")
    parser.add_argument("--filterer-max-filters", type=int, env_var="LOCUST_FILTERER_MAX_FILTERS", default=5, help="Filterer User: Maximum number of filters to apply on a listing page")
    parser.add_argument("--filterer-visit-product-ratio", type=int, env_var="LOCUST_FILTERER_VISIT_PRODUCT_RATIO", default=10, help="Filterer User: Percentage of times a product is visited after filtering.")
    parser.add_argument("--max-pagination-surfing", type=int, env_var="LOCUST_MAX_PAGINATION_SURFING", default=3, help="Random surfer number of maximum pages they paginate through")

    parser.add_argument('--purchaser-weight', env_var='SWBENCH_PURCHASER_WEIGHT', type=int, default=5, help='Weight for purchasing users')
    parser.add_argument('--browsing-user-weight', env_var='SWBENCH_BROWSING_USER_WEIGHT', type=int, default=95, help='Weight for browsing users')

@events.test_start.add_listener
def on_test_start(environment, **_kwargs):
    purchaser = Purchaser
    browsing_user = BrowsingUser

    purchaser.weight = environment.parsed_options.purchaser_weight
    browsing_user.weight = environment.parsed_options.browsing_user_weight

    environment.user_classes = [ purchaser, browsing_user ]

class Purchaser(ShopwareUser):
    weight = 5
    wait_time = constant(10)

    # Visit random product listing page
    # add up to five products to cart
    # do checkout
    @task
    def order(self):
        loggedIn = self.auth.guestOrLoggedInUser()

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

        if loggedIn == False:
            self.auth.decideCheckoutGuestRecurringOrNewAccount()

        self.checkoutOrder()

class BrowsingUser(ShopwareUser):
    weight = 90
    wait_time = constant(10)

    # Visit random product listing page
    # and apply a filter
    @task(10)
    def filter(self):
        self.auth.guestOrLoggedInUser()

        url = randomWithNTopPages(listings, 10)
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

    # Visit random product listing page
    # and apply a filter
    @task(10)
    def search(self):
        self.auth.guestOrLoggedInUser()

        self.visitPage("/")
        response = self.search.search(getRandomWordFromFixture())
        time.sleep(1)
        self.visitRandomProductDetailPagesFromListing(response)

    @task(10)
    def searchAndFilter(self):
        self.auth.guestOrLoggedInUser()

        self.visitPage("/")
        response = self.search.search(getRandomWordFromFixture())
        time.sleep(1)
        ajaxResponse = self.applyRandomFilterOnProductListingPage(response)
        time.sleep(1)
        self.visitRandomProductDetailPagesFromListing(ajaxResponse)

    @task(10)
    def searchForWordFromWordlist(self):
        self.auth.guestOrLoggedInUser()

        self.visitPage("/")
        response = self.search.search(getRandomWordFromOperatingSystem())
        time.sleep(1)
        self.visitRandomProductDetailPagesFromListing(response)
        time.sleep(1)
        ajaxResponse = self.applyRandomFilterOnProductListingPage(response)
        time.sleep(1)
        self.visitRandomProductDetailPagesFromListing(ajaxResponse)

    # Visit a random product listing page and paginate through 1-3 additional pages
    @task(10)
    def listing_page_pagination(self):
        self.auth.guestOrLoggedInUser()

        url = randomWithNTopPages(listings, 10)
        self.visitProductListingPageAndUseThePagination(
            url, random.randint(0, self.environment.parsed_options.max_pagination_surfing))

    @task(20)
    def listing_page(self):
        self.auth.guestOrLoggedInUser()

        url = randomWithNTopPages(listings, 10)
        self.visitProductListingPageAndRetrieveProductUrls(
            productListingUrl=url)

    @task(30)
    def detail_page(self):
        self.auth.guestOrLoggedInUser()

        url = randomWithNTopPages(details, 25)
        self.visitProduct(url)

class AbandoningCartUser(ShopwareUser):
    weight = 5
    wait_time = constant(10)

    @task
    def browseAroundFromHomepageAndAddToAnonymousCart(self):
        self.auth.guestOrLoggedInUser()

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
    def authenticatedSearchAfterHomepageAndAddToCart(self):
        if self.environment.parsed_options.guest_ratio == 100:
            return

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

class Registerer(ShopwareUser):
    @task
    def register(self):
        self.auth.clearCookies()
        self.auth.register(writeToFixture=True)

@events.init.add_listener
def on_locust_init(environment, **_kwargs):
    jmeter_listener.JmeterListener(
        env=environment,
        testplan="examplePlan",
        results_filename=os.getenv('SWBENCH_NAME', "results") + "_requests.csv"
    )

listings = getListings()
details = getProductDetails()

def randomWithNTopPages(pages, numTopPages):
    randomPage = random.choice(pages)
    topPage = random.choice(pages[:numTopPages])
    return random.choice([randomPage, topPage])