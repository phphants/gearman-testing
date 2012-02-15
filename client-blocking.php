<?php

// Using gearman 0.28

$client = new GearmanClient();
$client->addServer("127.0.0.1");

// set the complete callback
$client->setCompleteCallback("cbDone");

// Construct a data object to request some weather for a location and units (c or f)
$data = new stdClass();
$data->location = "portsmouth, uk";
$data->unit = "c";
$task1 = $client->addTask("get_weather", json_encode($data));

// Run the tasks (this blocks!)
$result = $client->runTasks();

// Callback function for when it's done
function cbDone($task)
{
	echo "Task [" . $task->functionName() . "] complete...\n";

	switch ($task->functionName())
	{
		case "add":
			echo "The sum is " . $task->data() . ".\n";
			break;
		case "get_weather":
			$weather = json_decode($task->data());

			echo "{$weather->title}\n";
			echo str_repeat("-", strlen($weather->title)) . "\n\n";

			echo "Current conditions:\n";
			echo "{$weather->condition->text}, {$weather->condition->temp}deg\n\n";

			echo "Forecast:\n";
			foreach ($weather->forecast as $forecast)
			{
				echo "{$forecast->day} - {$forecast->text}. High: {$forecast->high} Low: {$forecast->low}\n";
			}
			break;
	}
}

