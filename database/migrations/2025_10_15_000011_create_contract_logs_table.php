<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action'); // created | updated | status_changed | signed | finalized | cancelled
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_logs');
    }
};