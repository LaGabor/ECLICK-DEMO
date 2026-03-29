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
        Schema::create('refund_export_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_export_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('receipt_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('refund_amount', 13, 4);
            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
            ])->default('pending');
            $table->text('payment_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_export_items');
    }
};
