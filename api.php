<?php

$curl = curl_init();

curl_setopt_array($curl, [
  CURLOPT_URL => 'https://api.bukalapak.com/notifications/messages?offset=0&limit=12&platform=onsite_agenlite&exclude_categories%5B%5D=promotion&exclude_categories%5B%5D=product-recommendation&exclude_categories%5B%5D=event&exclude_categories%5B%5D=feature-renewal&exclude_categories%5B%5D=mitra-operational-info',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => [
    'User-Agent: Dalvik/2.1.0 (Linux; U; Android 11; SM-A366B Build/AP3A.240905.015.A2) 2052002 BLMitraAndroid',
    'Accept: application/json',
    'Accept-Encoding: gzip',
    'bukalapak-mitra-version: 2052002',
    'x-user-id: 179884941',
    'x-device-ad-id: 00000000-0000-0000-0000-000000000000',
    'bukalapak-identity: a0e8201cf079d42a',
    'bukalapak-app-version: 4037005',
    'ad-user-agent: com.bukalapak.mitra/2.52.2 (Android 15; en_US; SM-A366B; Build/AP3A.240905.015.A2)',
    'conversion-tracking-params: 00000000-0000-0000-0000-000000000000 15 30 2.52.2',
    'http-referrer: ',
    'authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6ImFjY291bnRzLmp3dC5hY2Nlc3MtdG9rZW4iLCJ0eXAiOiJKV1QifQ.eyJpc3MiOiJodHRwczovL2FjY291bnRzLmJ1a2FsYXBhay5jb20vIiwic3ViIjoiMTc5ODg0OTQxIiwiYXVkIjpbImh0dHBzOi8vYWNjb3VudHMuYnVrYWxhcGFrLmNvbSIsImh0dHBzOi8vYXBpLmJ1a2FsYXBhay5jb20iLCJodHRwczovL2FwaS5zZXJ2ZXJtaXRyYS5jb20iXSwiZXhwIjoxNzUxMTA1MDIwLCJuYmYiOjE3NTEwMDA2ODAsImlhdCI6MTc1MTAwMDY4MCwianRpIjoiNGkyWW16eWRJYk5tdDZmY0w0ejJJUSIsImNsaWVudF9pZCI6ImY3NWI3NGM0YmM1MTZhN2NiMWE5Mjk2YyIsInNjb3BlIjoicHVibGljIHVzZXIgc3RvcmUgKi4qLioifQ.iok04U8aQ9VTXUCQGnWz0ZUD4K4jcivRPAr7WAjcYqKTg4RQTBxEauL-vI2jikIxj6-UsdUjnDF8Egj34coN7dHMJfmwwpN34iRH_Qg7XFQAg4M7VqI25JLBi-OYKS3brFnebI3u-bVnvWLV7xRp5xhtW1gobpfboKx5QCZJxOkqm-qqSqJaMcKxSRB28f8MlCd-tt4QhF1iHKgt2AyarFzcQmqcf3z2y30Hu6QLZzWJdtbon1Svq9_FzgPu14GezwS6PtVB2c1-hhaVP8ii64zdng0U-nWlNrn3xAW5lMX4YIYzx4zopY404Kyy_d91U7dCqgTu9NBNHGyiRyTXSg',
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo 'cURL Error #:' . $err;
} else {
  echo $response;
}