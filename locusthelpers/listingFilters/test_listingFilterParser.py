import os
import pytest
from locusthelpers.listingFilters.listingFilterParser import ListingFilterParser


def test_finds_the_correct_amount_of_filters(productListingPageContent):
    parser = ListingFilterParser(productListingPageContent)
    filters = parser.findFilters()
    # actually there are 13 filters, but the price range filter is currently not supported
    assert len(filters) == 12


def test_finds_the_correct_filters(productListingPageContent):
    parser = ListingFilterParser(productListingPageContent)
    filters = parser.findFilters()
    filterNames = [filter.name for filter in filters]
    assert filterNames == [
        "manufacturer",
        "properties",
        "properties",
        "properties",
        "properties",
        "properties",
        "properties",
        "properties",
        "properties",
        "properties",
        "properties",
        "properties",
    ]


def test_finds_the_correct_possible_values(productListingPageContent):
    parser = ListingFilterParser(productListingPageContent)
    filters = parser.findFilters()
    manufacturerFilter = filters[0]
    colorFilter = filters[1]
    assert len(manufacturerFilter.possibleValues) == 251

    assert manufacturerFilter.possibleValues[0] == "78c992f2ad124779aa90e1fd6508b398"
    assert manufacturerFilter.possibleValues[1] == "362615553c0f45dd843be1cd6ac961a4"

    assert colorFilter.possibleValues[0] == "39bc7994c3ac4a67a9c34a2f4cd8e222"
    assert colorFilter.possibleValues[1] == "10113a8c66d74169b686a28f8b896db8"


@pytest.fixture
def productListingPageContent() -> str:
    file = open(
        os.path.dirname(__file__) + "/test_fixtures/product_listing.html", mode="r"
    )
    contents = file.read()
    file.close()
    return contents
