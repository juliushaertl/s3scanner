<?php
return [
	'ocs' => [
		['name' => 'Scan#scan', 'url' => '/scan/{userId}', 'verb' => 'POST'],
		['name' => 'Scan#scan', 'url' => '/scan/{userId}/{path}', 'verb' => 'POST', 'requirements' => array('path' => '.+')],
	]
];
