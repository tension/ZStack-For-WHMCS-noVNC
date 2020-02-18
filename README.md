# ZStack-For-WHMCS-noVNC
这是专属给 ZStack For WHMCS 插件使用的 noVNC 模块

### VNC 服务器建立方法
**以 CentOS 7 安装了宝塔的服务器为例：

安全里添加 6080 端口 VNC 监听服务端口

建立站点 例如 ```vnc.show``` 如果使用http则不需要设置证书，如果需要 https 那就设置证书

**手动上传压缩包内的文件网站根目录或：
```sh
yum install -y git
git clone git://github.com/tension/ZStack-For-WHMCS-noVNC.git
```

**然后 SSH 进入到网站目录执行下面的代码。

运行完关掉 SSH 即可，会在后台监听。

```sh
# 安装依赖服务
yum install screen tigervnc-server -y
#启动vncserver
vncserver
#创建Screen
screen -S VNCServer
```
请自行替换证书文件和密钥文件

HTTPS
```sh
#运行noVNC监听服务 SSL 版,请自行替换证书、密钥路径
./websockify/run --token-plugin TokenFile --token-source ../token/ --cert /www/server/panel/vhost/cert/vnc.show/fullchain.pem --key /www/server/panel/vhost/cert/vnc.show/privkey.pem 6080
```
HTTP
```sh
#运行noVNC监听服务 NO SSL 版
./websockify/run --token-plugin TokenFile --token-source ../token/ 6080
```
### 增加粘贴功能 2020-02-16 07:40
![粘贴前](https://cdn.modulesocean.com/l8crw.png)

![粘贴后](https://cdn.modulesocean.com/5ebjl.png)


### ZStack For WHMCS 模块插件

![详情](https://cdn.modulesocean.com/jj77b.jpg)

![快照](https://cdn.modulesocean.com/urbsn.jpg)

![重装系统](https://cdn.modulesocean.com/hynz0.jpg)

![产品设置](https://cdn.modulesocean.com/yshrd.png)

*ModulesOcean 为您提供一站式的全方位服务，有兴趣的可查阅 https://modulesocean.com*