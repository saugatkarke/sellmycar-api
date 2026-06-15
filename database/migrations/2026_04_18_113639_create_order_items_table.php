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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->string('product_title');
            $table->string('product_make');
            $table->string('product_model');
            $table->integer('product_year');
            $table->integer('quantity')->default(1);
            $table->decimal('price',
                config('ecommerce.price.precision'),
                config('ecommerce.price.scale')
            );
            $table->decimal('subtotal',
                config('ecommerce.price.precision'),
                config('ecommerce.price.scale')
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
