import csv
import os
import random

listings = []
details = []


def initListings():
    dataDir = os.getenv('SWBENCH_DATA_DIR', os.path.dirname(os.path.realpath(__file__)) + '/../fixtures')
    path = dataDir + '/listing_urls.csv'

    try:
        with open(path) as file:
            reader = csv.reader(file, delimiter=',')
            for row in reader:
                listings.append(row[0])
    except FileNotFoundError as e:
        return


def initProducts():
    dataDir = os.getenv('SWBENCH_DATA_DIR', os.path.dirname(os.path.realpath(__file__)) + '/../fixtures')
    path = dataDir + '/product_urls.csv'

    try:
        with open(path) as file:
            reader = csv.reader(file, delimiter=',')
            for row in reader:
                details.append(row[0])
    except FileNotFoundError as e:
        return



def getListings():
    return listings


def getProductDetails():
    return details


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
