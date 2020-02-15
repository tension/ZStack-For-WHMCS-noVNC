# ZStack-For-WHMCS-noVNC
这是专属给 ZStack For WHMCS 插件使用的 noVNC 模块

### VNC 服务器建立方法
任意服务器即可，以宝塔为例：
安全设置打开 6080 端口 VNC 服务端口
建立站点 例如 vnc.show ，如果使用http则不需要设置证书，如果需要 https 那就设置证书
上传压缩包内的文件到网站文件，然后 SSH 进入到网站目录 执行下面的代码。
运行完关掉 SSH 即可，会在后台监听。

```sh
# 安装依赖服务
yum install screen tigervnc-server -y
#启动vncserver
vncserver
#创建Screen
screen -S VNCServer
```
```sh
#运行noVNC监听服务 SSL 版,请自行替换证书、密钥路径
./websockify/run --token-plugin TokenFile --token-source ../token/ --cert /www/server/panel/vhost/cert/vnc.show/fullchain.pem --key /www/server/panel/vhost/cert/vnc.show/privkey.pem 6080
```
```sh
#运行noVNC监听服务 NO SSL 版
./websockify/run --token-plugin TokenFile --token-source ../token/ 6080
```
### 增加粘贴功能 2020-02-16 07:40
![粘贴前](https://cdn.modulesocean.com/l8crw.png)

![粘贴后](https://cdn.modulesocean.com/5ebjl.png)
