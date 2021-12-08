import csv
import logging
import os
import random

from locust import constant, task
from locust_plugins.users import HttpUserWithResources
from lxml import etree
from requests.models import Response

from locusthelpers import csrf
from locusthelpers.authentication import Authentication
from locusthelpers.form import submitForm
from locusthelpers.shopware_user import ShopwareUser


class Purchaser(ShopwareUser):
    weight = 10
    wait_time = constant(15)

    def on_start(self):
        auth = Authentication(self.client)
        auth.register()

    @task
    def order(self):
        url = random.choice(listings)
        productUrls = self.visitProductListingPageAndRetrieveProductUrls(
            productListingUrl=url)

        if len(productUrls) == 0:
            logging.error("No products found on this page")
            return

        # get the maximum number of products to order, either 5 or the number of productUrls
        maxProducts = min(5, len(productUrls))

        for detailPageUrl in random.sample(productUrls, random.randint(1, maxProducts)):
            self.visitProduct(detailPageUrl)
            self.addProductToCart(detailPageUrl)

        self.checkoutOrder()


class PaginationSurfer(ShopwareUser):
    weight = 30
    wait_time = constant(2)

    @task()
    def detail_page(self):
        url = random.choice(listings)
        self.visitProductListingPageAndUseThePagination(
            url, random.randint(0, 3))


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


listings = []
details = []
numbers = []


def initListings():
    path = os.path.dirname(os.path.realpath(__file__)) + \
        '/fixtures/listing_urls.csv'
    with open(path) as file:
        reader = csv.reader(file, delimiter=',')
        for row in reader:
            listings.append(row[0])


def initProducts():
    path = os.path.dirname(os.path.realpath(__file__)) + \
        '/fixtures/product_urls.csv'
    with open(path) as file:
        reader = csv.reader(file, delimiter=',')
        for row in reader:
            details.append(row[0])


def initNumbers():
    path = os.path.dirname(os.path.realpath(__file__)) + \
        '/fixtures/product_numbers.csv'
    with open(path) as file:
        reader = csv.reader(file, delimiter=',')
        for row in reader:
            numbers.append(row[0])


initListings()
initProducts()
initNumbers()
