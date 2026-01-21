<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('country')->nullable()->after('phone');
            $table->string('address_line1')->nullable()->after('country');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city')->nullable()->after('address_line2');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->date('date_of_birth')->nullable()->after('postal_code');
            $table->enum('verification_level', ['none','basic','standard','advanced'])->default('none')->after('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'country',
                'address_line1',
                'address_line2',
                'city',
                'state',
                'postal_code',
                'date_of_birth',
                'verification_level',
            ]);
        });
    }
};
