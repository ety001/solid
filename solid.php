#!/usr/bin/php -q
<?php
$limit = 20;
$notify_user = array(
	'liu'=>'o1cjbvuYJH6L99beLTZectDMFte0',
	'me'=>'o1cjbvmen0avmqPZoZ4_Rs51OHKA',
	'ba'=>'o1cjbvkXgt5YN7EI51A8KnYneIEk',
	'robot'=>'o1cjbvoPvmfdpyOMVzmFAc96vPKk'
);
$notify_tmpl = array(
	'touser'=>'',
	'template_id'=>'HVzyNtUPoJi5S8bcPJ6t7LpC_Rr5Egqt5uj-rh5EEbA',
	'url'=>'http://www.yeelink.net/devices/343089/#sensor_380664',
	'data'=>array(
		'first'=>array('value'=>'','color'=>'#173177')
	)
);
$slack_rot = 'https://hooks.slack.com/services/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';
$key = '294ef3f6c15f19f4a2a2df492bedc95a';
$access_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
$appid = 'wxc32fbd38f1048e22';
$secret = '4e80511370c336b73606a6f68943f8da';
$tmpl_url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';

function send_data($val=0, $device_id = 343089, $sensor_id=380664, $isnotify=true) {
	global $key;
	$url = 'http://api.yeelink.net/v1.1/device/'.$device_id.'/sensor/'.$sensor_id.'/datapoints';
	$query = json_encode(array('timestamp'=>date('Y-m-d H:i:s',time()),'value'=>$val ));
if($isnotify==true){file_put_contents('/shell/data.log', $query."\n", FILE_APPEND);}
	$options = array(
		'http' =>
        	array(
        		'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
				    "U-ApiKey: {$key}\r\n".
                	            "Content-Length: ".strlen($query)."\r\n".
                        	    "User-Agent:MyAgent/1.0\r\n",
            		'method'  => 'POST',
            		'content' => $query
          	)
      	);
      	$context  = stream_context_create($options);
      	$result = file_get_contents($url, false, $context);
	if($isnotify==true)
	{
		sleep(5);
		send_notify($val);
	}
      	return $result;
}
function send_notify($val)
{
	global $limit, $notify_user, $notify_tmpl, $tmpl_url;
	if($val>$limit)return;
	$has_send_today = file_get_contents('/shell/sendtag.log');
	if(($has_send_today+24*3600)>time())return;
	$notify_tmpl['data']['first']['value'] = '土壤湿度不足'.$val.'%，请及时浇水。';
	$access_token = get_access_token();
	$tmpl_url = $tmpl_url.$access_token;
	foreach($notify_user as $k => $user_id)
	{
		$notify_tmpl['touser'] = $user_id;
		$msg = json_encode($notify_tmpl);
//file_put_contents('/tmp/msg.log', $tmpl_url."\n".$msg."\n", FILE_APPEND);
		post_method($tmpl_url, $msg);
	}
	file_put_contents('/shell/sendtag.log', time());
}
function get_access_token()
{
	global $appid, $secret, $access_token_url;
	$input = file_get_contents('/shell/access_token.txt');
	if($input){
		$o = explode('|', $input);
		if($o[0]!=''&&($o[1]+7200)>time()){
			return $o[0];
		}
	}
	$url = sprintf( $access_token_url , $appid, $secret);
	$result = file_get_contents($url);
	$r = json_decode($result, true);
	$access_token = $r['access_token'];
	$output = $access_token .'|'.time();
	file_put_contents('/shell/access_token.txt', $output);
	return $access_token;
}
function post_method($url, $data, $header='')
{
	global $key;
	if($header==''){
		$header = "Content-Type: application/x-www-form-urlencoded\r\n".
                                    "U-ApiKey: {$key}\r\n".
                                    "Content-Length: ".strlen($data)."\r\n".
                                    "User-Agent:MyAgent/1.0\r\n";
	}
	$options = array(
                'http' =>
                array(
                        'header' => $header,
                        'method'  => 'POST',
                        'content' => $data
                )
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
//file_put_contents('/tmp/solid.log',$result."\n",FILE_APPEND);
	return $result;
}
function send_to_slack($msg)
{
	global $slack_rot;
	if($msg > 20)return;
	$msg = '当前土壤湿度为：'.$msg.'%';
	$data = 'payload={"channel": "#mynotice", "text": "'.$msg.'"}';
	$command = "curl -X POST --data-urlencode '".$data."' {$slack_rot} > /dev/null 2>&1";
	exec($command);	
}

//send_notify($argv[1]*100);
$result = send_data($argv[1]*100);
file_put_contents('/tmp/solid_result.log', $result."\n", FILE_APPEND);

$command = 'cat /sys/class/thermal/thermal_zone0/temp';
exec($command, $r1, $r2);
$val = $r1[0]/1000;
send_data($val, 343089, 380665, false);

//send_to_slack($argv[1]*100);
