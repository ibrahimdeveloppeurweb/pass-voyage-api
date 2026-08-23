<?php
require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;
(new Dotenv())->bootEnv(__DIR__.'/.env');
$kernel = new App\Kernel('dev', false);
$kernel->boot();
$cm = $kernel->getContainer()->get('App\Manager\Business\CreditManager');
$passes = $cm->getPassengerPasses(null, '+2250700000000');
echo json_encode($passes, JSON_PRETTY_PRINT);
