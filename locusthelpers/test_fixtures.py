import os
import pytest

from locusthelpers.fixtures import extractSearchTermsFromProductDetailUrls


def test_extractSearchTermsFromProductDetailUrls(productDetailPageUrls):
    terms = extractSearchTermsFromProductDetailUrls(productDetailPageUrls)
    assert terms == ["Incredible", "Paper", "Pristea", "Intelligent",
                     "Granite", "Guru", "Smile", "Heavy", "Duty", "Paper", "ePeak"]


@pytest.fixture
def productDetailPageUrls() -> list:
    return [
        "/Incredible-Paper-Pristea/97dd610b3721479aa06c93bd749e13c5",
        "/Intelligent-Granite-Guru-Smile/cce4a79b21974334a0b3212a1399fb69",
        "/Heavy-Duty-Paper-ePeak/9ec5a1bb8d484e7ba9e8a65f4e4895e8",
    ]
