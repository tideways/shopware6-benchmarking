from requests.models import Response
from lxml import etree
import json
import logging
from locusthelpers.listingFilters.listingFilter import ListingFilter


class ListingFilterParser:
    def __init__(self, html: str):
        self.html = html

    def findFilters(self) -> list[ListingFilter]:
        root = etree.fromstring(
            self.html, etree.HTMLParser())
        availableFilterElements = root.xpath(
            './/div[contains(@class, "filter-multi-select")]')

        # remove elements that do not contain filter-multi-select in an array
        availableFilterElements = [
            filterElement
            for filterElement in availableFilterElements
            if "filter-multi-select" in filterElement.attrib.get("class").split()
        ]

        listingFilters = []

        for filterElement in availableFilterElements:
            if filterElement.attrib.has_key("data-filter-multi-select-options"):
                logging.debug("Found multi select filter")
                filterOptions = json.loads(
                    filterElement.attrib["data-filter-multi-select-options"]
                )
            elif filterElement.attrib.has_key("data-filter-property-select-options"):
                logging.debug("Found single select filter")
                filterOptions = json.loads(
                    filterElement.attrib["data-filter-property-select-options"]
                )
            else:
                logging.debug("Found attributes: ",
                              filterElement.attrib.keys())
                raise Exception("Could not find filter options")

            listingFilters.append(
                ListingFilter(
                    name=filterOptions.get('name'),
                    possibleValues=self.__findPossibleValuesForFilterElement(
                        filterElement)
                )
            )

        return listingFilters

    def findListingWidgetUrlAndParams(self) -> str:
        root = etree.fromstring(
            self.html, etree.HTMLParser())
        productListingWrapperElement = root.xpath(
            './/div[@class="cms-element-product-listing-wrapper"]')[0]

        listingOptions = json.loads(
            productListingWrapperElement.attrib.get("data-listing-options"))

        return listingOptions.get("dataUrl"), listingOptions.get("params")

    def __findPossibleValuesForFilterElement(self, filterElement: etree.Element):
        valueCheckboxElements = filterElement.xpath(
            './/input[@type="checkbox"]'
        )

        # extract the values from the checkboxes
        possibleValues = [
            valueCheckboxElement.attrib.get("value")
            for valueCheckboxElement in valueCheckboxElements
        ]

        return possibleValues
