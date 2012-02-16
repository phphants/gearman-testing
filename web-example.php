<?php

require_once("shared.php");

if(isset($_REQUEST["job"]))
{
	$client = new GearmanClient();
	$client->addServer("127.0.0.1");

	switch($_REQUEST["job"])
	{
		case "start":
			// Start a new background worker
			$data = new stdClass();
			$data->location = "portsmouth, uk";
			$data->unit = "c";

			$job_handle = $client->doBackground("get_weather", json_encode($data));

			if($client->returnCode() != GEARMAN_SUCCESS)
			{
				echo json_encode(array("status" => "failed"));
			}
			else
			{
				echo json_encode(array("status" => "success", "job_handle" => $job_handle));
			}
			break;
		case "status":
			// Check the status of a background worker
			$job_handle = $_REQUEST['job_handle'];
			$status = $client->jobStatus($job_handle);
			if($status[0] && !$status[1])
			{
				echo json_encode(array("status" => "queued"));
			}
			else if($status[0] && $status[1])
			{
				if($status[3] > 0)
				{
					$percent = ceil(($status[2] / $status[3]) * 100);
				}
				else
				{
					$percent = 0;
				}
				echo json_encode(array("status" => "running", "percent" => $percent));
			}
			else
			{
				echo json_encode(array("status" => "complete"));
			}
			break;
		case "data":
			// Retrieve the data
			$job_handle = $_REQUEST['job_handle'];
			$file = sys_get_temp_dir() . "/gm_data_" . md5($job_handle);
			$json = file_get_contents($file);
			$weather_text = format_yql_weather(json_decode($json));
			echo json_encode(array("data" => $weather_text));
			break;
		case "stop":
			// Kill a running background worker!
			break;
	}
	die();
}

?>
<html>
<head>
	<title>Gearman Web Example</title>
	<script src="https://ajax.googleapis.com/ajax/libs/prototype/1.7.0.0/prototype.js" type="text/javascript"></script>
	<script src="web-example.js" type="text/javascript"></script>
	<link rel="stylesheet" href="web-example.css" type="text/css" media="screen" />
</head>
<body>
	<h1>Gearman Web Example</h1>
	<ul>
		<li><a href="javascript:;" id="link_start">Start Request</a></li>
		<li><a href="javascript:;" id="link_stop">Stop Request</a></li>
		<li><strong>Current status:</strong> <span id="current_status">Idle</span></li>
	</ul>
	<textarea id="output"></textarea>
</body>
</html>