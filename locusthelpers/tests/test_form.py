import os
import pytest

from locusthelpers.form import getFormFieldOptionValues
from requests.models import Response


def test_finds_the_correct_salutation_values(registerPageResponse):
    options = getFormFieldOptionValues(
        registerPageResponse, "/account/register", "salutationId"
    )
    assert options == [
        "",
        "7d03e235f0d94d3dab24df2d42142cb4",
        "6b839799568747b4bcb37a3fb395db8e",
        "4554920a08d142b99bba7915173315a7",
    ]


def test_finds_the_correct_salutation_values_with_filtering_empty(registerPageResponse):
    options = getFormFieldOptionValues(
        registerPageResponse, "/account/register", "salutationId", filterEmpty=True
    )
    assert options == [
        "7d03e235f0d94d3dab24df2d42142cb4",
        "6b839799568747b4bcb37a3fb395db8e",
        "4554920a08d142b99bba7915173315a7",
    ]


def test_finds_the_correct_country_values_with_filtering_empty(registerPageResponse):
    options = getFormFieldOptionValues(
        registerPageResponse,
        "/account/register",
        "billingAddress[countryId]",
        filterEmpty=True,
    )

    assert len(options) == 250
    assert options[0] == "f815a79987bc4338b998962b37186f80"


@pytest.fixture
def registerPageResponse():
    file = open(
        os.path.dirname(__file__) + "/test_fixtures/register_page.html", mode="r"
    )
    contents = file.read()
    file.close()
    response = Response()
    response._content = contents

    return response
