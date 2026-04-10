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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->string('status')->default('open'); // open, completed, late, absent
            $table->text('notes')->nullable();
            $table->decimal('worked_hours', 8, 2)->nullable(); // Horas trabalhadas (calculado automaticamente)
            $table->timestamps();

            // Index para melhor performance nas queries
            $table->index('user_id');
            $table->index('check_in');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
