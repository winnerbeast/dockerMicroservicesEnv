#!/bin/sh
docker-compose -f docker-compose-mongo.yaml up -d

#睡眠两分钟，等待mongodb所有容器起来之后将它们配置加入分片
sleep 30s
#initiate：来启动一个新的副本集，并加入节点到副本集合中。
#基于docker-compose -f 指令操作服务，也必须用docker-compose中的服务名来表示每个服务，在docker-compose.yml文件内也是如此
docker-compose -f docker-compose-mongo.yaml exec config1 bash -c "echo 'rs.initiate({_id: \"fates-mongo-config\",configsvr: true, members: [{ _id : 0, host : \"config1:27019\" },{ _id : 1, host : \"config2:27019\" }, { _id : 2, host : \"config3:27019\" }]})' | mongo --port 27019"
 docker-compose -f docker-compose-mongo.yaml exec shard1 bash -c "echo 'rs.initiate({_id: \"shard1\",members: [{ _id : 10, host : \"shard1:27018\" },{ _id : 11, host : \"shard1-2:27018\" },{ _id : 12, host : \"shard1-3:27018\", arbiterOnly: true }]})' | mongo --port 27018"
 docker-compose -f docker-compose-mongo.yaml exec shard2 bash -c "echo 'rs.initiate({_id: \"shard2\",members: [{ _id : 20, host : \"shard2:27018\" },{ _id : 21, host : \"shard2-2:27018\" },{ _id : 22, host : \"shard2-3:27018\", arbiterOnly: true }]})' | mongo --port 27018"
 docker-compose -f docker-compose-mongo.yaml exec shard3 bash -c "echo 'rs.initiate({_id: \"shard3\",members: [{ _id : 30, host : \"shard3:27018\" },{ _id : 31, host : \"shard3-2:27018\" },{ _id : 32, host : \"shard3-3:27018\", arbiterOnly: true }]})' | mongo --port 27018"
 docker-compose -f docker-compose-mongo.yaml exec mongos bash -c "echo 'sh.addShard(\"shard1/shard1:27018,shard1-2:27018,shard1-3:27018\")' | mongo"
 docker-compose -f docker-compose-mongo.yaml exec mongos bash -c "echo 'sh.addShard(\"shard2/shard2:27018,shard2-2:27018,shard2-3:27018\")' | mongo"
  docker-compose -f docker-compose-mongo.yaml exec mongos bash -c "echo 'sh.addShard(\"shard3/shard3:27018,shard3-2:27018,shard3-3:27018\")' | mongo"