FROM registry.cn-shenzhen.aliyuncs.com/gymoo/php-nginx-mc:1.3

ENV LETSENCRYPT_EMAIL jason@gymoo.cn

COPY . /app

RUN chmod -R 777 /app
