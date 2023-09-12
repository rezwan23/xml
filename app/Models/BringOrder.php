<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BringOrder extends Model
{
    use HasFactory;


    protected $fillable = [
        'order_number', 'contact_id', 'bring_consignment_number', 'labels', 'tracking',
        'is_picked', 'pickup_request_number', 'delivered_request_number','email',
        'return_request_number', 'is_delivered', 'is_returned', 'hook_id'
    ];
}
