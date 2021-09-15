# 如何安装 PHP 扩展

## 0 安装前准备工作
```shell
# 1.进入 PHP 容器
## 因容器系统为轻量级Alpine系统，故此需用 bin/sh命令
[root@nezha Docker-LNMP]# sudo docker exec -it php /bin/sh
# 2.修改源 /etc/apk/repositories
echo -e http://mirrors.ustc.edu.cn/alpine/v3.12/main/ > /etc/apk/repositories
# 3.解决 gcc 版本过低的问题
apk update
## 3.1 build-base软件包，包含了元软件包，将安装GCC、libc dev和binutils软件包（以及其他软件包）
apk add build-base
# 4. 安装phpize
apk add m4 autoconf 
# 5. 安装 wget vim 等其它工具
apk add wget vim 
```
## 1. PECL 安装一切编译式扩展
### 1.1 Swoole安装
```shell
# 1.进入 PHP 容器
## 因容器系统为轻量级Alpine系统，故此需用 bin/sh命令
[root@nezha Docker-LNMP]# docker exec -it php /bin/sh
# 2.pecl安装
pecl install swoole
# 3.添加swoole.so到php.ini
echo "extension=swoole.so" >> /usr/local/etc/php/php.ini
```

#### 1.1.1 编译安装Swoole
```shell
wget https://github.com/swoole/swoole-src/archive/v4.7.1.tar.gz &&\
	tar -zxvf v4.7.1.tar.gz &&\
	cd swoole-src-4.7.1 &&\
	phpize &&\
	./configure &&\
	make && make install &&\
	sed -i '$a \\n[swoole]\nextension=swoole.so' /etc/php.ini &&\
cd ../ && rm -rf v4.7.1.tar.gz swoole-src-4.7.1
```

### 1.2 其余PHP扩展安装
```shell
# 1.进入 PHP 容器
[root@nezha Docker-LNMP]# docker exec -it php /bin/sh
# 3.pecl安装
pecl install redis
pecl install mongodb
# 2.添加 redis.so到php.ini
echo "extension=redis.so" >> /usr/local/etc/php/php.ini
echo "extension=mongodb.so" >> /usr/local/etc/php/php.ini
```

## 2 MQ系列扩展安装
### 2.1 rabbitmq扩展
1.rabbitmq扩展安装前需要准备好rabbitmq-c 
```shell
# 1.进入 PHP 容器
[root@nezha Docker-LNMP]# docker exec -it php /bin/sh
#准备rabbitmq-c
wget https://github.com/alanxz/rabbitmq-c/archive/v0.9.0.tar.gz
apk add cmake
tar xf v0.9.0.tar.gz && cd rabbitmq-c-0.9.0
mkdir build && cd build
cmake -DCMAKE_INSTALL_PREFIX=/usr/local/rabbitmq-c ..
cmake --build . --target install
cd /usr/local/rabbitmq-c/
ln -s lib64 lib

# 2.安装amqp扩展
## 2.1 安装amqp要注意amqp版本是否支持PHP
wget https://pecl.php.net/get/amqp-1.11.0beta.tgz
tar xf amqp-1.11.0beta.tgz
cd amqp-1.11.0beta
phpize
./configure --with-php-config=/usr/local/bin/php-config --with-amqp --with-librabbitmq-dir=/usr/local/rabbitmq-c
make && make install

# 3.pecl安装,但需注意版本是否与PHP匹配，否则不会成功
pecl install amqp

# 4.添加 amqp.so到php.ini
echo "extension=amqp.so" >> /usr/local/etc/php/php.ini
```
#### 2.1.1 安装报错
```shell
CMake Error at /usr/share/cmake/Modules/FindPackageHandleStandardArgs.cmake:230 (message):
Could NOT find OpenSSL, try to set the path to OpenSSL root folder in the
system variable OPENSSL_ROOT_DIR (missing: OPENSSL_CRYPTO_LIBRARY
OPENSSL_INCLUDE_DIR) (Required is at least version "0.9.8")
```

- 解决:需要安装openssl
```shell
##安装openssl时需要用到下面基础包  
apk add gcc g++ make libffi-dev openssl-dev libtool
wget https://www.openssl.org/source/openssl-1.1.1h.tar.gz
tar -zxvf openssl-1.1.1h.tar.gz  
cd openssl-1.1.1h
./config
make
make install

## make install报错 make: *** wait: No child process.  Stop.
### 解决：make install -j
```

### 2.2 kafka扩展
1. kafka扩展安装需要准备好librdkafka环境
```shell
[root@nezha Docker-LNMP]# docker exec -it php /bin/sh
apk add git
git clone git://github.com/edenhill/librdkafka
cd librdkafka
./configure
make && make install
pecl install rdkafka
echo "extension=rdkafka.so" >> /usr/local/etc/php/php.ini
```
## 3 docker安装官方扩展库

- 此方式针对在容器下，直接安装PHP官方提供的扩展库

```shell
# 1.进入 PHP 容器
[root@nezha Docker-LNMP]# docker exec -it php /bin/sh
# 2.docker-php-ext-install 安装
docker-php-ext-install pdo_mysql
docker-php-ext-install sockets
echo "extension=sockets.so" >> /usr/local/etc/php/php.ini
echo "extension=pdo_mysql.so" >> /usr/local/etc/php/php.ini
```

## 4.退出 PHP 容器
[root@nezha /]# exit
exit
## 5.重启 PHP 容器
```sh
[root@nezha Docker-LNMP]# docker restart php



