FROM php:7.4.10-cli

# change timezone from UTC to KST
RUN apt-get update && \
    apt-get install -y tzdata && \
    ln -fs /usr/share/zoneinfo/Asia/Seoul /etc/localtime && \
    dpkg-reconfigure --frontend noninteractive tzdata

# install ext-zip
RUN apt-get install -y libzip-dev unzip && \
	docker-php-ext-install zip

# install composer
RUN php -r "copy('https://composer.github.io/installer.sig', 'installer.sig');" && \
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"  && \
	php -r "if (hash_file('sha384', 'composer-setup.php') === file_get_contents('installer.sig')) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"  && \
	php composer-setup.php --install-dir=/usr/local/bin --filename=composer  && \
	php -r "unlink('composer-setup.php');"  && \
	php -r "unlink('installer.sig');"

# change composer repo to korean mirror
RUN composer config -g repos.packagist composer https://packagist.kr

RUN pecl install protobuf && \
    echo "extension=protobuf.so" > /usr/local/etc/php/conf.d/protobuf.ini

RUN pecl install grpc && \
    echo "extension=grpc.so" > /usr/local/etc/php/conf.d/grpc.ini

RUN pecl install xdebug && \
	echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini

