<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

class DataController extends Controller
{
    public function getData()
    {
        $response = Http::get('http://webserver.flak.no/vbilder/FlakXMLStockBalance.xml');

        $new = simplexml_load_string($response->body());
  
        // Convert into json
        $con = json_encode($new);
        
        // Convert into associative array
        $newArr = json_decode($con, true);

        // dd($newArr['StockBalances']['StockBalance']);

        return json_decode(json_encode($newArr['StockBalances']['StockBalance']));

        $arr = [
            [
                'ProductNo' => 1036922,
                'Description' => "Emma Hedström",
                "SRP" => 109.000000,
                "StockBalance" => 20
            ]
        ];


        return json_decode(json_encode($arr), true);




    }
}
