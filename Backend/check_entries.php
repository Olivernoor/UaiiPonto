<?php
require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::capture();
$kernel->bootstrap();

// Usar o container do Laravel
$app = app();

// Verificar as batidas do usuário 2 para hoje
$entries = \App\Models\TimeEntry::where('user_id', 2)
    ->whereDate('check_in', \Carbon\Carbon::today())
    ->orderBy('check_in', 'desc')
    ->get(['id', 'type', 'check_in', 'check_out']);

echo "Total de entradas hoje: " . count($entries) . "\n";
foreach ($entries as $entry) {
    $check_in = $entry->check_in instanceof \Carbon\Carbon ? $entry->check_in->format('Y-m-d H:i:s') : $entry->check_in;
    $check_out = $entry->check_out instanceof \Carbon\Carbon ? $entry->check_out->format('Y-m-d H:i:s') : ($entry->check_out ?? 'NULL');
    echo "ID: {$entry->id}, Tipo: {$entry->type}, check_in: {$check_in}, check_out: {$check_out}\n";
}
