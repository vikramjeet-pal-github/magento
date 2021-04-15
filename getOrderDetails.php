<?php
$userData = array("username" => "admin123", "password" => "admin@123");
$baseUrl = "http://localhost/molekule"; // your magento base url
$ch = curl_init($baseUrl."/rest/V1/integration/admin/token");
 $data_json = [
        "entity"=> [
            "entity_id" => 5142,
            "customerId" => 2225,
            "product_sku" => "nbfndbfn"
            
            
        ]
    ];
 echo $data_string = json_encode($data_json);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Lenght: " . strlen(json_encode($userData))));

$token = curl_exec($ch);

$ch = curl_init($baseUrl."/rest/V1/orders/create/?entity=5142&payment=Cash On Delivery");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . json_decode($token)));

$result = curl_exec($ch);
    $result = json_decode($result, 1);
    echo "-----------update order----------------";
    echo "<pre>";
    print_r($result);