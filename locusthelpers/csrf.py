from logging import debug, info, warning
from lxml import etree
from requests.models import Response


def getCsrfTokenForForm(response: Response, formActionPath: str) -> str:
    root = etree.fromstring(response.content, etree.HTMLParser())

    csrfElement = root.find(
        './/form[@action="' + formActionPath + '"]/input[@name="_csrf_token"]')

    return csrfElement.attrib.get('value')
