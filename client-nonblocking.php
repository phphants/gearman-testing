<?php

require_once("shared.php");

// Using gearman 0.28

$client = new GearmanClient();
$client->addServer("127.0.0.1");

// Construct a data object to request some weather for a location and units (c or f)
$data = new stdClass();
$data->location = "portsmouth, uk";
$data->unit = "c";

// Create a new background job
$job_handle = $client->doBackground("get_weather", json_encode($data));
echo "Sent job '{$job_handle}'\n";

// Check the return code - what other codes are there and what do they mean?
if($client->returnCode() != GEARMAN_SUCCESS)
{
	echo "Bad return code: " . $client->returnCode() . "\n";
	die();
}

// Loop while we wait for job to finish... in a real world, we probably wouldn't
// do this as you may as well use client-blocking.php example :)
// But this demonstrates how you might check a job in a secondary script or
// from an AJAX poll or something! The $job_handle is a string so can be stored
// somewhere, put in session/cookie/whatever and re-used to check statuses
$done = false;
do
{
	sleep(1);
	$status = $client->jobStatus($job_handle);

	if($status[0] && !$status[1])
	{
		echo "Job is queued...\n";
	}
	else if($status[0] && $status[1])
	{
		echo "Job is running...\n";
	}
	else
	{
		$done = true;
	}
}
while(!$done);

// When job has finished, there doesn't seem to be a way of retrieving data
// so in this example, I just write it to /tmp in the worker and read the json
// in here
echo "Done, getting data...\n";
$file = sys_get_temp_dir() . "/gm_data_" . md5($job_handle);
$json = file_get_contents($file);
echo format_yql_weather(json_decode($json));
unlink($file);

echo "\n\nDone!\n";