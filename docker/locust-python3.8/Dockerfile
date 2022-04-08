FROM python:3.8-slim
USER root
RUN apt-get update && apt-get install librdkafka-dev -y
RUN pip3 install locust locust-plugins lxml 

ENTRYPOINT ["locust"]
