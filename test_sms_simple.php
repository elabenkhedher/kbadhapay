<?php
require 'vendor/autoload.php';

use App\Kernel;
use App\Service\SmsService;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');
if (file_exists(__DIR__.'/.env.local')) {
    $dotenv->overload(__DIR__.'/.env.local');
}

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();

// On rend le service public temporairement via services.yaml si besoin, 
// mais ici on va essayer de le récupérer s'il est déjà public ou via alias.
try {
    $smsService = $container->get(SmsService::class);
    echo "Envoi d'un message SIMPLE à 96079666...\n";
    $result = $smsService->send('96079666', 'KbadhaPay - Test simple');
    if ($result) {
        echo "Côté PHP : OK (Twilio a accepté la requête)\n";
    } else {
        echo "Côté PHP : ERREUR\n";
    }
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
