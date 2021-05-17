<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    include_once("vendor/autoload.php");

    MercadoPago\SDK::setAccessToken('APP_USR-6303223304445950-041119-2258a73cb301ac1d1cab88b1eccf12c1-542833570');

    MercadoPago\SDK::setClientId('6303223304445950');
    MercadoPago\SDK::setClientSecret('4lwuUSWAEiqKfqKJ1lPuK0NXemEArcqZ');


    

    $merchant_order = null;
    switch($_GET["topic"]) {
        case "payment":
            $payment = MercadoPago\Payment::find_by_id($_GET["id"]);

            $merchant_order = MercadoPago\MerchantOrder::find_by_id($_GET["id"]);
    	break;

        case "plan":
            $plan = MercadoPago\Plan.find_by_id($_GET["id"]);
        break;

        case "subscription":
            $plan = MercadoPago\Subscription.find_by_id($_GET["id"]);
        break;

        case "invoice":
            $plan = MercadoPago\Invoice.find_by_id($_GET["id"]);
        break;

        case "merchant_order":
        	$merchant_order = MercadoPago\MerchantOrder::find_by_id($_GET["id"]);
        break;
    }

    $paid_amount = 0;
    if ($payment->status == 'approved'){
        $paid_amount += $payment->transaction_amount;
    }

    if($paid_amount >= $payment->transaction_amount){
        if (isset($merchant_order->shipments) && $merchant_order->shipments > 0) { // The merchant_order has shipments
            if($merchant_order->shipments[0]->status == "ready_to_ship") {
                print_r("Totally paid. Print the label and release your item.");
            }
        } else { // The merchant_order don't has any shipments
        	print_r("Totally paid. Release your item.<br>");
            $data = [
                'product' => [],
                'client' => [],
                'status' => $payment->status,
                'external_reference' => $payment->external_reference,
                'tax_mp' => number_format($payment->fee_details[0]->amount, 2, ',', '.'),
                'tax_li' => 0,
                'total' => number_format($payment->transaction_amount, 2, ',', '.'),
                'subtotal' => number_format($payment->transaction_details->net_received_amount, 2, ',', '.')
            ];

            if(isset($payment->fee_details[1]->amount)){
                $data['tax_li'] = number_format($payment->fee_details[1]->amount, 2, ',', '.');
            }

            array_push($data['product'], ['name' => $payment->description]);
            array_push($data['client'], [
                'firstname' => ucfirst($payment->payer->first_name),
                'lastname' => ucfirst($payment->payer->last_name),
                'email' => $payment->payer->email,
                'phone' => isset($payment->payer->phone->number) ? $payment->payer->phone->number : 0
            ]);
            
            // var_dump($payment->payer);
            // var_dump($payment->additional_info->items[1]);
            // var_dump($data);

            $url = 'https://api.salescode.dev:8081/sc/payment';

            // // use key 'http' even if you send the request to https://...
            // $options = array(
            //     'http' => array(
            //         'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            //         'method'  => 'POST',
            //         'content' => http_build_query($data)
            //     )
            // );
            // $context  = stream_context_create($options);
            // $result = file_get_contents($url, false, $context);

            // if ($result === FALSE) { /* Handle error */ }

            // var_dump($result);

            //url-ify the data for the POST
            $fields_string = http_build_query($data);

            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, true);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

            //So that curl_exec returns the contents of the cURL; rather than echoing it
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

            //execute post
            $result = curl_exec($ch);
            curl_close($ch);
            echo $result;

        }
    } else {
			print_r("Not paid yet. Do not release your item.");
    }
}
?>