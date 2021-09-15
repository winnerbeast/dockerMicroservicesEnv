
# 1.修改镜像源
```shell
sudo docker exec -it redis bash
apt-get update 
apt-get install -y vim 
sudo cp /etc/apt/sources.list /etc/apt/sources.list.bak
sudo vim /etc/apt/sources.list
```
修改镜像源,加入下面的东西
```shell
deb http://mirrors.ustc.edu.cn/debian/ buster main
deb-src http://mirrors.ustc.edu.cn/debian/ buster main

deb http://security.debian.org/debian-security buster/updates main
deb-src http://security.debian.org/debian-security buster/updates main

#buster- updates,previously known as 'volatile'

deb http://mirrors.ustc.edu.cn/debian/ buster-updates main
deb-src http://mirrors.ustc.edu.cn/debian/ buster-updates main

deb http://mirrors.ustc.edu.cn/debian/ buster-backports main non-free contrib
deb-src http://mirrors.ustc.edu.cn/debian/ buster-backports main non-free contrib
```
# 2. 安装RedisBloom
```shell
apt-get install -y build-essential &&  apt-get install -y wget && cd /home 

wget https://github.com/RedisLabsModules/rebloom/archive/v1.1.1.tar.gz

tar -zxvf v1.1.1.tar.gz && cd RedisBloom-1.1.1/ && make
#一开始Redis启动配置文件有指定，所以需要把指定文件的注释给打开
echo "loadmodule /home/RedisBloom-1.1.1/rebloom.so" >> /usr/local/redis/redis.conf
#重新启动配置文件
sudo docker exec -it redis  bash -c "redis-server /usr/local/redis/redis.conf" 
```
