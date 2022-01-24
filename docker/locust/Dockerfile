FROM locustio/locust:2.5.1
USER root
RUN apt-get update && apt-get install librdkafka-dev -y
USER locust
RUN pip3 install locust-plugins lxml 
