FROM bitscoid/nginx-php

# copy source code
COPY . /var/www/bits

# run.sh will replace default web root from /var/www/bits to $WEBROOT
ENV WEBROOT /var/www/bits/public

# run.sh will use redis as session store with docker container name $PHP_REDIS_SESSION_HOST
ENV REDIS_HOST redis

# download required node/php packages, 
# some node modules need gcc/g++ to build
RUN cd /var/www/bits \
    # install node modules
    && npm install \
    # install php composer packages
    && composer install \
    # clean
    && npm run build \
    # set .env
    && cp .env.docker .env \
    # change /var/www/bits user/group
    && chown -Rf nobody:nobody /var/www/bits
