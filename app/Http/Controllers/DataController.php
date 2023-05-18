<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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
}
