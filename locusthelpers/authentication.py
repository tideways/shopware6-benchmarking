"""
MIT LICENSE

Copyright 2022 Tideways GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
"""

import json
from re import S
from locust.clients import HttpSession
from lxml import etree
import uuid
import os
import logging
import random
from requests.models import Response
from locusthelpers.form import getFormFieldOptionValues 

class Authentication:
    def __init__(self, client: HttpSession, guest_ratio: int, accounts_new_ratio: int, checkout_guest_ratio: int, checkout_accounts_new_ratio: int):
        self.client = client
        self.guest_ratio = guest_ratio
        self.accounts_new_ratio = accounts_new_ratio
        self.checkout_guest_ratio = checkout_guest_ratio
        self.checkout_accounts_new_ratio = checkout_accounts_new_ratio

    def clearCookies(self):
        self.client.cookies.clear()

    def __readSalutationIdFromRegisterPage(self, registerPageResponse: Response) -> str:
        salutationIdOptions = getFormFieldOptionValues(
            registerPageResponse, "/account/register", "salutationId", filterEmpty=True
        )
        return salutationIdOptions[0]

    def __readCountryIdFromRegisterPage(self, registerPageResponse: Response) -> str:
        countryIdOptions = getFormFieldOptionValues(
            registerPageResponse, "/account/register", "billingAddress[countryId]", filterEmpty=True
        )
        return countryIdOptions[0]


    def register(self, writeToFixture: bool = False, checkout: bool = False, guest: bool = False):
        if not checkout:
            path = '/account/register'
            pageName = 'register-page'
            redirectTo = 'frontend.account.login.page'
            redirectPageName = 'account-profile-page'
            guest = False
        else:
            path = '/checkout/register'
            pageName = 'checkout-register-page'
            redirectPageName = 'confirm-page'
            redirectTo = 'frontend.checkout.confirm.page'

        response = self.client.get(path, name=pageName)
        root = etree.fromstring(response.content, etree.HTMLParser())
        csrfElement = root.find(
            './/form[@action="/account/register"]/input[@name="_csrf_token"]')

        userMailAddress = 'user-' + \
            str(uuid.uuid4()).replace('-', '') + '@example.com'
        logging.info("Registering user " + userMailAddress)
        password = 'shopware'

        register = {
            'redirectTo': redirectTo,
            'salutationId': self.__readSalutationIdFromRegisterPage(response),
            'firstName': 'Firstname',
            'lastName': 'Lastname',
            'email': userMailAddress,
            'billingAddress[street]': 'Test street',
            'billingAddress[zipcode]': '11111',
            'billingAddress[city]': 'Test city',
            'billingAddress[countryId]': self.__readCountryIdFromRegisterPage(response),
            '_csrf_token': csrfElement.attrib.get('value')
        }

        if guest:
           register['guest'] = 'True'
        else:
           register['password'] = password

        if writeToFixture:
            dataDir = os.getenv('SWBENCH_DATA_DIR', os.path.dirname(os.path.realpath(__file__)) + '/../fixtures')
            path = dataDir + '/users.csv'
            with open(path, 'a') as file:
                file.write(userMailAddress + ',' + password + '\n')

        response = self.client.post('/account/register', data=register, name='register', allow_redirects=False)

        if response.status_code == 301 or response.status_code == 302:
            self.client.get(response.headers['Location'], name=redirectPageName)

    def login(self, user: str, password: str, checkout: bool = False):
        logging.info("Logging in user " + user)
        # @TODO missing cart request
        response = self.client.get('/account/login', name='login-page')
        root = etree.fromstring(response.content, etree.HTMLParser())
        csrfElement = root.find(
            './/form[@action="/account/login"]/input[@name="_csrf_token"]')

        if checkout == True:
            redirectTo = 'frontend.checkout.confirm.page'
            redirectPageName = 'confirm-page'
        else:
            redirectTo = 'frontend.account.home.page'
            redirectPageName = 'account-profile-page'

        login = {
            'email': user,
            'password': password,
            'redirectTo': redirectTo,
            '_csrf_token': csrfElement.attrib.get('value')
        }

        response = self.client.post('/account/login', data=login, name='login', allow_redirects=False)

        if response.status_code == 301 or response.status_code == 302:
            self.client.get(response.headers['Location'], name=redirectPageName)

    """
    Used before browsing users to decide weather they are logged in or guests
    """
    def guestOrLoggedInUser(self):
        self.clearCookies()
        if random.randint(1, 100) >= self.guest_ratio:
            self.loginRandomUserFromFixture()
            return True

        return False

    """
    Login existing user or create a new one during checkout based on configured ratio
    """
    def decideCheckoutGuestRecurringOrNewAccount(self):
        if random.randint(0, 100) < self.checkout_guest_ratio:
            self.register(checkout=True, guest=True)
        elif random.randint(0, 100) < self.checkout_accounts_new_ratio:
            self.register(checkout=True)
        else:
            self.loginRandomUserFromFixture(checkout=True)

    def loginRandomUserFromFixture(self, checkout: bool = False):
        dataDir = os.getenv('SWBENCH_DATA_DIR', os.path.dirname(os.path.realpath(__file__)) + '/../fixtures')
        path = dataDir + '/users.csv'

        # choose random user from fixture
        with open(path) as file:
            users = file.readlines()
            user = users[random.randint(0, len(users) - 1)]
            user = user.split(',')
            userMailAddress = user[0]
            password = user[1].strip()
            self.login(userMailAddress, password, checkout)

    def registerOrLogin(self):
        """
        Register or login a random user from fixture
        """
        if random.randint(0, 100) < self.accounts_new_ratio:
            self.register()
        else:
            self.loginRandomUserFromFixture()
