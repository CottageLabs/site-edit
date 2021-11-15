FROM python:2.7-alpine3.6

MAINTAINER matthew
LABEL version="1.0.0"

WORKDIR /usr/src/app

RUN echo "@extra http://nl.alpinelinux.org/alpine/v3.6/main" >> /etc/apk/repositories && \
        apk update && \
        # apk add "libpq-dev"
        apk add build-base postgresql-dev

RUN pip install --upgrade pip enum34
RUN pip install --no-cache-dir psycopg2 openpyxl pika xlrd

COPY exporter /usr/src/app/
COPY tweaker /usr/src/tweaker/
COPY launcher.sh /

# CMD ["python","-c","import time;time.sleep(10000)"]
CMD [ "/launcher.sh" ]
