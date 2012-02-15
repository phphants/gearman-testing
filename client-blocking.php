<?php

require_once("shared.php");

// Using gearman 0.28

$client = new GearmanClient();
$client->addServer("127.0.0.1");

// set the complete callback
$client->setCompleteCallback("weather_complete");

// Construct a data object to request some weather for a location and units (c or f)
$data = new stdClass();
$data->location = "portsmouth, uk";
$data->unit = "c";
$task1 = $client->addTask("get_weather", json_encode($data));

// Run the tasks (this blocks!)
$result = $client->runTasks();

/**
 * Callback function for when the task is done
 * @param GearmanTask $task
 */
function weather_complete($task)
{
	echo "Task [" . $task->functionName() . "] complete...\n";

	$weather = json_decode($task->data());

	echo format_yql_weather($weather);
}