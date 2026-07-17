<?php

$clientId = getenv("ZOHO_CLIENT_ID") ?: "1000.B45XORRZTDGF92YWO16OVLADJBIIEW";
$clientSecret = getenv("ZOHO_CLIENT_SECRET") ?: "db8b19419f3afc519e089d09bdf383bea7357b31e1";
$redirectUri = getenv("ZOHO_REDIRECT_URI") ?: "https://crm.zoho.com/";
$refreshToken = getenv("ZOHO_REFRESH_TOKEN") ?: "1000.88986f9b8c1e8d2257ed4ae8a7d13263.6a946b736a5755b571b6775b9252bf6b";

$curl = curl_init();

$accountsUrl = getenv("ZOHO_ACCOUNTS_URL") ?: "https://accounts.zoho.com";

curl_setopt_array($curl, array(
  CURLOPT_URL => $accountsUrl . '/oauth/v2/token',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => http_build_query(array(
    "grant_type" => "refresh_token",
    "client_id" => $clientId,
    "client_secret" => $clientSecret,
    "redirect_uri" => $redirectUri,
    "refresh_token" => $refreshToken,
  )),
  CURLOPT_HTTPHEADER => array(
    "Content-Type: application/x-www-form-urlencoded",
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;

// Decode the JSON response
$data = json_decode($response, true);

// Check if access_token exists
if (isset($data['access_token'])) {
    $data["generated_at"] = time();
    $data["expires_at"] = time() + ((int)($data["expires_in"] ?? 3600));
    $file = fopen("auth_token.txt", "w");
    fwrite($file, json_encode($data));
    fclose($file);

    echo "\nAuth token saved successfully to auth_token.txt";
} else {
    echo "\nAuth token not found in response.";
}

