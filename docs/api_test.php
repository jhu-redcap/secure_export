<?php
	//ACF2E078CD9962B7348AC768C3753D74
	$data = array(
		'token' => 'putyourtokenhere99999',
		'content' => 'project',
		'format' => 'json',
		'returnFormat' => 'json'
	);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
	$output = curl_exec($ch);
	print $output;
	curl_close($ch);