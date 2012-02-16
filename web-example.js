

var current_job_handle = '';
var base_url = 'web-example.php?job=';

function addOnLoad(fn)
{
	var current_onload = window.onload;

	if (typeof(current_onload) != "function") window.onload = fn;
	else
	{
		window.onload = function() {
			if (current_onload) current_onload();
			fn();
		};
	}
}

function checkStatus()
{
	if(current_job_handle != '')
	{
		var url = base_url + 'status&job_handle=' + current_job_handle;
		
		new Ajax.Request(url, {
			method: 'get',
			onSuccess: function(transport) {
				var response = transport.responseText.evalJSON();
				if(response.status == 'queued')
				{
					$('current_status').innerHTML = 'Job is queued...';
				}
				else if(response.status == 'running')
				{
					$('current_status').innerHTML = 'Job is running... ' + response.percent + '% complete';
				}
				else if(response.status == 'complete')
				{
					$('current_status').innerHTML = 'Job is complete!';
					
					var url = base_url + 'data&job_handle=' + current_job_handle;
					current_job_handle = '';
					
					new Ajax.Request(url, {
						method: 'get',
						onSuccess: function(transport) {
							var response = transport.responseText.evalJSON();
							if(response.data != '')
							{
								$('output').innerHTML = response.data;
							}
						}
					});
				}
			}
		});
		
		if(current_job_handle != '')
		{
			setTimeout('checkStatus()',1000);
		}
	}
}

addOnLoad(function() {
	$('link_start').observe('click', function(event) {
		if(current_job_handle == '')
		{
			var url = base_url + 'start';
			
			new Ajax.Request(url, {
				method: 'get',
				onSuccess: function(transport) {
					var response = transport.responseText.evalJSON();
					if(response.status == 'success')
					{
						current_job_handle = response.job_handle;
						$('current_status').innerHTML = 'Job started with handle: ' + current_job_handle;
						checkStatus();
					}
					else
					{
						$('current_status').innerHTML = 'Failed to start job :(';
					}
				}
			});
		}
		else
		{
			alert('Already doing job ' + current_job_handle);
		}
	});
	$('link_stop').observe('click', function(event) {
		if(current_job_handle != '')
		{
			var url = base_url + 'stop&job_handle=' + current_job_handle;
			
			new Ajax.Request(url, {
				method: 'get',
				onSuccess: function(transport) {
					var response = transport.responseText.evalJSON();
					if(response.status == 'success')
					{
						$('current_status').innerHTML = 'Killed job';
						//current_job_handle = '';
					}
					else
					{
						$('current_status').innerHTML = 'Failed to kill job :(';
					}
				}
			});
		}
		else
		{
			alert('Not aware of a job to stop...');
		}
	});
});