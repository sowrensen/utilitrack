<?php

use App\Models\Category;
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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Category::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('usable')->default(0);
            $table->unsignedInteger('leftover')->nullable();
            $table->string('unit', 10)->nullable();
            $table->timestamp('purchase_date')->nullable();
            $table->timestamp('usage_date')->nullable();
            $table->unsignedInteger('interval')->nullable();
            $table->unsignedInteger('usage_per_day')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
