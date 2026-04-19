<?php

namespace App\EventListener;

use App\Entity\Infraction;
use App\Service\SmsService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostPersistEventArgs;

#[AsEntityListener(event: Events::postPersist, entity: Infraction::class)]
class InfractionSmsListener
{
    public function __construct(private SmsService $smsService) {}

    public function postPersist(Infraction $infraction, PostPersistEventArgs $args): void
    {
        $user = $infraction->getUser();

        if (!$user || !$user->getTelephone()) {
            return;
        }

        $montant = number_format($infraction->getMontantTotal(), 3, ',', ' ');
        $date    = $infraction->getDateEcheance()?->format('d/m/Y') ?? 'non définie';
        $id      = $infraction->getId();

        $message = "⚠️ KbadhaPay - Nouvelle infraction enregistrée.\n"
                 . "Référence : #$id\n"
                 . "Montant   : $montant DT\n"
                 . "Échéance  : $date\n"
                 . "Paiement  : https://kbadhapay.tn/citizen/infraction/$id/payer";

        $this->smsService->send($user->getTelephone(), $message);
    }
}
