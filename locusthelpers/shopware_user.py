import logging
import random
from urllib.parse import parse_qs, urlencode, urlparse

from locust_plugins.users import HttpUserWithResources
from locust_plugins.users.resource import HttpUserWithResources
from lxml import etree
from requests.models import Response

from locusthelpers.tideways import HttpTidewaysSession
from locusthelpers import csrf
from locusthelpers.authentication import Authentication
from locusthelpers.form import submitForm
from locusthelpers.listingFilters.listingFilterParser import \
    ListingFilterParser
from locusthelpers.search import Search

class ShopwareUser(HttpUserWithResources):
    abstract = True

    # constructor, initialize authentication
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

        session = HttpTidewaysSession(
            base_url=self.host,
            request_event=self.environment.events.request,
            user=self,
        )
        session.tideways_apikey = self.environment.parsed_options.tideways_apikey
        session.tideways_trace_rate = self.environment.parsed_options.tideways_trace_rate
        session.trust_env = False
        self.client = session

        self.auth = Authentication(self.client)
        self.search = Search(self)

    def visitPage(self, url: str, name=None, catch_response=False) -> Response:
        if not name:
            name = url
        response = self.client.get(url, name=name)

        self.client.get('/widgets/checkout/info',
                        name='cart-widget',
                        catch_response=catch_response)

        return response

    def visitHomepage(self) -> Response:
        logging.info("Visit homepage")
        return self.visitPage('/', name='homepage')

    def getAjaxResource(self, url: str, name: str = None):
        if not name:
            name = url
        logging.info("Fetching ajax resource " + url)
        return self.client.get(url, name=name)

    def visitProduct(self, productDetailPageUrl: str):
        logging.info("Visit product detail page " + productDetailPageUrl)
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
        response = self.visitPage(
            '/checkout/confirm', name='confirm-page', catch_response=True)
        if response.url != self.host + '/checkout/confirm':
            logging.error(
                "Did not end up on checkout confirmation page, is the cart empty?")
            raise Exception(
                "Did not end up on checkout confirmation page, is the cart empty?")

        if response.status_code != 200:
            response.failure(
                "Checkout confirmation page returned status code " + str(response.status_code))
            raise Exception(
                "Checkout confirmation page returned status code " + str(response.status_code))

        return response

    def checkoutOrder(self):
        logging.info("Going into checkout…")
        self.visitCart()
        # time.sleep(1)

        confirmationPageResponse = self.visitCheckoutConfirmationPage()

        orderResponse = self.client.post('/checkout/order', name='order', data={
            'tos': 'on',
            '_csrf_token': csrf.getCsrfTokenForForm(confirmationPageResponse, '/checkout/order'),
        })

        logging.info("Checkout finished with status code " +
                     str(orderResponse.status_code))

    def visitProductListingPage(self, productListingUrl: str) -> Response:
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

        ajaxResponse = self.getAjaxResource(
            listingWidgetUrl + queryString, name='listing-widget-filtered')

        productUrls = self.findProductUrlsFromProductListing(ajaxResponse)
        if len(productUrls) == 0:
            logging.info("No products found, not applying filter permanently")
        else:
            # Adjust the original response to include the new filter
            # This way, subsequent requests know about it
            response.url = url.path + queryString
            logging.info("Found " + str(len(productUrls)) +
                         " products, applying filter permanently")

        return ajaxResponse

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

    def visitRandomProductDetailPagesFromListing(self, response: Response, maxNumberOfProducts: int = 5, minNumberOfProducts: int = 0) -> list[Response]:
        productUrls = self.findProductUrlsFromProductListing(response)

        if len(productUrls) == 0:
            logging.info(
                "No products found, not visiting product detail pages")
            return []

        maxProducts = min(maxNumberOfProducts, len(productUrls))
        minProducts = min(minNumberOfProducts, len(productUrls))

        productsToVisit = random.sample(
            productUrls, random.randint(minProducts, maxProducts))

        responses = []
        for productUrl in productsToVisit:
            responses.append(self.visitProduct(productUrl))

        return responses