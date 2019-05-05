<?php ?>
<html><head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>在线提交</title>
  <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
	
  
</head>
<body onload="connect();">
    <div class="container">
	    <div class="row clearfix">
	        <div class="col-md-1 column">
	    </div>
		    <div class="thumbnail">
		               <div class="caption" id="dialog"></div>
		     </div>
	     <div class="col-md-2 column">
	           <div class="thumbnail">
                   <div class="caption" id="userlist"></div>
               </div>
	     </div>
	     <div class="col-md-3 column" style="">
				
				<table id="dynamicTable" width="100%" border="0" cellspacing="0" cellpadding="0">
					<thead>
						<tr>
							<td align="center" bgcolor="#CCCCCC">ID</td>
							<td align="center" bgcolor="#CCCCCC">name</td>
							<td align="center" bgcolor="#CCCCCC">sex</td>
							<td align="center" bgcolor="#CCCCCC">age</td>
							<td align="center" bgcolor="#CCCCCC">class</td>
						</tr>
					</thead>
					<form id="form-data">
					<tbody>
						<tr id='old'>
							<td align="center">eg:</td>
							<td align="center">zhang</td>
							<td align="center">mgentel</td>
							<td align="center">11</td>
							<td align="center">9</td>
						</tr>
						<?php 
							$redis = new redis();
							$redis -> connect('127.0.0.1',6379);
							$old = $redis->sMembers('myset');
							if(!empty($old)){
								foreach ($old as $val){
						?>
								<tr id='old'>
									<?php foreach (json_decode($val) as $v){?>
									<td align="center"><?php echo urldecode($v);?></td>
									<?php }?>
								</tr>
						<?php 	}}?>
						
						<tr>
							<td align="center">
								<?php echo $_SESSION['client_name'];?></td>
							<td align="center">
								<input type="text" name="start_end_time" /></td>
							<td align="center">
								<input type="text" name="unit_department" /></td>
							<td align="center">
								<input type="text" name="post" /></td>
							<td align="center">
								<input type="text" name="sad" /></td>
						</tr>
					</tbody>
					</form>
				</table>
				<input type="button" id="btn_submit" value="提交">
		</div>
    </div>
	
	
	<script src="https://cdn.bootcss.com/jquery/3.4.0/jquery.min.js"></script>
<script type="text/javascript">

</script>
	
    <script type="text/javascript">var _bdhmProtocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cscript src='" + _bdhmProtocol + "hm.baidu.com/h.js%3F7b1919221e89d2aa5711e4deb935debd' type='text/javascript'%3E%3C/script%3E"));</script>
    <script type="text/javascript">
      // 动态自适应屏幕
      document.write('<meta name="viewport" content="width=device-width,initial-scale=1">');
      $("textarea").on("keydown", function(e) {
          // 按enter键自动提交
          if(e.keyCode === 13 && !e.ctrlKey) {
              e.preventDefault();
              $('form').submit();
              return false;
          }

          // 按ctrl+enter组合键换行
          if(e.keyCode === 13 && e.ctrlKey) {
              $(this).val(function(i,val){
                  return val + "\n";
              });
          }
      });

      
      $("#btn_submit").click(function () {

    	  var input = $("#form-data").serialize();
          var to_client_id = 'all';
          var to_client_name = '所有人';
          ws.send('{"type":"submit","to_client_id":"'+to_client_id+'","to_client_name":"'+to_client_name+'","content":"'+input+'"}');
          input = "";
          
  	});

      
    </script>
    
    <script type="text/javascript" src="/js/swfobject.js"></script>
  <script type="text/javascript" src="/js/web_socket.js"></script>
  <script type="text/javascript" src="/js/jquery.min.js"></script>

  <script type="text/javascript">
    if (typeof console == "undefined") {    this.console = { log: function (msg) {  } };}
    // 如果浏览器不支持websocket，会使用这个flash自动模拟websocket协议，此过程对开发者透明
    WEB_SOCKET_SWF_LOCATION = "/swf/WebSocketMain.swf";
    // 开启flash的websocket debug
    WEB_SOCKET_DEBUG = true;
    var ws, name, client_list={};

    // 连接服务端
    function connect() {
       // 创建websocket
       ws = new WebSocket("ws://"+document.domain+":7272");
       // 当socket连接打开时，输入用户名
       ws.onopen = onopen;
       // 当有消息时根据消息类型显示不同信息
       ws.onmessage = onmessage; 
       ws.onclose = function() {
    	  console.log("连接关闭，定时重连");
          connect();
       };
       ws.onerror = function() {
     	  console.log("出现错误");
       };
    }

    // 连接建立时发送登录信息
    function onopen()
    {
        if(!name)
        {
            show_prompt();
        }
        // 登录
        var login_data = '{"type":"login","client_name":"'+name.replace(/"/g, '\\"')+'","room_id":"<?php echo isset($_GET['room_id']) ? $_GET['room_id'] : 1?>"}';
        console.log("websocket握手成功，发送登录数据:"+login_data);
        ws.send(login_data);
    }

    // 服务端发来消息时
    function onmessage(e)
    {
       
        var data = JSON.parse(e.data);
        switch(data['type']){
            // 服务端ping客户端
            case 'ping':
                ws.send('{"type":"pong"}');
                break;;
            // 登录 更新用户列表
            case 'login':
                //{"type":"login","client_id":xxx,"client_name":"xxx","client_list":"[...]","time":"xxx"}
                say(data['client_id'], data['client_name'],  data['client_name']+' 加入了聊天室', data['time']);
                if(data['client_list'])
                {
                    client_list = data['client_list'];
                }
                else
                {
                    client_list[data['client_id']] = data['client_name']; 
                }
                flush_client_list();
                console.log(data['client_name']+"登录成功");
                break;
            // 发言
            case 'say':
                console.log('say');
                //{"type":"say","from_client_id":xxx,"to_client_id":"all/client_id","content":"xxx","time":"xxx"}
                say(data['from_client_id'], data['from_client_name'], data['content'], data['time']);
                break;
            // 用户退出 更新用户列表
            case 'logout':
                //{"type":"logout","client_id":xxx,"time":"xxx"}
                say(data['from_client_id'], data['from_client_name'], data['from_client_name']+' 退出了', data['time']);
                delete client_list[data['from_client_id']];
                flush_client_list();
                break;
            case 'submit':
            	submit(data['from_client_id'], data['from_client_name'], data['content'], data['time']);
                //{"type":"say","from_client_id":xxx,"to_client_id":"all/client_id","content":"xxx","time":"xxx"}
                //say(data['from_client_id'], data['from_client_name'], data['content'], data['time']);
                break;
                
        }
    }

    // 输入姓名
    function show_prompt(){  
        name = prompt('输入你的名字：', '');
        if(!name || name=='null'){  
            name = '游客';
        }
    }  

    // 提交对话
    function onSubmit() {
      var input = document.getElementById("textarea");
      var to_client_id = $("#client_list option:selected").attr("value");
      var to_client_name = $("#client_list option:selected").text();
      ws.send('{"type":"say","to_client_id":"'+to_client_id+'","to_client_name":"'+to_client_name+'","content":"'+input.value.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')+'"}');
      input.value = "";
      input.focus();
    }

    // 刷新用户列表框
    function flush_client_list(){
    	var userlist_window = $("#userlist");
    	var client_list_slelect = $("#client_list");
    	userlist_window.empty();
    	client_list_slelect.empty();
    	userlist_window.append('<h4>在线用户</h4><ul>');
    	client_list_slelect.append('<option value="all" id="cli_all">所有人</option>');
    	for(var p in client_list){
            userlist_window.append('<li id="'+p+'">'+client_list[p]+'</li>');
            client_list_slelect.append('<option value="'+p+'">'+client_list[p]+'</option>');
        }
    	$("#client_list").val(select_client_id);
    	userlist_window.append('</ul>');
    }

    // 
    function say(from_client_id, from_client_name, content, time){
       

    	$("#dialog").append('<div class="speech_item">【'+from_client_name+'】'+time+'加入</div>');
    }
    function submit(from_client_id, from_client_name, content, time){
        
		console.log('submit');
		$("#old").after(content);
    }

    function serTr(){
        

    	$("#dialog").append('<div class="speech_item">【'+from_client_name+'】'+time+'加入</div>');
    }

    
    $(function(){
    	select_client_id = 'all';
	    $("#client_list").change(function(){
	         select_client_id = $("#client_list option:selected").attr("value");
	    });
        $('.face').click(function(event){
            $(this).sinaEmotion();
            event.stopPropagation();
        });
    });


  </script>
    
</body>
</html>
