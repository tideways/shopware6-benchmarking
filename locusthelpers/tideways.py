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

from locust.clients import HttpSession
import time
import random
import hashlib
import hmac

class HttpTidewaysSession(HttpSession):
    def __init__(self, base_url, request_event, user, *args, **kwargs):
        super().__init__(base_url, request_event, user, *args, **kwargs)
        self.tideways_apikey = ""
        self.tideways_trace_rate = 0

    def request(self, method, url, name=None, catch_response=False, context={}, **kwargs):
        kwargs['headers'] = self.profilingHeaders()
        return super().request(method, url, name, catch_response, context, **kwargs)

    def profilingHeaders(self):
        if random.randint(1, 100) > self.tideways_trace_rate:
            return {}

        apiKey = self.tideways_apikey
        if len(apiKey) == 0:
            return {}

        m = hashlib.md5()
        m.update(apiKey.encode("utf-8"))
        profilingHash = m.hexdigest()
        validUntil = int(time.time())+120
        hm = hmac.new(str.encode(profilingHash), digestmod="sha256")
        hm.update(("method=&time=" + str(validUntil) + "&user=").encode("utf-8"))
        token = hm.hexdigest()
        header = "method=&time=" + str(validUntil) + "&user=&hash=" + token

        return {"X-Tideways-Profiler": header}