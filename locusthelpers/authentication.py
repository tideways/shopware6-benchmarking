import json
from re import S
from locust.clients import HttpSession
from lxml import etree
import uuid
import os
import logging
import random


class Authentication:
    countryId = 1
    salutationId = 1

    def __init__(self, client: HttpSession):
        self.client = client

    def clearCookies(self):
        self.client.cookies.clear()

    def initRegister(self):
        path = os.path.dirname(os.path.realpath(
            __file__)) + '/../fixtures/register.json'
        with open(path) as file:
            data = json.load(file)
            self.countryId = data['countryId']
            self.salutationId = data['salutationId']

    def register(self, writeToFixture: bool = False):
        self.initRegister()
        # @TODO missing cart request
        response = self.client.get('/account/register', name='register')
        root = etree.fromstring(response.content, etree.HTMLParser())
        csrfElement = root.find(
            './/form[@action="/account/register"]/input[@name="_csrf_token"]')

        userMailAddress = 'user-' + \
            str(uuid.uuid4()).replace('-', '') + '@example.com'
        logging.info("Registering user " + userMailAddress)
        password = 'shopware'

        register = {
            'redirectTo': 'frontend.account.home.page',
            'salutationId': self.salutationId,
            'firstName': 'Firstname',
            'lastName': 'Lastname',
            'email': userMailAddress,
            'password': password,
            'billingAddress[street]': 'Test street',
            'billingAddress[zipcode]': '11111',
            'billingAddress[city]': 'Test city',
            'billingAddress[countryId]': self.countryId,
            '_csrf_token': csrfElement.attrib.get('value')
        }

        if writeToFixture:
            path = os.path.dirname(os.path.realpath(
                __file__)) + '/../fixtures/users.csv'
            with open(path, 'a') as file:
                file.write(userMailAddress + ',' + password + '\n')

        self.client.post('/account/register', data=register, name='register')

    def login(self, user: str, password: str):
        logging.info("Logging in user " + user)
        # @TODO missing cart request
        response = self.client.get('/account/login', name='login')
        root = etree.fromstring(response.content, etree.HTMLParser())
        csrfElement = root.find(
            './/form[@action="/account/login"]/input[@name="_csrf_token"]')

        login = {
            'email': user,
            'password': password,
            'redirectTo': 'frontend.account.home.page',
            '_csrf_token': csrfElement.attrib.get('value')
        }

        self.client.post('/account/login', data=login, name='login')

    def loginRandomUserFromFixture(self):
        path = os.path.dirname(os.path.realpath(
            __file__)) + '/../fixtures/users.csv'

        # choose random user from fixture
        with open(path) as file:
            users = file.readlines()
            user = users[random.randint(0, len(users) - 1)]
            user = user.split(',')
            userMailAddress = user[0]
            password = user[1].strip()
            self.login(userMailAddress, password)

    def registerOrLogin(self):
        """
        Register or login a random user from fixture
        """
        if random.randint(0, 1) == 0:
            self.loginRandomUserFromFixture()
        else:
            self.register()
