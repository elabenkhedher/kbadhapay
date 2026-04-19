<?php

namespace App\Controller\Admin;

use App\Entity\Taxe;
use App\Form\TaxeType;
use App\Repository\TaxeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/taxe', name: 'admin_taxe_')]
#[IsGranted('ROLE_ADMIN')]
class TaxeController extends AbstractController
{
    // ── Liste de toutes les taxes ─────────────────────────────────
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(TaxeRepository $repo): Response
    {
        return $this->render('admin/taxe/index.html.twig', [
            'taxes' => $repo->findAll(),
        ]);
    }

    // ── Création d'une nouvelle taxe ──────────────────────────────
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $taxe = new Taxe();
        $form = $this->createForm(TaxeType::class, $taxe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($taxe);
            $em->flush();
            $this->addFlash('success', 'La taxe a été créée avec succès.');

            return $this->redirectToRoute('admin_taxe_index');
        }

        return $this->render('admin/taxe/new.html.twig', [
            'taxe' => $taxe,
            'form' => $form,
        ]);
    }

    // ── Détail d'une taxe ─────────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Taxe $taxe): Response
    {
        return $this->render('admin/taxe/show.html.twig', [
            'taxe' => $taxe,
        ]);
    }

    // ── Modification d'une taxe ───────────────────────────────────
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Taxe $taxe, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TaxeType::class, $taxe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'La taxe a été modifiée avec succès.');

            return $this->redirectToRoute('admin_taxe_index');
        }

        return $this->render('admin/taxe/edit.html.twig', [
            'taxe' => $taxe,
            'form' => $form,
        ]);
    }

    // ── Suppression avec vérification CSRF ───────────────────────
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Taxe $taxe, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $taxe->getId(), $request->request->get('_token'))) {
            $em->remove($taxe);
            $em->flush();
            $this->addFlash('success', 'La taxe a été supprimée.');
        } else {
            $this->addFlash('danger', 'Token de sécurité invalide. Suppression annulée.');
        }

        return $this->redirectToRoute('admin_taxe_index');
    }
}
