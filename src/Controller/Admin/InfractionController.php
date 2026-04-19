<?php

namespace App\Controller\Admin;

use App\Entity\Infraction;
use App\Form\InfractionType;
use App\Repository\InfractionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/infraction', name: 'admin_infraction_')]
#[IsGranted('ROLE_ADMIN')]
class InfractionController extends AbstractController
{
    // ── Liste de toutes les infractions ───────────────────────────
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(InfractionRepository $repo): Response
    {
        return $this->render('admin/infraction/index.html.twig', [
            'infractions' => $repo->findBy([], ['date_infraction' => 'DESC']),
        ]);
    }

    // ── Enregistrement d'une nouvelle infraction ──────────────────
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $infraction = new Infraction();
        $form = $this->createForm(InfractionType::class, $infraction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($infraction);
            $em->flush();
            $this->addFlash('success', 'L\'infraction a été enregistrée avec succès.');

            return $this->redirectToRoute('admin_infraction_index');
        }

        return $this->render('admin/infraction/new.html.twig', [
            'infraction' => $infraction,
            'form'       => $form,
        ]);
    }

    // ── Détail d'une infraction ───────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Infraction $infraction): Response
    {
        return $this->render('admin/infraction/show.html.twig', [
            'infraction' => $infraction,
        ]);
    }

    // ── Modification d'une infraction ─────────────────────────────
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Infraction $infraction, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(InfractionType::class, $infraction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'L\'infraction a été modifiée avec succès.');

            return $this->redirectToRoute('admin_infraction_index');
        }

        return $this->render('admin/infraction/edit.html.twig', [
            'infraction' => $infraction,
            'form'       => $form,
        ]);
    }

    // ── Suppression avec CSRF ─────────────────────────────────────
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Infraction $infraction, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $infraction->getId(), $request->request->get('_token'))) {
            $em->remove($infraction);
            $em->flush();
            $this->addFlash('success', 'L\'infraction a été supprimée.');
        } else {
            $this->addFlash('danger', 'Token de sécurité invalide. Suppression annulée.');
        }

        return $this->redirectToRoute('admin_infraction_index');
    }
}
