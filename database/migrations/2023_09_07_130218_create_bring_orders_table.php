<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bring_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number');
            $table->string('bring_consignment_number');
            $table->string('labels');
            $table->string('tracking');
            $table->string('hook_id')->nullable();
            $table->boolean('is_picked')->default(0);
            $table->integer('pickup_request_number')->default(0);
            $table->integer('delivered_request_number')->default(0);
            $table->integer('return_request_number')->default(0);
            $table->boolean('is_delivered')->default(0);
            $table->boolean('is_returned')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bring_orders');
    }
};
