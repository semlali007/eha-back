FROM

USER root

ENV SIMPLESAMLPHP_VERSION=2.2.2
#ADD username /username
#ADD password /password

#RUN curl -fsSL --user "`cat /username`:`cat /password`" https://si-devops-repository.edf.fr/repository/eha-web-achat-raw/simplesamlphp/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz -o /appli/projects/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz

COPY ./src /opt/app-root/

RUN mkdir /opt/app-root/src && curl -fsSL --user "$NEXUS_USERNAME:$NEXUS_PASSWORD" https://si-devops-repository.edf.fr/repository/eha-web-achat-raw/simplesamlphp/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz -o /opt/app-root/src/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz


RUN $DNF_CMD -y update --nobest && \
    $DNF_CMD -y install zlib-devel bzip2 && \
    tar xzf /opt/app-root/src/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz -C /opt/app-root/src && \
    mv /opt/app-root/src/simplesamlphp-${SIMPLESAMLPHP_VERSION} /opt/app-root/src/simplesamlphp && \
    rm /opt/app-root/src/projects/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz && \
    chgrp -R 0 /opt/app-root/src && \
    chmod -R ug+rwX /opt/app-root/src

EXPOSE 9000

COPY ./composer.json .

# Install the dependencies
RUN /usr/libexec/s2i/assemble

# Set the default command for the resulting image
CMD /usr/libexec/s2i/run