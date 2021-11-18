import requests
import time
import csv
import os
import random
import uuid
import json
from locust import task, constant
from locust_plugins.users import HttpUserWithResources
from locusthelpers import csrf

from lxml import etree
import logging

from locusthelpers.form import submitForm


class Purchaser(HttpUserWithResources):
    weight = 10
    wait_time = constant(15)
    countryId = 1
    salutationId = 1

    def on_start(self):
        self.initRegister()
        self.register()

    def initRegister(self):
        path = os.path.dirname(os.path.realpath(
            __file__)) + '/fixtures/register.json'
        with open(path) as file:
            data = json.load(file)
            self.countryId = data['countryId']
            self.salutationId = data['salutationId']

    def register(self):
        response = self.client.get('/account/register', name='register')

        root = etree.fromstring(response.content, etree.HTMLParser())
        csrfElement = root.find(
            './/form[@action="/account/register"]/input[@name="_csrf_token"]')

        userMailAddress = 'user-' + \
            str(uuid.uuid4()).replace('-', '') + '@example.com'
        logging.info("Registering user " + userMailAddress)

        register = {
            'redirectTo': 'frontend.account.home.page',
            'salutationId': self.salutationId,
            'firstName': 'Firstname',
            'lastName': 'Lastname',
            'email': userMailAddress,
            'password': 'shopware',
            'billingAddress[street]': 'Test street',
            'billingAddress[zipcode]': '11111',
            'billingAddress[city]': 'Test city',
            'billingAddress[countryId]': self.countryId,
            '_csrf_token': csrfElement.attrib.get('value')
        }

        self.client.post('/account/register', data=register, name='register')

    def visitProduct(self, productDetailPageUrl: str):
        logging.info("Visit product detail page")
        self.client.get(productDetailPageUrl, name='product-detail-page')
        self.client.get('/widgets/checkout/info', name='cart-widget')

    def addProductToCart(self, productDetailPageUrl: str):
        logging.info("Adding product to cart " + productDetailPageUrl)
        productDetailPageResponse = self.client.get(
            productDetailPageUrl, name='listing-page')

        self.client.get('/widgets/checkout/info', name='cart-widget')

        submitForm(productDetailPageResponse,
                   self.client, "/checkout/line-item/add", name='add-to-cart')

    def checkoutOrder(self):
        logging.info("Going into checkout…")
        self.client.get('/checkout/cart', name='cart-page')

        confirmationPageResponse = self.client.get(
            '/checkout/confirm', name='confirm-page'
        )

        orderResponse = self.client.post('/checkout/order', name='order', data={
            'tos': 'on',
            '_csrf_token': csrf.getCsrfTokenForForm(confirmationPageResponse, '/checkout/order'),
        })

        logging.info("Checkout finished with status code " +
                     str(orderResponse.status_code))

    @task
    def order(self):
        url = random.choice(listings)
        logging.info("Visit listing " + url)

        self.client.get(url, name='listing-page-logged-in')
        self.client.get('/widgets/checkout/info', name='cart-widget')

        for detailPageUrl in random.sample(details, random.randint(1, 5)):
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
