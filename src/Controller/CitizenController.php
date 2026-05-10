<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\Reclamation;
use App\Form\DocumentUploadType;
use App\Form\ReclamationCitoyenType;
use App\Service\FileUploaderService;
use App\Repository\InfractionRepository;
use App\Repository\PaiementRepository;
use App\Repository\ReclamationRepository;
use App\Repository\TaxeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/citizen', name: 'citizen_')]
#[IsGranted('ROLE_CITOYEN')]
class CitizenController extends AbstractController
{
    // ─────────────────────────────────────────────────────────────────
    // 1. DASHBOARD CITOYEN
    // ─────────────────────────────────────────────────────────────────
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(
        TaxeRepository       $taxeRepo,
        InfractionRepository $infraRepo,
        PaiementRepository   $paiRepo,
        ReclamationRepository $reclRepo
    ): Response {
        /** @var \App\Entity\User $citoyen */
        $citoyen = $this->getUser();

        // Taxes actives (dues par tous les citoyens)
        $taxesDues = $taxeRepo->findBy(['actif' => true]);

        // Infractions à payer pour ce citoyen
        $infractions = $infraRepo->findBy(['user' => $citoyen, 'statut' => 'a_payer']);

        // Tous les paiements du citoyen
        $paiements = $paiRepo->findBy(['user' => $citoyen], ['date_paiement' => 'DESC']);

        // Total payé (somme des montants)
        $totalPaye = array_sum(array_map(
            fn(Paiement $p) => (float) ($p->getMontant() ?? 0),
            $paiements
        ));

        // Réclamations du citoyen
        $reclamations = $reclRepo->findBy(['user' => $citoyen], ['date_soumission' => 'DESC']);
        $nbReclamations = count(array_filter($reclamations, fn($r) => $r->getStatut() === 'en_cours'));

        return $this->render('citizen/dashboard.html.twig', [
            'nbTaxesDues'   => count($taxesDues),
            'nbInfractions' => count($infractions),
            'totalPaye'     => number_format($totalPaye, 3, ',', ' '),
            'nbReclamations'=> $nbReclamations,
            'taxesDues'     => array_slice($taxesDues, 0, 3),       // 3 premières
            'infractions'   => $infractions,                         // toutes à payer
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. LISTE DES TAXES ACTIVES
    // ─────────────────────────────────────────────────────────────────
    #[Route('/taxes', name: 'taxes', methods: ['GET'])]
    public function taxes(TaxeRepository $taxeRepo): Response
    {
        return $this->render('citizen/taxes.html.twig', [
            'taxes' => $taxeRepo->findBy(['actif' => true]),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. PAYER UNE TAXE
    // ─────────────────────────────────────────────────────────────────
    #[Route('/taxe/{id}/payer', name: 'payer_taxe', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function payerTaxe(
        int $id,
        TaxeRepository $taxeRepo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $taxe = $taxeRepo->find($id);
        if (!$taxe || !$taxe->isActif()) {
            $this->addFlash('danger', 'Taxe introuvable ou inactive.');
            return $this->redirectToRoute('citizen_taxes');
        }

        if ($request->isMethod('GET')) {
            return $this->render('citizen/confirm_payer_taxe.html.twig', [
                'taxe' => $taxe
            ]);
        }

        // Vérification CSRF
        if (!$this->isCsrfTokenValid('payer_taxe_' . $id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('citizen_dashboard');
        }

        $taxe = $taxeRepo->find($id);
        if (!$taxe || !$taxe->isActif()) {
            $this->addFlash('danger', 'Taxe introuvable ou inactive.');
            return $this->redirectToRoute('citizen_taxes');
        }

        /** @var \App\Entity\User $citoyen */
        $citoyen = $this->getUser();

        $paiement = new Paiement();
        $paiement
            ->setUser($citoyen)
            ->setTaxe($taxe)
            ->setMontant($taxe->getMontant())
            ->setStatut('paye')
            ->setModePaiement('en_ligne')
            ->setReference('PAY-' . strtoupper(uniqid()))
            ->setDatePaiement(new \DateTime())
            ->setDateSoumission(new \DateTime())
            ->setSujet('Paiement taxe : ' . $taxe->getNomTaxe());
        // Marquer Taxe comme payée
        $taxe->setStatut('paye');
        
        $em->persist($paiement);
        $em->flush();

        $this->addFlash('success', 'Paiement effectué avec succès ! Référence : ' . $paiement->getReference());

        return $this->redirectToRoute('citizen_dashboard');
    }

    // ─────────────────────────────────────────────────────────────────
    // 4. MES INFRACTIONS
    // ─────────────────────────────────────────────────────────────────
    #[Route('/infractions', name: 'infractions', methods: ['GET'])]
    public function infractions(InfractionRepository $infraRepo): Response
    {
        /** @var \App\Entity\User $citoyen */
        $citoyen = $this->getUser();

        return $this->render('citizen/infractions.html.twig', [
            'infractions' => $infraRepo->findBy(['user' => $citoyen], ['date_infraction' => 'DESC']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 5. PAYER UNE INFRACTION
    // ─────────────────────────────────────────────────────────────────
    #[Route('/infraction/{id}/payer', name: 'payer_infraction', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function payerInfraction(
        int $id,
        InfractionRepository $infraRepo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $infraction = $infraRepo->find($id);

        if (!$infraction || $infraction->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Infraction introuvable ou non autorisée.');
            return $this->redirectToRoute('citizen_infractions');
        }

        if ($infraction->getStatut() === 'paye') {
            $this->addFlash('warning', 'Cette amende est déjà réglée.');
            return $this->redirectToRoute('citizen_infractions');
        }

        if ($request->isMethod('GET')) {
            return $this->render('citizen/confirm_payer_infraction.html.twig', [
                'infraction' => $infraction
            ]);
        }

        if (!$this->isCsrfTokenValid('payer_infraction_' . $id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('citizen_infractions');
        }

        $infraction = $infraRepo->find($id);

        if (!$infraction || $infraction->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Infraction introuvable ou non autorisée.');
            return $this->redirectToRoute('citizen_infractions');
        }

        if ($infraction->getStatut() === 'paye') {
            $this->addFlash('warning', 'Cette amende est déjà réglée.');
            return $this->redirectToRoute('citizen_infractions');
        }

        /** @var \App\Entity\User $citoyen */
        $citoyen = $this->getUser();

        // Créer le paiement
        $paiement = new Paiement();
        $paiement
            ->setUser($citoyen)
            ->setInfraction($infraction)
            ->setMontant($infraction->getMontantTotal())
            ->setStatut('paye')
            ->setModePaiement('en_ligne')
            ->setReference('AME-' . strtoupper(uniqid()))
            ->setDatePaiement(new \DateTime())
            ->setDateSoumission(new \DateTime())
            ->setSujet('Paiement amende : ' . $infraction->getTypeInfraction());

        // Marquer l'infraction comme payée
        $infraction->setStatut('paye');

        $em->persist($paiement);
        $em->flush();

        $this->addFlash('success', 'Amende payée avec succès ! Référence : ' . $paiement->getReference());

        return $this->redirectToRoute('citizen_infractions');
    }

    // ─────────────────────────────────────────────────────────────────
    // 6. HISTORIQUE DES PAIEMENTS
    // ─────────────────────────────────────────────────────────────────
    #[Route('/paiements', name: 'paiements', methods: ['GET'])]
    public function paiements(PaiementRepository $paiRepo): Response
    {
        /** @var \App\Entity\User $citoyen */
        $citoyen = $this->getUser();

        return $this->render('citizen/paiements.html.twig', [
            'paiements' => $paiRepo->findBy(['user' => $citoyen], ['date_paiement' => 'DESC']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 7. MES RÉCLAMATIONS
    // ─────────────────────────────────────────────────────────────────
    #[Route('/reclamations', name: 'reclamations', methods: ['GET'])]
    public function reclamations(ReclamationRepository $reclRepo): Response
    {
        /** @var \App\Entity\User $citoyen */
        $citoyen = $this->getUser();

        return $this->render('citizen/reclamations.html.twig', [
            'reclamations' => $reclRepo->findBy(['user' => $citoyen], ['date_soumission' => 'DESC']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 8. DÉPOSER UNE NOUVELLE RÉCLAMATION
    // ─────────────────────────────────────────────────────────────────
    #[Route('/reclamation/new', name: 'nouvelle_reclamation', methods: ['GET', 'POST'])]
    public function nouvelleReclamation(Request $request, EntityManagerInterface $em): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationCitoyenType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $citoyen */
            $citoyen = $this->getUser();

            $reclamation
                ->setUser($citoyen)
                ->setStatut('en_cours')
                ->setDateSoumission(new \DateTime());

            $em->persist($reclamation);
            $em->flush();

            $this->addFlash('success', 'Votre réclamation a été soumise avec succès. Nous vous répondrons dans les meilleurs délais.');

            return $this->redirectToRoute('citizen_reclamations');
        }

        return $this->render('citizen/nouvelle_reclamation.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reclamation/{id}/edit', name: 'modifier_reclamation', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function modifierReclamation(int $id, Request $request, ReclamationRepository $reclRepo, EntityManagerInterface $em): Response

    {
        $reclamation = $reclRepo->find($id);

        if (!$reclamation || $reclamation->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Réclamation introuvable ou non autorisée.');
            return $this->redirectToRoute('citizen_reclamations');
        }

        if ($reclamation->getStatut() !== 'en_cours') {
            $this->addFlash('warning', 'Vous ne pouvez plus modifier cette réclamation car elle est déjà traitée.');
            return $this->redirectToRoute('citizen_reclamations');
        }

        $form = $this->createForm(ReclamationCitoyenType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre réclamation a été mise à jour.');
            return $this->redirectToRoute('citizen_reclamations');
        }

        return $this->render('citizen/modifier_reclamation.html.twig', [
            'form' => $form,
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/reclamation/{id}/delete', name: 'supprimer_reclamation', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function supprimerReclamation(int $id, Request $request, ReclamationRepository $reclRepo, EntityManagerInterface $em): Response

    {
        $reclamation = $reclRepo->find($id);

        if (!$reclamation || $reclamation->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Réclamation introuvable ou non autorisée.');
            return $this->redirectToRoute('citizen_reclamations');
        }

        if ($reclamation->getStatut() !== 'en_cours') {
            $this->addFlash('warning', 'Vous ne pouvez pas supprimer une réclamation déjà traitée.');
            return $this->redirectToRoute('citizen_reclamations');
        }

        if ($this->isCsrfTokenValid('delete_reclamation_' . $id, $request->request->get('_token'))) {
            $em->remove($reclamation);
            $em->flush();
            $this->addFlash('success', 'La réclamation a été supprimée.');
        } else {
            $this->addFlash('danger', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('citizen_reclamations');
    }


    // ─────────────────────────────────────────────────────────────────
    // 9. PRÉFÉRENCES DE NOTIFICATION
    // ─────────────────────────────────────────────────────────────────
    #[Route('/preferences', name: 'preferences', methods: ['GET', 'POST'])]
    public function preferences(Request $request, EntityManagerInterface $em, \App\Repository\NotificationRepository $notifRepo): Response
    {
        /** @var \App\Entity\User $citoyen */
        $citoyen = $this->getUser();

        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('preferences', $request->request->get('_token'))) {
                $prefs = [
                    'sms'   => $request->request->has('pref_sms'),
                    'email' => $request->request->has('pref_email'),
                ];
                $citoyen->setPreferencesNotification($prefs);
                $em->flush();
                $this->addFlash('success', 'Vos préférences de rappel ont été enregistrées.');
            }
        }

        $notifications = $notifRepo->findBy(['user' => $citoyen], ['date_planifiee' => 'ASC']);

        return $this->render('citizen/preferences.html.twig', [
            'prefs' => $citoyen->getPreferencesNotification() ?? ['sms' => false, 'email' => true, 'push' => false],
            'notifications' => $notifications,
        ]);
    }


    #[Route('/documents', name: 'app_profile_documents', methods: ['GET', 'POST'])]
    public function uploadDocuments(
        Request $request,
        FileUploaderService $uploader,
        EntityManagerInterface $em,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
    
        $form = $this->createForm(DocumentUploadType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
    
            // --- Handle National ID ---
            $idFile = $form->get('idDocumentPath')->getData();
            if ($idFile) {
                $uploader->remove(
                    $user->getIdDocumentPath(),
                    $this->getParameter('kernel.project_dir') . '/public'
                );
    
                $filename = $uploader->uploadIdDocument($idFile);
                $user->setIdDocumentPath('uploads/id-documents/' . $filename);
            }
    
            // --- Handle Passport ---
            $passportFile = $form->get('passportPath')->getData();
            if ($passportFile) {
                $uploader->remove(
                    $user->getPassportPath(),
                    $this->getParameter('kernel.project_dir') . '/public'
                );
    
                $filename = $uploader->uploadPassport($passportFile);
                $user->setPassportPath('uploads/passports/' . $filename);
            }
    
            $em->flush();
    
            $this->addFlash('success', 'Your documents have been uploaded successfully.');
    
            return $this->redirectToRoute('citizen_dashboard');
        }
    
        return $this->render('citizen/document.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}
