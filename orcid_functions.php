<?php

//
//
// Functions
//
//

function getToken($client_id, $client_secret, $scope, $grant_type, $mainurl) {

	$uri   = $mainurl . "/oauth/token";

	$fields = array(
		'client_id' => $client_id,
		'client_secret' => $client_secret,
		'scope' => $scope,
		'grant_type' => $grant_type
	);

		foreach($fields as $key=>$value) { 
			$fields_string .= $key . '=' . $value . '&'; 
		}
	
	rtrim($fields_string, '&');				
					
	$curl_handle=curl_init();

	curl_setopt($curl_handle, CURLOPT_HEADER, false);
	curl_setopt($curl_handle, CURLINFO_HEADER_OUT, false); // enable tracking

	curl_setopt($curl_handle, CURLOPT_URL, $uri);
	curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($curl_handle, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_handle, CURLOPT_USERAGENT, 'TAMU_Library');

	curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array("Accept: application/json" )); 

	curl_setopt($curl_handle,CURLOPT_POST, count($fields));
	curl_setopt($curl_handle,CURLOPT_POSTFIELDS, $fields_string);

	$content = curl_exec($curl_handle);
	$http_status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

	curl_close($curl_handle);

		if ($http_status == 200) {
			$obj = json_decode($content);
			return $obj->{'access_token'};	
		} else { 
			return 0;
		}

	return; 
			
}

function createXML($firstname, $lastname, $email) {

	$xml = "<?xml version='1.0' encoding='UTF-8'?>";
	$xml = $xml . "<orcid-message xmlns:xsi='http://www.orcid.org/ns/orcid https://raw.github.com/ORCID/ORCID-Source/master/orcid-model/src/main/resources/orcid-message-1.1.xsd' xmlns='http://www.orcid.org/ns/orcid'>";
	$xml = $xml . "<message-version>1.1</message-version>";
	$xml = $xml . "<orcid-profile>";
	$xml = $xml . "<orcid-bio>";
	$xml = $xml . "<personal-details>";
	$xml = $xml . "<given-names>" . $firstname . "</given-names>";
	$xml = $xml . "<family-name>" . $lastname . "</family-name>";
	$xml = $xml . "</personal-details>";
	$xml = $xml . "<contact-details>";
	$xml = $xml . "<email primary='true'>" . $email . "</email>";
	$xml = $xml . "</contact-details>";
	$xml = $xml . "</orcid-bio>";
	$xml = $xml . "</orcid-profile>";
	$xml = $xml . "</orcid-message>";

	return $xml;

}

function createID($token, $xml, $mainurl) {
	
	$uri = $mainurl . '/v1.1/orcid-profile';

	$thecmd = 'curl -H "Accept: application/xml" -H "Content-Type: application/vdn.orcid+xml" -H "Authorization: Bearer ' . $token . '"  ' . $uri . ' -X POST -d "' . $xml . '" -L -i';
	
	exec($thecmd, $info);
	
	$mainurl = str_replace("http://", "", $mainurl);
	$mainurl = str_replace("https://", "", $mainurl);	

		if ($info[0] == "HTTP/1.1 201 Created") {
			echo "Created" . "<br>";
			$orcID = str_replace("Location: http://" . $mainurl . "/", "", $info[7]);
			$orcID = str_replace("Location: https://" . $mainurl . "/", "", $info[7]);			
			$orcID = str_replace("/orcid-profile", "", $orcID);
		} elseif ($info[0] == "HTTP/1.1 400 Bad Request") {
			echo "Bad Request: " . $info[11] . "<br>";
			$orcID = $info[11];
		} else {
			echo "Unknown error" . "<br>";
			$orcID = "Unknown error";
		}
	
		if (validorcID($orcID)) {
			return $orcID;
		} else {
			return $orcID;
		}

	return;
}

function findIDPublic($email_query, $family_name_query, $given_name_query, $type) {

$siteurl = 'pub.orcid.org';
$uri = 'http://' . $siteurl . '/v1.1/search/orcid-bio';

	if ($type == 'email') {
		$query = 'email:' . $email_query . '+AND+family-name:' . $family_name_query;
		debugout("Finding Active ORCID for " .$email_query);
	} else {
		$query = 'given-names:' . $given_name_query . '+AND+family-name:' . $family_name_query;	
		debugout("Finding Active ORCID for " . $family_name_query . ', ' . $given_name_query);
	}
	
	$uri = $uri . "?q=" . $query;	
	
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

	$headers = 	array(
					  'Accept: application/orcid+xml'
					 );

	curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);	
	
	$content = curl_exec($curl_handle);

	$headerSent = curl_getinfo($curl_handle, CURLINFO_HEADER_OUT ); // request headers

	$http_status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

	curl_close($curl_handle);
	
		try {
			$xml = simplexml_load_string($content);
			$numresults = $xml->{'orcid-search-results'}['num-found'];
			$orcID = $xml->{'orcid-search-results'}->{'orcid-search-result'}->{'orcid-profile'}->{'orcid'};
			debugout("I found " . $numresults . " Results");
		} catch (Exception $e) {
			// handle the error
			echo "error";
			echo "<br><br>";
			$orcID = 0;
			$numresults = 0;
			echo $e->getMessage();
		}

		if ($numresults > 1) {
			return -1;
		} elseif (validorcID($orcID)) {
			return $orcID;
		} else {
			return 0;
		}

	return;
		
}

function findID($accessToken, $email_query, $family_name_query, $mainurl) {

	$uri = $mainurl . '/v1.1/search/orcid-bio';
	$query = 'email:' . $email_query . '+AND+family-name:' . $family_name_query;
	$uri = $uri . "?q=" . $query;	
	
	debugout("Finding Active ORCID for " . $email_query);

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
	
		try {
		   $xml = simplexml_load_string($content);
			$orcID = $xml->{'orcid-search-results'}->{'orcid-search-result'}->{'orcid-profile'}->{'orcid-identifier'}->{'path'};
		} catch (Exception $e) {
		   // handle the error
		   echo "error";
		   echo "<br><br>";
		   $orcID = 0;
		   echo $e->getMessage();
		}
		
		if (validorcID($orcID)) {
			return $orcID;
		} else {
			return 0;
		}

	return;
		
}

function isClaimed($accessToken, $orcID, $mainurl) {

	$uri = $mainurl . '/v1.1/search/orcid-bio';
	$query = 'orcid:' . $orcID;
	$uri = $uri . "?q=" . $query;	
	
	debugout("Finding info for OrcID " . $orcID);

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

	curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array("Accept: application/orcid+xml")); 

	$content = curl_exec($curl_handle);

	$headerSent = curl_getinfo($curl_handle, CURLINFO_HEADER_OUT ); // request headers

	$http_status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

	curl_close($curl_handle);

		try {
			$xml = simplexml_load_string($content);
			$orcID = $xml->{'orcid-search-results'}->{'orcid-search-result'}->{'orcid-profile'}->{'orcid-identifier'}->{'path'};

				if (validorcID($orcID)) {
					$givenNames = $xml->{'orcid-search-results'}->{'orcid-search-result'}->{'orcid-profile'}->{'orcid-bio'}->{'personal-details'}->{'given-names'};
				} else {
					$givenNames == NULL;
				}
				
		} catch (Exception $e) {
		   // handle the error
		   echo "error";
		   echo "<br><br>";
		   $givenNames = 0;
		   echo $e->getMessage();
		}

		if ($givenNames == "Reserved For Claim") {
			return "waiting";
		} elseif ($givenNames != NULL){
			return "claimed";
		} elseif ($givenNames == NULL){
			return "not found";
		} else {
			return "error";
		}

	return;
		
}

function validorcID ($orcID) {

	if (preg_match("/^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9 A-Z a-z]{4}$/", $orcID)) {
//		echo "Valid Orc: " . $orcID . "<br>";
		return true;
	} else {
//		echo "Not Valid Orc: " . $orcID . "<br>";
		return false;
	}

	return;
}

function createXML_1_2($firstname, $lastname, $email) {

	// Not fully implemented...  Just playing around
	
	$xml = "<?xml-model href='https://raw.github.com/ORCID/ORCID-Source/master/orcid-model/src/main/resources/orcid-message-1.2_rc1.xsd' type='application/xml' schematypens='http://purl.oclc.org/dsdl/schematron'?>";
	
	$xml = $xml . "<orcid-message xmlns='http://www.orcid.org/ns/orcid' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.orcid.org/ns/orcid https://raw.github.com/ORCID/ORCID-Source/master/orcid-model/src/main/resources/orcid-message-1.2_rc3.xsd'>";
	
	$xml = $xml . "<message-version>1.2_rc1</message-version>";
	$xml = $xml . "<orcid-profile>";
	$xml = $xml . "<orcid-bio>";
	$xml = $xml . "<personal-details><given-names>Library</given-names>";
	$xml = $xml . "<family-name>Patron</family-name>";
	$xml = $xml . "</personal-details>";
	$xml = $xml . "<contact-details>";
	$xml = $xml . "<email primary='true'>orcid17@mailinator.com</email>";
	$xml = $xml . "</contact-details>";
	$xml = $xml . "</orcid-bio>";
	$xml = $xml . "<orcid-activities>";
	$xml = $xml . "<affiliations>";
	$xml = $xml . "<affiliation>";
	$xml = $xml . "<type>employment</type>";
	$xml = $xml . "<organization>";
	$xml = $xml . "<name>Texas A &amp; M University</name>";
	$xml = $xml . "<address>";
	$xml = $xml . "<city>College Station</city>";
	$xml = $xml . "<region>TX</region>";
	$xml = $xml . "<country>US</country>";
	$xml = $xml . "</address>";
	$xml = $xml . "<disambiguated-organization>";
	$xml = $xml . "<disambiguated-organization-identifier>14736</disambiguated-organization-identifier>";
	$xml = $xml . "<disambiguation-source>RINGGOLD</disambiguation-source>";
	$xml = $xml . "</disambiguated-organization>";
	$xml = $xml . "</organization>";
	$xml = $xml . "</affiliation>";		
	$xml = $xml . "</affiliations>";
	$xml = $xml . "</orcid-activities>";
	$xml = $xml . "</orcid-profile>";
	$xml = $xml . "</orcid-message>";

return $xml;

}

function debugout($info_out)
{ 
	if (DEBUG_OUT) {
		echo $info_out . "<br>";
	}
	
	return; 
}

function getAuthorization($client_id, $client_secret, $grant_type, $code, $mainurl) {

	$uri = $mainurl . '/oauth/token';	
	
	$fields = array(
		'client_id' => $client_id,
		'client_secret' => $client_secret,
		'grant_type' => $grant_type,
		'code' => $code
	);

		foreach($fields as $key=>$value) { 
			$fields_string .= $key . '=' . $value . '&'; 
		}
	
	rtrim($fields_string, '&');				
					
	$curl_handle=curl_init();

	curl_setopt($curl_handle, CURLOPT_HEADER, false);
	curl_setopt($curl_handle, CURLINFO_HEADER_OUT, false); // enable tracking

	curl_setopt($curl_handle, CURLOPT_URL, $uri);
	curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($curl_handle, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_handle, CURLOPT_USERAGENT, 'TAMU_Library');

	curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array("Accept: application/json" )); 

	curl_setopt($curl_handle,CURLOPT_POST, count($fields));
	curl_setopt($curl_handle,CURLOPT_POSTFIELDS, $fields_string);

	$content = curl_exec($curl_handle);
	$http_status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

	curl_close($curl_handle);

		if (DEBUG_OUT) {
			echo 'Response:<br><pre>' .  htmlspecialchars($content) . '</pre></br>'; 	
		}
		
		if ($http_status == 200) {
			$obj = json_decode($content);
			return array($obj->{'access_token'}, $obj->{'orcid'});	
		} else { 
			return array(500);	
		}

	return; 
			
}


?>