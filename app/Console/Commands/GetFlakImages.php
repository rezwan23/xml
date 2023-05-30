<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Storage;

class GetFlakImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'getflakimages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get and Store Flak Images form stored xml file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Schema::dropIfExists('product_images');

        Schema::create('product_images', function (Blueprint $table) {
            $table->string('ProductNo')->nullable();
            $table->string('ImageURL')->nullable();
            $table->string('Category')->nullable();
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
                    if($key == 'ProductNo' || $key == 'ImageURL' || $key == 'Category'){
                            $newData[$key] = is_array($singleData[$key]) ? implode('/', $singleData[$key]) : str_replace(',', '/', $singleData[$key]);
                    }
                }
                array_push($dataArr, $newData);
            }

            DB::table('product_images')->insert($dataArr);
        }
    }
}
