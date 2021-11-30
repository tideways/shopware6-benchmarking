import requests
import time
import csv
import os
import random
import uuid
import json
import hashlib
import hmac
from locust import HttpUser, task, between, constant
from lxml import etree
import logging
from locust import events

@events.init_command_line_parser.add_listener
def _(parser):
    parser.add_argument("--tideways-apikey", type=str, env_var="LOCUST_TIDEWAYS_APIKEY", default="", help="The API Key to trigger Tideways callgraph traces with")
    parser.add_argument("--tideways-trace-rate", type=int, env_var="LOCUST_TIDEWAYS_TRACE_RATE", default=1, help="The sample rate for triggering callgraph traces")

class HttpTidewaysUser(HttpUser):
    """
    provides a user that can trigger callgraph traces
    """

    abstract = True

    def tidewaysProfilingHeaders(self):
        if random.randint(1, 100) > self.environment.parsed_options.tideways_trace_rate:
            return {}

        apiKey = self.environment.parsed_options.tideways_apikey

        if len(apiKey) == 0:
            return {}

        m = hashlib.md5()
        m.update(apiKey.encode("utf-8"))
        profilingHash = m.hexdigest()
        validUntil = int(time.time())+120
        hm = hmac.new(str.encode(profilingHash), digestmod="sha256")
        hm.update(("method=&time=" + str(validUntil) + "&user=").encode("utf-8"))
        token = hm.hexdigest()
        header = "method=&time=" + str(validUntil) + "&user=&hash=" + token

        print(f'Tideways header: ' + header + '\n')

        return {"X-Tideways-Profiler": header}

class Purchaser(HttpTidewaysUser):
    weight = 10
    wait_time = constant(15)
    countryId = 1
    salutationId = 1

    def on_start(self):
        self.initRegister()
        self.register()

    def initRegister(self):
        path = os.path.dirname(os.path.realpath(__file__)) + '/fixtures/register.json'
        with open(path) as file:
            data = json.load(file)
            self.countryId = data['countryId']
            self.salutationId = data['salutationId']

    def register(self):
        response = self.client.get('/account/register', name='register')

        root = etree.fromstring(response.content, etree.HTMLParser())
        csrfElement = root.find('.//form[@action="/account/register"]/input[@name="_csrf_token"]')

        register = {
            'redirectTo': 'frontend.account.home.page',
            'salutationId': self.salutationId,
            'firstName': 'Firstname',
            'lastName': 'Lastname',
            'email': 'user-' + str(uuid.uuid4()).replace('-', '') + '@example.com',
            'password': 'shopware',
            'billingAddress[street]': 'Test street',
            'billingAddress[zipcode]': '11111',
            'billingAddress[city]': 'Test city',
            'billingAddress[countryId]': self.countryId,
            '_csrf_token': csrfElement.attrib.get('value')
        }

        self.client.post('/account/register', data=register, name='register', headers=self.tidewaysProfilingHeaders())

    def addProduct(self):
        number = random.choice(numbers)

        self.client.post('/checkout/product/add-by-number', name='add-product', data={
            'redirectTo': 'frontend.checkout.cart.page',
            'number': number
        }, headers=self.tidewaysProfilingHeaders())

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
        }, headers=self.tidewaysProfilingHeaders())

        self.client.get('/checkout/cart', name='cart-page', headers=self.tidewaysProfilingHeaders())

        self.client.get('/checkout/confirm', name='confirm-page', headers=self.tidewaysProfilingHeaders())

        self.client.post('/checkout/order', name='order', data={
            'tos': 'on'
        }, headers=self.tidewaysProfilingHeaders())

class Surfer(HttpTidewaysUser):
    weight = 30
    wait_time = constant(2)

    @task(10)
    def listing_page(self):
        url = random.choice(listings)
        self.client.get(url, name='listing-page', headers=self.tidewaysProfilingHeaders())
        self.client.get('/widgets/checkout/info', name='cart-widget', headers=self.tidewaysProfilingHeaders())

    @task(4)
    def detail_page(self):
        url = random.choice(details)
        self.client.get(url, name='detail-page', headers=self.tidewaysProfilingHeaders())
        self.client.get('/widgets/checkout/info', name='cart-widget', headers=self.tidewaysProfilingHeaders())

listings = []
details = []
numbers = []

def initListings():
    path = os.path.dirname(os.path.realpath(__file__)) + '/fixtures/listing_urls.csv'
    with open(path) as file:
        reader = csv.reader(file, delimiter=',')
        for row in reader:
            listings.append(row[0])

def initProducts():
    path = os.path.dirname(os.path.realpath(__file__)) + '/fixtures/product_urls.csv'
    with open(path) as file:
        reader = csv.reader(file, delimiter=',')
        for row in reader:
            details.append(row[0])

def initNumbers():
    path = os.path.dirname(os.path.realpath(__file__)) + '/fixtures/product_numbers.csv'
    with open(path) as file:
        reader = csv.reader(file, delimiter=',')
        for row in reader:
            numbers.append(row[0])

initListings()
initProducts()
initNumbers()
