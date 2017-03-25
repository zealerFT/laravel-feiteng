#  Sunallies-agile
光合联萌前台API服务

# 1 安装部署

## 1.1 前置依赖

要求部署环境上已经有如下工具:
> * 1.系统环境:Debian Stable ( wheezy 7.8 )
> * 2.服务器: nginx/1.4.6
> * 3.数据库:mysql/5.5.44
> * 4.php版本 >=5.5.9

## 1.2 安装依赖

### 1.2.1 安装系统工具

在一个干净的debain上安装需要php, php-fpm, curl，composer，redis。
*** 本服务部署以php7.0为例：
```bash
    #安装php环境
    sudo apt-get install php7.0 php7.0-cli php7.0-fpm
    #安装curl
    sudo apt-get install curl
```

### 1.2.2 安装composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 1.2.3 安装php扩展

sunallies-agile服务正常运行需要如下php扩展：Mcrypt/MySQL PDO/CURL/gd。
````bash
sudo apt-get install  php7.0-gd php7.0-json php7.0-mysql php7.0-zip php7.0-bcmath php7.0-readline php7.0-mbstring php7.0-xml php7.0-mcrypt php7.0-curl php7.0-sybase
```

### 1.2.4 安装redis
该项目缓存使用的是redis，故需要安装redis
```bash
apt-get install redis-server
```

## 1.3 部署源码

### 1.3.1 从gitlab上获得项目源码

项目源码托管在内网上，项目名称为： sunallies-agile。

```bash
# 使用master分支
git@code.sunallies.net:aishan/sunallies-agile.git
git checkout master
```
### 1.3.2 项目目录权限配置

storage：  数据存储目录，用于存储项目缓存数据，需要有写入权限.
bootstrap/cache: 目录的写权限
```bash
chmod 777 -R storage/ bootstrap/cache
```

### 1.3.3 composer加载项目扩展依赖

```bash
#切换到网站根目录下
composer install
```
### 1.3.4 nginx服务器配置

```bash
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```
## 1.4 配置文件

先复制配置示例：

```bash
#根目录下找到 .env.example
cp .env.example .env
```
.env即该项目配置文件

### 1.4.1 前后台数据库数据源配置

* API_FRONT_URL为前台数据源接口地址

* API_TA_URL为TA数据API接口地址


### 1.4.2 rmg队列配置
在.env中修改如下配置:
```bash
RMQ_HOST=
RMQ_PORT=
RMQ_USER=
RMQ_PASS=
RMQ_VHOST=
```

## 1.5 部署队列服务和定时任务

### 1.5.1队列服务启用
如果QUEUE_DRIVER使用了非sync的配置，那么需要进行队列的监听，
此时，需要借助supervisor来实现。
#### 安装supervisor
```bash
sudo apt-get install supervisor
```
#### 配置supervisor
在/etc/supervisor/conf.d目录下新建一个文件
```bash
sudo vi  agile-redis-queue-listener.conf
```
写入配置：
```text
[program:agile-redis-queue-listener]
process_name=%(program_name)s_%(process_num)02d
command=php /{站点路径}/sunallies-agile/artisan queue:work --queue pvm.api.profits  --sleep=1 --tries=3 --daemon
autostart=true
autorestart=true
user=root
numprocs=1
redirect_stderr=true
stdout_logfile=/{站点路径}/sunallies-agile/storage/logs/worker.log

```
然后重启supervisor，查看是否有:agile-redis-queue-listener的任务在运行
```bash
#重启
sudo supervisorctl relaod
#查看当前运行任务
sudo supervisorctl
```

### 1.5.2定时任务启用
借助crontab实现，在crontab中增加一条记录：
```bash
* * * * * php /{项目路径}/sunallies-agile/artisan schedule:run 1>> /dev/null 2>&1
```


# 2 更新部署

初次安装完毕后， 如果需要升级，需要执行如下步骤。

## 2.1 依赖更新

需要参考每一次的更新文档

## 2.2 代码更新

在网站的根目录执行：

```bash
git pull --rebase
```


# 3 开发配置

## 3.1 开启调试

在根目录.env文件中修改如下配置即可控制是否开启调试模式:
```bash
APP_DEBUG=false
```

# 4 2017春节活动部署

## 4.1 微信分享

在根目录.env文件中修改微信相关参数:WECHAT_APPID 和 WECHAT_SECRET

## 4.2 开启消息队列监控
## 4.3 数据库迁移
## 4.4 活动时间配置




-