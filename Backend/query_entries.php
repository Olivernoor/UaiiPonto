<?php
// Carregar dependências
require 'vendor/autoload.php';

// Carregar .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'),
    getenv('DB_USERNAME'),
    getenv('DB_PASSWORD')
);

$stmt = $db->query("SELECT id, user_id, type, check_in, check_out, created_at FROM time_entries WHERE user_id = 2 AND DATE(check_in) = CURDATE() ORDER BY check_in DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Batidas de hoje para usuário 2:\n";
if (empty($rows)) {
    echo "Nenhuma batida encontrada!\n";
} else {
    foreach ($rows as $row) {
        echo sprintf("ID: %d, Type: %s, check_in: %s, check_out: %s\n", 
            $row['id'], 
            $row['type'], 
            $row['check_in'], 
            $row['check_out'] ?? 'NULL'
        );
    }
}
