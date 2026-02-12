<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('dispute_logs')) {
            Schema::create('dispute_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dispute_id')->index();
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->string('action'); // status_changed|mediator_assigned|note_updated|message_posted
                $table->string('from_status')->nullable();
                $table->string('to_status')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('dispute_messages')) {
            Schema::create('dispute_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dispute_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->text('body');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_logs');
        Schema::dropIfExists('dispute_messages');
    }
};
