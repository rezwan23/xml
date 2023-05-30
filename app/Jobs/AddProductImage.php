<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AddProductImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $dataArr = [];

        foreach($this->data as $singleData){
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
