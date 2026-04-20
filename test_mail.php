<?php

require_once __DIR__.'/vendor/autoload_runtime.php';

use App\Kernel;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $mailer = $container->get('mailer');
    
    $email = (new Email())
        ->from('elabenkedher@gmail.com')
        ->to('elabenkedher@gmail.com')
        ->subject('Test kbadhapay')
        ->text('Si vous voyez ceci, le mailer fonctionne !');
    
    try {
        echo "Tentative d'envoi...\n";
        $mailer->send($email);
        echo "Email envoyé avec succès !\n";
    } catch (\Exception $e) {
        echo "Erreur : " . $e->getMessage() . "\n";
    }
    
    return 0;
};
