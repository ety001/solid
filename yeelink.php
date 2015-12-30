<?php
$key = '294ef3f6c15f19f4a2a2df492bedc95a';
function send_temp($val=0) {
	global $key;
	if(!$val){
		$command = 'cat /sys/class/thermal/thermal_zone0/temp';
		exec($command, $r1, $r2);
		$val = $r1[0]/1000;
	}
	$url = 'http://api.yeelink.net/v1.1/device/343089/sensor/380665/datapoints';
	$query = json_encode(array('timestamp'=>date('Y-m-d H:i:s',time()),'value'=>$val ));
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
      	return $result;
}
echo send_temp();
