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


class Purchaser(HttpUserWithResources):
    weight = 10
    wait_time = constant(15)

    def on_start(self):
        auth = Authentication(self.client)
        auth.register()

    def visitPage(self, url: str, name=None) -> Response:
        if not name:
            name = url
        response = self.client.get(url, name=name)

        self.client.get('/widgets/checkout/info', name='cart-widget')

        return response

    def visitProduct(self, productDetailPageUrl: str):
        logging.info("Visit product detail page")
        return self.visitPage(
            productDetailPageUrl, name='product-detail-page')

    def addProductToCart(self, productDetailPageUrl: str):
        logging.info("Adding product to cart " + productDetailPageUrl)
        productDetailPageResponse = self.visitProduct(productDetailPageUrl)

        submitForm(productDetailPageResponse,
                   self.client, "/checkout/line-item/add", name='add-to-cart')

    def checkoutOrder(self):
        logging.info("Going into checkout…")
        self.visitPage('/checkout/cart', name='cart-page')

        confirmationPageResponse = self.visitPage(
            '/checkout/confirm', name='confirm-page'
        )

        orderResponse = self.client.post('/checkout/order', name='order', data={
            'tos': 'on',
            '_csrf_token': csrf.getCsrfTokenForForm(confirmationPageResponse, '/checkout/order'),
        })

        logging.info("Checkout finished with status code " +
                     str(orderResponse.status_code))

    def visitProductListingPageAndRetrieveProductUrls(self, productListingUrl: str) -> list:
        logging.info("Visit product listing page " + productListingUrl)
        response = self.visitPage(productListingUrl, name='listing-page')
        root = etree.fromstring(response.content, etree.HTMLParser())
        productUrlElements = root.xpath(
            './/div[contains(@class, "product-box")]//a')

        productUrls = [productUrl.attrib.get(
            'href') for productUrl in productUrlElements]

        # Remove duplicate product urls
        productUrls = list(set(productUrls))

        # Remove host prefix from all product urls
        productUrls = [productUrl.replace(self.host, '')
                       for productUrl in productUrls]

        return productUrls

    @task
    def order(self):
        url = random.choice(listings)
        productUrls = self.visitProductListingPageAndRetrieveProductUrls(
            productListingUrl=url)

        for detailPageUrl in random.sample(productUrls, random.randint(1, 5)):
            self.visitProduct(detailPageUrl)
            self.addProductToCart(detailPageUrl)

        self.checkoutOrder()


class Surfer(HttpUserWithResources):
    weight = 30
    wait_time = constant(2)

    @task(10)
    def listing_page(self):
        url = random.choice(listings)
        self.client.get(url, name='listing-page')
        self.client.get('/widgets/checkout/info', name='cart-widget')

    @task(4)
    def detail_page(self):
        url = random.choice(details)
        self.client.get(url, name='detail-page')
        self.client.get('/widgets/checkout/info', name='cart-widget')


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
