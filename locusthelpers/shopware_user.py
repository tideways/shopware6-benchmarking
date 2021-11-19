from locust_plugins.users.resource import HttpUserWithResources
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


class ShopwareUser(HttpUserWithResources):
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

    def visitCart(self):
        self.visitPage('/checkout/cart', name='cart-page')

    def visitCheckoutConfirmationPage(self):
        return self.visitPage('/checkout/confirm', name='confirm-page')

    def checkoutOrder(self):
        logging.info("Going into checkout…")
        self.visitCart()

        confirmationPageResponse = self.visitCheckoutConfirmationPage()

        orderResponse = self.client.post('/checkout/order', name='order', data={
            'tos': 'on',
            '_csrf_token': csrf.getCsrfTokenForForm(confirmationPageResponse, '/checkout/order'),
        })

        logging.info("Checkout finished with status code " +
                     str(orderResponse.status_code))

    def visitProductListingPage(self, productListingUrl: str) -> list:
        logging.info("Visit product listing page " + productListingUrl)
        return self.visitPage(productListingUrl, name='listing-page')

    def visitProductListingPageAndUseThePagination(self, productListingUrl: str, numberOfTimesToPaginate: int) -> list:
        pages = self.visitProductListingPageAndRetrievePageNumbers(
            productListingUrl=productListingUrl)

        # for a random number of times, visit a random page
        for i in range(numberOfTimesToPaginate):
            pages = self.visitProductListingPageAndRetrievePageNumbers(
                productListingUrl=productListingUrl + "?p=" + random.choice(pages))

    def visitProductListingPageAndRetrieveProductUrls(self, productListingUrl: str) -> list:
        response = self.visitProductListingPage(productListingUrl)
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

    def visitProductListingPageAndRetrievePageNumbers(self, productListingUrl: str) -> list:
        response = self.visitProductListingPage(productListingUrl)
        root = etree.fromstring(response.content, etree.HTMLParser())
        pagintationElements = root.xpath(
            './/nav[@aria-label="pagination"]//input[@name="p"]')

        pages = [page.attrib.get(
            'value') for page in pagintationElements]

        # Remove duplicate pages
        pages = list(set(pages))

        return pages
