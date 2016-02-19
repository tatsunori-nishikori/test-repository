# 情報共有OSS(Knowledge)インストール手順

https://support-project.org/knowledge/index


## 事前準備
- webappsユーザ作成

```
$ sudo useradd webapps
```

## 1. Java 1.8のインストール

```
$ sudo yum -y install java-1.8.0-openjdk
```

### 1.7系使わないので削除

```
$ sudo yum -y remove java-1.7.0-openjdk
```

### JAVA_HOMEの設定

```
$ sudo su - webapps
$ echo "JAVA_HOME=`readlink $(readlink $(which java))`" >> ~/.bashrc
```


## 2. tomcat9のインストール

```
$ sudo wget http://ftp.jaist.ac.jp/pub/apache/tomcat/tomcat-9/v9.0.0.M1/bin/apache-tomcat-9.0.0.M1.tar.gz -P /usr/local/src
$ cd /usr/local/lib && 
$ sudo tar xvzf /usr/local/src/apache-tomcat-9.0.0.M1.tar.gz
$ sudo mv apache-tomcat-9.0.0.M1 tomcat-9.0.0
```

### tomcat user追加

```
$ sudo useradd -r tomcat8 --shell /bin/false
```

### tomcat 環境変数設定

/etc/profile.d/tomcat.sh

```
# tomcat environment
export JAVA_HOME=`readlink $(readlink $(which java))`
export PATH=$PATH:$JAVA_HOME/bin
export CATALINA_HOME=/usr/local/lib/tomcat-9.0.0
``` 

### 起動スクリプト

/etc/init.d/tomcat

```
#!/bin/bash

# chkconfig: - 85 15
# description: Tomcat system control
# source function library
. /etc/rc.d/init.d/functions
source /etc/profile.d/tomcat.sh

export JAVA_OPTS="-Dfile.encoding=UTF-8 \
  -Dnet.sf.ehcache.skipUpdateCheck=true \
  -XX:+UseConcMarkSweepGC \
  -XX:+CMSClassUnloadingEnabled \
  -XX:+UseParNewGC \
  -XX:MaxPermSize=128m \
  -Xms512m -Xmx512m"

export TOMCAT_USER=tomcat
export SHUTDOWN_WAIT=5

tomcat_pid() {
  echo `ps aux | grep org.apache.catalina.startup.Bootstrap | grep -v grep | awk '{ print $2 }'`
}

start(){
  pid=$(tomcat_pid)
  if [ -n "$pid" ]; then
    echo "Tomcat is already running"
  else
    echo "Starting tomcat"
    ulimit -n 100000
    umask 007
    sudo -u $TOMCAT_USER $CATALINA_HOME/bin/startup.sh
  fi

  return 0
}

stop(){
  pid=$(tomcat_pid)
  if [ -n "$pid" ]; then
    echo "Shutting down tomcat"
    sudo -u $TOMCAT_USER $CATALINA_HOME/bin/shutdown.sh

    let kwait=$SHUTDOWN_WAIT
    count=0;

    until [ `ps -p $pid | grep -c $pid` = '0' ] || [ $count -gt $kwait ]
    do
      echo -n -e "\nwaiting for processes to exit";
      sleep 1
      let count=$count+1;
    done

    if [ $count -gt $kwait ]; then
      echo -n -e "\nkilling processes which didn't stop after $SHUTDOWN_WAIT seconds"
      kill -9 $pid
    fi

  else
    echo "Tomcat is not running"
  fi
}

case "$1" in
  start)
    start
    ;;
  stop)
    stop
    ;;
  restart)
    stop
    start
    ;;
  status)
    pid=$(tomcat_pid)
    if [ -n "$pid" ]; then
      echo "Tomcat is running with pid: $pid"
    else
      echo "Tomcat is not running"
    fi
    ;;
  *)
    echo "Usage: $0 {start|stop|restart|status}"
esac

exit 0
```

### 設定ファイル

/usr/local/lib/tomcat-9.0.0/conf/context.xml
```
<Context path="/knowledge" docBase="knowledge" reloadable="false" allowLinking="true" />
```

### 起動

```
$ sudo service tomcat start
$ sudo chkconfig tomcat on
```


## 3. プロジェクトwarファイルの設置

```
$ sudo su - webapps
$ mkdir -p www/knowledge/versions/1.0.0
$ cd www/knowledge/versions/1.0.0 wget https://github.com/support-project/knowledge/releases/download/v1.0.0/knowledge.war
$ mkdir -p ~/www/knowledge/current
$ ln -s ~/www/knowledge/versions/1.0.0/knowledge.war ~/www/knowledge/current/
$ sudo ln -s /home/webapps/www/knowledge/current/knowledge.war /usr/local/lib/tomcat-9.0.0/webapps/
$ sudo chown -h tomcat:tomcat /usr/local/lib/tomcat-9.0.0/webapps/knowledge.war
```

