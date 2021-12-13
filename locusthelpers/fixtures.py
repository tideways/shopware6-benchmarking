import csv
import os
import random

listings = []
details = []
numbers = []


def initListings():
    path = os.path.dirname(os.path.realpath(__file__)) + \
        '/../fixtures/listing_urls.csv'
    with open(path) as file:
        reader = csv.reader(file, delimiter=',')
        for row in reader:
            listings.append(row[0])


def initProducts():
    path = os.path.dirname(os.path.realpath(__file__)) + \
        '/../fixtures/product_urls.csv'
    with open(path) as file:
        reader = csv.reader(file, delimiter=',')
        for row in reader:
            details.append(row[0])


def initNumbers():
    path = os.path.dirname(os.path.realpath(__file__)) + \
        '/../fixtures/product_numbers.csv'
    with open(path) as file:
        reader = csv.reader(file, delimiter=',')
        for row in reader:
            numbers.append(row[0])


def getListings():
    return listings


def getProductDetails():
    return details


def getProductNumbers():
    return numbers


def extractSearchTermsFromProductDetailUrls(productDetailUrls):
    searchTerms = []
    for url in productDetailUrls:
        for term in url.split('/')[1].split('-'):
            searchTerms.append(term)
    return searchTerms


def getRandomWordFromFixture() -> str:
    return random.choice(extractSearchTermsFromProductDetailUrls(getProductDetails()))


def getRandomWordFromOperatingSystem() -> str:
    # @TODO maybe this is a bit inefficient?
    with open('/usr/share/dict/words') as f:
        words = f.read().splitlines()
    return words[random.randint(0, len(words) - 1)]


initListings()
initProducts()
initNumbers()
