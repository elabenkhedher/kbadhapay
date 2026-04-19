<?php

namespace App\Controller\Admin;

use App\Entity\Reclamation;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reclamation', name: 'admin_reclamation_')]
#[IsGranted('ROLE_ADMIN')]
class ReclamationController extends AbstractController
{
    // ── Liste de toutes les réclamations ──────────────────────────
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ReclamationRepository $repo): Response
    {
        return $this->render('admin/reclamation/index.html.twig', [
            'reclamations' => $repo->findBy([], ['date_soumission' => 'DESC']),
        ]);
    }

    // ── Réclamations en cours ─────────────────────────────────────
    #[Route('/en-cours', name: 'en_cours', methods: ['GET'])]
    public function enCours(ReclamationRepository $repo): Response
    {
        return $this->render('admin/reclamation/en_cours.html.twig', [
            'reclamations' => $repo->findBy(
                ['statut' => 'en_cours'],
                ['date_soumission' => 'ASC']
            ),
        ]);
    }

    // ── Détail d'une réclamation ──────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Reclamation $reclamation): Response
    {
        // Formulaire de réponse affiché sur la page show
        $formRepondre = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_reclamation_repondre', ['id' => $reclamation->getId()]))
            ->setMethod('POST')
            ->add('statut', ChoiceType::class, [
                'label'      => 'Décision',
                'choices'    => [
                    'Réclamation résolue' => 'resolue',
                    'Réclamation rejetée' => 'rejetee',
                    'En cours de traitement' => 'en_cours',
                ],
                'attr'       => ['class' => 'form-control custom-select'],
                'label_attr' => ['class' => 'font-weight-bold'],
            ])
            ->add('reponse', TextareaType::class, [
                'label'      => 'Réponse / Motif',
                'attr'       => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Rédigez la réponse au citoyen…'],
                'label_attr' => ['class' => 'font-weight-bold'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider la décision',
                'attr'  => ['class' => 'btn btn-primary btn-block mt-3'],
            ])
            ->getForm();

        return $this->render('admin/reclamation/show.html.twig', [
            'reclamation'   => $reclamation,
            'formRepondre'  => $formRepondre,
        ]);
    }

    // ── Répondre / changer le statut d'une réclamation ───────────
    #[Route('/{id}/repondre', name: 'repondre', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function repondre(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder()
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'resolue'  => 'resolue',
                    'rejetee'  => 'rejetee',
                    'en_cours' => 'en_cours',
                ],
            ])
            ->add('reponse', TextareaType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data    = $form->getData();
            $nouveau = $data['statut'];

            $reclamation->setStatut($nouveau);

            // Si l'entité Reclamation a un champ 'reponse', on le met à jour
            // (à ajouter à l'entité si besoin)
            if (method_exists($reclamation, 'setReponse')) {
                $reclamation->setReponse($data['reponse']);
            }

            // Assigner l'agent connecté comme traitant
            if (method_exists($reclamation, 'setAgent') && $this->getUser()) {
                $reclamation->setAgent($this->getUser());
            }

            $em->flush();

            $labels = [
                'resolue'  => 'résolue',
                'rejetee'  => 'rejetée',
                'en_cours' => 'mise en cours de traitement',
            ];
            $this->addFlash(
                $nouveau === 'rejetee' ? 'warning' : 'success',
                sprintf('La réclamation a été %s.', $labels[$nouveau] ?? 'mise à jour')
            );
        } else {
            $this->addFlash('danger', 'Erreur lors du traitement de la réclamation.');
        }

        return $this->redirectToRoute('admin_reclamation_show', ['id' => $reclamation->getId()]);
    }

    // ── Suppression avec CSRF ─────────────────────────────────────
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reclamation->getId(), $request->request->get('_token'))) {
            $em->remove($reclamation);
            $em->flush();
            $this->addFlash('success', 'La réclamation a été supprimée.');
        } else {
            $this->addFlash('danger', 'Token de sécurité invalide. Suppression annulée.');
        }

        return $this->redirectToRoute('admin_reclamation_index');
    }
}
