<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Database\Schema\Blueprint;


class DataController extends Controller
{
    public function getData()
    {

        Artisan::call('getflakdata');

        return response(['message' => 'Done']);

    }

    public function getProductsData(Request $request)
    {

        if($request->productNos && is_array($request->productNos)){
            $arr = $request->productNos;
        }else{
            $arr = [];
        }

        $data = DB::table('products')->whereIn('ProductNo', $arr)->get();

        return $data;
    }


    public function getLargeXml(Request $request)
    {
        Schema::dropIfExists('product_images');

        Schema::create('product_images', function (Blueprint $table) {
            $table->string('ProductNo');
            $table->string('ImageURL');
        });


        $contents = Storage::get('flak.xml');

        $new = simplexml_load_string($contents);

        // Convert into json
        $con = json_encode($new);
        
        // Convert into associative array
        $newArr = json_decode($con, true);

        $data = array_chunk($newArr['Products']['Product'], 200);

        // dd($data);

        foreach($data as $singleDataArr){
            dispatch(new \App\Jobs\addProductImage($singleDataArr));
        }

    }
}
