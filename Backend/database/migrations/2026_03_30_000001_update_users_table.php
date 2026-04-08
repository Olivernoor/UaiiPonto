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
        Schema::table('users', function (Blueprint $table) {
            // Adicionar coluna organization se não existir
            if (!Schema::hasColumn('users', 'organization')) {
                $table->string('organization')->unique()->nullable()->after('email');
            }
            
            // Adicionar coluna role se não existir
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user')->after('organization');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'organization')) {
                $table->dropColumn('organization');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
