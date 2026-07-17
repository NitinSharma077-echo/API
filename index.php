<?php

$AccessToken= '1000.5c91aec9fd7ee3f8a6a5c1d370ec5882.3c99ca759a9167e43a266175b66e70e8';

$json = file_get_contents("auth_token.txt");

// Convert to array
$data = json_decode($json, true);

// Extract only the access token
$AccessToken = $data["access_token"];

// print_r($AccessToken);

// exit();

$Rec_Id=$_GET['id'];  

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