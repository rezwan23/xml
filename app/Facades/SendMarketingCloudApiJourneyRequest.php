<?php 


namespace App\Facades;

use Illuminate\Support\Facades\Facade;


class SendMarketingCloudApiJourneyRequest extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'MC';
    }
}