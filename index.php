<?php
$compiledir = __DIR__ . '/token/';
$tokenname = $_GET['token'];
$path = '?token='.$tokenname;

function removeExpireFile($compiledir, $file) {
	$filename = explode(",", base64_decode($file));
	$expiredata = $filename[2];
	if (!file_exists($compiledir.$file)) {
		if ( $expiredata < time() ) {
			unlink($compiledir.$file);
		}
	}
}

try {
	$serverIP = $_SERVER['SERVER_NAME'];
	
	// 遍历目录删除到期文件
	$files = scandir($compiledir);
	foreach ($files as $v) {
	    $newPath = $compiledir . DIRECTORY_SEPARATOR . $v;
	    if ( is_file( $newPath ) ) {
	        removeExpireFile($compiledir, $v);
	    }
	}
	
	$filepath = $compiledir . $tokenname;
	$token = explode(",", base64_decode($tokenname));
	$vncip = $token[0];
	$vncport = $token[1];
	$expiredata = $token[2];
	$serviceid = $token[3];
	
	// 如果提交的产品ID不存在则报错
	if (empty($serviceid)) throw new Exception('参数错误');
	
	// 如果时间戳大于当前时间则 检查文件是否存在并写入文件
	if ( $expiredata > time() ) {
		// 如果文件不存在则写入
		if (!file_exists($file)) {
			file_put_contents($filepath, $tokenname . ': '. $vncip . ':' . $vncport);
		}
	}
	
} catch (Exception $e) {
    die($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <title>noVNC</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <style type="text/css" media="screen">
        body {
            margin: 0;
            background-color: dimgrey;
            height: 100%;
            display: flex;
            flex-direction: column;
            background-color: #000;
        }
        html {
            height: 100%;
        }
		:focus {
			outline: none;
		}
        #top_bar {
            background-color: #6e84a3;
            color: white;
            font: bold 12px Helvetica;
            padding: 0 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 50px;
        }
        .button {
        	width: 50%;
		    display: flex;
		    align-items: center;
		    flex-direction: row-reverse;
        }
        .btn {
		    border: 1px solid #EEE;
		    padding: 0 10px;
		    cursor: pointer;
		    border-radius: 3px;
		    line-height: 25px;
		    margin-left: 10px;
        }
        #screen {
            flex: 1; /* fill remaining space */
            overflow: hidden;
            padding: 5px;
        }
        .noVNC_vcenter {
	        position: fixed;
	        width: 500px;
	        top: 50%;
	        left: 50%;
	        transform: translate(-50%, -50%);
	        background-color: #FFF;
	        border-radius: 4px;
	        overflow: hidden;
        }
        .noVNC_clipboard_heading {
	        padding: 0 15px;
	        line-height: 40px;
	        background-color: #EEE;
        }
        .noVNC_clipboard_body {
	        padding: 15px;
        }
        .noVNC_clipboard_body textarea {
		    width: 465px;
		    height: 150px;
		    border-radius: 4px;
		    border: 1px solid #CCC;
		    margin-bottom: 10px;
		    display: block;
		    padding: 5px;
        }
        .noVNC_clipboard_body button {
	        cursor: pointer;
	        padding: 5px 15px;
	        border-radius: 4px;
	        border: 1px solid #CCC;
	        background-color: #EEE;
        }

    </style>

    <!-- Promise polyfill for IE11 -->
    <script src="vendor/promise.js"></script>

    <!-- ES2015/ES6 modules polyfill -->
    <script nomodule src="vendor/browser-es-module-loader/dist/browser-es-module-loader.js"></script>
	<script src="https://cdnjs.loli.net/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <!-- actual script modules -->
    <script type="module" crossorigin="anonymous">
        // RFB holds the API to connect and communicate with a VNC server
        import RFB from './core/rfb.js';

        let rfb;
        let desktopName;
        let resizeTimeout;
        
        //Ctrl+Alt+Del命令 rfb.sendCtrlAltDel();
         
        //重启命令	rfb.xvpReboot();
         
        //关机命令	rfb.xvpShutdown();
           
        //注销命令	rfb.xvpReset();
        
        //客户端窗口发生变化,页面刷新
        function UIresize() {
            if (readQueryVariable('resize', false)) {
                var innerW = window.innerWidth;
                var innerH = window.innerHeight;
                var controlbarH = document.getElementById('top_bar').offsetHeight;
                if (innerW !== undefined && innerH !== undefined)
                    rfb.requestDesktopSize(innerW, innerH - controlbarH);
            }
        }
        
        //重绘命令
        window.onresize = function () {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function () {
                UIresize();
            }, 500);
        };
        
        // When this function is called we have
        // successfully connected to a server
        function connectedToServer(e) {
            //status("连接到 " + desktopName);
            status("已连接到 " + title);
        }

        // This function is called when we are disconnected
        function disconnectedFromServer(e) {
            if (e.detail.clean) {
                status("连接已超时");
            } else {
                status("出问题了，连接已关闭");
            }
        }

        // When this function is called, the server requires
        // credentials to authenticate
        function credentialsAreRequired(e) {
            const password = prompt("Password Required:");
            rfb.sendCredentials({ password: password });
        }

        // When this function is called we have received
        // a desktop name from the server
        function updateDesktopName(e) {
            desktopName = e.detail.name;
        }

        // Since most operating systems will catch Ctrl+Alt+Del
        // before they get a chance to be intercepted by the browser,
        // we provide a way to emulate this key sequence.
        function sendCtrlAltDel() {
            rfb.sendCtrlAltDel();
            return false;
        }

        // Show a status text in the top bar
        function status(text) {
            document.getElementById('status').textContent = text;
        }

        // This function extracts the value of one variable from the
        // query string. If the variable isn't defined in the URL
        // it returns the default value instead.
        function readQueryVariable(name, defaultValue) {
            // A URL with a query parameter can look like this:
            // https://www.example.com?myqueryparam=myvalue
            //
            // Note that we use location.href instead of location.search
            // because Firefox < 53 has a bug w.r.t location.search
            const re = new RegExp('.*[?&]' + name + '=([^&#]*)'),
                  match = document.location.href.match(re);

            if (match) {
                // We have to decode the URL since want the cleartext value
                return decodeURIComponent(match[1]);
            }

            return defaultValue;
        }

        document.getElementById('sendCtrlAltDelButton')
            .onclick = sendCtrlAltDel;

        // Read parameters specified in the URL query string
        // By default, use the host and port of server that served this file
        // const host = readQueryVariable('host', window.location.hostname);
        // let port = readQueryVariable('port', window.location.port);
        // const path = readQueryVariable('path', 'websockify');
        const title = readQueryVariable('title', window.location.title);
        document.title = title;
        const host = '<?php echo $serverIP?>';
        let port = '6080';
        const password = readQueryVariable('password');
        const path = '<?php echo $path ?>';

        // | | |         | | |
        // | | | Connect | | |
        // v v v         v v v

        status("正在连接...");

        // Build the websocket URL used to connect
        let url;
        if (window.location.protocol === "https:") {
            url = 'wss';
        } else {
            url = 'ws';
        }
        url += '://' + host;
        if(port) {
            url += ':' + port;
        }
        url += '/' + path;

        // Creating a new RFB object will start a new connection
        rfb = new RFB(document.getElementById('screen'), url,
                      { credentials: { password: password } });

        // Add listeners to important events from the RFB module
        rfb.addEventListener("connect",  connectedToServer);
        rfb.addEventListener("disconnect", disconnectedFromServer);
        rfb.addEventListener("credentialsrequired", credentialsAreRequired);
        rfb.addEventListener("desktopname", updateDesktopName);
        
        document.getElementById("noVNC_clipboard_button")
            .addEventListener('click', toggleClipboardPanel);
            
        document.getElementById("noVNC_clipboard_send_button")
            .addEventListener('click', clipboardSend);
            
        document.getElementById("noVNC_clipboard_clear_button")
            .addEventListener('click', clipboardClear);
        
        function toggleClipboardPanel() {
	        $('.noVNC_vcenter').toggle();	        
	    }
	    
	    window.sendString = function (str) {
	        f(str.split(""));
	        function f(t) {
	            var character = t.shift();
	            var i=[];
	            var code = character.charCodeAt();
	            var needs_shift = character.match(/[A-Z!@#$%^&*()_+{}:\"<>?~|]/);
	            if (needs_shift) {
	                rfb.sendKey(XK_Shift_L,1);
	            }
	            //rfb.sendKey(code,1);
	            rfb.sendKey(code,0);
	            if (needs_shift) {
	                rfb.sendKey(XK_Shift_L,0);
	            }
	            
	            if (t.length > 0) {
	                setTimeout(function() {f(t);}, 10);
	            }
	        }
	    }
            
        function clipboardClear() {
	        $('#noVNC_clipboard_text').val('');
	        rfb.clipboardPasteFrom("");
	    }
	    
	    function clipboardSend() {
	        const text = $('#noVNC_clipboard_text').val();
	        sendString(text);
	        $('.noVNC_vcenter').toggle();
	        $('#noVNC_clipboard_text').val('');
	        
	    }
	    

        // Set parameters that can be changed on an active connection
        rfb.viewOnly = readQueryVariable('view_only', false);
        rfb.scaleViewport = readQueryVariable('scale', false);
    </script>
</head>

<body>
    <div id="top_bar">
        <div id="status">载入中...</div>
        <div class="button">
        	<div class="btn" id="sendCtrlAltDelButton">发送 Ctrl+Alt+Del</div>
        	<div class="btn" onclick="location.reload();">重新连接</div>
        	<div class="btn" id="noVNC_clipboard_button">复制粘贴</div>
        </div>
    </div>
    <div id="screen">
        <!-- This is where the remote screen will appear -->
    </div>
	<div class="noVNC_vcenter" style="display: none;">
        <div id="noVNC_clipboard" class="noVNC_panel">
		    <div class="noVNC_clipboard_heading">粘贴内容</div>
		    <div class="noVNC_clipboard_body">
		    	<textarea id="noVNC_clipboard_text" autofocus="autofocus" rows="5"></textarea>
			    <button id="noVNC_clipboard_clear_button" type="button">清除内容</button>
			    <button id="noVNC_clipboard_send_button" type="button">发送内容</button>
		    </div>
		</div>
    </div>
</body>
</html>
