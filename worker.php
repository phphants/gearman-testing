<?php

// Using gearman 0.28

$worker= new GearmanWorker();
$worker->addServer('127.0.0.1');

$worker->addFunction("get_weather", "get_weather");
$worker->addFunction("kill_job", "kill_job");

console("Started worker script.");

// This is the "worker" loop, endless (unless there is an error...)
// How would we detect this in a real-world situation? You could daemonize this
// script but that doesn't seem ideal solution.
while ($worker->work())
{
	console("--------------------------");
	console("Waiting for new job");

	if ($worker->returnCode() != GEARMAN_SUCCESS)
	{
		console("It brokes: " . $worker->returnCode());
		break;
	}
}

/**
 * Log something to the console
 * @param string $text
 */
function console($text)
{
	echo "[" . getmypid() . "] " . date("Y-m-d H:i:s") . "   $text\n";
}

/**
 * Function to kill another job
 */
function kill_job($job)
{
	console("New job: " . $job->handle() . " (" . __FUNCTION__ . ")");

	$data = json_decode($job->workload());

	// kill job defined in $data
	$pidfile = sys_get_temp_dir() . "/gm_pid_" . md5($data->job_handle);
	$pid = file_get_contents($pidfile);

	if($pid)
	{
		console("Killing handle {$data->job_handle} (pid={$pid})");

		$norestart = sys_get_temp_dir() . "/gm_norestart_" . md5($data->job_handle);
		touch($norestart);

		$cmd = "kill -9 {$pid}";
		$errno = 0;
		$output = array();
		exec($cmd . " 2>&1", $output, $errno);

		if($errno == 0)
		{
			console("Killed.");
		}
		else
		{
			consoled("Failed to kill pid");
		}
	}
	else
	{
		console("pidfile for {$data->job_handle} not found");
	}

	console("Done");

	return true;
}

/**
 * Get weather information
 * @param GearmanJob $job
 */
function get_weather($job)
{
	console("New job: " . $job->handle() . " (" . __FUNCTION__ . ")");
	$norestart = sys_get_temp_dir() . "/gm_norestart_" . md5($job->handle());
	if(file_exists($norestart))
	{
		console("norestart exists, returning");
		return;
	}

	$pidfile = sys_get_temp_dir() . "/gm_pid_" . md5($job->handle());
	file_put_contents($pidfile, getmypid());

	$data = json_decode($job->workload());

	// Extract variables from provided data, with defaults
	$location = $data->location ?: "portsmouth, uk";
	$unit = $data->unit ?: "c";

	// Build a YQL query
	console("Building YQL query");
	$yql_query = "use 'http://github.com/yql/yql-tables/raw/master/weather/weather.bylocation.xml' as we;";
	$yql_query .= "select * from we where location=\"{$location}\" and unit='{$unit}'";

	$yql_query_url = "https://query.yahooapis.com/v1/public/yql?q=" . urlencode($yql_query) . "&format=json";

	// Make call with cURL
	console("Executing YQL query");
	$ch = curl_init($yql_query_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$json = curl_exec($ch);

	// Tidy up the returned JSON
	console("Extracting relevant data");
	$obj = json_decode($json);
	$new_json = json_encode($obj->query->results->weather->rss->channel->item);

	// Sleep for fun...
	console("Falling asleep here");

	$sleep_for = 10;
	for($i = 1; $i <= $sleep_for; $i++)
	{
		console("Sending Status $i/$sleep_for");
		$job->sendStatus($i,$sleep_for);
		sleep(1);
	}

	// Write the data to /tmp for client-nonblocking background worker
	// In a realworld we'd write this data to somewhere else, maybe a database
	// or something, as the client might not have access to local filesystem!
	$datafile = sys_get_temp_dir() . "/gm_data_" . md5($job->handle());
	file_put_contents($datafile, $new_json);

	console("Done, returning weather");
	unlink($pidfile);

	return $new_json;
}
