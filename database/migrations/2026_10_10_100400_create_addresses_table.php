<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('phone', 20);
            /* Theo mô hình hành chính 2 cấp (từ 7/2025): tỉnh → xã/phường, không còn cấp huyện. */
            $table->string('province_code', 10);
            $table->string('province_name', 100);
            $table->string('ward', 150);
            $table->string('street');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
