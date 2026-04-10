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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'daily', 'weekly', 'monthly'
            $table->date('date_from');
            $table->date('date_to');
            $table->integer('days_worked')->default(0);
            $table->decimal('total_hours', 10, 2)->default(0);
            $table->integer('late_arrivals')->default(0);
            $table->integer('absences')->default(0);
            $table->json('details')->nullable(); // Dados detalhados do relatório
            $table->timestamps();

            // Index para melhor performance
            $table->index('user_id');
            $table->index('type');
            $table->index('date_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
