FROM php:8.2-fpm

# 安装 Nginx
RUN apt-get update && apt-get install -y nginx && \
    rm -rf /var/lib/apt/lists/*

# 创建项目目录
WORKDIR /var/www/html

# 复制项目文件
COPY . .

# 配置 Nginx
RUN echo 'server { \
    listen 80; \
    server_name _; \
    root /var/www/html; \
    index index.php; \
    \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        include fastcgi_params; \
    } \
}' > /etc/nginx/sites-available/default

# 创建缓存目录并设置权限
RUN mkdir -p /var/www/html/yycache && \
    chown -R www-data:www-data /var/www/html/yycache && \
    chmod -R 755 /var/www/html/yycache

# 启动脚本
RUN echo '#!/bin/bash\n\
php-fpm -D\n\
nginx -g "daemon off;"' > /start.sh && chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]