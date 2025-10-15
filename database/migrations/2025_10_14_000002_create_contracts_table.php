<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->bigInteger('price_cents');
            $table->string('currency', 3)->default('USD');
            $table->timestamp('deadline_at')->nullable();
            $table->enum('status', ['draft','pending_approval','active','completed','cancelled'])->default('draft');
            $table->timestamp('buyer_accepted_at')->nullable();
            $table->timestamp('seller_accepted_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};