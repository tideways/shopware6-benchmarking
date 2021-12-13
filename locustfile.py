import logging
import random

from locust import constant, task
from locust_plugins.users import HttpUserWithResources
from lxml import etree
from requests.models import Response

from locusthelpers import csrf
from locusthelpers.authentication import Authentication
from locusthelpers.form import submitForm
from locusthelpers.search import Search
from locusthelpers.shopware_user import ShopwareUser

from locusthelpers.fixtures import getListings, getProductDetails, getProductNumbers, getRandomWordFromFixture, getRandomWordFromOperatingSystem
from locust import task, HttpUser
from locust.exception import StopUser
from locust_plugins import run_single_user


class Purchaser(ShopwareUser):
    weight = 10
    wait_time = constant(15)

    # Visit random product listing page
    # add up to five products to cart
    # do checkout
    @task
    def order(self):
        auth = Authentication(self.client)
        # 50% chance to login or register
        if random.randint(0, 1) == 0:
            auth.loginRandomUserFromFixture()
        else:
            auth.register()
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
        search = Search(self)
        response = search.search(getRandomWordFromFixture())
        self.visitRandomProductDetailPagesFromListing(response)

    @task
    def searchAndFilter(self):
        self.visitPage("/")
        search = Search(self)
        response = search.search(getRandomWordFromFixture())
        ajaxResponse = self.applyRandomFilterOnProductListingPage(response)
        self.visitRandomProductDetailPagesFromListing(ajaxResponse)

    @task
    def searchForWordFromWordlist(self):
        self.visitPage("/")
        search = Search(self)
        response = search.search(getRandomWordFromOperatingSystem())
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
        auth = Authentication(self.client)
        auth.register(writeToFixture=True)


class Surfer(ShopwareUser):
    weight = 30
    wait_time = constant(2)

    def on_start(self):
        # Percentage of users that are authenticated
        probability = 0.5
        auth = Authentication(self.client)
        if bool(random.random() < probability) is True:
            auth.register()
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


listings = getListings()
details = getProductDetails()
numbers = getProductNumbers()


if __name__ == "__main__":
    Filterer.host = "https://shopware64.tideways.io"
    run_single_user(Filterer, include_length=True,
                    include_time=True, include_context=True)
