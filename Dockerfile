FROM php:8.1-cli

LABEL authors="jens.stahl@cn-consult.eu"

COPY . /opt/pbst/

RUN apt-get update && \
    apt-get install -y wget git && \
    rm -r /var/lib/apt/  && \
    # Set timezone correct
    rm /etc/localtime && \
    ln -s /usr/share/zoneinfo/Europe/Berlin /etc/localtime && \
    # Install PHP extensions
    # apt-get install -y php php-zip php-curl \
    # create php.ini \
    cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
    # Install composer
    cd /usr/local/bin \
    EXPECTED_SIGNATURE=$(wget -q -O - https://composer.github.io/installer.sig) && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', 'composer-setup.php');")  && \
    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ] ;\
    then >&2 echo 'ERROR: Invalid installer signature' \
      rm composer-setup.php \
      exit 1 ;\
    fi && \
    php composer-setup.php --quiet --filename=composer && \
    rm composer-setup.php && \
    chmod a+x composer && \
    # Config \
    mkdir -p /etc/pbst && \
    # Programm \
    ln -s /usr/local/bin/php /usr/bin/php && \
    cd /opt/pbst && \
    COMPOSER_ALLOW_SUPERUSER=1 composer install && \
    # Data \
    mkdir -p /var/opt/pbst && \
    # Logs \
    rm -rf /var/log/* && \
    echo "error_log = /var/log/php_errors.log" > /usr/local/etc/php/conf.d/90_error.ini && \
    touch /var/log/php_errors.log && \
    chmod 666 /var/log/php_errors.log

ENV PATH "$PATH:/opt/pbst"

VOLUME /etc/pbst
VOLUME /var/opt/pbst
VOLUME /var/log

WORKDIR /opt/pbst/

ENTRYPOINT ["/opt/pbst/pbst"]
CMD []
