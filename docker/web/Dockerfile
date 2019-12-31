FROM nginx:alpine
RUN echo "upstream php-upstream { server app:9000; }" > /etc/nginx/conf.d/upstream.conf

COPY ./docker/web/nginx.conf /etc/nginx/
COPY ./docker/web/inventory.conf /etc/nginx/conf.d/

WORKDIR /app
CMD ["nginx"]
EXPOSE 8080
