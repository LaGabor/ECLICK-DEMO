<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('promotion_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('receipt_image');
            $table->string('ap_code');
            $table->date('purchase_date');
            $table->enum('status', [
                'pending',
                'under_review',
                'approved',
                'rejected',
                'appealed',
                'payment_pending',
                'paid',
                'payment_failed',
            ])->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
