<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\redis;

use yii\base\Component;
use yii\db\Exception;
use yii\helpers\Inflector;

/**
 * 单独定义一个php类，来实现与redis服务器的连接
 * The redis connection class is used to establish a connection to a [redis](http://redis.io/) server.
 * 默认情况下，假设本地服务器的6379端口有个redis守护进程，使用0号数据库
 * By default it assumes there is a redis server running on localhost at port 6379 and uses the database number 0.
 * 可以通过hostname,port方式，也可以使用unixSocket套接字方式。
 * It is possible to connect to a redis server using [[hostname]] and [[port]] or using a [[unixSocket]].
 * 支持redis的AUTH命令，当服务器需要认证时，需配置password成员属性。认证是在连接完成之后。
 * It also supports [the AUTH command](http://redis.io/commands/auth) of redis.
 * When the server needs authentication, you can set the [[password]] property to
 * authenticate with the server after connect.
 *
 * 所有的redis命令，通过executeCommand()方法完成。
 * The execution of [redis commands](http://redis.io/commands) is possible with via [[executeCommand()]].
 *
	接下来这些操作，需要了解下redis服务器的基础知识才能看得懂，比如：
	如何连接，哪些增删改查基本命令，缓存数据如何同步到文件系统等。

 * @method mixed append($key, $value) Append a value to a key. <https://redis.io/commands/append>
 * @method mixed auth($password) Authenticate to the server. <https://redis.io/commands/auth>
 * @method mixed bgrewriteaof() Asynchronously rewrite the append-only file. <https://redis.io/commands/bgrewriteaof>
 * @method mixed bgsave() Asynchronously save the dataset to disk. <https://redis.io/commands/bgsave>
 * @method mixed bitcount($key, $start = null, $end = null) Count set bits in a string. <https://redis.io/commands/bitcount>
 * @method mixed bitfield($key, ...$operations) Perform arbitrary bitfield integer operations on strings. <https://redis.io/commands/bitfield>
 * @method mixed bitop($operation, $destkey, ...$keys) Perform bitwise operations between strings. <https://redis.io/commands/bitop>
 * @method mixed bitpos($key, $bit, $start = null, $end = null) Find first bit set or clear in a string. <https://redis.io/commands/bitpos>
 * @method mixed blpop(...$keys, $timeout) Remove and get the first element in a list, or block until one is available. <https://redis.io/commands/blpop>
 * @method mixed brpop(...$keys, $timeout) Remove and get the last element in a list, or block until one is available. <https://redis.io/commands/brpop>
 * @method mixed brpoplpush($source, $destination, $timeout) Pop a value from a list, push it to another list and return it; or block until one is available. <https://redis.io/commands/brpoplpush>
 * @method mixed clientKill(...$filters) Kill the connection of a client. <https://redis.io/commands/client-kill>
 * @method mixed clientList() Get the list of client connections. <https://redis.io/commands/client-list>
 * @method mixed clientGetname() Get the current connection name. <https://redis.io/commands/client-getname>
 * @method mixed clientPause($timeout) Stop processing commands from clients for some time. <https://redis.io/commands/client-pause>
 * @method mixed clientReply($option) Instruct the server whether to reply to commands. <https://redis.io/commands/client-reply>
 * @method mixed clientSetname($connectionName) Set the current connection name. <https://redis.io/commands/client-setname>
 * @method mixed clusterAddslots(...$slots) Assign new hash slots to receiving node. <https://redis.io/commands/cluster-addslots>
 * @method mixed clusterCountkeysinslot($slot) Return the number of local keys in the specified hash slot. <https://redis.io/commands/cluster-countkeysinslot>
 * @method mixed clusterDelslots(...$slots) Set hash slots as unbound in receiving node. <https://redis.io/commands/cluster-delslots>
 * @method mixed clusterFailover($option = null) Forces a slave to perform a manual failover of its master.. <https://redis.io/commands/cluster-failover>
 * @method mixed clusterForget($nodeId) Remove a node from the nodes table. <https://redis.io/commands/cluster-forget>
 * @method mixed clusterGetkeysinslot($slot, $count) Return local key names in the specified hash slot. <https://redis.io/commands/cluster-getkeysinslot>
 * @method mixed clusterInfo() Provides info about Redis Cluster node state. <https://redis.io/commands/cluster-info>
 * @method mixed clusterKeyslot($key) Returns the hash slot of the specified key. <https://redis.io/commands/cluster-keyslot>
 * @method mixed clusterMeet($ip, $port) Force a node cluster to handshake with another node. <https://redis.io/commands/cluster-meet>
 * @method mixed clusterNodes() Get Cluster config for the node. <https://redis.io/commands/cluster-nodes>
 * @method mixed clusterReplicate($nodeId) Reconfigure a node as a slave of the specified master node. <https://redis.io/commands/cluster-replicate>
 * @method mixed clusterReset($resetType = "SOFT") Reset a Redis Cluster node. <https://redis.io/commands/cluster-reset>
 * @method mixed clusterSaveconfig() Forces the node to save cluster state on disk. <https://redis.io/commands/cluster-saveconfig>
 * @method mixed clusterSetslot($slot, $type, $nodeid = null) Bind a hash slot to a specific node. <https://redis.io/commands/cluster-setslot>
 * @method mixed clusterSlaves($nodeId) List slave nodes of the specified master node. <https://redis.io/commands/cluster-slaves>
 * @method mixed clusterSlots() Get array of Cluster slot to node mappings. <https://redis.io/commands/cluster-slots>
 * @method mixed command() Get array of Redis command details. <https://redis.io/commands/command>
 * @method mixed commandCount() Get total number of Redis commands. <https://redis.io/commands/command-count>
 * @method mixed commandGetkeys() Extract keys given a full Redis command. <https://redis.io/commands/command-getkeys>
 * @method mixed commandInfo(...$commandNames) Get array of specific Redis command details. <https://redis.io/commands/command-info>
 * @method mixed configGet($parameter) Get the value of a configuration parameter. <https://redis.io/commands/config-get>
 * @method mixed configRewrite() Rewrite the configuration file with the in memory configuration. <https://redis.io/commands/config-rewrite>
 * @method mixed configSet($parameter, $value) Set a configuration parameter to the given value. <https://redis.io/commands/config-set>
 * @method mixed configResetstat() Reset the stats returned by INFO. <https://redis.io/commands/config-resetstat>
 * @method mixed dbsize() Return the number of keys in the selected database. <https://redis.io/commands/dbsize>
 * @method mixed debugObject($key) Get debugging information about a key. <https://redis.io/commands/debug-object>
 * @method mixed debugSegfault() Make the server crash. <https://redis.io/commands/debug-segfault>
 * @method mixed decr($key) Decrement the integer value of a key by one. <https://redis.io/commands/decr>
 * @method mixed decrby($key, $decrement) Decrement the integer value of a key by the given number. <https://redis.io/commands/decrby>
 * @method mixed del(...$keys) Delete a key. <https://redis.io/commands/del>
 * @method mixed discard() Discard all commands issued after MULTI. <https://redis.io/commands/discard>
 * @method mixed dump($key) Return a serialized version of the value stored at the specified key.. <https://redis.io/commands/dump>
 * @method mixed echo($message) Echo the given string. <https://redis.io/commands/echo>
 * @method mixed eval($script, $numkeys, ...$keys, ...$args) Execute a Lua script server side. <https://redis.io/commands/eval>
 * @method mixed evalsha($sha1, $numkeys, ...$keys, ...$args) Execute a Lua script server side. <https://redis.io/commands/evalsha>
 * @method mixed exec() Execute all commands issued after MULTI. <https://redis.io/commands/exec>
 * @method mixed exists(...$keys) Determine if a key exists. <https://redis.io/commands/exists>
 * @method mixed expire($key, $seconds) Set a key's time to live in seconds. <https://redis.io/commands/expire>
 * @method mixed expireat($key, $timestamp) Set the expiration for a key as a UNIX timestamp. <https://redis.io/commands/expireat>
 * @method mixed flushall($ASYNC = null) Remove all keys from all databases. <https://redis.io/commands/flushall>
 * @method mixed flushdb($ASYNC = null) Remove all keys from the current database. <https://redis.io/commands/flushdb>
 * @method mixed geoadd($key, $longitude, $latitude, $member, ...$more) Add one or more geospatial items in the geospatial index represented using a sorted set. <https://redis.io/commands/geoadd>
 * @method mixed geohash($key, ...$members) Returns members of a geospatial index as standard geohash strings. <https://redis.io/commands/geohash>
 * @method mixed geopos($key, ...$members) Returns longitude and latitude of members of a geospatial index. <https://redis.io/commands/geopos>
 * @method mixed geodist($key, $member1, $member2, $unit = null) Returns the distance between two members of a geospatial index. <https://redis.io/commands/geodist>
 * @method mixed georadius($key, $longitude, $latitude, $radius, $metric, ...$options) Query a sorted set representing a geospatial index to fetch members matching a given maximum distance from a point. <https://redis.io/commands/georadius>
 * @method mixed georadiusbymember($key, $member, $radius, $metric, ...$options) Query a sorted set representing a geospatial index to fetch members matching a given maximum distance from a member. <https://redis.io/commands/georadiusbymember>
 * @method mixed get($key) Get the value of a key. <https://redis.io/commands/get>
 * @method mixed getbit($key, $offset) Returns the bit value at offset in the string value stored at key. <https://redis.io/commands/getbit>
 * @method mixed getrange($key, $start, $end) Get a substring of the string stored at a key. <https://redis.io/commands/getrange>
 * @method mixed getset($key, $value) Set the string value of a key and return its old value. <https://redis.io/commands/getset>
 * @method mixed hdel($key, ...$fields) Delete one or more hash fields. <https://redis.io/commands/hdel>
 * @method mixed hexists($key, $field) Determine if a hash field exists. <https://redis.io/commands/hexists>
 * @method mixed hget($key, $field) Get the value of a hash field. <https://redis.io/commands/hget>
 * @method mixed hgetall($key) Get all the fields and values in a hash. <https://redis.io/commands/hgetall>
 * @method mixed hincrby($key, $field, $increment) Increment the integer value of a hash field by the given number. <https://redis.io/commands/hincrby>
 * @method mixed hincrbyfloat($key, $field, $increment) Increment the float value of a hash field by the given amount. <https://redis.io/commands/hincrbyfloat>
 * @method mixed hkeys($key) Get all the fields in a hash. <https://redis.io/commands/hkeys>
 * @method mixed hlen($key) Get the number of fields in a hash. <https://redis.io/commands/hlen>
 * @method mixed hmget($key, ...$fields) Get the values of all the given hash fields. <https://redis.io/commands/hmget>
 * @method mixed hmset($key, $field, $value, ...$more) Set multiple hash fields to multiple values. <https://redis.io/commands/hmset>
 * @method mixed hset($key, $field, $value) Set the string value of a hash field. <https://redis.io/commands/hset>
 * @method mixed hsetnx($key, $field, $value) Set the value of a hash field, only if the field does not exist. <https://redis.io/commands/hsetnx>
 * @method mixed hstrlen($key, $field) Get the length of the value of a hash field. <https://redis.io/commands/hstrlen>
 * @method mixed hvals($key) Get all the values in a hash. <https://redis.io/commands/hvals>
 * @method mixed incr($key) Increment the integer value of a key by one. <https://redis.io/commands/incr>
 * @method mixed incrby($key, $increment) Increment the integer value of a key by the given amount. <https://redis.io/commands/incrby>
 * @method mixed incrbyfloat($key, $increment) Increment the float value of a key by the given amount. <https://redis.io/commands/incrbyfloat>
 * @method mixed info($section = null) Get information and statistics about the server. <https://redis.io/commands/info>
 * @method mixed keys($pattern) Find all keys matching the given pattern. <https://redis.io/commands/keys>
 * @method mixed lastsave() Get the UNIX time stamp of the last successful save to disk. <https://redis.io/commands/lastsave>
 * @method mixed lindex($key, $index) Get an element from a list by its index. <https://redis.io/commands/lindex>
 * @method mixed linsert($key, $where, $pivot, $value) Insert an element before or after another element in a list. <https://redis.io/commands/linsert>
 * @method mixed llen($key) Get the length of a list. <https://redis.io/commands/llen>
 * @method mixed lpop($key) Remove and get the first element in a list. <https://redis.io/commands/lpop>
 * @method mixed lpush($key, ...$values) Prepend one or multiple values to a list. <https://redis.io/commands/lpush>
 * @method mixed lpushx($key, $value) Prepend a value to a list, only if the list exists. <https://redis.io/commands/lpushx>
 * @method mixed lrange($key, $start, $stop) Get a range of elements from a list. <https://redis.io/commands/lrange>
 * @method mixed lrem($key, $count, $value) Remove elements from a list. <https://redis.io/commands/lrem>
 * @method mixed lset($key, $index, $value) Set the value of an element in a list by its index. <https://redis.io/commands/lset>
 * @method mixed ltrim($key, $start, $stop) Trim a list to the specified range. <https://redis.io/commands/ltrim>
 * @method mixed mget(...$keys) Get the values of all the given keys. <https://redis.io/commands/mget>
 * @method mixed migrate($host, $port, $key, $destinationDb, $timeout, ...$options) Atomically transfer a key from a Redis instance to another one.. <https://redis.io/commands/migrate>
 * @method mixed monitor() Listen for all requests received by the server in real time. <https://redis.io/commands/monitor>
 * @method mixed move($key, $db) Move a key to another database. <https://redis.io/commands/move>
 * @method mixed mset(...$keyValuePairs) Set multiple keys to multiple values. <https://redis.io/commands/mset>
 * @method mixed msetnx(...$keyValuePairs) Set multiple keys to multiple values, only if none of the keys exist. <https://redis.io/commands/msetnx>
 * @method mixed multi() Mark the start of a transaction block. <https://redis.io/commands/multi>
 * @method mixed object($subcommand, ...$argumentss) Inspect the internals of Redis objects. <https://redis.io/commands/object>
 * @method mixed persist($key) Remove the expiration from a key. <https://redis.io/commands/persist>
 * @method mixed pexpire($key, $milliseconds) Set a key's time to live in milliseconds. <https://redis.io/commands/pexpire>
 * @method mixed pexpireat($key, $millisecondsTimestamp) Set the expiration for a key as a UNIX timestamp specified in milliseconds. <https://redis.io/commands/pexpireat>
 * @method mixed pfadd($key, ...$elements) Adds the specified elements to the specified HyperLogLog.. <https://redis.io/commands/pfadd>
 * @method mixed pfcount(...$keys) Return the approximated cardinality of the set(s) observed by the HyperLogLog at key(s).. <https://redis.io/commands/pfcount>
 * @method mixed pfmerge($destkey, ...$sourcekeys) Merge N different HyperLogLogs into a single one.. <https://redis.io/commands/pfmerge>
 * @method mixed ping($message = null) Ping the server. <https://redis.io/commands/ping>
 * @method mixed psetex($key, $milliseconds, $value) Set the value and expiration in milliseconds of a key. <https://redis.io/commands/psetex>
 * @method mixed psubscribe(...$patterns) Listen for messages published to channels matching the given patterns. <https://redis.io/commands/psubscribe>
 * @method mixed pubsub($subcommand, ...$arguments) Inspect the state of the Pub/Sub subsystem. <https://redis.io/commands/pubsub>
 * @method mixed pttl($key) Get the time to live for a key in milliseconds. <https://redis.io/commands/pttl>
 * @method mixed publish($channel, $message) Post a message to a channel. <https://redis.io/commands/publish>
 * @method mixed punsubscribe(...$patterns) Stop listening for messages posted to channels matching the given patterns. <https://redis.io/commands/punsubscribe>
 * @method mixed quit() Close the connection. <https://redis.io/commands/quit>
 * @method mixed randomkey() Return a random key from the keyspace. <https://redis.io/commands/randomkey>
 * @method mixed readonly() Enables read queries for a connection to a cluster slave node. <https://redis.io/commands/readonly>
 * @method mixed readwrite() Disables read queries for a connection to a cluster slave node. <https://redis.io/commands/readwrite>
 * @method mixed rename($key, $newkey) Rename a key. <https://redis.io/commands/rename>
 * @method mixed renamenx($key, $newkey) Rename a key, only if the new key does not exist. <https://redis.io/commands/renamenx>
 * @method mixed restore($key, $ttl, $serializedValue, $REPLACE = null) Create a key using the provided serialized value, previously obtained using DUMP.. <https://redis.io/commands/restore>
 * @method mixed role() Return the role of the instance in the context of replication. <https://redis.io/commands/role>
 * @method mixed rpop($key) Remove and get the last element in a list. <https://redis.io/commands/rpop>
 * @method mixed rpoplpush($source, $destination) Remove the last element in a list, prepend it to another list and return it. <https://redis.io/commands/rpoplpush>
 * @method mixed rpush($key, ...$values) Append one or multiple values to a list. <https://redis.io/commands/rpush>
 * @method mixed rpushx($key, $value) Append a value to a list, only if the list exists. <https://redis.io/commands/rpushx>
 * @method mixed sadd($key, ...$members) Add one or more members to a set. <https://redis.io/commands/sadd>
 * @method mixed save() Synchronously save the dataset to disk. <https://redis.io/commands/save>
 * @method mixed scard($key) Get the number of members in a set. <https://redis.io/commands/scard>
 * @method mixed scriptDebug($option) Set the debug mode for executed scripts.. <https://redis.io/commands/script-debug>
 * @method mixed scriptExists(...$sha1s) Check existence of scripts in the script cache.. <https://redis.io/commands/script-exists>
 * @method mixed scriptFlush() Remove all the scripts from the script cache.. <https://redis.io/commands/script-flush>
 * @method mixed scriptKill() Kill the script currently in execution.. <https://redis.io/commands/script-kill>
 * @method mixed scriptLoad($script) Load the specified Lua script into the script cache.. <https://redis.io/commands/script-load>
 * @method mixed sdiff(...$keys) Subtract multiple sets. <https://redis.io/commands/sdiff>
 * @method mixed sdiffstore($destination, ...$keys) Subtract multiple sets and store the resulting set in a key. <https://redis.io/commands/sdiffstore>
 * @method mixed select($index) Change the selected database for the current connection. <https://redis.io/commands/select>
 * @method mixed set($key, $value, ...$options) Set the string value of a key. <https://redis.io/commands/set>
 * @method mixed setbit($key, $offset, $value) Sets or clears the bit at offset in the string value stored at key. <https://redis.io/commands/setbit>
 * @method mixed setex($key, $seconds, $value) Set the value and expiration of a key. <https://redis.io/commands/setex>
 * @method mixed setnx($key, $value) Set the value of a key, only if the key does not exist. <https://redis.io/commands/setnx>
 * @method mixed setrange($key, $offset, $value) Overwrite part of a string at key starting at the specified offset. <https://redis.io/commands/setrange>
 * @method mixed shutdown($saveOption = null) Synchronously save the dataset to disk and then shut down the server. <https://redis.io/commands/shutdown>
 * @method mixed sinter(...$keys) Intersect multiple sets. <https://redis.io/commands/sinter>
 * @method mixed sinterstore($destination, ...$keys) Intersect multiple sets and store the resulting set in a key. <https://redis.io/commands/sinterstore>
 * @method mixed sismember($key, $member) Determine if a given value is a member of a set. <https://redis.io/commands/sismember>
 * @method mixed slaveof($host, $port) Make the server a slave of another instance, or promote it as master. <https://redis.io/commands/slaveof>
 * @method mixed slowlog($subcommand, $argument = null) Manages the Redis slow queries log. <https://redis.io/commands/slowlog>
 * @method mixed smembers($key) Get all the members in a set. <https://redis.io/commands/smembers>
 * @method mixed smove($source, $destination, $member) Move a member from one set to another. <https://redis.io/commands/smove>
 * @method mixed sort($key, ...$options) Sort the elements in a list, set or sorted set. <https://redis.io/commands/sort>
 * @method mixed spop($key, $count = null) Remove and return one or multiple random members from a set. <https://redis.io/commands/spop>
 * @method mixed srandmember($key, $count = null) Get one or multiple random members from a set. <https://redis.io/commands/srandmember>
 * @method mixed srem($key, ...$members) Remove one or more members from a set. <https://redis.io/commands/srem>
 * @method mixed strlen($key) Get the length of the value stored in a key. <https://redis.io/commands/strlen>
 * @method mixed subscribe(...$channels) Listen for messages published to the given channels. <https://redis.io/commands/subscribe>
 * @method mixed sunion(...$keys) Add multiple sets. <https://redis.io/commands/sunion>
 * @method mixed sunionstore($destination, ...$keys) Add multiple sets and store the resulting set in a key. <https://redis.io/commands/sunionstore>
 * @method mixed swapdb($index, $index) Swaps two Redis databases. <https://redis.io/commands/swapdb>
 * @method mixed sync() Internal command used for replication. <https://redis.io/commands/sync>
 * @method mixed time() Return the current server time. <https://redis.io/commands/time>
 * @method mixed touch(...$keys) Alters the last access time of a key(s). Returns the number of existing keys specified.. <https://redis.io/commands/touch>
 * @method mixed ttl($key) Get the time to live for a key. <https://redis.io/commands/ttl>
 * @method mixed type($key) Determine the type stored at key. <https://redis.io/commands/type>
 * @method mixed unsubscribe(...$channels) Stop listening for messages posted to the given channels. <https://redis.io/commands/unsubscribe>
 * @method mixed unlink(...$keys) Delete a key asynchronously in another thread. Otherwise it is just as DEL, but non blocking.. <https://redis.io/commands/unlink>
 * @method mixed unwatch() Forget about all watched keys. <https://redis.io/commands/unwatch>
 * @method mixed wait($numslaves, $timeout) Wait for the synchronous replication of all the write commands sent in the context of the current connection. <https://redis.io/commands/wait>
 * @method mixed watch(...$keys) Watch the given keys to determine execution of the MULTI/EXEC block. <https://redis.io/commands/watch>
 * @method mixed zadd($key, ...$options) Add one or more members to a sorted set, or update its score if it already exists. <https://redis.io/commands/zadd>
 * @method mixed zcard($key) Get the number of members in a sorted set. <https://redis.io/commands/zcard>
 * @method mixed zcount($key, $min, $max) Count the members in a sorted set with scores within the given values. <https://redis.io/commands/zcount>
 * @method mixed zincrby($key, $increment, $member) Increment the score of a member in a sorted set. <https://redis.io/commands/zincrby>
 * @method mixed zinterstore($destination, $numkeys, $key, ...$options) Intersect multiple sorted sets and store the resulting sorted set in a new key. <https://redis.io/commands/zinterstore>
 * @method mixed zlexcount($key, $min, $max) Count the number of members in a sorted set between a given lexicographical range. <https://redis.io/commands/zlexcount>
 * @method mixed zrange($key, $start, $stop, $WITHSCORES = null) Return a range of members in a sorted set, by index. <https://redis.io/commands/zrange>
 * @method mixed zrangebylex($key, $min, $max, $LIMIT = null, $offset = null, $count = null) Return a range of members in a sorted set, by lexicographical range. <https://redis.io/commands/zrangebylex>
 * @method mixed zrevrangebylex($key, $max, $min, $LIMIT = null, $offset = null, $count = null) Return a range of members in a sorted set, by lexicographical range, ordered from higher to lower strings.. <https://redis.io/commands/zrevrangebylex>
 * @method mixed zrangebyscore($key, $min, $max, $WITHSCORES = null, $LIMIT = null, $offset = null, $count = null) Return a range of members in a sorted set, by score. <https://redis.io/commands/zrangebyscore>
 * @method mixed zrank($key, $member) Determine the index of a member in a sorted set. <https://redis.io/commands/zrank>
 * @method mixed zrem($key, ...$members) Remove one or more members from a sorted set. <https://redis.io/commands/zrem>
 * @method mixed zremrangebylex($key, $min, $max) Remove all members in a sorted set between the given lexicographical range. <https://redis.io/commands/zremrangebylex>
 * @method mixed zremrangebyrank($key, $start, $stop) Remove all members in a sorted set within the given indexes. <https://redis.io/commands/zremrangebyrank>
 * @method mixed zremrangebyscore($key, $min, $max) Remove all members in a sorted set within the given scores. <https://redis.io/commands/zremrangebyscore>
 * @method mixed zrevrange($key, $start, $stop, $WITHSCORES = null) Return a range of members in a sorted set, by index, with scores ordered from high to low. <https://redis.io/commands/zrevrange>
 * @method mixed zrevrangebyscore($key, $max, $min, $WITHSCORES = null, $LIMIT = null, $offset = null, $count = null) Return a range of members in a sorted set, by score, with scores ordered from high to low. <https://redis.io/commands/zrevrangebyscore>
 * @method mixed zrevrank($key, $member) Determine the index of a member in a sorted set, with scores ordered from high to low. <https://redis.io/commands/zrevrank>
 * @method mixed zscore($key, $member) Get the score associated with the given member in a sorted set. <https://redis.io/commands/zscore>
 * @method mixed zunionstore($destination, $numkeys, $key, ...$options) Add multiple sorted sets and store the resulting sorted set in a new key. <https://redis.io/commands/zunionstore>
 * @method mixed scan($cursor, $MATCH = null, $pattern = null, $COUNT = null, $count = null) Incrementally iterate the keys space. <https://redis.io/commands/scan>
 * @method mixed sscan($key, $cursor, $MATCH = null, $pattern = null, $COUNT = null, $count = null) Incrementally iterate Set elements. <https://redis.io/commands/sscan>
 * @method mixed hscan($key, $cursor, $MATCH = null, $pattern = null, $COUNT = null, $count = null) Incrementally iterate hash fields and associated values. <https://redis.io/commands/hscan>
 * @method mixed zscan($key, $cursor, $MATCH = null, $pattern = null, $COUNT = null, $count = null) Incrementally iterate sorted sets elements and associated scores. <https://redis.io/commands/zscan>
 *
 * @property string $driverName Name of the DB driver. This property is read-only.
 * @property bool $isActive Whether the DB connection is established. This property is read-only.
 * @property LuaScriptBuilder $luaScriptBuilder This property is read-only.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class Connection extends Component
{
    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';

    /**
     * @var string the hostname or ip address to use for connecting to the redis server. Defaults to 'localhost'.
     * If [[unixSocket]] is specified, hostname and port will be ignored.
     */
    public $hostname = 'localhost';
    /**
     * @var integer the port to use for connecting to the redis server. Default port is 6379.
     * If [[unixSocket]] is specified, hostname and port will be ignored.
     */
    public $port = 6379;
    /**
	* 字符串，unix套接字路径，（比如/var/run/redis/redis.sock),也是连接redis服务器的一种方式
     * @var string the unix socket path (e.g. `/var/run/redis/redis.sock`) to use for connecting to the redis server.
     * This can be used instead of [[hostname]] and [[port]] to connect to the server using a unix socket.
	 如果套接字指定了，那主机名端口的方式就会忽略
     * If a unix socket path is specified, [[hostname]] and [[port]] will be ignored.
     * @since 2.0.1
     */
    public $unixSocket;
    /**
     * @var string the password for establishing DB connection. Defaults to null meaning no AUTH command is sent.
     * See http://redis.io/commands/auth
     */
    public $password;
    /**
     * @var integer the redis database to use. This is an integer value starting from 0. Defaults to 0.
     * Since version 2.0.6 you can disable the SELECT command sent after connection by setting this property to `null`.
     */
    public $database = 0;
    /**支持浮点数，连接redis服务器的超时时间。若不设置则从php.ini里读取ini_get("default_socket_timeout")
     * @var float timeout to use for connection to redis. If not set the timeout set in php.ini will be used: `ini_get("default_socket_timeout")`.
     */
    public $connectionTimeout = null;
    /**操作超时时间，浮点数。也就是向redis存入数据或者从redis获取数据超时的时间。php默认值，是哪个呢？稍后再续。
     * @var float timeout to use for redis socket when reading and writing data. If not set the php default value will be used.
     */
    public $dataTimeout = null;
    /**
	* 因为是使用php的stream_socket_client()函数连接，所以下面的是配置该函数的参数
     * @var integer Bitmask field which may be set to any combination of connection flags passed to [stream_socket_client()](http://php.net/manual/en/function.stream-socket-client.php).
     * Currently the select of connection flags is limited to `STREAM_CLIENT_CONNECT` (default), `STREAM_CLIENT_ASYNC_CONNECT` and `STREAM_CLIENT_PERSISTENT`.
     * @see http://php.net/manual/en/function.stream-socket-client.php
     * @since 2.0.5
     */
    public $socketClientFlags = STREAM_CLIENT_CONNECT;
    /**
	* 当前redis支持哪些命令呢？还专门弄了个列表。
     * @var array List of available redis commands.
     * @see http://redis.io/commands
     */
    public $redisCommands = [
        'APPEND', // Append a value to a key
        'AUTH', // Authenticate to the server
        'BGREWRITEAOF', // Asynchronously rewrite the append-only file
        'BGSAVE', // Asynchronously save the dataset to disk
        'BITCOUNT', // Count set bits in a string
        'BITFIELD', // Perform arbitrary bitfield integer operations on strings
        'BITOP', // Perform bitwise operations between strings
        'BITPOS', // Find first bit set or clear in a string
        'BLPOP', // Remove and get the first element in a list, or block until one is available
        'BRPOP', // Remove and get the last element in a list, or block until one is available
        'BRPOPLPUSH', // Pop a value from a list, push it to another list and return it; or block until one is available
        'CLIENT KILL', // Kill the connection of a client
        'CLIENT LIST', // Get the list of client connections
        'CLIENT GETNAME', // Get the current connection name
        'CLIENT PAUSE', // Stop processing commands from clients for some time
        'CLIENT REPLY', // Instruct the server whether to reply to commands
        'CLIENT SETNAME', // Set the current connection name
        'CLUSTER ADDSLOTS', // Assign new hash slots to receiving node
        'CLUSTER COUNTKEYSINSLOT', // Return the number of local keys in the specified hash slot
        'CLUSTER DELSLOTS', // Set hash slots as unbound in receiving node
        'CLUSTER FAILOVER', // Forces a slave to perform a manual failover of its master.
        'CLUSTER FORGET', // Remove a node from the nodes table
        'CLUSTER GETKEYSINSLOT', // Return local key names in the specified hash slot
        'CLUSTER INFO', // Provides info about Redis Cluster node state
        'CLUSTER KEYSLOT', // Returns the hash slot of the specified key
        'CLUSTER MEET', // Force a node cluster to handshake with another node
        'CLUSTER NODES', // Get Cluster config for the node
        'CLUSTER REPLICATE', // Reconfigure a node as a slave of the specified master node
        'CLUSTER RESET', // Reset a Redis Cluster node
        'CLUSTER SAVECONFIG', // Forces the node to save cluster state on disk
        'CLUSTER SETSLOT', // Bind a hash slot to a specific node
        'CLUSTER SLAVES', // List slave nodes of the specified master node
        'CLUSTER SLOTS', // Get array of Cluster slot to node mappings
        'COMMAND', // Get array of Redis command details
        'COMMAND COUNT', // Get total number of Redis commands
        'COMMAND GETKEYS', // Extract keys given a full Redis command
        'COMMAND INFO', // Get array of specific Redis command details
        'CONFIG GET', // Get the value of a configuration parameter
        'CONFIG REWRITE', // Rewrite the configuration file with the in memory configuration
        'CONFIG SET', // Set a configuration parameter to the given value
        'CONFIG RESETSTAT', // Reset the stats returned by INFO
        'DBSIZE', // Return the number of keys in the selected database
        'DEBUG OBJECT', // Get debugging information about a key
        'DEBUG SEGFAULT', // Make the server crash
        'DECR', // Decrement the integer value of a key by one
        'DECRBY', // Decrement the integer value of a key by the given number
        'DEL', // Delete a key
        'DISCARD', // Discard all commands issued after MULTI
        'DUMP', // Return a serialized version of the value stored at the specified key.
        'ECHO', // Echo the given string
        'EVAL', // Execute a Lua script server side
        'EVALSHA', // Execute a Lua script server side
        'EXEC', // Execute all commands issued after MULTI
        'EXISTS', // Determine if a key exists
        'EXPIRE', // Set a key's time to live in seconds
        'EXPIREAT', // Set the expiration for a key as a UNIX timestamp
        'FLUSHALL', // Remove all keys from all databases
        'FLUSHDB', // Remove all keys from the current database
        'GEOADD', // Add one or more geospatial items in the geospatial index represented using a sorted set
        'GEOHASH', // Returns members of a geospatial index as standard geohash strings
        'GEOPOS', // Returns longitude and latitude of members of a geospatial index
        'GEODIST', // Returns the distance between two members of a geospatial index
        'GEORADIUS', // Query a sorted set representing a geospatial index to fetch members matching a given maximum distance from a point
        'GEORADIUSBYMEMBER', // Query a sorted set representing a geospatial index to fetch members matching a given maximum distance from a member
        'GET', // Get the value of a key
        'GETBIT', // Returns the bit value at offset in the string value stored at key
        'GETRANGE', // Get a substring of the string stored at a key
        'GETSET', // Set the string value of a key and return its old value
        'HDEL', // Delete one or more hash fields
        'HEXISTS', // Determine if a hash field exists
        'HGET', // Get the value of a hash field
        'HGETALL', // Get all the fields and values in a hash
        'HINCRBY', // Increment the integer value of a hash field by the given number
        'HINCRBYFLOAT', // Increment the float value of a hash field by the given amount
        'HKEYS', // Get all the fields in a hash
        'HLEN', // Get the number of fields in a hash
        'HMGET', // Get the values of all the given hash fields
        'HMSET', // Set multiple hash fields to multiple values
        'HSET', // Set the string value of a hash field
        'HSETNX', // Set the value of a hash field, only if the field does not exist
        'HSTRLEN', // Get the length of the value of a hash field
        'HVALS', // Get all the values in a hash
        'INCR', // Increment the integer value of a key by one
        'INCRBY', // Increment the integer value of a key by the given amount
        'INCRBYFLOAT', // Increment the float value of a key by the given amount
        'INFO', // Get information and statistics about the server
        'KEYS', // Find all keys matching the given pattern
        'LASTSAVE', // Get the UNIX time stamp of the last successful save to disk
        'LINDEX', // Get an element from a list by its index
        'LINSERT', // Insert an element before or after another element in a list
        'LLEN', // Get the length of a list
        'LPOP', // Remove and get the first element in a list
        'LPUSH', // Prepend one or multiple values to a list
        'LPUSHX', // Prepend a value to a list, only if the list exists
        'LRANGE', // Get a range of elements from a list
        'LREM', // Remove elements from a list
        'LSET', // Set the value of an element in a list by its index
        'LTRIM', // Trim a list to the specified range
        'MGET', // Get the values of all the given keys
        'MIGRATE', // Atomically transfer a key from a Redis instance to another one.
        'MONITOR', // Listen for all requests received by the server in real time
        'MOVE', // Move a key to another database
        'MSET', // Set multiple keys to multiple values
        'MSETNX', // Set multiple keys to multiple values, only if none of the keys exist
        'MULTI', // Mark the start of a transaction block
        'OBJECT', // Inspect the internals of Redis objects
        'PERSIST', // Remove the expiration from a key
        'PEXPIRE', // Set a key's time to live in milliseconds
        'PEXPIREAT', // Set the expiration for a key as a UNIX timestamp specified in milliseconds
        'PFADD', // Adds the specified elements to the specified HyperLogLog.
        'PFCOUNT', // Return the approximated cardinality of the set(s) observed by the HyperLogLog at key(s).
        'PFMERGE', // Merge N different HyperLogLogs into a single one.
        'PING', // Ping the server
        'PSETEX', // Set the value and expiration in milliseconds of a key
        'PSUBSCRIBE', // Listen for messages published to channels matching the given patterns
        'PUBSUB', // Inspect the state of the Pub/Sub subsystem
        'PTTL', // Get the time to live for a key in milliseconds
        'PUBLISH', // Post a message to a channel
        'PUNSUBSCRIBE', // Stop listening for messages posted to channels matching the given patterns
        'QUIT', // Close the connection
        'RANDOMKEY', // Return a random key from the keyspace
        'READONLY', // Enables read queries for a connection to a cluster slave node
        'READWRITE', // Disables read queries for a connection to a cluster slave node
        'RENAME', // Rename a key
        'RENAMENX', // Rename a key, only if the new key does not exist
        'RESTORE', // Create a key using the provided serialized value, previously obtained using DUMP.
        'ROLE', // Return the role of the instance in the context of replication
        'RPOP', // Remove and get the last element in a list
        'RPOPLPUSH', // Remove the last element in a list, prepend it to another list and return it
        'RPUSH', // Append one or multiple values to a list
        'RPUSHX', // Append a value to a list, only if the list exists
        'SADD', // Add one or more members to a set
        'SAVE', // Synchronously save the dataset to disk
        'SCARD', // Get the number of members in a set
        'SCRIPT DEBUG', // Set the debug mode for executed scripts.
        'SCRIPT EXISTS', // Check existence of scripts in the script cache.
        'SCRIPT FLUSH', // Remove all the scripts from the script cache.
        'SCRIPT KILL', // Kill the script currently in execution.
        'SCRIPT LOAD', // Load the specified Lua script into the script cache.
        'SDIFF', // Subtract multiple sets
        'SDIFFSTORE', // Subtract multiple sets and store the resulting set in a key
        'SELECT', // Change the selected database for the current connection
        'SET', // Set the string value of a key
        'SETBIT', // Sets or clears the bit at offset in the string value stored at key
        'SETEX', // Set the value and expiration of a key
        'SETNX', // Set the value of a key, only if the key does not exist
        'SETRANGE', // Overwrite part of a string at key starting at the specified offset
        'SHUTDOWN', // Synchronously save the dataset to disk and then shut down the server
        'SINTER', // Intersect multiple sets
        'SINTERSTORE', // Intersect multiple sets and store the resulting set in a key
        'SISMEMBER', // Determine if a given value is a member of a set
        'SLAVEOF', // Make the server a slave of another instance, or promote it as master
        'SLOWLOG', // Manages the Redis slow queries log
        'SMEMBERS', // Get all the members in a set
        'SMOVE', // Move a member from one set to another
        'SORT', // Sort the elements in a list, set or sorted set
        'SPOP', // Remove and return one or multiple random members from a set
        'SRANDMEMBER', // Get one or multiple random members from a set
        'SREM', // Remove one or more members from a set
        'STRLEN', // Get the length of the value stored in a key
        'SUBSCRIBE', // Listen for messages published to the given channels
        'SUNION', // Add multiple sets
        'SUNIONSTORE', // Add multiple sets and store the resulting set in a key
        'SWAPDB', // Swaps two Redis databases
        'SYNC', // Internal command used for replication
        'TIME', // Return the current server time
        'TOUCH', // Alters the last access time of a key(s). Returns the number of existing keys specified.
        'TTL', // Get the time to live for a key
        'TYPE', // Determine the type stored at key
        'UNSUBSCRIBE', // Stop listening for messages posted to the given channels
        'UNLINK', // Delete a key asynchronously in another thread. Otherwise it is just as DEL, but non blocking.
        'UNWATCH', // Forget about all watched keys
        'WAIT', // Wait for the synchronous replication of all the write commands sent in the context of the current connection
        'WATCH', // Watch the given keys to determine execution of the MULTI/EXEC block
        'ZADD', // Add one or more members to a sorted set, or update its score if it already exists
        'ZCARD', // Get the number of members in a sorted set
        'ZCOUNT', // Count the members in a sorted set with scores within the given values
        'ZINCRBY', // Increment the score of a member in a sorted set
        'ZINTERSTORE', // Intersect multiple sorted sets and store the resulting sorted set in a new key
        'ZLEXCOUNT', // Count the number of members in a sorted set between a given lexicographical range
        'ZRANGE', // Return a range of members in a sorted set, by index
        'ZRANGEBYLEX', // Return a range of members in a sorted set, by lexicographical range
        'ZREVRANGEBYLEX', // Return a range of members in a sorted set, by lexicographical range, ordered from higher to lower strings.
        'ZRANGEBYSCORE', // Return a range of members in a sorted set, by score
        'ZRANK', // Determine the index of a member in a sorted set
        'ZREM', // Remove one or more members from a sorted set
        'ZREMRANGEBYLEX', // Remove all members in a sorted set between the given lexicographical range
        'ZREMRANGEBYRANK', // Remove all members in a sorted set within the given indexes
        'ZREMRANGEBYSCORE', // Remove all members in a sorted set within the given scores
        'ZREVRANGE', // Return a range of members in a sorted set, by index, with scores ordered from high to low
        'ZREVRANGEBYSCORE', // Return a range of members in a sorted set, by score, with scores ordered from high to low
        'ZREVRANK', // Determine the index of a member in a sorted set, with scores ordered from high to low
        'ZSCORE', // Get the score associated with the given member in a sorted set
        'ZUNIONSTORE', // Add multiple sorted sets and store the resulting sorted set in a new key
        'SCAN', // Incrementally iterate the keys space
        'SSCAN', // Incrementally iterate Set elements
        'HSCAN', // Incrementally iterate hash fields and associated values
        'ZSCAN', // Incrementally iterate sorted sets elements and associated scores
    ];

    /**
     * @var resource redis socket connection
     */
    private $_socket = false;


    /**
	* 魔术方法__sleep何时被调用？就是在序列化时被php自动调用，
	调用时，返回的结果给出序列对象需要序列化的属性，某些不需要的则在反序列化时用默认值
	这是一个异步保存对象的方式，保存好的对象可以网络传输。
     * Closes the connection when this component is being serialized.
     * @return array
     */
    public function __sleep()
    {
        $this->close();
        return array_keys(get_object_vars($this));
    }

    /**
	* 是否已经建立了连接，所谓已经建立了连接，就是_socket有连接资源
     * Returns a value indicating whether the DB connection is established.
     * @return bool whether the DB connection is established
     */
    public function getIsActive()
    {
        return $this->_socket !== false;
    }

    /**
	* 从无到有建立redis连接的过程
     * Establishes a DB connection.
     * It does nothing if a DB connection has already been established.
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->_socket !== false) {
            return;
        }
		//使用新版的三元运算符?:，优先判断套接字unixSocket
        $connection = ($this->unixSocket ?: $this->hostname . ':' . $this->port) . ', database=' . $this->database;
        \Yii::trace('Opening redis DB connection: ' . $connection, __METHOD__);
		
		//stream_socket_client函数，建立连接时需要给出五个参数
        $this->_socket = @stream_socket_client(
			//要不就是unix://xxxxx，要不就是tcp://host:port，属于流封装协议吗？
            $this->unixSocket ? 'unix://' . $this->unixSocket : 'tcp://' . $this->hostname . ':' . $this->port,
            $errorNumber,//连接失败时，系统界别的错误号
            $errorDescription,//连接失败时，错误信息
            $this->connectionTimeout ? $this->connectionTimeout : ini_get('default_socket_timeout'),//超时时间
            $this->socketClientFlags//连接选项（详情请看手册）
        );
		//建立了连接之后，接下来看看有什么要干的呢？
        if ($this->_socket) {
			//设置存取的超时（与连接超时不是一回事）
            if ($this->dataTimeout !== null) {
				//需要三个参数，超时需要两个参数，一个是秒，一个是毫秒，两个相加之和才是超时的时间
                stream_set_timeout($this->_socket, $timeout = (int) $this->dataTimeout, (int) (($this->dataTimeout - $timeout) * 1000000));
            }
			//是否需要认证？
            if ($this->password !== null) {
                $this->executeCommand('AUTH', [$this->password]);
            }
			//是否指定了数据库？
            if ($this->database !== null) {
                $this->executeCommand('SELECT', [$this->database]);
            }
			//初始化连接（目前没啥实现的，主要留给开发人员做事件响应处理）
            $this->initConnection();
        } else {
            \Yii::error("Failed to open redis DB connection ($connection): $errorNumber - $errorDescription", __CLASS__);
            $message = YII_DEBUG ? "Failed to open redis DB connection ($connection): $errorNumber - $errorDescription" : 'Failed to open DB connection.';
            throw new Exception($message, $errorDescription, $errorNumber);
        }
    }

    /**
	* 关闭当前的redis连接
     * Closes the currently active DB connection.
     * It does nothing if the connection is already closed.
     */
    public function close()
    {
        if ($this->_socket !== false) {
            $connection = ($this->unixSocket ?: $this->hostname . ':' . $this->port) . ', database=' . $this->database;
            \Yii::trace('Closing DB connection: ' . $connection, __METHOD__);
			//断开连接，就是执行QUIT命令
            $this->executeCommand('QUIT');
			//然后关闭本地的php连接资源
            stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);
            $this->_socket = false;
        }
    }

    /**触发了个事件而已，没啥可做的目前
     * Initializes the DB connection.
     * This method is invoked right after the DB connection is established.
     * The default implementation triggers an [[EVENT_AFTER_OPEN]] event.
     */
    protected function initConnection()
    {
        $this->trigger(self::EVENT_AFTER_OPEN);
    }

    /**
     * Returns the name of the DB driver for the current [[dsn]].
     * @return string name of the DB driver
     */
    public function getDriverName()
    {
        return 'redis';
    }

    /**
	* 返回Lua脚本构造器（Lua脚本是redis服务器支持的命令脚本语言）
     * @return LuaScriptBuilder
     */
    public function getLuaScriptBuilder()
    {
        return new LuaScriptBuilder();
    }

    /**
	* 这个魔术方法非常重要，当调用连接类不存在的方法时，最后会调用到__call()方法。并且传递方法名和参数。
	这里就是利用这个原理执行redis的命令。而支持的命令早已在redisCommands里列出来了。
	如果不在列表里，则继续往上找
     * Allows issuing all supported commands via magic methods.
     *
     * ```php
     * $redis->hmset('test_collection', 'key1', 'val1', 'key2', 'val2')
     * ```
     *
     * @param string $name name of the missing method to execute
     * @param array $params method call arguments
     * @return mixed
     */
    public function __call($name, $params)
    {
		//命令拆分，且转换成大写的。比如PostTag转换成"POST TAG"
        $redisCommand = strtoupper(Inflector::camel2words($name, false));
        if (in_array($redisCommand, $this->redisCommands)) {
            return $this->executeCommand($redisCommand, $params);
        } else {
			//父级的组件
            return parent::__call($name, $params);
        }
    }

    /**执行redis命令，注意，这不是命令行执行，而是网络协议。命令格式有要求
     * Executes a redis command.
     * For a list of available commands and their parameters see http://redis.io/commands.
     *
	 *执行命令时的参数，参数之间用空格隔开。比如SET mykey somevalue NX。
	 则调用时的格式是：$redis->executeCommand('SET',['mykey','somevalue','NX']);
     * The params array should contain the params separated by white space, e.g. to execute
     * `SET mykey somevalue NX` call the following:
     *
     * ```php
     * $redis->executeCommand('SET', ['mykey', 'somevalue', 'NX']);
     * ```
     *
     * @param string $name the name of the command  参数1是redis命令名
     * @param array $params list of parameters for the command  参数2是redis命令的参数
     * @return array|bool|null|string Dependent on the executed command this method
     * will return different data types:
     *返回的类型，有以下这些个：
     * - `true` for commands that return "status reply" with the message `'OK'` or `'PONG'`. 返回真。
     * - `string` for commands that return "status reply" that does not have the message `OK` (since version 2.0.1).
	 状态字符串
     * - `string` for commands that return "integer reply"
     *   as the value is in the range of a signed 64 bit integer.整数字符串
     * - `string` or `null` for commands that return "bulk reply".字符串
     * - `array` for commands that return "Multi-bulk replies".数组
     *
     * See [redis protocol description](http://redis.io/topics/protocol)
     * for details on the mentioned reply types.
     * @throws Exception for commands that return [error reply](http://redis.io/topics/protocol#error-reply).
     */
    public function executeCommand($name, $params = [])
    {
        $this->open();
		/*
			如果要看明白下面的操作，你一定得知道redis协议和redis命令才行。光知道redis命令不行，因为redis命令
			好多都是针对命令行执行的，这也是我们初学时的重点。而下面的操作是在建立网络tcp连接的基础上，由A端向
			redis端发送redis命令，redis端执行，返回响应结果到A端。这如何发送命令，格式是什么？响应的格式又有哪些？这不就是cs通讯网络协议的内容嘛？是的，redis也有协议。http://www.redis.cn/topics/protocol.html
			星号开头的是参数的数量
			美元符开头的是每个参数
			比如SET mykey  myvalue。使用网络协议传送的话，最终组成的字符串的格式如下：
			*3\r\n
			$3\r\nSET\r\n
			$5\r\nmykey\r\n
			$7\r\nmyvalue\r\n
			统一为一个字符串就是："*3\r\n$3\r\nSET\r\n$5\r\nmykey\r\n$7\r\nmyvalue\r\n"
		*/
		//把命令和参数合并到一个数组里
        $params = array_merge(explode(' ', $name), $params);
		//数组元素的个数
        $command = '*' . count($params) . "\r\n";
		//每个元素的长度，及每个元素本身字符,比如："$3\r\nSET\r\n"
        foreach ($params as $arg) {
            $command .= '$' . mb_strlen($arg, '8bit') . "\r\n" . $arg . "\r\n";
        }

        \Yii::trace("Executing Redis Command: {$name}", __METHOD__);
		//向连接中输入命令
        fwrite($this->_socket, $command);
		//解析redis服务器的响应，参数以空格连接成字符串
        return $this->parseResponse(implode(' ', $params));
    }

    /**
	* 解析还真得看看官网的redis协议，才能明白。
     * @param string $command
     * @return mixed
     * @throws Exception on error
     */
    private function parseResponse($command)
    {
		//尝试读取响应，否则就抛异常，这可是大事。
		//fgets读取的内容有三种情况：碰到换行符，碰到EOF,读取长度为1024-1字节。
		//看先遇到哪种情况吧。
        if (($line = fgets($this->_socket)) === false) {
            throw new Exception("Failed to read from socket.\nRedis command was: " . $command);
        }
		/*
			同样，如果不熟悉redis协议，而只知道redis命令，你会怀疑为什么这样解析redis的响应。
			redis根据响应（回复）的不同，设置了如下五种类型，可以从响应的第一个字节来判断。
			1用单行回复，也叫状态回复，回复的第一个字节将是“+”
			2错误消息，回复的第一个字节将是“-”
			3整型数字，回复的第一个字节将是“:”
			4批量回复，回复的第一个字节将是“$”
			5多个批量回复，回复的第一个字节将是“*”
			
		*/
        $type = $line[0];
		//php官网手册没有说明mb_substr函数第三个参数是负数的情况。
		//这里通过计算结果反推，是获得$line从第二位开始到倒数第三位为止的子串
        $line = mb_substr($line, 1, -2, '8bit');
		//分支判断响应的第一个字符
        switch ($type) {
            case '+': // Status reply  加号，说明是状态响应
                if ($line === 'OK' || $line === 'PONG') {
                    return true;
                } else {
                    return $line;
                }
            case '-': // Error reply 减号，说明是报错反馈
                throw new Exception("Redis error: " . $line . "\nRedis command was: " . $command);
            case ':': // Integer reply  冒号，直接返回。这样的命令有INCR,MOVE,SADD,DEL等
                // no cast to int as it is in the range of a signed 64 bit integer
                return $line;
            case '$': // Bulk replies  美元符。块反馈，一般需要多次读取
				/*
					如下是一个客户端命令，服务端响应的例子：
					C: GET mykey
					S: $6\r\nfoobar\r\n

					根据fgets读取响应的规则，肯定是碰到了换行符才读取完毕的，也就是说，fgets读取的是:$6\r\n
				*/
				//-1表示请求的值不存在
                if ($line == '-1') {
                    return null;
                }
                $length = (int)$line + 2;//为啥加2，是因为实际数据后面有个\r\n。也就是说，下次读取应该是foobar\r\n。
                $data = '';
				//循环读取
                while ($length > 0) {
					//按照$length指定的长度，读取一定的字节
                    if (($block = fread($this->_socket, $length)) === false) {
                        throw new Exception("Failed to read from socket.\nRedis command was: " . $command);
                    }
                    $data .= $block;
					//减去刚刚读取的数据长度，方便下回再读。
					//正常情况下，一次就读取$length字节就完毕。但是有可能上一步的fread并不如期待那般真正读取到了
					//$length个字节。而是提前遇到了换行符，EOF也是有可能的。故这种情况下，需要判断length是否为0，多次读取。
                    $length -= mb_strlen($block, '8bit');
                }
				//始终排除掉最后那两个换行符
                return mb_substr($data, 0, -2, '8bit');
            case '*': // Multi-bulk replies  多块响应。
				/*
					有的命令可以明显的返回多行数据，比如：
					C: LRANGE mylist 0 3
					s: *4
					s: $3
					s: foo
					s: $3
					s: bar
					s: $5
					s: Hello
					s: $5
					s: World
					这种情况总是以*号开头，后面的数字4表示后续有四个值。
					如果指定的key比如mylist不存在，则返回*0\r\n。
					如果命令在服务端执行超时，则返回*-1\r\n
				*/
				

                $count = (int) $line;
                $data = [];
				//key不存在，或者命令超时都不会进入循环。
                for ($i = 0; $i < $count; $i++) {
					//递归调用自己，这时候，应该就是$开头的那些整型返回值了
                    $data[] = $this->parseResponse($command);
                }

                return $data;
            default:
                throw new Exception('Received illegal data from redis: ' . $line . "\nRedis command was: " . $command);
        }
    }
}
