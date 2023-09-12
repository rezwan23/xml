<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\BringOrder;

class SendBringHookResponseToMC implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $consignmentNumber;

    private $status;

    /**
     * Create a new job instance.
     */
    public function __construct($consignmentNumber, $status)
    {
        $this->consignmentNumber = $consignmentNumber;
        $this->status = $status;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if($this->status == 'RETURN'){
            $this->sendReturnEmail();
        }else if($this->status == 'DELIVERED'){
            $this->sendDeliveredMessage();
        }else if($this->status == 'IN_TRANSIT'){
            $this->sendInTransitMessage();
        }
    }


    public function sendInTransitMessage()
    {
        $data = '{"grant_type": "client_credentials","client_id": "h648p678uy7skfwd5bdn6o57","client_secret": "DvHRbrI8l9P1OzEPdpNLaqTW","scope": "email_read email_write email_send journeys_read list_and_subscribers_read","account_id": "100011507"}';
        

        $tokenRequestUrl = 'https://mcydsqwghhgrrz-nct4xrjcryrq8.auth.marketingcloudapis.com/v2/token';


        $response = Http::withBody($data, 'application/json')
        ->post($tokenRequestUrl);

        if($response->status() == 200){
            $data = json_decode($response->body(), true);

            $token = $data['access_token'];


            $bringOrder = BringOrder::where('bring_consignment_number', $this->consignmentNumber)->first();

            if($bringOrder){
                $pickupRequestNumber = $bringOrder->pickup_request_number + 1;

                $contactId = $bringOrder->contact_id;
                
                $email = $bringOrder->email;

                $orderNumber = $bringOrder->order_number;

                $url = 'https://mcydsqwghhgrrz-nct4xrjcryrq8.rest.marketingcloudapis.com/messaging/v1/email/messages/checkout-bring-3'.rand(1000, 99999).$pickupRequestNumber;

                $data = '{"definitionKey": "ecom-bring-checkout-2","recipient":{"contactKey": "'.$contactId.'","to": "'.$email.'","attributes": {"orderNumber": "'.$orderNumber.'"}}}';


                $response = Http::withBody($data, 'application/json')
                ->withHeaders(['Authorization' => 'Bearer '.$token])
                ->post($url);

                if($response->status() == 202){
                    $bringOrder->update(['pickup_request_number' => $pickupRequestNumber, 'is_picked' => 1]);
                    \Log::channel('mc-email-checkout-2')->info('OrderNumber : ' . $orderNumber . '=>' . $response->body());
                }else{
                    \Log::channel('mc-email-checkout-2')->error('OrderNumber : ' . $orderNumber . '=>' . $response->body());
                }

            }else{
                \Log::channel('mc-email-checkout-2')->error('Bring Order of Consognment Number : ' . $this->consignmentNumber . ' Not Found');
            }

        }else{
            \Log::channel('mc-email-checkout-2')->error( $response->body());
        }
    }


    public function sendDeliveredMessage()
    {
        $data = '{"grant_type": "client_credentials","client_id": "h648p678uy7skfwd5bdn6o57","client_secret": "DvHRbrI8l9P1OzEPdpNLaqTW","scope": "email_read email_write email_send journeys_read list_and_subscribers_read","account_id": "100011507"}';
        

        $tokenRequestUrl = 'https://mcydsqwghhgrrz-nct4xrjcryrq8.auth.marketingcloudapis.com/v2/token';


        $response = Http::withBody($data, 'application/json')
        ->post($tokenRequestUrl);

        if($response->status() == 200){
            $data = json_decode($response->body(), true);

            $token = $data['access_token'];


            $bringOrder = BringOrder::where('bring_consignment_number', $this->consignmentNumber)->first();

            if($bringOrder){
                $deliveredRequestNumber = $bringOrder->delivered_request_number + 1;

                $contactId = $bringOrder->contact_id;
                
                $email = $bringOrder->email;

                $orderNumber = $bringOrder->order_number;

                $url = 'https://mcydsqwghhgrrz-nct4xrjcryrq8.rest.marketingcloudapis.com/messaging/v1/email/messages/checkout-bring-3'.rand(1000, 99999).$deliveredRequestNumber;

                $data = '{"definitionKey": "ecom-bring-checkout-3","recipient":{"contactKey": "'.$contactId.'","to": "'.$email.'","attributes": {"orderNumber": "'.$orderNumber.'"}}}';


                $response = Http::withBody($data, 'application/json')
                ->withHeaders(['Authorization' => 'Bearer '.$token])
                ->post($url);

                if($response->status() == 202){
                    $bringOrder->update(['delivered_request_number' => $deliveredRequestNumber, 'is_delivered' => 1]);
                    \Log::channel('mc-email-checkout-3')->info('OrderNumber : ' . $orderNumber . '=>' . $response->body());
                }else{
                    \Log::channel('mc-email-checkout-3')->error('OrderNumber : ' . $orderNumber . '=>' . $response->body());
                }

            }else{
                \Log::channel('mc-email-checkout-3')->error('Bring Order of Consognment Number : ' . $this->consignmentNumber . ' Not Found');
            }

        }else{
            \Log::channel('mc-email-checkout-3')->error( $response->body());
        }
    }


    public function sendReturnEmail()
    {
        $data = '{"grant_type": "client_credentials","client_id": "h648p678uy7skfwd5bdn6o57","client_secret": "DvHRbrI8l9P1OzEPdpNLaqTW","scope": "email_read email_write email_send journeys_read list_and_subscribers_read","account_id": "100011507"}';
        

        $tokenRequestUrl = 'https://mcydsqwghhgrrz-nct4xrjcryrq8.auth.marketingcloudapis.com/v2/token';


        $response = Http::withBody($data, 'application/json')
        ->post($tokenRequestUrl);

        if($response->status() == 200){
            $data = json_decode($response->body(), true);

            $token = $data['access_token'];


            $bringOrder = BringOrder::where('bring_consignment_number', $this->consignmentNumber)->first();

            if($bringOrder){
                $returnRequestNumber = $bringOrder->return_request_number + 1;

                $contactId = $bringOrder->contact_id;
                
                $email = $bringOrder->email;

                $orderNumber = $bringOrder->order_number;

                $url = 'https://mcydsqwghhgrrz-nct4xrjcryrq8.rest.marketingcloudapis.com/messaging/v1/email/messages/return-bring'.rand(1000, 99999).$returnRequestNumber;

                $data = '{"definitionKey": "bring-return-pioner","recipient":{"contactKey": "'.$contactId.'","to": "'.$email.'","attributes": {"orderNumber": "'.$orderNumber.'"}}}';


                $response = Http::withBody($data, 'application/json')
                ->withHeaders(['Authorization' => 'Bearer '.$token])
                ->post($url);

                if($response->status() == 202){
                    $bringOrder->update(['return_request_number' => $returnRequestNumber, 'is_returned' => 1]);
                    \Log::channel('mc-email-return')->info('OrderNumber : ' . $orderNumber . '=>' . $response->body());
                }else{
                    \Log::channel('mc-email-return')->error('OrderNumber : ' . $orderNumber . '=>' . $response->body());
                }

            }else{
                \Log::channel('mc-email-return')->error('Bring Order of Consognment Number : ' . $this->consignmentNumber . ' Not Found');
            }

        }else{
            \Log::channel('mc-email-return')->error( $response->body());
        }
    }
}
