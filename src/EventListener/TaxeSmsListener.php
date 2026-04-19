<?php

namespace App\EventListener;

use App\Entity\Taxe;
use App\Service\SmsService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostPersistEventArgs;

#[AsEntityListener(event: Events::postPersist, entity: Taxe::class)]
class TaxeSmsListener
{
    public function __construct(private SmsService $smsService) {}

    public function postPersist(Taxe $taxe, PostPersistEventArgs $args): void
    {
        $user = $taxe->getUser();

        if (!$user || !$user->getTelephone()) {
            return;
        }

        $montant = number_format($taxe->getMontant(), 3, ',', ' ');
        $date    = $taxe->getDateEcheance()?->format('d/m/Y') ?? 'non définie';
        $id      = $taxe->getId();
        $type    = $taxe->getType() ?? 'Taxe';

        $message = "KbadhaPay - Nouvelle taxe assignee\n"
                 . "Type : $type\n"
                 . "Ref : #$id\n"
                 . "Montant : $montant DT\n"
                 . "Echeance : $date\n"
                 . "Paiement : kbadhapay.tn/citizen/taxe/$id/payer";

        $this->smsService->send($user->getTelephone(), $message);
    }
}
