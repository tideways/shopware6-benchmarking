# This dockerfile can be used to test the code using python3.8.
# Use it in the docker-compose.yml file and run `docker-compose build` to build the image.
FROM python:3.8-slim
USER root
RUN apt-get update && apt-get install librdkafka-dev -y
RUN pip3 install locust locust-plugins lxml 

ENTRYPOINT ["locust"]
