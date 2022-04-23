
# 0 准备mycat配置文件与Dockerfile
## 1.mycat 搭建
```shell
sudo  docker  network create --driver bridge --subnet 172.19.0.0/24 nezha_mycat_net
sudo  docker  network ls | grep nezha_mycat_net
sudo  docker  network inspect nezha_mycat_net
sudo  docker  network rm nezha_mycat_net

#新建目录
mkdir /home/nezha/mycat
#切换目录
cd /home/nezha/mycat
#下载mycat release1.6.7.6到当前目录
wget http://dl.mycat.org.cn/1.6.7.6/20201126013625/Mycat-server-1.6.7.6-release-20201126013625-linux.tar.gz
mv Mycat-server-1.6.7.6-release-20201126013625-linux.tar.gz mycat1.6.7.6.tar.gz
#解压 mycat 并移动文件到 conf 目录，因为使用docker直接挂载conf目录会报错，mycat启动时需要依赖conf目录中的文件。
tar -zxvf mycat1.6.7.6.tar.gz -C /home/nezha/mycat /home/nezha/mycat/conf
```

## 2.创建镜像容器

```shell
#下载dockerfile文件到 /home/nezha/mycat 目录
wget https://raw.githubusercontent.com/AlphaYu/Adnc/master/doc/mycat/Dockerfile
#如果下载失败，请手动下载并上传到 /home/nezha/mycat 目录，文件地址如下
#https://github.com/AlphaYu/Adnc/blob/master/doc/mycat/Dockerfile

#创建镜像文件
docker build -t mycat:1.6.7.6 .
#运行容器并挂载配置文件目录与日志目录
#-v /home/nezha/mycat/conf:/usr/local/mycat/conf 挂载配置文件目录
#-v /home/nezha/mycat/logs:/usr/local/mycat/logs 挂载日志目录
# --network=nezha_mycat_net 是自建的bridge网络，如果使用docker默认网络，不需要这段

docker run --privileged=true -p 8066:8066 -p 9066:9066 --name mycat -v /home/nezha/mycat/conf:/home/nezha/mycat/conf -v /home/nezha/mycat/logs:/usr/local/mycat/logs --restart=always --network=nezha_mycat_net --ip 172.19.0.7 -d  mycat:1.6.7.6
```

## 3.验证
```shell
# 进入mysql容器
docker exec -it mysql /bin/bash
# 登录mycat,172.19.0.7 是指mycat容器的Ip地址，如果容器没有指定固定Ip，你的可能不一样，请注意。
mysql -uroot -pnezha -P8066 -h172.19.0.7 --default_auth=mysql_native_password

mysql -uroot -h192.168.1.66  -P8066 -pnezha --default_auth=mysql_native_password
# 显示所有数据库
show databases;
# 多次执行下面的sql,观察hostname的变化。
select @@hostname;
```

## 4.分片规则介绍
- server.xml： mycat服务配置文件
```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE mycat:server SYSTEM "server.dtd">
<mycat:server xmlns:mycat="http://io.mycat/">
    <system>
        <property name="useSqlStat">0</property>  <!-- 1为开启实时统计、0为关闭 -->
        <property name="useGlobleTableCheck">0</property>  <!-- 1为开启全加班一致性检测、0为关闭 -->
        <property name="sequnceHandlerType">2</property>
        <property name="processorBufferPoolType">0</property>
        <property name="handleDistributedTransactions">0</property>
        <property name="useOffHeapForMerge">1</property>
        <property name="memoryPageSize">1m</property>
        <property name="spillsFileBufferSize">1k</property>
        <property name="useStreamOutput">0</property>
        <property name="systemReserveMemorySize">384m</property>
        <property name="useZKSwitch">true</property>
    </system>
        <!-- mycat的账号 -->
<user name="nezha" defaultAccount="true">
    <!-- 密码 -->
    <property name="password">nezha</property>
    <!-- 该账号可访问的逻辑库,对应schema.xml文件的schema节点的name-->
    <property name="schemas">nezha_user,nezha_order</property>
</user>
</mycat:server>
```
含义：表示mysql客户端用user=nezha、password=nezha的账户，可登录到mycat服务，登录的数据库(实质是逻辑库)为nezha_user、nezha_order

- schema.xml

```xml
<?xml version="1.0"?>
<!DOCTYPE mycat:schema SYSTEM "schema.dtd">
<mycat:schema xmlns:mycat="http://io.mycat/">
    <!-- 配置2个逻辑库-->
    <schema name="nezha_user" checkSQLschema="true" sqlMaxLimit="100">
        <table name="user01" primaryKey= "id" dataNode="dn_nezha_user_1,dn_nezha_user_2" rule="sharding-by-murmur" />   <!-- 一致性hash-->
        <table name="user02" primaryKey= "id" dataNode="dn_nezha_user_1,dn_nezha_user_2" rule="sharding-by-murmur" />   <!-- 一致性hash-->
    </schema>
    
    <schema name="nezha_order" checkSQLschema="true" sqlMaxLimit="100">
        <table name="order01" dataNode="dn_nezha_order_1,dn_nezha_order_2" rule="auto-sharding-long" />  <!-- 范围求模分片-->
        <table name="order02" dataNode="dn_nezha_order_1,dn_nezha_order_2" rule="auto-sharding-long" />  <!-- 范围求模分片-->
    </schema>


    <!-- 逻辑库对应的真实数据库-->
    <dataNode name="dn_nezha_order_1" dataHost="dn_nezha_order_1" database="order" />
    <dataNode name="dn_nezha_order_2" dataHost="dn_nezha_order_2" database="order" />
    <dataNode name="dn_nezha_user_1" dataHost="dn_nezha_user_1" database="user" />
    <dataNode name="dn_nezha_user_2" dataHost="dn_nezha_user_2" database="user" />


    <!-- 真实数据库地址：192.168.30.129:3306(order)-->
    <dataHost name="dn_nezha_order_1" maxCon="1000" minCon="10" balance="1" writeType="0" dbType="mysql" dbDriver="native">
        <heartbeat>select user()</heartbeat>
        <writeHost host="hostM1" url="192.168.30.129:3306" user="root" password="root" >
            <readHost host="hostS2" url="192.168.30.129:3306" user="root" password="root" />
        </writeHost>
        <writeHost host="hostS1" url="172.20.0.12:3306" user="root" password="root" />
    </dataHost>

    <dataHost name="dn_nezha_order_2" maxCon="1000" minCon="10" balance="1" writeType="0" dbType="mysql" dbDriver="native">
        <heartbeat>select user()</heartbeat>
        <writeHost host="hostM1" url="192.168.30.129:3307" user="root" password="root" >
            <readHost host="hostS2" url="192.168.30.129:3307" user="root" password="root" />
        </writeHost>
        <writeHost host="hostS1" url="172.20.0.12:3306" user="root" password="root" />
    </dataHost>

    <!--192.168.30.130:3306(user)-->
    <dataHost name="dn_nezha_user_1" maxCon="1000" minCon="10" balance="1" writeType="0" dbType="mysql" dbDriver="native">
        <heartbeat>select user()</heartbeat>
        <writeHost host="hostM1" url="192.168.30.130:3306" user="root" password="root" >
            <readHost host="hostS2" url="192.168.30.130:3306" user="root" password="root" />
        </writeHost>
        <writeHost host="hostS1" url="172.20.0.12:3306" user="root" password="root" />
    </dataHost>

    <dataHost name="dn_nezha_user_2" maxCon="1000" minCon="10" balance="1" writeType="0" dbType="mysql" dbDriver="native">
        <heartbeat>select user()</heartbeat>
        <writeHost host="hostM1" url="192.168.30.130:3307" user="root" password="root" >
            <readHost host="hostS2" url="192.168.30.130:3307" user="root" password="root" />
        </writeHost>
        <writeHost host="hostS1" url="172.20.0.12:3306" user="root" password="root" />
    </dataHost>

    <!--真实数据库所在的服务器地址，这里配置了1主2从。主服务器(hostM1)宕机会自动切换到(hostS1) -->
    <!--
    <dataHost name="dh_1" maxCon="1000" minCon="10" balance="1" writeType="0" dbType="mysql" dbDriver="native">
        <heartbeat>select user()</heartbeat>
        <writeHost host="hostM1" url="172.20.0.11:3306" user="root" password="alpha.abc" >
            <readHost host="hostS2" url="172.20.0.13:3306" user="root" password="alpha.abc" />
        </writeHost>
        <writeHost host="hostS1" url="172.20.0.12:3306" user="root" password="alpha.abc" />
    </dataHost>
    -->

</mycat:schema>

```

- rule.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- - - Licensed under the Apache License, Version 2.0 (the "License"); 
	- you may not use this file except in compliance with the License. - You 
	may obtain a copy of the License at - - http://www.apache.org/licenses/LICENSE-2.0 
	- - Unless required by applicable law or agreed to in writing, software - 
	distributed under the License is distributed on an "AS IS" BASIS, - WITHOUT 
	WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. - See the 
	License for the specific language governing permissions and - limitations 
	under the License. -->
<!DOCTYPE mycat:rule SYSTEM "rule.dtd">
<mycat:rule xmlns:mycat="http://io.mycat/">
    <tableRule name="rule1">
        <rule>
            <columns>id</columns>
            <algorithm>func1</algorithm>
        </rule>
    </tableRule>

    <tableRule name="sharding-by-date">
        <rule>
            <columns>createTime</columns>
            <algorithm>partbyday</algorithm>
        </rule>
    </tableRule>

    <tableRule name="rule2">
        <rule>
            <columns>user_id</columns>
            <algorithm>func1</algorithm>
        </rule>
    </tableRule>

    <tableRule name="sharding-by-intfile">
        <rule>
            <columns>sharding_id</columns>
            <algorithm>hash-int</algorithm>
        </rule>
    </tableRule>
    <tableRule name="auto-sharding-long">
        <rule>
            <columns>user_id</columns>
            <algorithm>rang-long</algorithm>
        </rule>
    </tableRule>
    <tableRule name="mod-long">
        <rule>
            <columns>id</columns>
            <algorithm>mod-long</algorithm>
        </rule>
    </tableRule>
    <tableRule name="sharding-by-murmur">
        <rule>
            <columns>id</columns>
            <algorithm>murmur</algorithm>
        </rule>
    </tableRule>
    <tableRule name="crc32slot">
        <rule>
            <columns>id</columns>
            <algorithm>crc32slot</algorithm>
        </rule>
    </tableRule>
    <tableRule name="sharding-by-month">
        <rule>
            <columns>create_time</columns>
            <algorithm>partbymonth</algorithm>
        </rule>
    </tableRule>
    <tableRule name="latest-month-calldate">
        <rule>
            <columns>calldate</columns>
            <algorithm>latestMonth</algorithm>
        </rule>
    </tableRule>

    <tableRule name="auto-sharding-rang-mod">
        <rule>
            <columns>id</columns>
            <algorithm>rang-mod</algorithm>
        </rule>
    </tableRule>

    <tableRule name="jch">
        <rule>
            <columns>id</columns>
            <algorithm>jump-consistent-hash</algorithm>
        </rule>
    </tableRule>

    <function name="murmur" class="io.mycat.route.function.PartitionByMurmurHash">
        <property name="seed">0</property><!-- 默认是0 -->
        <property name="count">2</property><!-- 要分片的数据库节点数量，必须指定，否则没法分片 -->
        <property name="virtualBucketTimes">160</property><!-- 一个实际的数据库节点被映射为这么多虚拟节点，默认是160倍，也就是虚拟节点数是物理节点数的160倍 -->
        <!-- <property name="weightMapFile">weightMapFile</property> 节点的权重，没有指定权重的节点默认是1。以properties文件的格式填写，以从0开始到count-1的整数值也就是节点索引为key，以节点权重值为值。所有权重值必须是正整数，否则以1代替 -->
        <!-- <property name="bucketMapPath">/etc/mycat/bucketMapPath</property>
            用于测试时观察各物理节点与虚拟节点的分布情况，如果指定了这个属性，会把虚拟节点的murmur hash值与物理节点的映射按行输出到这个文件，没有默认值，如果不指定，就不会输出任何东西 -->
    </function>

    <function name="crc32slot" class="io.mycat.route.function.PartitionByCRC32PreSlot">
        <property name="count">2</property><!-- 要分片的数据库节点数量，必须指定，否则没法分片 -->
    </function>
    <function name="hash-int"
              class="io.mycat.route.function.PartitionByFileMap">
        <property name="mapFile">partition-hash-int.txt</property>
    </function>
    <function name="rang-long"
              class="io.mycat.route.function.AutoPartitionByLong">
        <property name="mapFile">autopartition-long.txt</property>
    </function>
    <function name="mod-long" class="io.mycat.route.function.PartitionByMod">
        <!-- how many data nodes -->
        <property name="count">3</property>
    </function>

    <function name="func1" class="io.mycat.route.function.PartitionByLong">
        <property name="partitionCount">8</property>
        <property name="partitionLength">128</property>
    </function>
    <function name="latestMonth"
              class="io.mycat.route.function.LatestMonthPartion">
        <property name="splitOneDay">24</property>
    </function>
    <function name="partbymonth"
              class="io.mycat.route.function.PartitionByMonth">
        <property name="dateFormat">yyyy-MM-dd</property>
        <property name="sBeginDate">2015-01-01</property>
    </function>


    <function name="partbyday"
              class="io.mycat.route.function.PartitionByDate">
        <property name="dateFormat">yyyy-MM-dd</property>
        <property name="sNaturalDay">0</property>
        <property name="sBeginDate">2014-01-01</property>
        <property name="sEndDate">2014-01-31</property>
        <property name="sPartionDay">10</property>
    </function>

    <function name="rang-mod" class="io.mycat.route.function.PartitionByRangeMod">
        <property name="mapFile">partition-range-mod.txt</property>
    </function>

    <function name="jump-consistent-hash" class="io.mycat.route.function.PartitionByJumpConsistentHash">
        <property name="totalBuckets">3</property>
    </function>
</mycat:rule> 
```

## 5.mycat-web安装

```shell
mkdir /home/nezha/mycat-web
cd    /home/nezha/mycat-web
wget http://dl.mycat.org.cn/mycat-web-1.0/Mycat-web-1.0-SNAPSHOT.war

# 运行
docker run --name mycat-web -d -p 18082:8082 -e TZ="Asia/Shanghai" --restart=always --network=nezha_mycat_net --ip 172.19.0.15 registry.cn-hangzhou.aliyuncs.com/zhengqing/mycat-web
```
## 5.搭建实战
1. 在mysql数据库上创建数据表
2. 在schema.xml做好分库分表的配置文件
3. 客户端连接到mycat服务，插入数据看是否生效

## 6.mycat集群
```shell
docker run --privileged=true -p 8067:8067 -p 9067:9067 --name mycat -v /home/nezha/mycat/conf:/home/nezha/mycat/conf -v /home/nezha/mycat/logs:/usr/local/mycat/logs --restart=always --network=nezha_mycat_net --ip 172.19.0.8 -d  mycat:1.6.7.6
```

