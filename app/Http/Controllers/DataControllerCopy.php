<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\BringOrder;
use App\Models\Package;
use App\Facades\SendMarketingCloudApiJourneyRequest as MC;


class DataController extends Controller
{
    public function getData()
    {

        Artisan::call('getflakdata');

        return response(['message' => 'Done']);

    }

    public function getProductsData(Request $request)
    {

        if($request->productNos && is_array($request->productNos) && count($request->productNos) > 0){
            $arr = $request->productNos;
        }else{
            $arr = [];
        }

        
        
        if(count($arr) > 0){
            $data = DB::table('products')
            ->join('product_images', 'products.ProductNo', '=', 'product_images.ProductNo')
            ->whereIn('products.ProductNo', $arr)
            ->get();
        }  else{
            $data = DB::table('products')
            ->join('product_images', 'products.ProductNo', '=', 'product_images.ProductNo')
            ->get();
        }
        

        $txt = "Product Name,Product Description,Category,ProductCode,SRP,Media Standard Url 1,Media Standard AltText 1 \n";

        foreach($data as $single){
            $productNo = str_replace(',', '-', $single->ProductNo ?? '');
            $productName = str_replace(',', '-', $single->Description ?? '');
            $productImage = str_replace(',', '-', $single->ImageURL ?? '');
            $Category = str_replace(',', '-', $single->Category ?? '');
            $SRP = $single->SRP;

            $txt .= "$productName,$productName,$Category,$productNo,$SRP,$productImage,  \n";
        }

        Storage::put('data.csv', $txt);

        return $data;
    }


    public function getLargeXml(Request $request)
    {
        Schema::dropIfExists('product_images');

        Schema::create('product_images', function (Blueprint $table) {
            $table->string('ProductNo')->nullable();
            $table->string('ImageURL')->nullable();
            $table->string('Category')->nullable();
            $table->string('WeightUOM')->nullable();
            $table->string('LengthUOM')->nullable();
            $table->string('HeightUOM')->nullable();
            $table->string('WidthUOM')->nullable();
        });


        $contents = Storage::get('flak.xml');

        $new = simplexml_load_string($contents);

        // Convert into json
        $con = json_encode($new);
        
        // Convert into associative array
        $newArr = json_decode($con, true);

        $data = array_chunk($newArr['Products']['Product'], 200);

        foreach($data as $singleDataArr){
            $dataArr = [];

            foreach($singleDataArr as $singleData){
                $newData = [];
                foreach($singleData as $key => $value){
                    if($key == 'ProductNo' || $key == 'ImageURL' || $key == 'Category' || $key == 'WeightUOM' || $key == 'LengthUOM' || $key == 'WidthUOM' || $key == 'HeightUOM'){
                            $newData[$key] = is_array($singleData[$key]) ? implode('/', $singleData[$key]) : str_replace(',', '/', $singleData[$key]);
                    }
                }
                array_push($dataArr, $newData);
            }

            DB::table('product_images')->insert($dataArr);
        }

        return response (['message' => 'Done']);

    }



    public function getBringProducts(Request $request)
    {

        if(!$request->PostalCode){
            return response(['message' => 'error']);
        }

        Log::info('PostCode => ' . $request->PostalCode . ' Weight => ' . $request->weight );

        $weight = $request->weight == 0.0 ? 1 : $request->weight;

        $response = Http::timeout(120)->post('https://api.bring.com/shippingguide/api/v2/products');

        $data = '{"consignments": [ {"fromCountryCode": "NO","fromPostalCode": "1940","id": "1","packages": [ {"grossWeight": '.$weight.',"id": "1"}],"products": [{"customerNumber": "20000062370","id": "5600"},{"customerNumber": "20000062370", "id": "5800"}],"toCountryCode": "NO", "toPostalCode": "'.$request->PostalCode.'"}],"edi": false,"language": "NO","numberOfAlternativeDeliveryDates": 3,"postingAtPostoffice": false,"trace": false,"withExpectedDelivery": true,"withGuiInformation": true,"withPrice": true,"withUniqueAlternateDeliveryDates": true}';


        $response = Http::withBody($data, 'application/json')
        ->withHeaders([
            'X-Mybring-API-Uid' => 'hmb@pionerboat.com',
            'X-Mybring-API-Key' => '7e131d95-8358-4341-902e-bb2a0c5ba44e',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->post('https://api.bring.com/shippingguide/api/v2/products')->body();

        $newArr = json_decode($response, true);

        $productsArr = [];

        if(array_key_exists('consignments', $newArr)){

            if(count($newArr['consignments']) > 0){
                foreach($newArr['consignments'] as $key=>$value){
                    $products = $value['products'];

                    

                    foreach($products as $singleProduct){

                        if(array_key_exists('errors', $singleProduct)){
                            continue;
                        }else{
                            $array = [
                                'BringProductId' => $singleProduct['id'],
                                'serviceName' => $singleProduct['guiInformation']['productName'],
                                'provider' => 'Bring',
                                'rate'  =>  $singleProduct['price']['listPrice']['priceWithoutAdditionalServices']['amountWithVAT'],
                                'BringPickupPointId' => ''
                            ];
    
                            array_push($productsArr, $array);
                        }

                    }
                }
            }

            
        }

        $deliveryZip = $request->PostalCode;

        $pickupUrl = 'https://api.bring.com/pickuppoint/api/pickuppoint/NO/postalCode/' . $deliveryZip;

        $response = Http::withBody($data, 'application/json')
        ->withHeaders([
            'X-Mybring-API-Uid' => 'hmb@pionerboat.com',
            'X-Mybring-API-Key' => '7e131d95-8358-4341-902e-bb2a0c5ba44e',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->get($pickupUrl)->body();




        $newArr = json_decode($response, true);


        $points = [];

        foreach($newArr as $singlePoint){
            if(array_key_exists('pickupPoint', $newArr)){
                foreach($singlePoint as $key => $value){
                    $array = [
                        'id' => $value['id'],
                        'address' => $value['address'] . ', ' . $value['postalCode'] . ' ' . $value['city']
                    ];
                    array_push($points, $array);
                }
            }
        }

        $lastArr = [];

        foreach($productsArr as $singlePr){
            if($singlePr['BringProductId'] == 5800){
                foreach($points as $key => $singlePoint){
                    $array1 = $singlePr;
                    $array1['serviceName'] = '5800', $singlePoint['id'] ?? 'NA' . ']]' . $array1['serviceName'].'-'.$singlePoint['address'];
                    $array1['BringPickupPointId'] = $singlePoint['id'];
                    array_push($lastArr, $array1);
                }
            }else{
                array_push($lastArr, $singlePr);
            }
        }

        return $lastArr;

    }


    public function getProductsXLData(Request $request)
    {
        $reqArr = explode(',', $request->fields);

        $txt = '';

        $count = count($reqArr);

        for ($i = 0; $i < $count ; $i++) {

            if($i == $count - 1){
                $txt.= $reqArr[$i]. ", \n";
            }else{
                $txt.= $reqArr[$i]. ',';
            }
        }


        $data= DB::table('product_images')->get($reqArr);


        foreach($data as $single){
            foreach($reqArr as $key => $value){
                if($key == $count - 1){
                    $txt .= $single->{$value} . ", \n";
                }else{
                    $txt .= $single->{$value} . ',';
                }
            }
        }


        Storage::put('alldata.csv', $txt);

        return response(['message' => 'done']);       

    }
    


    public function createBringBooking(Request $request)
    {
        $pickupUrl = 'https://api.bring.com/booking-api/api/booking';

        $data = $request->body ?? '{"consignments":[{"correlationId":"517796","packages":[{"containerId":null,"correlationId":"517796","dimensions":{"heightInCm":3,"lengthInCm":23,"widthInCm":17},"goodsDescription":null,"packageType":null,"volumeInDm3":2,"weightInKg":5}],"parties":{"pickupPoint":{"countryCode": "DK",  "id": "599435" },"recipient":{"additionalAddressInfo":null,"addressLine":"Bassengvegen10","addressLine2":null,"city":"Oslo","contact":{"email":"demo@online.no","name":"DemoRecipientContact","phoneNumber":"+4791234567"},"countryCode":"NO","name":"DemoRecipient","postalCode":"0185","reference":null},"sender":{"additionalAddressInfo":null,"addressLine":"Industriveien1","addressLine2":null,"city":"Oslo","contact":{"email":null,"name":"DemoSenderContact","phoneNumber":"+4763853000"},"countryCode":"NO","name":"Pioner Boat","postalCode":"1940","reference":"517796"}},"product":{"customerNumber":"20000062370","id":"5800"},"shippingDateTime":"2023-08-27T12:59:30"}],"schemaVersion":1,"testIndicator":true}';
        $data = $request->body ?? '{"consignments":[{"correlationId":"517796","packages":[{"containerId":null,"correlationId":"517796","dimensions":{"heightInCm":3,"lengthInCm":23,"widthInCm":17},"goodsDescription":null,"packageType":null,"volumeInDm3":2,"weightInKg":5}],"parties":{"pickupPoint":{"countryCode": "DK",  "id": "599435" },"recipient":{"additionalAddressInfo":null,"addressLine":"Bassengvegen10","addressLine2":null,"city":"Oslo","contact":{"email":"demo@online.no","name":"DemoRecipientContact","phoneNumber":"+4791234567"},"countryCode":"NO","name":"DemoRecipient","postalCode":"0185","reference":null},"sender":{"additionalAddressInfo":null,"addressLine":"Industriveien1","addressLine2":null,"city":"Oslo","contact":{"email":null,"name":"DemoSenderContact","phoneNumber":"+4712345678"},"countryCode":"NO","name":"DemoSender","postalCode":"0010","reference":"517796"}},"product":{"customerNumber":"20000062370","id":"5800"},"shippingDateTime":"2022-08-27T12:59:30"}],"schemaVersion":1,"testIndicator":true}';


        // Log::info('body => ' . $request->body );

        // return $request->body;

        // dd(json_encode($request->body));

        $response = Http::withBody(json_encode($data), 'application/json')
        ->withHeaders([
            'X-Mybring-API-Uid' => 'hmb@pionerboat.com',
            'X-Mybring-API-Key' => '7e131d95-8358-4341-902e-bb2a0c5ba44e',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Bring-Client-URL' => 'https://www.pionerboat.shop/'
        ])->post($pickupUrl);


        if($response->status() == 200){
            $data = json_decode($response->body(), true);

            $orderNumber = $request->orderNumber;

            $contactId = $request->contactId;

            $email = $request->email;

            $consignmentNumber = $data['consignments'][0]['confirmation']['consignmentNumber'];

           



            $bringOrder = BringOrder::create([
                'email' => $email,
                'contact_id' => $contactId,
                'order_number' => $orderNumber,
                'bring_consignment_number' => $data['consignments'][0]['confirmation']['consignmentNumber'],
                'labels' => $data['consignments'][0]['confirmation']['links']['labels'],
                'tracking' => $data['consignments'][0]['confirmation']['links']['tracking']
            ]);


            $packages = $data['consignments'][0]['confirmation']['packages'];

            foreach($packages as $package){
                Package::create([
                    'bring_order_id' => $bringOrder->id,
                    'package_number' => $package['packageNumber'],
                    'correlationId'  => $package['correlationId'] 
                ]);
            }


            $hookBody = '{"configuration": {"content_type": "application/json","headers": [{"key": "Content-Type","value": "application/json"}],"url": "https://flak.pionerboat.no/public/index.php/bring-order-status/"},"event_groups": ["DELIVERED","IN_TRANSIT","RETURN"],"trackingId": "'.$consignmentNumber.'"}';



            $url = 'https://api.bring.com/event-cast/api/v1/webhooks';


            $hookRes = Http::withBody($hookBody, 'application/json')
                ->withHeaders([
                    'X-Mybring-API-Uid' => 'hmb@pionerboat.com',
                    'X-Mybring-API-Key' => '7e131d95-8358-4341-902e-bb2a0c5ba44e'
                ])->post($url);


            if($hookRes->status() == 201){
                $hookId = json_decode($hookRes->body(),true)['id'];
                $bringOrder->update(['hook_id' => $hookId]);
            }


            

            return response ([
                'hook' => $hookRes->body(),
                'message' => 'Booking Created', 
                'consignmentNumber' => $data['consignments'][0]['confirmation']['consignmentNumber'], 
                'labels' => $data['consignments'][0]['confirmation']['links']['labels'],
                'tracking' => $data['consignments'][0]['confirmation']['links']['tracking']
            ]);
        }else{

            $data = json_decode($response->body(), true);

            $messages = [];

            foreach($data['consignments'][0]['errors'] as $error){
                array_push($messages, $error['messages'][0]['message']);
            }

            return response ([
                'messages' => $messages
            ], 400);
        }
    }






    public function processBringOrderChangeRequest(Request $request)
    {

        \Log::channel('bring-hook-response')->info(json_encode($request->all()));

        $data = $request->all();

        $status = $data['status'];
        $consignmentNumber = $data['shipment'];
        $hookId = $data['id'];

        $istest = null;

        if($consignmentNumber == null){
            $istest = true;
        }

        MC::sendRequest($consignmentNumber, $status, $istest, $hookId);

    }


    public function createHook(Request $request)
    {

        $hookBody = '{"configuration": {"content_type": "application/json","headers": [{"key": "Content-Type","value": "application/json"}],"url": "https://flak.pionerboat.no/public/index.php/bring-order-status/"},"event_groups": ["DELIVERED","IN_TRANSIT","RETURN"],"trackingId": "70722151297667044"}';

        $url = 'https://api.bring.com/event-cast/api/v1/webhooks';


        $hookRes = Http::withBody($hookBody, 'application/json')
            ->withHeaders([
                'X-Mybring-API-Uid' => 'hmb@pionerboat.com',
                'X-Mybring-API-Key' => '7e131d95-8358-4341-902e-bb2a0c5ba44e'
            ])->post($url);

        return json_decode($hookRes->body(), true);
    }

}
