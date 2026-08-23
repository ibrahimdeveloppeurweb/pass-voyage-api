<?php
require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;
(new Dotenv())->bootEnv(__DIR__.'/.env');
$pdo = new PDO($_ENV['DATABASE_URL']);
$stmt = $pdo->query("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'credit_request' AND COLUMN_NAME = 'travel_date'");
echo $stmt->fetchColumn();
