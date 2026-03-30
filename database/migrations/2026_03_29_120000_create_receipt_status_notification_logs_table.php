<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_status_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 64);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['receipt_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_status_notification_logs');
    }
};
