<?php
require __DIR__.'/../vendor/autoload.php';
// Boot symfony
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$ticketManager = $container->get(\App\Manager\Business\TicketManager::class);

$result = $ticketManager->getFormattedTicketList();

// print only the first 5 and last 5 elements to see if there are empty ones
$count = count($result);
echo "Total tickets: " . $count . "\n";
print_r(array_slice($result, 0, 5));
if ($count > 5) {
    echo "\nLast 5:\n";
    print_r(array_slice($result, -5));
}
