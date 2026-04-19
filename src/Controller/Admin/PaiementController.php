<?php

namespace App\Controller\Admin;

use App\Entity\Paiement;
use App\Repository\PaiementRepository;
<<<<<<< HEAD
=======
use App\Repository\InfractionRepository;
>>>>>>> 1db43f20671c9056a1768bbbcec7dc29b3b3cbff
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/paiement', name: 'admin_paiement_')]
#[IsGranted('ROLE_ADMIN')]
class PaiementController extends AbstractController
{
    // ── Liste de tous les paiements ───────────────────────────────
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PaiementRepository $repo): Response
    {
        return $this->render('admin/paiement/index.html.twig', [
            'paiements' => $repo->findBy([], ['date_soumission' => 'DESC']),
        ]);
    }

    // ── Paiements en attente uniquement ───────────────────────────
    #[Route('/en-attente', name: 'en_attente', methods: ['GET'])]
<<<<<<< HEAD
    public function enAttente(PaiementRepository $repo): Response
    {
        return $this->render('admin/paiement/en_attente.html.twig', [
            'paiements' => $repo->findBy(
                ['statut' => 'en_attente'],
                ['date_soumission' => 'ASC']  // plus anciens en premier
=======
    public function enAttente(InfractionRepository $repo): Response
    {
        return $this->render('admin/paiement/en_attente.html.twig', [
            'paiements' => $repo->findBy(
                ['statut' => 'a_payer']            
>>>>>>> 1db43f20671c9056a1768bbbcec7dc29b3b3cbff
            ),
        ]);
    }

    // ── Détail d'un paiement ──────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Paiement $paiement): Response
    {
        return $this->render('admin/paiement/show.html.twig', [
            'paiement' => $paiement,
        ]);
    }

    // ── Suppression avec CSRF ─────────────────────────────────────
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Paiement $paiement, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $paiement->getId(), $request->request->get('_token'))) {
            $em->remove($paiement);
            $em->flush();
            $this->addFlash('success', 'Le paiement a été supprimé.');
        } else {
            $this->addFlash('danger', 'Token de sécurité invalide. Suppression annulée.');
        }

        return $this->redirectToRoute('admin_paiement_index');
    }
}
