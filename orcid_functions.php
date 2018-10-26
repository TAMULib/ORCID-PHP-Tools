<?php

//
//
// Functions
//
//

function get_info_from_orcid($orcid, $request, $mainurl, $accessToken) {

	$uri = $mainurl . '/v2.0/' . $orcid . $request;

	$timeout = 5;		
	$curl_handle=curl_init();

	curl_setopt($curl_handle, CURLOPT_HEADER, false);
	curl_setopt($curl_handle, CURLINFO_HEADER_OUT, false); // enable tracking

	curl_setopt($curl_handle, CURLOPT_URL, $uri);
	curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($curl_handle, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl_handle, CURLOPT_USERAGENT, 'TAMU_Library');

		if ($timeout > 0) {
			curl_setopt($curl_handle, CURLOPT_TIMEOUT, $timeout);		
		} else {
			curl_setopt($curl_handle, CURLOPT_TIMEOUT, 10);					
		}	
	
	$headers = 	array(
					  'Accept: application/orcid+xml',
					  'Content-Type: application/vdn.orcid+xml',
					  'Authorization: Bearer ' . $accessToken
					 );

	curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);	

	$content = curl_exec($curl_handle);

	$headerSent = curl_getinfo($curl_handle, CURLINFO_HEADER_OUT ); // request headers

	$http_status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

	curl_close($curl_handle);
	
//	echo 'Response:<br><pre>' .  htmlspecialchars($content) . '</pre></br>'; 
//  https://stackoverflow.com/questions/1575788/php-library-for-parsing-xml-with-a-colons-in-tag-names
	$content = preg_replace('~(</?|\s)([a-z0-9_]+):~is', '$1$2_', $content);
//	echo 'Response:<br><pre>' .  htmlspecialchars($content) . '</pre></br>'; 

		try {
			$xml = simplexml_load_string($content);			
		} 	catch (Exception $e) {
			// handle the error
			echo "error";
			echo "<br><br>";
			echo $e->getMessage();
			$xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"><response>ERROR</response>');
		}

	return $xml;
		
}

function get_user_accessToken ($orcid, $dbconnection, $db) {

		$strSQL = "SELECT * FROM orcid.orcid where orcid = '" . $orcid . "'";
		$result = mysqli_query($dbconnection, $strSQL);	
		$num_rows = mysqli_num_rows($result);

		if ($num_rows > 0) {
			while ($row = mysqli_fetch_array($result)) {
				return $row["authToken"];
			}
		} else {
			return "";
		}
		

}

function debugout($info_out)
{ 
	if (DEBUG_OUT) {
		echo $info_out . "<br>";
	}
	
	return; 
}

function put_info_into_orcid($orcid, $request, $mainurl, $accessToken, $xml) {

// example https://api.orcid.org/v1.2/0000-0003-4327-0476/orcid-bio

	$uri = $mainurl . '/v2.0/' . $orcid . $request;

	$timeout = 5;		
	$curl_handle=curl_init();

	curl_setopt($curl_handle, CURLOPT_HEADER, false);
	curl_setopt($curl_handle, CURLINFO_HEADER_OUT, false); // enable tracking

	curl_setopt($curl_handle, CURLOPT_URL, $uri);
	curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($curl_handle, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl_handle, CURLOPT_USERAGENT, 'TAMU_Library');
    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $xml);

		if ($timeout > 0) {
			curl_setopt($curl_handle, CURLOPT_TIMEOUT, $timeout);		
		} else {
			curl_setopt($curl_handle, CURLOPT_TIMEOUT, 10);					
		}
	
	$headers = 	array(
					  'Accept: application/orcid+xml',
					  'Content-Type: application/vdn.orcid+xml',
					  'Authorization: Bearer ' . $accessToken
					 );

	curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);	

	$content = curl_exec($curl_handle);

	$headerSent = curl_getinfo($curl_handle, CURLINFO_HEADER_OUT ); // request headers

	$http_status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

	curl_close($curl_handle);
	
//	echo 'Response:<br><pre>' .  htmlspecialchars($content) . '</pre></br>'; 

	if ($http_status == 200) {
		return "true";
	} else {
		return "false";
	}

}

function create_scholars_link_xml ($hash_uid) {
	
$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><orcid-message xmlns="http://www.orcid.org/ns/orcid" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://raw.github.com/ORCID/ORCID-Source/master/orcid-model/src/main/resources/orcid-message-1.2.xsd">
<message-version>1.2</message-version><orcid-profile><orcid-preferences><locale>es</locale></orcid-preferences>
<orcid-bio>
<researcher-urls visibility="public">
<researcher-url>
<url-name>Scholars@TAMU</url-name>
<url>http://scholars.library.tamu.edu/vivo/display/n' . $hash_uid . '</url>
</researcher-url>			
</researcher-urls>
</orcid-bio>﻿</orcid-profile></orcid-message>';

return $xml;
	
}
?>