import requests
import time
import csv
import os
import random
import uuid
import json
from locust import task, between, constant
from locust_plugins.users import HttpUserWithResources

from lxml import etree
import logging


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

    def addProduct(self):
        number = random.choice(numbers)

        self.client.post('/checkout/product/add-by-number', name='add-product', data={
            'redirectTo': 'frontend.checkout.cart.page',
            'number': number
        })

    @task
    def order(self):
        url = random.choice(listings)
        logging.error("Visit listing " + url)
        response = self.client.get(url, name='listing-page-logged-in')

        root = etree.fromstring(response.content, etree.HTMLParser())
        csrfElement = root.find('.//input[@name="_csrf_token"]')

        self.client.get('/widgets/checkout/info', name='cart-widget')
        number = random.choice(numbers)

        self.client.post('/checkout/line-item/add', name='line-item-add', data={
            "lineItems[" + number + "][id]": number,
            "lineItems[" + number + "][referenceId]": number,
            "lineItems[" + number + "][quantity]": "1",
            '_csrf_token': csrfElement.attrib.get('value')
        })

        self.client.get('/checkout/cart', name='cart-page')

        self.client.get('/checkout/confirm', name='confirm-page')

        self.client.post('/checkout/order', name='order', data={
            'tos': 'on'
        })


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
