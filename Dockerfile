FROM

USER root

ENV SIMPLESAMLPHP_VERSION=2.2.2

RUN $DNF_CMD -y update && \
    $DNF_CMD -y install zlib-devel bzip2 && \
    curl -fsSL -u "$NEXUS_USERNAME:$NEXUS_PASSWORD" https://si-devops-repository.edf.fr/repository/mtb-mtb-raw/org/php/simplesamlphp/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz -o /appli/projects/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz && \
    tar xzf /appli/projects/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz -C /appli/projects && \
    mv /appli/projects/simplesamlphp-${SIMPLESAMLPHP_VERSION} /appli/projects/simplesamlphp && \
    rm /appli/projects/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz && \
    chgrp -R 0 /appli && \
    chmod -R ug+rwX /appli

EXPOSE 9000

COPY ./src/composer.json .

# Install the dependencies
RUN /usr/libexec/s2i/assemble

# Set the default command for the resulting image
CMD /usr/libexec/s2i/run