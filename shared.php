<?php

/**
 * Format a yql weather object nicely
 * @param object $weather
 * @return string Nice text
 */
function format_yql_weather($weather)
{
	$str = "{$weather->title}\n";
	$str .= str_repeat("-", strlen($weather->title)) . "\n\n";

	$str .= "Current conditions:\n";
	$str .= "{$weather->condition->text}, {$weather->condition->temp}deg\n\n";

	$str .= "Forecast:\n";
	if(is_array($weather->forecast))
	{
		foreach ($weather->forecast as $forecast)
		{
			$str .= "{$forecast->day} - {$forecast->text}. High: {$forecast->high} Low: {$forecast->low}\n";
		}
	}

	return $str;
}

