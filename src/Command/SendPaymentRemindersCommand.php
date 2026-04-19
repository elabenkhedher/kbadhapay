<?php

namespace App\Command;

use App\Entity\Notification;
use App\Repository\InfractionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

#[AsCommand(
    name: 'app:send-reminders',
    description: 'Envoie des rappels intelligents pour les paiements d\'infractions',
)]
class SendPaymentRemindersCommand extends Command
{
    public function __construct(
        private InfractionRepository $infractionRepository,
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $router,
        private HttpClientInterface $httpClient
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTime();

        // Trouver toutes les infractions non payées avec une date d'échéance
        $infractions = $this->infractionRepository->createQueryBuilder('i')
            ->where('i.statut = :statut')
            ->andWhere('i.date_echeance IS NOT NULL')
            ->setParameter('statut', 'a_payer')
            ->getQuery()
            ->getResult();

        $count = 0;

        foreach ($infractions as $infraction) {
            $user = $infraction->getUser();
            if (!$user) continue;

            $diffDays = (int) $now->diff($infraction->getDateEcheance())->format('%R%a');
            
            // J-15, J-7, J-1, J+1, J+7
            $targetDays = [+15, +7, +1, -1, -7]; 

            if (in_array($diffDays, $targetDays)) {
                $prefs = $user->getPreferencesNotification() ?? ['sms' => false, 'email' => true, 'push' => false];
                $montant = number_format($infraction->getMontantTotal(), 3, ',', ' ');
                $date = $infraction->getDateEcheance()->format('d/m/Y');
                
                // Note: En mode CLI, le context HTTP n'est pas dispo, on devrait injecter le host,
                // mais pour la demo on simule un lien
                $lien = "https://kbadhapay.tn/citizen/infraction/{$infraction->getId()}/payer";

                $titre = $diffDays > 0 ? "Rappel: Échéance proche" : "Urgent: Échéance dépassée";
                $messageBase = $diffDays > 0 
                    ? "N'oubliez pas de régler votre amende de $montant DT avant le $date. "
                    : "Votre amende est en retard. Montant total avec pénalités: $montant DT. ";
                $messageBase .= "Lien de paiement: $lien";

                foreach (['sms', 'email', 'push'] as $canal) {
                    if (!empty($prefs[$canal])) {
                        $notif = new Notification();
                        $notif->setUser($user)
                              ->setTitre($titre)
                              ->setMessage($messageBase)
                              ->setCanal($canal)
                              ->setDatePlanifiee($now)
                              ->setStatut('envoye')
                              ->setTypeLien('infraction')
                              ->setIdLien($infraction->getId());
                        
                        // Send SMS via Twilio API
                        if ($canal === 'sms' && $user->getTelephone()) {
                            try {
                                $this->httpClient->request('POST', 'https://api.twilio.com/2010-04-01/Accounts/' . ($_ENV['TWILIO_ACCOUNT_SID'] ?? 'mock_sid') . '/Messages.json', [
                                    'auth_basic' => [($_ENV['TWILIO_ACCOUNT_SID'] ?? 'mock_sid'), ($_ENV['TWILIO_AUTH_TOKEN'] ?? 'mock_token')],
                                    'body' => [
                                        'From' => $_ENV['TWILIO_PHONE_NUMBER'] ?? '+1234567890',
                                        'To' => $user->getTelephone(),
                                        'Body' => $messageBase
                                    ]
                                ]);
                            } catch (\Exception $e) {
                                $io->warning("Erreur envoi SMS: " . $e->getMessage());
                                $notif->setStatut('echec');
                            }
                        }

                        // Send Web Push
                        if ($canal === 'push' && $user->getPushSubscription()) {
                            try {
                                $auth = [
                                    'VAPID' => [
                                        'subject' => 'mailto:admin@kbadhapay.tn',
                                        'publicKey' => $_ENV['VAPID_PUBLIC_KEY'] ?? 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjBJuBkr3qBUYIHBQFLXYp5Nksh8U',
                                        'privateKey' => $_ENV['VAPID_PRIVATE_KEY'] ?? 'mock_private_key'
                                    ]
                                ];
                                $webPush = new WebPush($auth);
                                $subscription = Subscription::create($user->getPushSubscription());
                                
                                $webPush->sendOneNotification($subscription, json_encode([
                                    'title' => $titre,
                                    'body' => $messageBase,
                                    'url' => $lien
                                ]));
                            } catch (\Exception $e) {
                                $io->warning("Erreur envoi Web Push: " . $e->getMessage());
                                $notif->setStatut('echec');
                            }
                        }

                        $this->em->persist($notif);
                        $count++;
                        
                        $io->text("[$canal] envoyé à " . $user->getUserIdentifier() . " pour infraction " . $infraction->getId());
                    }
                }
            }
        }

        $this->em->flush();

        $io->success("$count notifications envoyées/planifiées.");

        return Command::SUCCESS;
    }
}
