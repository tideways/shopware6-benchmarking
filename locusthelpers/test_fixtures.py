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
