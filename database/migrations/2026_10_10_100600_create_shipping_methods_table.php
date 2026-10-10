<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('fee')->default(0);
            /* Đơn từ mức này trở lên được miễn phí ship; null = không miễn. */
            $table->unsignedBigInteger('free_from_amount')->nullable();
            /* Mã tỉnh áp dụng; null = toàn quốc. Khớp tỉnh cụ thể được ưu tiên hơn toàn quốc. */
            $table->json('province_codes')->nullable();
            $table->string('estimated_days', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
