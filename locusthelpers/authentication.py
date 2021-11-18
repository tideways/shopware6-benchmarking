import json
from locust.clients import HttpSession
from lxml import etree
import uuid
import os
import logging


class Authentication:
    countryId = 1
    salutationId = 1

    def __init__(self, client: HttpSession):
        self.client = client

    def initRegister(self):
        path = os.path.dirname(os.path.realpath(
            __file__)) + '/../fixtures/register.json'
        with open(path) as file:
            data = json.load(file)
            self.countryId = data['countryId']
            self.salutationId = data['salutationId']

    def register(self):
        self.initRegister()
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
