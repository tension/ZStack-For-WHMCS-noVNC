<?php
$compiledir = dirname(dirname(__FILE__)) . '/token/';
$tokenname = $_GET['token'];
$path = '?token='.$tokenname;

function removeExpireFile($compiledir, $file) {
    $filename = explode(",", base64_decode($file));
    $expiredata = $filename[2];
    if (file_exists($compiledir.$file)) {
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
    $type = $token[4];

    // 如果提交的产品ID不存在则报错
    if (empty($serviceid)) throw new Exception('参数错误');
	
    // 如果时间戳大于当前时间则 检查文件是否存在并写入文件
    if ( $expiredata > time() ) {
        // 如果文件不存在则写入
        if (!file_exists($filepath)) {
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

        <title>Spice Web Client</title>
        <!-- ES2015/ES6 modules polyfill -->
        <script type="module">
            window._spice_has_module_support = true;
        </script>
        <script>
            window.addEventListener("load", function() {
                if (window._spice_has_module_support) return;
                var loader = document.createElement("script");
                loader.src = "thirdparty/browser-es-module-loader/dist/" +
                    "browser-es-module-loader.js";
                document.head.appendChild(loader);
            });
        </script>
        <link rel="stylesheet" href="spice.css" type="text/css" media="screen" charset="utf-8">
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
	        #login {
		        width: auto;
			    background-color: #013581;
			    color: white;
			    font: bold 12px Helvetica;
			    padding: 0 10px;
			    display: flex;
			    align-items: center;
			    justify-content: space-between;
			    height: 50px;
			    border: 0 none;
			    border-radius: 0;
			    background-image: none;
			    margin: 0;
	        }
	        .button {
	            width: 40%;
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
	        #spice-area {
	            flex: 1; /* fill remaining space */
	            overflow: hidden;
	            padding: 5px;
	            background-color: #000;
				border: 0 none;
	            border-radius: 0;
	            height: unset;
				width: unset;
				box-shadow: none;
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
	        	display: flex;
	        	align-items: center;
	        	justify-content: space-between;
	            padding: 0 15px;
	            line-height: 40px;
	            background-color: #EEE;
	        }
	        .noVNC_clipboard_heading a {
	        	font-size: 14px;
	        	color: #AAA;
	        	text-decoration: none;
	        }
	        .noVNC_clipboard_body {
	            padding: 15px;
	        }
	        .noVNC_clipboard_body textarea {
	            width: 98%;
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
	        }
	    </style>
        <script type="module" crossorigin="anonymous">
            import * as SpiceHtml5 from './src/main.js';

            var host = null, port = null;
            var sc;

            function spice_set_cookie(name, value, days) {
                var date, expires;
                date = new Date();
                date.setTime(date.getTime() + (days*24*60*60*1000));
                expires = "; expires=" + date.toGMTString();
                document.cookie = name + "=" + value + expires + "; path=/";
            };

            function spice_query_var(name, defvalue) {
                var match = RegExp('[?&]' + name + '=([^&]*)')
                                  .exec(window.location.search);
                return match ?
                    decodeURIComponent(match[1].replace(/\+/g, ' '))
                    : defvalue;
            }

            function spice_error(e)
            {
                disconnect();
                if (e !== undefined && e.message === "Permission denied.") {
                  var pass = prompt("Password");
                  connect(pass);
                }
            }

            function connect(password)
            {
                var host, port, scheme = "ws://", uri;

                // By default, use the host and port of server that served this file
                //host = spice_query_var('host', window.location.hostname);
				host = '<?php echo $serverIP ?>';
                // Note that using the web server port only makes sense
                //  if your web server has a reverse proxy to relay the WebSocket
                //  traffic to the correct destination port.
                var default_port = window.location.port;
                if (!default_port) {
                    if (window.location.protocol == 'http:') {
                        default_port = 80;
                    }
                    else if (window.location.protocol == 'https:') {
                        default_port = 443;
                    }
                }
                //port = spice_query_var('port', default_port);
                port = '6080';
                if (window.location.protocol == 'https:') {
                    scheme = "wss://";
                }
                
                document.title = spice_query_var('title', window.location.title);

                // If a token variable is passed in, set the parameter in a cookie.
                // This is used by nova-spiceproxy.
                //var token = spice_query_var('token', null);
                var token = '<?php echo $tokenname ?>';
                if (token) {
                    spice_set_cookie('token', token, 1)
                }

                if (password === undefined) {
                    password = spice_query_var('password', '');
                }
                //var path = spice_query_var('path', 'websockify');
                var path = '<?php echo $path ?>';

                if ((!host) || (!port)) {
                    console.log("must specify host and port in URL");
                    return;
                }

                if (sc) {
                    sc.stop();
                }

                uri = scheme + host + ":" + port;

                if (path) {
                  uri += path[0] == '/' ? path : ('/' + path);
                }

                try
                {
                    sc = new SpiceHtml5.SpiceMainConn({uri: uri, screen_id: "spice-screen", dump_id: "debug-div",
                                message_id: "message-div", password: password, onerror: spice_error, onagent: agent_connected });
                }
                catch (e)
                {
                    alert(e.toString());
                    disconnect();
                }

            }

            function disconnect()
            {
                console.log(">> disconnect");
                if (sc) {
                    sc.stop();
                }
                if (window.File && window.FileReader && window.FileList && window.Blob)
                {
                    var spice_xfer_area = document.getElementById('spice-xfer-area');
                    if (spice_xfer_area != null) {
                      document.getElementById('spice-area').removeChild(spice_xfer_area);
                    }
                    document.getElementById('spice-area').removeEventListener('dragover', SpiceHtml5.handle_file_dragover, false);
                    document.getElementById('spice-area').removeEventListener('drop', SpiceHtml5.handle_file_drop, false);
                }
                console.log("<< disconnect");
            }

            function agent_connected(sc)
            {
                window.addEventListener('resize', SpiceHtml5.handle_resize);
                window.spice_connection = this;

                SpiceHtml5.resize_helper(this);

                if (window.File && window.FileReader && window.FileList && window.Blob)
                {
                    var spice_xfer_area = document.createElement("div");
                    spice_xfer_area.setAttribute('id', 'spice-xfer-area');
                    document.getElementById('spice-area').appendChild(spice_xfer_area);
                    document.getElementById('spice-area').addEventListener('dragover', SpiceHtml5.handle_file_dragover, false);
                    document.getElementById('spice-area').addEventListener('drop', SpiceHtml5.handle_file_drop, false);
                }
                else
                {
                    console.log("File API is not supported");
                }
            }

            /* SPICE port event listeners
            window.addEventListener('spice-port-data', function(event) {
                // Here we convert data to text, but really we can obtain binary data also
                var msg_text = arraybuffer_to_str(new Uint8Array(event.detail.data));
                DEBUG > 0 && console.log('SPICE port', event.detail.channel.portName, 'message text:', msg_text);
            });

            window.addEventListener('spice-port-event', function(event) {
                DEBUG > 0 && console.log('SPICE port', event.detail.channel.portName, 'event data:', event.detail.spiceEvent);
            });
            */

            connect(undefined);
        </script>

    </head>

    <body>

        <div id="login">
	        如果长时间处于黑屏状态，请按任意键唤醒。
	        <div class="button">
	            <div class="btn" onclick="location.reload();">重新连接</div>
	        </div>
	    </div>

        <div id="spice-area">
            <div id="spice-screen" class="spice-screen"></div>
        </div>

        <div id="message-div" class="spice-message"></div>

        <div id="debug-div">
        <!-- If DUMPXXX is turned on, dumped images will go here -->
        </div>

    </body>
</html>
