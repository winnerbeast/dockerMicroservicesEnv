# 0 准备安装环境
```shell
sudo  docker  network create --driver bridge --subnet 10.10.0.0/24 nezha_user_net
sudo  docker network ls | grep nezha_user_net
sudo  docker network inspect nezha_user_net
sudo  docker network rm nezha_user_net

#删除指定名字的容器
sudo docker rm -f $(sudo docker ps -a |  grep "nezha*"  | awk '{print $1}')
#删除全部容器
docker rm $(docker ps -aq)
```

## 1 安装
```shell
sudo docker-compose -f docker-compose-consul-cluster.yml up -d
```
## 2 测试
### 2.1 容器外
```shell
# 查看当前容器的consul下的集群状态
sudo docker exec consul_nezha_user_1_c  consul operator raft list-peers
# 查看当前 consul_nezha_user_1_c 容器下的节点信息
sudo docker exec consul_nezha_user_1_c  consul members

sudo docker exec consul_nezha_user_2_c consul operator raft list-peers
sudo docker exec consul_nezha_user_2_c consul members
#查看当前容器的consul中所有LAN和WAN的server节点
sudo docker exec consul_nezha_user_5_s consul members -wan
```
### 2.1 容器内
```shell
# 进入容器
sudo docker exec -it consul_nezha_user_2_c /bin/sh
sudo docker exec -it consul_nezha_user_2_c  sh
```

### 2.2 Web-UI
```shell
# nezha_user_2
http://192.168.30.130:8501/ui/nezha_user_2/services
# nezha_user_1
http://192.168.30.130:8500/ui/nezha_user_1/services
```
## 3 consul基本操作
> consul以HTTP形式操作
- 注册服务：
```shell
#URL：http://192.168.30.130:8500/v1/agent/service/register
# method: PUT
# DATA：
{
      "id": "nezha_user_1",
      "name": "userService",
      "tags": [ "xdp-/core.product" ],
      "address": "192.168.30.120",
      "port": 18306,
      "checks": [
        {
          "name": "core.product.check",
          "http": "http://192.168.30.120:18306",
          "interval": "10s",
          "timeout": "5s"
        }
      ]
}
```

- 更新服务：
```shell
#语法：/v1/agent/service/register
#method: PUT
#URL：http://192.168.30.130:8500/v1/agent/service/register

#DATA: JSON格式数据
  {
      "id": "nezha_user_111",
      "name": "userService111",
      "tags": [ "xdp-/core.product" ],
      "address": "192.168.30.130",
      "port": 18306,
      "checks": [
        {
          "name": "core.product.check",
          "http": "http://192.168.30.128:18306",
          "interval": "10s",
          "timeout": "5s"
        }
      ]
    }
```

- 删除服务：
```shell
#语法：/v1/agent/service/deregister/<serviceID> 
#URL：http://192.168.30.130:8500/v1/agent/service/deregister/nezha_user_1
#method: PUT
```
- 查询服务：
  - 本地请求(Agent)查询方式
```shell
#语法：/v1/agent/service/{service ID}
#method: GET

#查询本地节点下所有的注册服务
# URL：http://192.168.30.130:8500/v1/agent/services

#指定服务ID在本地节点中注册的服务查询
#URL：http://192.168.30.130:8500/v1/agent/service/nezha_user_1  
```
- 数据中心(catalog)查询方式
```shell
#HTTP-API：查询当前数据中心中注册的所有服务
#语法：/v1/catalog/services
#method: GET
#URL：http://192.168.30.130:8500/v1/catalog/services

#HTTP-API：指定服务在当前数据中心下的注册服务中进行查询
#语法：/v1/catalog/service/{ServiceName}
#method: GET
#URL：http://192.168.30.130:8500/v1/catalog/service/userService
```

## 4 API操作列表
- 本地请求：基于`Agent`
```shell
# 请求例子：curl http://localhost:8500/v1/agnect/services 
/v1/agent/checks                                --获取本地agent注册的所有检查(包括配置文件和http注册)
/v1/agent/services                              --获取本地agent注册的所有服务
/v1/agent/members                               --获取集群中的成员
/v1/agent/self                                  --获取本地agent的配置和成员信息
/v1/agent/join/<address>                        --触发本地agent加入node
/vq/agent/force-leave/<node>                    --强制删除node
/v1/agent/check/register                        --在本地agent增加一个检查项，使用PUT方法传输一个json格式的数据
/v1/agent/check/deregister/<checkID>            --注销一个本地agent的检查项
/v1/agent/check/pass/<checkID>                  --设置一个本地检查项的状态为passing
/v1/agent/check/warn/<checkID>                  --设置一个本地检查项的状态为warning
/v1/agent/check/fail<checkID>                   --设置一个本地检查项的状态为critical
/v1/agent/service/register                      --在本地agent增加一个新的服务项，使用PUT方法传输一个json格式的数据
/v1/agent/service/deregister/<serviceID>        --注销一个本地agent的服务项
```

- 数据中心请求：基于`catalog`类型
```shell
/v1/catalog/register                          --注册一个新的service、node、check
/v1/catalog/deregister                        --注销一个service、node、check
/v1/catalog/datacenters                       --列出知道的数据中心
/v1/catalog/nodes                             --在给定的数据中心列出node
/v1/catalog/services                          --在给定的数据中心列出service
/v1/catalog/service/<service>                 --查看某个服务的信息
/v1/catalog/node/<node>   
```

