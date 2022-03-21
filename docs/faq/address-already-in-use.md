# 启动时报错 Address already in use

1. 检查是否开启了两次框架，每个端口只能开启一个框架。
2. 如果是之前已经在 20001 端口或者你设置了别的应用同样占用此端口，更换配置文件 `global.php` 中的 port 即可。
3. 如果是之前框架成功启动，但是使用 Ctrl+C 停止后再次启动导致的报错，请根据下面的步骤来检查是否存在僵尸进程。

- 如果系统内装有 `htop`，可以直接在 `htop` 中开启 Tree 模式并使用 filter 过滤 php，检查残留的框架进程。
- 如果系统没有 `htop`，使用 `ps aux | grep vendor/bin/start | grep -v grep` 如果存在进程，请使用以下命令尝试杀掉：
  
```bash
# 如果确定框架的数据都已保存且没有需要保存的缓存数据，直接杀掉 SIGKILL 即可，输入下面这条
ps aux | grep vendor/bin/start | grep -v grep | awk '{print $2}' | xargs kill -9

# 如果不确定框架是不是还继续运行，想尝试正常关闭（走一遍储存保存数据的事件），使用下面这条
# 首先使用 'ps aux | grep vendor/bin/start | grep -v grep' 找到进程中第二列最小的pid
# 然后使用下面的这条命令，假设最小的pid是23643
kill -INT 23643
# 如果使用 ps aux 看不到框架相关进程，证明关闭成功，否则需要使用第一条强行杀死
```
