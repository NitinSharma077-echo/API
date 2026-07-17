<?php

function getZohoRefreshConfig(){
	$config = array(
		"client_id" => getenv("ZOHO_CLIENT_ID") ?: "",
		"client_secret" => getenv("ZOHO_CLIENT_SECRET") ?: "",
		"refresh_token" => getenv("ZOHO_REFRESH_TOKEN") ?: "",
		"redirect_uri" => getenv("ZOHO_REDIRECT_URI") ?: "https://crm.zoho.in/",
	);

	if ($config["client_id"] != "" && $config["client_secret"] != "" && $config["refresh_token"] != "") {
		return $config;
	}

	if (!file_exists("create_auth.php")) {
		return $config;
	}

	$authScript = file_get_contents("create_auth.php");
	if (preg_match("/CURLOPT_URL\\s*=>\\s*'([^']+)'/", $authScript, $matches)) {
		$parts = parse_url($matches[1]);
		if (isset($parts["query"])) {
			parse_str($parts["query"], $query);
			$config["client_id"] = $config["client_id"] ?: ($query["client_id"] ?? "");
			$config["client_secret"] = $config["client_secret"] ?: ($query["client_secret"] ?? "");
			$config["refresh_token"] = $config["refresh_token"] ?: ($query["refresh_token"] ?? "");
			$config["redirect_uri"] = $config["redirect_uri"] ?: ($query["redirect_uri"] ?? "https://crm.zoho.in/");
		}
	}

	if (preg_match('/\\$clientId\\s*=\\s*getenv\\("ZOHO_CLIENT_ID"\\)\\s*\\?:\\s*"([^"]+)"/', $authScript, $matches)) {
		$config["client_id"] = $config["client_id"] ?: $matches[1];
	}
	if (preg_match('/\\$clientSecret\\s*=\\s*getenv\\("ZOHO_CLIENT_SECRET"\\)\\s*\\?:\\s*"([^"]+)"/', $authScript, $matches)) {
		$config["client_secret"] = $config["client_secret"] ?: $matches[1];
	}
	if (preg_match('/\\$refreshToken\\s*=\\s*getenv\\("ZOHO_REFRESH_TOKEN"\\)\\s*\\?:\\s*"([^"]+)"/', $authScript, $matches)) {
		$config["refresh_token"] = $config["refresh_token"] ?: $matches[1];
	}
	if (preg_match('/\\$redirectUri\\s*=\\s*getenv\\("ZOHO_REDIRECT_URI"\\)\\s*\\?:\\s*"([^"]+)"/', $authScript, $matches)) {
		$config["redirect_uri"] = $config["redirect_uri"] ?: $matches[1];
	}

	return $config;
}

function readZohoTokenFile(){
	if (!file_exists("auth_token.txt")) {
		return array();
	}

	$data = json_decode(file_get_contents("auth_token.txt"), true);
	return is_array($data) ? $data : array();
}

function saveZohoTokenFile($data){
	$data["generated_at"] = time();
	$data["expires_at"] = time() + ((int)($data["expires_in"] ?? 3600));
	file_put_contents("auth_token.txt", json_encode($data));
}

function refreshZohoAccessToken(){
	$config = getZohoRefreshConfig();

	if ($config["client_id"] == "" || $config["client_secret"] == "" || $config["refresh_token"] == "") {
		return "";
	}

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://accounts.zoho.com/oauth/v2/token",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => http_build_query(array(
			"grant_type" => "refresh_token",
			"client_id" => $config["client_id"],
			"client_secret" => $config["client_secret"],
			"redirect_uri" => $config["redirect_uri"],
			"refresh_token" => $config["refresh_token"],
		)),
		CURLOPT_HTTPHEADER => array(
			"Content-Type: application/x-www-form-urlencoded",
		),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);

	if ($err) {
		return "";
	}

	$data = json_decode($response, true);
	if (!is_array($data) || !isset($data["access_token"])) {
		return "";
	}

	saveZohoTokenFile($data);
	return $data["access_token"];
}

function getZohoAccessToken(){
	$data = readZohoTokenFile();

	if (isset($data["access_token"]) && isset($data["expires_at"]) && $data["expires_at"] > time() + 300) {
		return $data["access_token"];
	}

	$refreshedToken = refreshZohoAccessToken();
	if ($refreshedToken != "") {
		return $refreshedToken;
	}

	if (isset($data["access_token"])) {
		return $data["access_token"];
	}

	return getenv("ZOHO_ACCESS_TOKEN") ?: '1000.5c91aec9fd7ee3f8a6a5c1d370ec5882.3c99ca759a9167e43a266175b66e70e8';
}

$AccessToken = getZohoAccessToken();

// print_r($AccessToken);

// exit();

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestBody = json_decode(file_get_contents("php://input"), true);

if ($requestMethod == "GET") {
	$Rec_Id = $_GET['id'] ?? "";
} elseif ($requestMethod == "POST") {
	$Rec_Id = $_POST['id'] ?? ($requestBody['id'] ?? ($_GET['id'] ?? ""));
} else {
	http_response_code(405);
	header("Allow: GET, POST");
	exit("Method Not Allowed");
}

if ($Rec_Id == "") {
	http_response_code(400);
	exit("Missing required parameter: id");
}

$mobilePhone ="";
$Salutation ="Mr";
$gender ="Male";
$pinCode ="";
$email ="";					
$first_name ="";
$lastName ="";
$leadAmount ="";

// echo "AccessToken: ".$AccessToken."<br/>";

function GetAccountData($AccessToken1, $Rec_Id){
	global $AccessToken;
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://www.zohoapis.com/crm/v2/GL_Ashok_1/$Rec_Id",
		CURLOPT_RETURNTRANSFER => true,	
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array(

			"Authorization: Zoho-oauthtoken ".$AccessToken,	
		),
	));

	$response = curl_exec($curl);

	$err = curl_error($curl);
	curl_close($curl);
	if ($err) {
		echo "cURL Error #:" . $err;

	} else {
		// $response = json_decode($response, true);	
		// echo "<pre>";
		// var_dump($response);
	}
	return $response;
}

$ZohoData=GetAccountData($AccessToken1, $Rec_Id);

print_r("Ist stage data ".$ZohoData);


// might be removed
function getNupayToken(){
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://gl.nupaybiz.com/Auth/token",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_SSL_VERIFYHOST =>0,
	  CURLOPT_SSL_VERIFYPEER=>0,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_HTTPHEADER => array(
		"api-key: Paynetjj3JhbUNmdfdm96VyTnVwjhfh21fs01k30",
		"cache-control: no-cache",
		"content-type: application/json",
		"postman-token: 9d224309-e7bb-508c-7922-4b66b0753d12"
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
		$result = json_decode($response);
		return $result->token;
	}
}

$get_nupay_token = getNupayToken();

//might be removed
function postToMuthoot($ZohoData, $AccessToken, $Rec_Id,$get_nupay_token){

	$jDecode = json_decode($ZohoData,true);
	$AccData=$jDecode["data"][0];
	
	//echo "<pre>"; print_r($AccData); echo "</pre>";
	foreach($AccData as $key=>$value)			
	{
		if ($key == 'Name')
		{	
			$str_arr = explode (" ", $value);
			$first_name = $str_arr[0]; 
			// $first_name = str_replace(" ", "" , $first_name);
			if($str_arr[1])
				$lastName = $str_arr[1];	
			elseif($str_arr[2])
				$lastName = $str_arr[1]." ".$str_arr[2];	
			else	
				$lastName = $str_arr[0];					
		}

    

		if($key=="Mobile"){
			$mobilePhone =$value;
		} 		
		//	if($key=="Gender")                          {$gender =$value;}
		if($key=="PIN_Code")       					{$pinCode =$value;}
		if($key=="Email")                           {$email =$value;}	
		if($key=="Loan_Amt_Rs_Lacs")                {$leadAmount = 100000;}
		if($key=="Original_GL_ID")               	{$orig_gl_id = $value;}


		//	if($key=="Salutation")                		{$Salutation = str_replace(".","",$value);}	   
	}

	$orig_gl_id = $Rec_Id;

    print_r(" second stage data ");
    print_r(" " .$mobilePhone);
    print_r(" " .$email);
    print_r(" " .$leadAmount);
    print_r("Orig id " .$orig_gl_id);
    print_r(" " .$pinCode);


	$Salutation ="Mr";
	$gender ="Male";


	$ch1 = curl_init();

	curl_setopt($ch1, CURLOPT_URL, 'https://middleware.muthoot.org:1880/DAB');
	curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch1, CURLOPT_POST, 1);
	curl_setopt($ch1, CURLOPT_POSTFIELDS, '{"mobile": "'.$mobilePhone.'","firstName" : "'.$first_name.'","lastName" : "'.$lastName.'","email" : "'.$email.'","pin" : '.$pinCode.',"utm" : "Dialabank","owner" : 1,"medium" : 204,"source" : 14,"leadcreatedbytype" : 3,"preferredChannel" : 2,"priority" : 5}');

	$headers = array();
	$headers[] = 'Owner: DAB';
	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Owner: Default Value utm Dial a bank';

	curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch1);

	print_r("THIRD STAGE ".$result);


	curl_close($ch1);

	// echo $result;

	echo "BREAK";

	print_r(" " .gettype($result). "  ");

	// $string = explode("NC WorkFlow", $result);

    $string = strval($result);

	print_r($string);
	$string1 = strstr($string, "NC WorkFlow");
	$string1 = substr($string1, 13, 20);
	$crm_no = str_replace('"', '', $string1);
	$crm_no = str_replace('<ItemKey>', '', $crm_no);
	$crm_no = str_replace('</', '', $crm_no);

    print_r("LAST NAME" .$lastName);

	print_r("CRM NUMBER ". $crm_no);

	$crm_no = str_replace("<", "", $crm_no);
	$crm_no = str_replace(",Dial a Ba", "", $crm_no);


	

	// print_r(" ".$string1);

	// var_dump(($string));

    if (curl_errno($ch1)) {
		echo 'Error:' . curl_error($ch1);
	}

		if (strpos($result, 'Lead Already Exist') == false && strpos($result, 'Muthoot_Existing_Cust') == false) {
			updateGL($AccessToken,$orig_gl_id,'GL_Ashok_1',$crm_no,'');
			// if ($crm_no != '') {
			print_r("UPDATING RECORD");
			updateGL_new($AccessToken,$Rec_Id,'GL_Ashok_1', $crm_no, '');
		}

		if ($crm_no == '') {

			updateGL_duplicate($AccessToken,$orig_gl_id,'GL_Ashok_1',$crm_no,'');
			// if ($crm_no != '') {
			print_r("UPDATING RECORD For Empty Value");
			updateGL_newduplicate($AccessToken,$Rec_Id,'GL_Ashok_1', $crm_no, '');

		}

		// }

    // }

}

postToMuthoot($ZohoData, $AccessToken, $Rec_Id,$get_nupay_token);

// might be removed
function createRecord($AccessToken,$Salutation,$first_name,$lastName,$gender,$mobilePhone,$email,$pinCode,$leadAmount,$get_nupay_token,$Rec_Id){
	$data = array(
		"Salutation"=>$Salutation,
		"FirstName"=>$first_name,
		"LastName" => $lastName,
		"Gender" => $gender,
		"MobilePhone" => $mobilePhone,
		"Email" => $email,
		"PinCode" => $pinCode,
		"LeadAmount" => $leadAmount,
		"cust_ref_no" => $Rec_Id
	);
	
	//$json_data = '{"mailto:salutation":"mr","firstname":"satisha","lastname":"singh","gender":"male","mobilephone":"7019472415","email":"satishaaaj5@gmail.com","PinCode":560018,"LeadAmount":100000}';
	$json_data = json_encode($data);
	echo "<pre>"; print_r($data); echo "</pre>"; 
	echo "<pre>"; print_r($json_data); echo "</pre>";
	echo "<pre>Refresh Token: "; print_r($get_nupay_token); echo "</pre>";
	
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://gl.nupaybiz.com/api/CRM_prod/generateLead",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_SSL_VERIFYHOST =>0,
		CURLOPT_SSL_VERIFYPEER=>0,
		CURLOPT_POSTFIELDS => $json_data,
		CURLOPT_HTTPHEADER => array(
			"api-key: Paynetjj3JhbUNmdfdm96VyTnVwjhfh21fs01k30",
			"token: $get_nupay_token"
			),
	));

	//$response = '{"StatusCode":"NP000","IsSuccess":"true","LeadId":"50785645","nupay_ref_no":13,"Message":"Lead created successfully"}';
	$response =  curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		//echo $response;
		$result = json_decode($response);
		$Message = $result->Message;
		$LeadId_ref = $result->LeadId;
		$nupay_ref = $result->nupay_ref_no;
		//echo "<pre>"; print_r($nupay_ref); echo "</pre>";
		if($result->IsSuccess == "true"){
			// updateGL($AccessToken,$Rec_Id,'GL_New',$LeadId_ref,$nupay_ref);
		}
		else{
			// updateExistCustomer($AccessToken,$Rec_Id,'GL_New',$Message,$nupay_ref);
		}
	}
}

function updateGL_new($AccessToken1,$Rec_Id,$module,$LeadId_ref,$nupay_ref){
	global $AccessToken;
	$module = "GL_Ashok_1";
	$url = "https://www.zohoapis.com/crm/v2/GL_Ashok_1/".$Rec_Id;

	$date = date("Y/m/d");
	$date = strval($date);

	$data='{ "data": [{"Muthoot_CRM_No": "'.str_replace("\n", " ", $LeadId_ref).'","AT_Cron_date":"'.$date.'", "Muthoot_Nupay_Ref_No": "'.str_replace("\n", " ", $nupay_ref).'"},], "trigger": ["approval"]}';

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL =>$url ,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",	
		CURLOPT_MAXREDIRS => 10,	
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "PUT",
		CURLOPT_POSTFIELDS => $data, 	
		CURLOPT_HTTPHEADER => array(
		"Authorization: Zoho-oauthtoken ".$AccessToken,
		"Content-Type: application/json"
		),
	));

	$response = curl_exec($curl);
	print_r($response);

	$err = curl_error($curl);
	curl_close($curl);
	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		echo "<script> jsFunction('https://crm.zoho.com/crm/org24981036/tab/GL/$Rec_Id'); </script>";
		// header("LOCATION: https://crm.zoho.com/crm/org24981036/tab/CustomModule2/".$Rec_Id);
	}
}


function updateGL_newduplicate($AccessToken1,$Rec_Id,$module,$LeadId_ref,$nupay_ref){
	global $AccessToken;
	$LeadId_ref = "Lead Already Exist";
	$module = "GL_Ashok_1";
	$url = "https://www.zohoapis.com/crm/v2/GL_Ashok_1/".$Rec_Id;

	print_r("RECORD ID ". $Rec_Id);

	$date = date("Y/m/d");
	$date = strval($date);

	$data='{ "data": [{"Muthoot_Existing_Cust": "Lead Already Exist","AT_Cron_date":"'.$date.'", "Muthoot_Nupay_Ref_No": "'.str_replace("\n", " ", $nupay_ref).'"},], "trigger": ["approval"]}';

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL =>$url ,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "PUT",
		CURLOPT_POSTFIELDS => $data,
		CURLOPT_HTTPHEADER => array(
		"Authorization: Zoho-oauthtoken ".$AccessToken,
		"Content-Type: application/json"
		),
	));

	$response = curl_exec($curl);
	print_r($response);
	$err = curl_error($curl);
	curl_close($curl);
	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		echo "<script> jsFunction('https://crm.zoho.com/crm/org24981036/tab/GL/$Rec_Id'); </script>";
		// header("LOCATION: https://crm.zoho.com/crm/org24981036/tab/CustomModule2/".$Rec_Id);
	}
}

function updateGL($AccessToken1,$Rec_Id,$module,$LeadId_ref,$nupay_ref){
	global $AccessToken;
	$module = "GL";
	$url = "https://www.zohoapis.com/crm/v2/GL_Ashok_1/".$Rec_Id;

	$data='{ "data": [{"Muthoot_CRM_No": "'.str_replace("\n", " ", $LeadId_ref).'","Muthoot_Nupay_Ref_No": "'.str_replace("\n", " ", $nupay_ref).'"},], "trigger": ["approval"]}';

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL =>$url ,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "PUT",
		CURLOPT_POSTFIELDS => $data,
		CURLOPT_HTTPHEADER => array(
		"Authorization: Zoho-oauthtoken ".$AccessToken,
		"Content-Type: application/json"
		),
	));

	$response = curl_exec($curl);
	print_r($response);

	$err = curl_error($curl);
	curl_close($curl);

	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		echo "<script> jsFunction('https://crm.zoho.com/crm/org24981036/tab/GL/$Rec_Id'); </script>";
		// header("LOCATION: https://crm.zoho.com/crm/org24981036/tab/CustomModule2/".$Rec_Id);
	}
}


function updateGL_duplicate($AccessToken1,$Rec_Id,$module,$LeadId_ref,$nupay_ref){
	global $AccessToken;
	$module = "GL";
	print_r("RECORD ID". $Rec_Id);
	$LeadId_ref = "Lead Already Exists";
	$url = "https://www.zohoapis.com/crm/v2/GL_Ashok_1/".$Rec_Id;

	$data='{ "data": [{"Muthoot_Existing_Cust": "Lead Already Exist","Muthoot_Nupay_Ref_No": "'.str_replace("\n", " ", $nupay_ref).'"},], "trigger": ["approval"]}';

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL =>$url ,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "PUT",
		CURLOPT_POSTFIELDS => $data,
		CURLOPT_HTTPHEADER => array(
		"Authorization: Zoho-oauthtoken ".$AccessToken,
		"Content-Type: application/json"
		),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);
	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		echo "<script> jsFunction('https://crm.zoho.com/crm/org24981036/tab/GL/$Rec_Id'); </script>";
		// header("LOCATION: https://crm.zoho.com/crm/org24981036/tab/CustomModule2/".$Rec_Id);
	}
}
	
function updateExistCustomer($AccessToken1,$Rec_Id,$module,$Message,$nupay_ref){
	global $AccessToken;
	$module = "GL";
	$url = "https://www.zohoapis.com/crm/v2/GL_Ashok_1/".$Rec_Id;
	$data='{ "data": [{"Muthoot_CRM_No": "'.str_replace("\n", " ", $nupay_ref).'"},], "trigger": ["approval"]}';
	
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL =>$url ,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "PUT",
		CURLOPT_POSTFIELDS => $data,
		CURLOPT_HTTPHEADER => array(
		"Authorization: Zoho-oauthtoken ".$AccessToken,
		"Content-Type: application/json"
		),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);
	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		echo "<script> jsFunction('https://crm.zoho.com/crm/org24981036/tab/GL/$Rec_Id'); </script>";
		// header("LOCATION: https://crm.zoho.com/crm/org24981036/tab/CustomModule2/".$Rec_Id);
	}
}
?>
