FROM openshift/dck_php-fpm-ubi8:8.2.12

USER root

ENV SIMPLESAMLPHP_VERSION=2.2.2

#Import de credentials du compte technique
RUN cd /tmp
ADD username /username
ADD password /password

RUN curl -fsSL https://"`cat /username`:`cat /password`"@si-devops-repository.edf.fr/repository/mtb-mtb-raw/org/php/simplesamlphp/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz -o /var/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz
RUN rm /username /password
RUN tar xzf simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz
RUN mv simplesamlphp-${SIMPLESAMLPHP_VERSION}-full simplesamlphp
