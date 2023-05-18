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
        $data = DB::table('products')->whereIn('ProductNo', $request->productNos)->get();

        return $data;
    }
}
