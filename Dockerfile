FROM php:8.2-cli

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /var/www

# Copia os arquivos do projeto
COPY . .

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Permissões do storage e cache
RUN chmod -R 775 storage bootstrap/cache

# Expõe a porta
EXPOSE 8000

# Inicia o servidor
CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan migrate:fresh && \
    rm -rf storage/app/public/* && \
    php artisan storage:link && \
    php artisan serve --host=0.0.0.0 --port=8000
