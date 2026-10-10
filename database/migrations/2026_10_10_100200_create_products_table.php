<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 50)->nullable()->unique();
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            /* Danh sách đường dẫn ảnh trên disk public; ảnh đầu là ảnh đại diện. */
            $table->json('images')->nullable();
            /* Giá tính bằng VND nguyên, không dùng số lẻ. */
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->string('unit', 30)->default('kg');
            $table->unsignedInteger('min_order_quantity')->default(1);
            $table->boolean('track_stock')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            /* Cặp thông số kỹ thuật: [{label, value}], hiển thị thành bảng ở trang chi tiết. */
            $table->json('specifications')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            /* Điểm trung bình lưu sẵn (denormalized) để sort/hiển thị nhanh, cập nhật khi duyệt review. */
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_featured']);
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
