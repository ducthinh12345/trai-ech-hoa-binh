<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* Nhật ký mọi lượt gọi/callback của cổng thanh toán để đối soát và debug. */
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 30);
            $table->string('transaction_ref', 100)->nullable()->index();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('status', 20);
            $table->string('response_code', 20)->nullable();
            $table->string('message')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
