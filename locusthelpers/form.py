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

from typing import List
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

def getFormFieldOptionValues(response: Response, formAction: str, fieldName: str, filterEmpty=False) -> List[str]:
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
