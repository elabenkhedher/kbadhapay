<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PaymentController extends AbstractController
{
    #[Route('/payment/process', name: 'payment_process', methods: ['POST'])]
    public function process(
        Request $request,
        \App\Repository\InfractionRepository $infraRepo,
        \App\Repository\TaxeRepository $taxeRepo,
        \Doctrine\ORM\EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $cardNumber = str_replace(' ', '', $data['cardNumber'] ?? '');
        $paymentId = $data['paymentId'] ?? null;
        $paymentType = $data['paymentType'] ?? null;

        // Validation Luhn basique
        if (!$this->isValidLuhn($cardNumber)) {
            return new JsonResponse(['status' => 'failed', 'message' => 'Numéro de carte invalide (Luhn)'], 400);
        }

        // SI SUCCÈS (4242... ou autre carte valide)
        if ($paymentId && $paymentType) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $paiement = new \App\Entity\Paiement();
            $paiement->setUser($user)
                     ->setModePaiement('en_ligne')
                     ->setStatut('paye')
                     ->setDatePaiement(new \DateTime())
                     ->setDateSoumission(new \DateTime());

            if ($paymentType === 'infraction') {
                $infraction = $infraRepo->find($paymentId);
                if ($infraction) {
                    $infraction->setStatut('paye');
                    $paiement->setInfraction($infraction)
                             ->setMontant($infraction->getMontantTotal())
                             ->setReference('AME-' . strtoupper(uniqid()))
                             ->setSujet('Paiement amende : ' . $infraction->getTypeInfraction());
                }
            } elseif ($paymentType === 'taxe') {
                $taxe = $taxeRepo->find($paymentId);
                if ($taxe) {
                    $paiement->setTaxe($taxe)
                             ->setMontant($taxe->getMontant())
                             ->setReference('PAY-' . strtoupper(uniqid()))
                             ->setSujet('Paiement taxe : ' . $taxe->getNomTaxe());
                }
            }

            $em->persist($paiement);
            $em->flush();
        }

        return new JsonResponse(['status' => 'success']);
    }

    private function isValidLuhn(string $number): bool
    {
        $sum = 0;
        $numDigits = strlen($number);
        $parity = $numDigits % 2;
        for ($i = 0; $i < $numDigits; $i++) {
            $digit = (int)$number[$i];
            if ($i % 2 == $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }
        return ($sum % 10 == 0);
    }
}
