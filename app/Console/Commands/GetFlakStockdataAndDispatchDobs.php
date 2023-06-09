<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class GetFlakStockdataAndDispatchDobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'getflakdata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get XML Data From Flak API And Store It to Database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Schema::dropIfExists('products');

        Schema::create('products', function (Blueprint $table) {
            $table->string('ProductNo');
            $table->string('Description');
            $table->string('SRP');
            $table->string('StockBalance');
        });


        $response = Http::timeout(120)->get('http://webserver.flak.no/vbilder/FlakXMLStockBalance.xml');

        $new = simplexml_load_string($response->body());
  
        // Convert into json
        $con = json_encode($new);
        
        // Convert into associative array
        $newArr = json_decode($con, true);



        $data = array_chunk($newArr['StockBalances']['StockBalance'], 500);


        foreach($data as $singleDataArr){
            DB::table('products')->insert($singleDataArr);
        }
    }
}
