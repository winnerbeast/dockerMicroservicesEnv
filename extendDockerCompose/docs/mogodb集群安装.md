# 0 准备安装环境
```shell
sudo  docker  network create --driver bridge --subnet 10.13.0.0/24 nezha_mongo_net
sudo  docker  network ls | grep nezha_mq_net
sudo  docker  network inspect nezha_mq_net
sudo  docker  network rm nezha_mq_net

#删除指定名字的容器
sudo docker rm -f $(sudo docker ps -a |  grep "nezha_user_mongo*"  | awk '{print $1}')
sudo docker rm -f $(sudo docker ps -a |  grep "mongo*"  | awk '{print $1}')

#启动所有容器
sudo docker start $(sudo docker ps -a | awk '{ print $1}' | tail -n +2)

sudo docker stop  $(sudo docker ps -a | awk '{ print $1}' | tail -n +2)

#删除全部容器
docker rm $(docker ps -aq)
```


## 1 安装
```shell
sudo ./mongo-deploy-and-start.sh
```
## 2 mongo测试
### 2.1 进入容器 
- 查看 `nezha_user_mongo_mongos` 角色
```shell
# 查看当前容器的zk下的集群的状态
[nezha@user mongo]$ sudo docker exec -it nezha_user_mongo_mongos bash
root@4eb585bc2cec:/# 
root@4eb585bc2cec:/# mongo

mongos> sh.status({"verbose":1})
--- Sharding Status --- 
  sharding version: {
  	"_id" : 1,
  	"minCompatibleVersion" : 5,
  	"currentVersion" : 6,
  	"clusterId" : ObjectId("614ff9f14b4a57a6f9bed095")
  }
  shards:
        {  "_id" : "shard1",  "host" : "shard1/shard1:27018,shard1-2:27018",  "state" : 1 }
        {  "_id" : "shard2",  "host" : "shard2/shard2:27018,shard2-2:27018",  "state" : 1 }
        {  "_id" : "shard3",  "host" : "shard3/shard3:27018,shard3-2:27018",  "state" : 1 }
  active mongoses:
        {  "_id" : "4eb585bc2cec:27017",  "advisoryHostFQDNs" : [ ],  "mongoVersion" : "4.0.0",  "ping" : ISODate("2021-09-26T08:40:16.555Z"),  "up" : NumberLong(9104),  "waiting" : true }

```

### 2.2 基本命令操作
```shell
mongos> show dbs
admin   0.000GB
config  0.001GB
mongos> use config
switched to db config
mongos> db.getCollection('shards').find({})
{ "_id" : "shard1", "host" : "shard1/shard1:27018,shard1-2:27018", "state" : 1 }
{ "_id" : "shard2", "host" : "shard2/shard2:27018,shard2-2:27018", "state" : 1 }
{ "_id" : "shard3", "host" : "shard3/shard3:27018,shard3-2:27018", "state" : 1 }

```
## 3 mongo基本操作

### 3.1 添加数据库
```shell
use nezha_user
```
- use 
  - 如果数据库不存在，则创建数据库，否则切换到指定数据库。
  - 默认创建的数据库 `nezha_user` 并不在数据库的列表中， 要显示它，需要向 `nezha_user`数据库插入一些数
```shell
> db.nezha_user.insert({"name":"莲花童子哪吒"})
WriteResult({ "nInserted" : 1 })
> show dbs
```
- show dbs
  - 查看所有数据库

### 3.2 删除数据库
- db.dropDatabase()
  - use 到指定数据库，方可删除数据库
```shell
> use nezha_user
switched to db runoob
> db.dropDatabase()
{
	"dropped" : "nezha_user",
	"ok" : 1,
	"operationTime" : Timestamp(1632717600, 7),
	"$clusterTime" : {
		"clusterTime" : Timestamp(1632717600, 7),
		"signature" : {
			"hash" : BinData(0,"AAAAAAAAAAAAAAAAAAAAAAAAAAA="),
			"keyId" : NumberLong(0)
		}
	}
}
>show dbs
admin   0.000GB
config  0.001GB
```
###  3.3 创建集合
> db.createCollection(name, options)
- name: 要创建的集合名称 
- options: 可选参数, 指定有关内存大小及索引的选项  

 在 `nezha_user` 数据库中创建 `nezha` 集合：
```shell
> use nezha_user
switched to db nezha_user
> db.createCollection("nezha")
{
	"ok" : 1,
	"operationTime" : Timestamp(1632718229, 8),
	"$clusterTime" : {
		"clusterTime" : Timestamp(1632718229, 8),
		"signature" : {
			"hash" : BinData(0,"AAAAAAAAAAAAAAAAAAAAAAAAAAA="),
			"keyId" : NumberLong(0)
		}
	}
}
>
```
使用 `show collections` 或 `show tables` 查看已有集合
```shell
> show collections
nezha
```
创建固定集合 `mycol`，整个集合空间大小 `6142800` B, 文档最大个数为 `10000` 个
```shell
> db.createCollection("mycol", { capped : true, autoIndexId : true, size : 6142800, max : 10000 } )
{ "ok" : 1 }
>
```
**插入一些文档 MongoDB 会自动创建集合**
```shell
> db.user.insert({"name" : "nezha"})
> show collections
user
```
MongoDB 中使用 `drop()` 方法来删除集合。
- db.collection.drop()
```shell
>use nezha_user
switched to db nezha_user
>show collections
nezha
user
>db.user.drop()
true
>show collections
nezha
```

### 3.4 插入文档
使用 `insert()` 或 `save()`方法向集合中插入文档
```shell
db.COLLECTION_NAME.insert(document)
或
db.COLLECTION_NAME.save(document)
```
- `save()`：如果 _id 主键存在则更新数据，如果不存在就插入数据。该方法新版本中已废弃，可以使用 `db.collection.insertOne()` 或 `db.collection.replaceOne()` 来代替。
- `insert()`: 若插入的数据主键已经存在，则会抛 `org.springframework.dao.DuplicateKeyException` 异常，提示主键重复，不保存当前数据。

`db.collection.insertMany()` 向集合一次插入多个文档

```shell
db.collection.insertMany(
   [ <document 1> , <document 2>, ... ],
   {
      writeConcern: <document>,
      ordered: <boolean>
   }
)
```
- document：要写入的文档。
- writeConcern：写入策略，默认为 1，即要求确认写操作，0  不要求。
- ordered：指定是否按顺序写入，默认 true，按顺序写入。  

  **插入数据：**
```shell
>db.nezha.insert({
title: '哪吒',
description: '莲花童子哪吒',
by: '哪吒教程',
url: 'http://www.nezha.com',
tags: ['mongodb', 'database', 'NoSQL'],
likes: 100
})
WriteResult({ "nInserted" : 1 })

>db.nezha.insertMany([
{
title: '哪吒12',
description: '莲花童子哪吒12',
by: '哪吒教程12',
url: 'http://www.nezha12.com',
tags: ['mongodb12', 'database12', 'NoSQL12'],
likes: 10012
},
{title: '哪吒123',
description: '莲花童子哪吒123',
by: '哪吒教程123',
url: 'http://www.nezha123.com',
tags: ['mongodb123', 'database123', 'NoSQL123'],
likes: 100123
}
])
```
**查看已插入的集合**
```shell
> db.nezha.find()
```
### 3.5 更新文档
`update()` 方法用于更新已存在的文档。

```shell
db.collection.update(
   <query>,
   <update>,
   {
     upsert: <boolean>,
     multi: <boolean>,
     writeConcern: <document>
   }
)
```
参数说明：
- query : `update` 的查询条件，类似 `sql update` 查询内 `where` 后面的。
- update : `update` 的对象和一些更新的操作符（如$,$inc...）等，也可以理解为 `sql update` 查询内 `set` 后面的
- upsert : 可选，如果不存在`update`的记录，是否插入`objNew`, `true` 为插入，默认是 `false`，不插入。
- multi : 可选，`mongodb` 默认是 `false` ,只更新找到的第一条记录，如果这个参数为 `true`, 就把按条件查出来多条记录全部更新。
- writeConcern :可选，抛出异常的级别。

`update()` 方法来更新标题(title)

```shell
>db.nezha.update({'title':'哪吒'},{$set:{'title':'哪吒是也哪吒'}})
>db.nezha.find().pretty()
```

修改多条相同的文档，则需要设置 multi 参数为 true。
```shell
>db.col.update({'title':'哪吒是也哪吒'},{$set:{'title':'哪吒 的公众号：莲花童子哪吒'}},{multi:true})
```

### 3.6 删除文档
`remove()` 移除集合中的数据。

`MongoDB` 数据更新可以使用 `update()` 函数。在执行 `remove()` 函数前先执行 `find()` 命令来判断执行的条件是否正确，这是一个比较好的习惯。
`remove() `方法基本语法格式：

```shell
db.collection.remove(
   <query>,
   {
     justOne: <boolean>,
     writeConcern: <document>
   }
)
```
参数：
- query :（可选）删除的文档的条件。
- justOne : （可选）如果设为 true 或 1，则只删除一个文档，如果不设置该参数，或使用默认值 false，则删除所有匹配条件的文档。
- writeConcern :（可选）抛出异常的级别。

```shell
> db.nezha.find()
>db.nezha.remove({'title':'哪吒12'})
WriteResult({ "nRemoved" : 1 })           # 删除了两条数据
>db.nezha.find()						  # 没有数据
```
只想删除第一条找到的记录可以设置 justOne 为 1
```shell
>db.nezha.remove(DELETION_CRITERIA,1)
>db.nezha.find()
```
删除所有数据
```shell
db.col.remove({})
```
### 3.7 查询文档
MongoDB 查询文档使用 `find()` 方法。   
`find()` 方法以非结构化的方式来显示所有文档。
语法：  
`MongoDB` 查询数据的语法格式如下：

```shell
db.collection.find(query, projection)
```
- query ：可选，使用查询操作符指定查询条件
- projection ：可选，使用投影操作符指定返回的键。查询时返回文档中所有键值， 只需省略该参数即可（默认省略）。

pretty() 方法以格式化的方式来显示所有文档。    
MongoDB 与 RDBMS Where 语句比较格式如下：

```shell
>db.col.find().pretty()
```
|  操作   | 格式  |范例  |RDBMS中的类似语句 |
|  ----  | ----  |----  |----  |
| 等于  | {<key>:<value>} | db.col.find({"by":"哪吒"}).pretty() | where by = '哪吒' |
| 小于  | {<key>:{$lt:<value>}} | db.col.find({"likes":{$lt:50}}).pretty() | where likes < 50 |
| 小于或等于  | {<key>:{$lte:<value>}} |db.col.find({"likes":{$lte:50}}).pretty() | where likes <= 50 |
| 大于  | {<key>:{$gt:<value>}} | db.col.find({"likes":{$gt:50}}).pretty() | where likes > 50 |
| 大于或等于  | {<key>:{$gte:<value>}}	 | db.col.find({"likes":{$gte:50}}).pretty() | where likes >= 50 |
| 不等于  | {<key>:{$ne:<value>}} | db.col.find({"likes":{$ne:50}}).pretty() | where likes != 50 |
|查询一个文档，使用` findOne() `方法||||

`MongoDB AND` 条件
`MongoDB` 的 `find()` 方法可以传入多个键(key)，每个键(key)以逗号隔开，即常规 SQL 的 AND 条件。

```shell
>db.col.find({key1:value1, key2:value2}).pretty()
```
实例:
通过 `by` 和 `title` 键来查询 “哪吒” 中 “哪吒视频” 的数据

```shell
> db.col.find({"by":"哪吒", "title":"哪吒 电视剧"}).pretty()
{
        "_id" : ObjectId("56063f17ade2f21f36b03133"),
        "title" : "哪吒 电视剧",
        "description" : 哪吒 电视剧是根据神话改变",
        "by" : "哪吒",
        "url" : "http://www.nezha.com",
        "tags" : [
                "mongodb",
                "database",
                "NoSQL"
        ],
        "likes" : 100
}
```

`MongoDB OR` 条件
`MongoDB OR` 条件语句使用了关键字 $or,语法格式如下：

```shell
>db.col.find(
   {
      $or: [
         {key1: value1}, {key2:value2}
      ]
   }
).pretty
```
实例
演示了查询键 by 值为 哪吒 或键 title 值为 漩涡鸣人 的文档。

```shell
>db.col.find({$or:[{"by":"哪吒"},{"title": "漩涡鸣人"}]}).pretty()
{
        "_id" : ObjectId("56063f17ade2f21f36b03133"),
        "title" : "漩涡鸣人",
        "description" : "漩涡鸣人 是火影得主角",
        "by" : "哪吒",
        "url" : "http://www.nezha.com",
        "tags" : [
                "mongodb",
                "database",
                "NoSQL"
        ],
        "likes" : 100
}
>
```
`AND` 和 `OR` 联合使用
演示了 `AND` 和 `OR` 联合使用，类似常规 `SQ`L 语句为： `'where likes>50 AND (by = '哪吒' OR title = '漩涡鸣人')'`

```shell
>db.col.find({"likes": {$gt:50}, $or: [{"by": "哪吒"},{"title": "漩涡鸣人"}]}).pretty()
{
        "_id" : ObjectId("56063f17ade2f21f36b03133"),
        "title" : "漩涡鸣人",
        "description" : "漩涡鸣人 是火影得主角",
        "by" : "哪吒",
        "url" : "http://www.nezha.com",
        "tags" : [
                "mongodb",
                "database",
                "NoSQL"
        ],
        "likes" : 100
}
```















