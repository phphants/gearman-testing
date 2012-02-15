<?php

// Using gearman 0.28

$worker= new GearmanWorker();
$worker->addServer('127.0.0.1');

$worker->addFunction("get_weather", "get_weather");

console("Started worker script.");

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
	echo date("Y-m-d H:i:s") . "   $text\n";
}

/**
 * Get weather information
 * @param GearmanJob $job
 */
function get_weather($job)
{
	console("New job: " . $job->handle() . " (" . __FUNCTION__ . ")");

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
	sleep(2);

	console("Done, returning weather");

	return $new_json;
}
