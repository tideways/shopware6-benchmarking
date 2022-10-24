FROM locustio/locust:2.5.1
USER root
RUN apt-get update && \
  apt-get install -y --no-install-recommends gcc git libssl-dev g++ make && \
  cd /tmp && git clone https://github.com/edenhill/librdkafka.git && \
  cd librdkafka && git checkout tags/v1.9.2 && \
  ./configure && make && make install && ldconfig && \
  cd ../ && rm -rf librdkafka
USER locust
RUN pip3 install locust-plugins lxml 
