from locust.clients import HttpSession
from lxml import etree
from requests.models import Response

def submitForm(response: Response, httpClient: HttpSession, formAction: str, catch_response=False, name: str = None) -> Response:
    if name is None:
        name = formAction

    root = etree.fromstring(
        response.content, etree.HTMLParser())
    form = root.find('.//form[@action="' + formAction + '"]')
    formData = {}
    for input in form.findall('.//input'):
        formData[input.attrib.get('name')] = input.attrib.get('value')

    return httpClient.post(formAction, data=formData, catch_response=catch_response, name=name)

def getFormFieldOptionValues(response: Response, formAction: str, fieldName: str, filterEmpty=False) -> list[str]:
    root = etree.fromstring(
        response.content, etree.HTMLParser())
    form = root.find('.//form[@action="' + formAction + '"]')
    field = form.find('.//select[@name="' + fieldName + '"]')
    
    values = []
    for option in field.findall('.//option'):
        if filterEmpty and option.attrib.get('value') == '':
            continue
        values.append(option.attrib.get('value'))

    return values

def getFormValues(response: Response, formAction: str, formData={}) -> dict:
    root = etree.fromstring(
        response.content, etree.HTMLParser())
    form = root.find('.//form[@action="' + formAction + '"]')
    for input in form.findall('.//input'):
        formData[input.attrib.get('name')] = input.attrib.get('value')

    return formData
