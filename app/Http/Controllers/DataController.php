<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

        $data = DB::table('products')
        ->join('product_images', 'products.ProductNo', '=', 'product_images.ProductNo')
        ->whereIn('products.ProductNo', $arr)->get();

        $txt = "Product Name,Product Description,Category,ProductCode,Media Standard Url 1,Media Standard AltText 1 \n";

        foreach($data as $single){
            $productNo = str_replace(',', '-', $single->ProductNo);
            $productName = str_replace(',', '-', $single->Description);
            $productImage = str_replace(',', '-', $single->ImageURL);
            $Category = str_replace(',', '-', $single->Category);

            $txt .= "$productName,$productName,$Category,$productNo,$productImage,  \n";
        }

        Storage::delete('data.csv');

        Storage::put('data.csv', $txt);

        return $data;
    }


    public function getLargeXml(Request $request)
    {
        Artisan::call('getflakimages');

        return response (['message' => 'Done']);

    }


    public function getCSV()
    {
        $data = DB::table('products')->get();

        $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");

        $txt = "Product Name,Product Description,Category,ProductCode,Media Standard Url 1,Media Standard AltText 1 \n";

        foreach($data as $single){
            $txt .= str_replace(',', '-', $single->Description) . "," . str_replace(',', '-', $single->Description). ",". ",,$single->ProductNo,,, \n";
        }


        Storage::put('ghani.csv', $txt);

    }
}
