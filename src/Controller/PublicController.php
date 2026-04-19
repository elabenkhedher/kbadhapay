<?php

namespace App\Controller;

use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublicController extends AbstractController
{
    #[Route('/verify/payment/{reference}', name: 'public_verify_payment', methods: ['GET'])]
    public function verifyPayment(string $reference, PaiementRepository $paiRepo): Response
    {
        $paiement = $paiRepo->findOneBy(['reference' => $reference]);

        return $this->render('public/verify_payment.html.twig', [
            'paiement' => $paiement,
            'reference' => $reference,
        ]);
    }
}
