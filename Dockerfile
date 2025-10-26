# Sử dụng image PHP 8.2 có sẵn Apache
FROM php:8.2-apache

# Đặt thư mục làm việc mặc định
WORKDIR /var/www/html

# Copy toàn bộ mã nguồn vào container
COPY . .

# Cài đặt các extension cần thiết (nếu dùng MySQL)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Mở cổng 80 cho web server
EXPOSE 80

# Khởi động Apache
CMD ["apache2-foreground"]
