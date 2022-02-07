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

import logging
from locust.clients import HttpSession

import random


class Search:
    """
    Class that helps with searching in the shopware shop
    """

    def __init__(self, user):
        self.user = user
        self.client = user.client

    def search(self, query: str):
        """
        Performs a search in the shopware shop, and randomly sends auto-suggest-typeahead requests
        :param query: The search query
        :return: The search result page
        """

        # Search for the first random (1-5) amount of characters of the query
        search_query = ""
        while search_query != query:
            search_query = query[:len(search_query) +
                                 random.randint(1, min(len(query), 5))]
            self.user.getAjaxResource(
                "/suggest?search=" + search_query, name="search-suggest")

        while search_query != query:
            search_query = query[:random.randint(1, 5)]

        logging.info("Visiting search result page " +
                     "/search?search=" + search_query)
        return self.user.visitPage("/search?search=" + search_query, name="search")

    def __doSuggestRequest(self, query: str):
        self.user.getAjaxResource("/suggest?search=" + query)
