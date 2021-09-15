# Docker-LNMP

利用 Docker-Compose 编排 LNMP 开发环境  

### 环境组成

- PHP 8.0
- Nginx
- MySQL 8.0
- Redis
- phpMyAdmin
- phpRedisAdmin  

### 目录结构
```
Docker-LNMP
|----docker                             Docker 目录
|--------config                         配置文件目录
|----------ini                          php 配置文件目录
|------------php.ini                    php 配置文件
|----------nginx                        Nginx 配置文件目录
|--------www                            应用根目录
|----docs                               扩展安装目录
|----extendDockerCompose                其余docker编排环境
|----README.md                          说明文件
|----docker-compose-fast.yml            docker compose 
```

### 准备docker安装环境

```shell
# 安装 Docker 和 Docker-Compose
yum -y install epel-release 
yum -y install docker docker-compose

# 启动 Docker 服务
service docker start

# 配置阿里云 Docker 镜像加速器（建议配置加速器, 可以提升 Docker 拉取镜像的速度）
mkdir -p /etc/docker
vim /etc/docker/daemon.json

# 新增下面内容
{
    "registry-mirrors": ["https://8auvmfwy.mirror.aliyuncs.com"]
}

# 重新加载配置、重启 Docker
systemctl daemon-reload 
systemctl restart docker 
```

### 安装

```shell
# 克隆项目
git clone https://github.com/duiying/Docker-LNMP.git
# 进入目录
cd Docker-LNMP
# 容器编排
docker-compose -f docker-compose-fast.yml up -d
```

### 测试

执行成功  

```
Creating cgi ... done
Creating proxy ... done
Creating mysql ...
Creating phpmyadmin ...
Creating phpredisadmin ...
Creating php ...
Creating nginx ...
```

访问 IP，效果图如下（可能需要等几秒钟）：  
    
<div align=center><img src="https://raw.githubusercontent.com/duiying/img/master/docker-lnmp.png" width="600"></div>  

### 学习文档

- [如何新建一个站点](docs/如何新建一个站点.md)
- [如何安装 PHP 扩展](docs/PHP扩展.md)

### 可能遇到的问题

```bash
# Error 信息
ERROR: for mysql  Cannot start service mysql: endpoint with name mysql already exists in network docker-lnmp_default
# 解决方案
这是由于端口被占用，需要清理此容器的网络占用
格式：docker network disconnect --force 网络模式 容器名称
docker network disconnect --force docker-lnmp_default mysql
检查是否还有其它容器占用
格式：docker network inspect 网络模式
```

### 如何清理所有容器和镜像？（谨慎操作！这会清除机器下所有容器或镜像）

```shell
# 删除所有容器
docker rm -f $(docker ps -aq)  
# 删除所有镜像
docker rmi $(docker images -q)
```

### 参考
- [https://github.com/gengxiankun/dockerfiles](https://github.com/gengxiankun/dockerfiles)