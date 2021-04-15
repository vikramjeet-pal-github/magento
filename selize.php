<?php

$json='{
	"orderid": "1000003141",
	"shipment": [{
			"itemnumber": "321224",
			"status": "Ready to ship"
		},
		{
			"itemnumber": "321225",
			"status": "In packing"
		}
	]

}';
$data =serialize($json);
$requst=json_decode($json,1);
echo '<pre>';
print_r($requst);

