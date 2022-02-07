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
