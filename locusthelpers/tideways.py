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

        print(f'Tideways header: ' + header + '\n')

        return {"X-Tideways-Profiler": header}