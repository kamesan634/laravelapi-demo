# PHP 8.3 + Laravel 11 Dockerfile
# 用於 laravelapi-demo 專案

FROM php:8.3-fpm

# 設定工作目錄
WORKDIR /var/www/html

# 安裝系統依賴套件
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 安裝 Redis 擴展
RUN pecl install redis && docker-php-ext-enable redis

# 安裝 PCOV（測試涵蓋率）
RUN pecl install pcov && docker-php-ext-enable pcov

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定 PHP 使用者權限
RUN groupadd -g 1000 www && \
    useradd -u 1000 -ms /bin/bash -g www www

# 複製應用程式代碼
COPY --chown=www:www . /var/www/html

# 切換到 www 使用者
USER www

# 暴露 PHP-FPM 端口
EXPOSE 9000

# 啟動 PHP-FPM
CMD ["php-fpm"]
