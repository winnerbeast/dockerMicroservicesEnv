# 0 准备安装环境
```shell
sudo  docker  network create --driver bridge --subnet 173.14.0.0/24 nezha_rab_net #容器外部网络
sudo  docker  network ls | grep nezha_rab_net
sudo  docker  network inspect nezha_rab_net
sudo  docker  network rm nezha_rab_net

#删除指定名字的容器
sudo docker rm -f $(sudo docker ps -a |  grep "nezha_user_rab*"  | awk '{print $1}')

#启动所有容器
sudo docker start $(sudo docker ps -a | awk '{ print $1}' | tail -n +2)

sudo docker stop $(sudo docker ps -a | awk '{ print $1}' | tail -n +2)

#删除全部容器
docker rm $(docker ps -aq)
```

## 1.rabbitmq 搭建准备
```shell
[nezha@localhost ~]$ mkdir /home/nezha/rabbitmq
#切换目录
[nezha@localhost ~]$ cd /home/nezha/rabbitmq
```

## 2.创建容器

```shell
[nezha@localhost /home/nezha/rabbitmq]$ sudo docker-compose -f docker-compose-rabbitmq.yml up -d
```

## 3.测试
```shell
# 进入rabbitmq容器
[nezha@localhost /home/nezha/rabbitmq]$ sudo docker exec -it nezha_user_rabbitmq1 /bin/bash
```
在容器内执行：
```shell
root@rabbitmq01:/# rabbitmqctl stop_app
Stopping rabbit application on node rabbit@nezha_user_rabbitmq1 ...
root@rabbitmq01:/# rabbitmqctl reset
Resetting node rabbit@nezha_user_rabbitmq1 ...
root@rabbitmq01:/# rabbitmqctl start_app
Starting node rabbit@rabbitmq01 ...
 completed with 3 plugins.
```
>rabbitmqctl stop_app 		停止rabbitmq   
> rabbitmqctl reset         		重置rabbitmq，相当于你手机里面的恢复出厂设置   
> rabbitmqctl start_app 		启动rabbitmq  

进入第二个容器：
```shell
[nezha@localhost /home/nezha/rabbitmq]$ sudo docker exec -it nezha_user_rabbitmq2 bash
root@nezha_user_rabbitmq1:/# 
```
在容器内依次输入如下指令：
```shell
rabbitmqctl stop_app
rabbitmqctl reset
rabbitmqctl join_cluster --ram rabbit@nezha_user_rabbitmq1
rabbitmqctl start_app
exit
```

指令详解：
> rabbitmqctl join_cluster --ram rabbit@nezha_user_rabbitmq1    
> 释义：使当前节点以内存节点的方式加入nezha_user_rabbitmq1

进入第三个容器：

```shell
[nezha@localhost /home/nezha/rabbitmq]$ sudo docker exec -it nezha_user_rabbitmq3 bash
root@nezha_user_rabbitmq3:/# 
```

在容器内依次输入如下指令：

```shell
rabbitmqctl stop_app
rabbitmqctl reset
rabbitmqctl join_cluster --ram rabbit@nezha_user_rabbitmq1
rabbitmqctl start_app
exit
```
启动容器成功后，可以通过浏览器访问 http://192.168.145.131:15672/， 登录账号guest  密码guest

## 4.高可用镜像模式集群
```shell
[nezha@localhost /home/nezha/rabbitmq]$sudo docker exec -it nezha_user_rabbitmq1 /bin/bash

root@nezha_user_rabbitmq1:/# rabbitmqctl set_policy ha-all "^" '{"ha-mode":"all"}'
```
> 输入命令后，系统输出：Setting policy "ha-all" for pattern "^" to "{"ha-mode":"all"}" with priority "0" for vhost "/" ...

表示**镜像模式集群设置成功**  
说明1：

> 1  不一定是在1号节点，在cluster中任意节点启用策略，策略会自动同步到集群节点的

> 2   rabbitmqctl set_policy ha-all "^" '{"ha-mode":"all"}'
> 这行命令创建了一个策略

说明2：  
rabbitmqctl set_policy ha-all "^" '{"ha-mode":"all"}' 详解：
> ha-all 策略名称
>
> ha-mode策略模式： all 即复制到所有节点
>
> "^" 表示所有匹配所有队列名称，例如 "^abcd" 这个是指同步“abcd”开头的队列名称







