FROM php:7.3-cli

WORKDIR /app

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

RUN sed -i \
      -e '/security.debian.org/d' \
      -e '/bullseye-updates/d' \
      -e 's|deb.debian.org/debian|archive.debian.org/debian|g' \
      /etc/apt/sources.list \
    && apt-get update -o Acquire::Check-Valid-Until=false \
    && apt-get install -y git unzip libzip-dev ca-certificates libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_mysql zip curl \
    && rm -rf /var/lib/apt/lists/*

COPY . .

RUN composer install

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]