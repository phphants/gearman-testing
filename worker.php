<?php

// Using gearman 0.28

$worker= new GearmanWorker();
$worker->addServer('127.0.0.1');

$worker->addFunction("add", "add_fn");

while (1)
{
  print "Waiting for job...\n";

  $ret= $worker->work();

  if ($worker->returnCode() != GEARMAN_SUCCESS)
  {
    break;
  }
}

function add_fn($job)
{
  echo "Handle job: " . $job->handle() . "\n";

  $numbers = json_decode($job->workload());

  echo "Adding.. 0";
  $res = 0;
  foreach($numbers as $num)
  {
    echo " + " . $num;
    $res += $num;
  }

  echo " = " . $res . "\n";
  return $res;
}
