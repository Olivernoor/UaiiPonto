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
        Schema::table('time_entries', function (Blueprint $table) {
            // Adicionar coluna 'type' para diferenciar tipos de batida
            // check_in, lunch_out, lunch_in, check_out
            $table->string('type')->default('check_in')->after('user_id');
            $table->string('location')->nullable()->after('notes'); // Localização do ponto
            $table->decimal('latitude', 10, 8)->nullable()->after('location');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn(['type', 'location', 'latitude', 'longitude']);
        });
    }
};
