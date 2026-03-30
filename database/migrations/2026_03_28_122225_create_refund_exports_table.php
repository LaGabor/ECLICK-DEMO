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
        Schema::create('refund_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('type', 32)->default('refund');
            $table->string('status', 32)->default('pending');
            $table->timestamp('exported_at')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->integer('total_rows')->default(0);
            $table->string('zip_path')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_exports');
    }
};
