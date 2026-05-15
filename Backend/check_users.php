<?php
$pdo = new PDO('sqlite:database/database.sqlite');
$result = $pdo->query('SELECT id, name, email, password FROM users');
$rows = $result->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
