from locusthelpers.listingFilters.listingFilterParser import ListingFilterParser
from locusthelpers.form import submitForm
from locusthelpers.authentication import Authentication
from locusthelpers import csrf
from requests.models import Response
from lxml import etree
from locust_plugins.users import HttpUserWithResources
from locust import constant, task
from locust_plugins.users.resource import HttpUserWithResources
import csv
import logging
import os
import random
from urllib.parse import urlencode, urlparse, parse_qs


class ShopwareUser(HttpUserWithResources):
    def visitPage(self, url: str, name=None) -> Response:
        if not name:
            name = url
        response = self.client.get(url, name=name)

        self.client.get('/widgets/checkout/info', name='cart-widget')

        return response

    def getAjaxResource(self, url: str, name: str = None):
        if not name:
            name = url
        logging.info("Fetching ajax resource " + url)
        return self.client.get(url, name=name)

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

    def applyRandomFilterOnProductListingPage(self, response: Response):
        listingFilterParser = ListingFilterParser(response.content)
        filters = listingFilterParser.findFilters()
        filter = random.choice(filters)
        filterValue = random.choice(filter.possibleValues)

        url = urlparse(response.url)
        queryParams = parse_qs(url.query)

        if filter.name in queryParams:
            queryParams[filter.name] = queryParams[filter.name][0] + \
                "|" + filterValue
        else:
            queryParams[filter.name] = filterValue

        # if page is missing in the queryParams, add it
        if 'p' not in queryParams:
            queryParams['p'] = 1

        # order by name ascending if no order was configured yet
        if 'order' not in queryParams:
            queryParams['order'] = 'name-asc'

        listingWidgetUrl, listingWidgetParams = listingFilterParser.findListingWidgetUrlAndParams()
        queryParams = queryParams | listingWidgetParams

        queryString = "?" + urlencode(queryParams, doseq=True)

        # Adjust the original
        response.url = url.path + queryString

        return self.getAjaxResource(listingWidgetUrl + queryString)

    def visitProductListingPageAndRetrieveProductUrls(self, productListingUrl: str) -> list:
        response = self.visitProductListingPage(productListingUrl)

        return self.findProductUrlsFromProductListing(response)

    def findProductUrlsFromProductListing(self, productListingResponse: Response) -> list:
        root = etree.fromstring(
            productListingResponse.content, etree.HTMLParser())
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
