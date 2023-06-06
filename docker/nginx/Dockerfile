FROM nginx

# Lets Encrypt
RUN apt-get update
RUN apt-get install -y certbot
RUN apt-get install -y python3-certbot-nginx
VOLUME /etc/letsencrypt
VOLUME /etc/cron.d/
#RUN mkdir /scripts
#COPY *.sh /scripts/

#RUN echo '''#!/bin/sh
#/scripts/certbot-renew.sh
#'''> /etc/cron.d/certbot-renew
