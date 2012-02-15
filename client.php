<?php

// Using gearman 0.28

$client = new GearmanClient();
$client->addServer("127.0.0.1");

$client->setCompleteCallback("cbDone");
$task1 = $client->addTask("add", json_encode(array(5,3,6)));

$result = $client->runTasks();

function cbDone($task)
{
  echo "Task complete...\n";
  var_dump($task->data());
  echo "\n";
}

