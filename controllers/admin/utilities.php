<?php

    require 'vendor/autoload.php';
    use GuzzleHttp\Client;


    $config = [
        "companyName" => "Whizmo Tech",
        "phoneNumber" => "88362145",
        "email"       => "whizmotech@gmail.com",
        "postal"      => "569139",
        "address"     => "3 Ang Mo Kio St 62, #06-16 (D) LINK@AMK (S) 569139"
    ];

    function callAPI($method, $url, $data){

        switch ($method) {
            case 'GET':
                $client = new GuzzleHttp\Client();
                $response = $client->request('GET', 'http://18.140.52.199:80'. $url);
                return $response;
                break;
            case 'POST':

                $client = new GuzzleHttp\Client(['base_uri' => 'http://18.140.52.199:80']);
                $response = $client->request('POST', $url, [
                    'json' => $data
                ]);
                return $response;
                break;
            default:
                # code...
                break;
        }
        
    }

    function formatOrderForPickupp($data,$orderNumber){

        global $config;

        switch ($data['roadbul_PickupTimeSlotId']) {

           

            case '26':

                $pickupTime = "12:00:00";
                $dropoffTime = "16:00:00";
                $pickupDate = $data['roadbul_PickupDate'];
                $dropoffDate = $data['roadbul_PickupDate'];
                $pickDatetime = $data['roadbul_PickupDate'].' '.$pickupTime;
                $dropDatetime = $data['roadbul_PickupDate'].' '.$dropoffTime;
                $dt = date_create_from_format( "d/m/Y H:i:s", $pickDatetime ); 
                $pickupDateTime = date_format($dt,"Y-m-d H:i:s");
                $dropoff = date_create_from_format( "d/m/Y H:i:s", $dropDatetime ); 
                $dropoffDateTime = date_format($dropoff,"Y-m-d H:i:s");
                
                break;

            case '27':
                $pickupTime = "16:00:00";
                $dropoffTime = "20:00:00";
                $pickupDate = $data['roadbul_PickupDate'];
                $dropoffDate = $data['roadbul_PickupDate'];
                $pickDatetime = $data['roadbul_PickupDate'].' '.$pickupTime;
                $dropDatetime = $data['roadbul_PickupDate'].' '.$dropoffTime;
                $dt = date_create_from_format( "d/m/Y H:i:s", $pickDatetime ); 
                $pickupDateTime = date_format($dt,"Y-m-d H:i:s");
                $dropoff = date_create_from_format( "d/m/Y H:i:s", $dropDatetime ); 
                $dropoffDateTime = date_format($dropoff,"Y-m-d H:i:s");


                
                break;

            case '28':

                $pickupTime = "15:00:00";
                $dropoffTime = "19:00:00";
                $pickupDate = $data['roadbul_PickupDate'];
                $dropoffDate = $data['roadbul_PickupDate'];
                $pickDatetime = $data['roadbul_PickupDate'].' '.$pickupTime;
                $dropDatetime = $data['roadbul_PickupDate'].' '.$dropoffTime;
                $dt = date_create_from_format( "d/m/Y H:i:s", $pickDatetime ); 
                $pickupDateTime = date_format($dt,"Y-m-d H:i:s");
                $dropoff = date_create_from_format( "d/m/Y H:i:s", $dropDatetime ); 
                $dropoffDateTime = date_format($dropoff,"Y-m-d H:i:s");

                break;

            case '29':

                $pickupTime = "09:00:00";
                $dropoffTime = "13:00:00";
                $pickupDate = $data['roadbul_PickupDate'];
                $dropoffDate = $data['roadbul_PickupDate'];
                $pickDatetime = $data['roadbul_PickupDate'].' '.$pickupTime;
                $dropDatetime = $data['roadbul_PickupDate'].' '.$dropoffTime;
                $dt = date_create_from_format( "d/m/Y H:i:s", $pickDatetime ); 
                $pickupDateTime = date_format($dt,"Y-m-d H:i:s");
                $dropoff = date_create_from_format( "d/m/Y H:i:s", $dropDatetime ); 
                $dropoffDateTime = date_format($dropoff,"Y-m-d H:i:s");
                $data["roadbul_ToName"] = $config['companyName'];
                $data["roadbul_ToMobilePhone"] = $config['phoneNumber'];
                $data["roadbul_ToAddress"] = $config['address'];
                $data["roadbul_ToZipCode"] = $config['postal'];

                break;

        }

        $format = array(
            "orderId" => (int) $data["order_id"],
            "pickupCustomerName" => $data["roadbul_FromName"],
            "pickupCustomerNumber" => $data["roadbul_FromMobilePhone"],
            "pickupCustomerAddress" => $data["roadbul_FromAddress"],
            "pickupCustomerPostal" => $data["roadbul_FromZipCode"],
            "dropoffCustomerName" => $data["roadbul_ToName"],
            "dropoffCustomerNumber" => $data["roadbul_ToMobilePhone"],
            "dropoffCustomerAddress" => $data["roadbul_ToAddress"],
            "dropoffCustomerPostal" => $data["roadbul_ToZipCode"],
            "itemName" => "Used Item",
            "itemLength" => 20,
            "itemWidth" => 20,
            "itemHeight" => 10,
            "itemWeight" => 1,
            "pickupDatetime" =>  $pickupDateTime,
            "dropoffDatetime" => $dropoffDateTime,
            "customerNote" => $data['customerNote'],
            "merchantDefinedCode" => "RB".substr($orderNumber, -6)
        );
        return $format;
    }


?>