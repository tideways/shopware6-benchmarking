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
