<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// Carregar Laravel
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Conectar ao DB
$connection = Schema::connection(config('database.default'));

// Adicionar columns
try {
    if (!$connection->hasColumn('users', 'organization')) {
        Schema::table('users', function (Blueprint $table) {
            $table->string('organization')->unique()->nullable()->after('email');
        });
        echo "✓ Coluna 'organization' adicionada\n";
    } else {
        echo "✓ Coluna 'organization' já existe\n";
    }
    
    if (!$connection->hasColumn('users', 'role')) {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('organization');
        });
        echo "✓ Coluna 'role' adicionada\n";
    } else {
        echo "✓ Coluna 'role' já existe\n";
    }
    
    echo "\n✓ Banco de dados atualizado com sucesso!\n";
} catch (\Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
