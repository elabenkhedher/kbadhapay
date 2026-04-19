<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Repository\InfractionRepository;
use App\Repository\PaiementRepository;
use App\Repository\TaxeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/kbadha', name: 'kbadha_')]
#[IsGranted('ROLE_KBADHA')]
class KbadhaController extends AbstractController
{
    // ─────────────────────────────────────────────────────────────────
    // 1. TABLEAU DE BORD
    // ─────────────────────────────────────────────────────────────────
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(PaiementRepository $paiementRepo): Response
    {
        /** @var \App\Entity\User $agent */
        $agent = $this->getUser();

        // Tous les paiements encaissés par cet agent
        $tousPaiements = $paiementRepo->findBy(
            ['encaissePar' => $agent, 'statut' => 'paye'],
            ['date_paiement' => 'DESC']
        );

        // Aujourd'hui
        $aujourdhui = new \DateTime('today');
        $demain     = new \DateTime('tomorrow');

        $paiementsAujourdhui = array_filter($tousPaiements, function (Paiement $p) use ($aujourdhui, $demain) {
            $d = $p->getDatePaiement();
            return $d && $d >= $aujourdhui && $d < $demain;
        });

        $totalAujourdhui = array_sum(array_map(
            fn(Paiement $p) => (float) ($p->getMontant() ?? 0),
            $paiementsAujourdhui
        ));

        // Ce mois-ci
        $debutMois = new \DateTime('first day of this month midnight');

        $paiementsMois = array_filter($tousPaiements, function (Paiement $p) use ($debutMois) {
            $d = $p->getDatePaiement();
            return $d && $d >= $debutMois;
        });

        $totalMois = array_sum(array_map(
            fn(Paiement $p) => (float) ($p->getMontant() ?? 0),
            $paiementsMois
        ));

        return $this->render('kbadha/dashboard.html.twig', [
            'nbPaiementsAujourdhui'   => count($paiementsAujourdhui),
            'totalAujourdhui'         => number_format($totalAujourdhui, 3, ',', ' '),
            'nbPaiementsMois'         => count($paiementsMois),
            'totalMois'               => number_format($totalMois, 3, ',', ' '),
            'derniersPaiements'       => array_slice($tousPaiements, 0, 5),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. RECHERCHE CITOYEN PAR CIN
    // ─────────────────────────────────────────────────────────────────
    #[Route('/citoyen/recherche', name: 'citoyen_recherche', methods: ['GET', 'POST'])]
    public function recherche(Request $request, UserRepository $userRepo): Response
    {
        $cin    = null;
        $erreur = null;

        $form = $this->createFormBuilder()
            ->add('cin', TextType::class, [
                'label' => 'Numéro CIN',
                'attr'  => [
                    'class'       => 'form-control form-control-lg',
                    'maxlength'   => 8,
                    'pattern'     => '[0-9]{8}',
                    'placeholder' => 'Ex: 12345678',
                    'inputmode'   => 'numeric',
                    'autofocus'   => true,
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cin     = trim($form->getData()['cin']);
            $citoyen = $userRepo->findByCin($cin);

            if ($citoyen) {
                return $this->redirectToRoute('kbadha_citoyen', ['id' => $citoyen->getId()]);
            }

            $erreur = sprintf('CIN "%s" non trouvé dans la base.', $cin);
        }

        return $this->render('kbadha/recherche.html.twig', [
            'form'   => $form,
            'cin'    => $cin,
            'erreur' => $erreur,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. FICHE CITOYEN
    // ─────────────────────────────────────────────────────────────────
    #[Route('/citoyen/{id}', name: 'citoyen', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function fichecitoyen(
        int $id,
        UserRepository $userRepo,
        TaxeRepository $taxeRepo,
        InfractionRepository $infraRepo,
        PaiementRepository $paiementRepo
    ): Response {
        $citoyen = $userRepo->find($id);

        if (!$citoyen) {
            $this->addFlash('danger', 'Citoyen introuvable.');
            return $this->redirectToRoute('kbadha_citoyen_recherche');
        }

        // Taxes actives non encore payées par ce citoyen
        $toutesLesActives = $taxeRepo->findBy(['actif' => true]);

        // Paiements de taxe déjà réglés par ce citoyen
        $paiementsTaxe = $paiementRepo->createQueryBuilder('p')
            ->where('p.user = :citoyen')
            ->andWhere('p.statut = :paye')
            ->andWhere('p.taxe IS NOT NULL')
            ->setParameter('citoyen', $citoyen)
            ->setParameter('paye', 'paye')
            ->getQuery()
            ->getResult();

        $taxesDejaPaYees = array_map(fn(Paiement $p) => $p->getTaxe()?->getId(), $paiementsTaxe);
        $taxesDejaPaYees = array_filter($taxesDejaPaYees);

        $taxesDues = array_filter(
            $toutesLesActives,
            fn($t) => !in_array($t->getId(), $taxesDejaPaYees, true)
        );

        // Infractions avec statut='a_payer' de ce citoyen
        $infractions = $infraRepo->findBy(['user' => $citoyen, 'statut' => 'a_payer']);

        return $this->render('kbadha/citoyen.html.twig', [
            'citoyen'     => $citoyen,
            'taxesDues'   => array_values($taxesDues),
            'infractions' => $infractions,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 4. ENCAISSEMENT
    // ─────────────────────────────────────────────────────────────────
    #[Route('/payer', name: 'payer', methods: ['POST'])]
    public function payer(
        Request $request,
        UserRepository $userRepo,
        TaxeRepository $taxeRepo,
        InfractionRepository $infraRepo,
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\User $agent */
        $agent = $this->getUser();

        $citoyenId    = (int) $request->request->get('citoyen_id');
        $mode         = $request->request->get('mode', 'especes');
        $taxeIds      = $request->request->all('taxe_ids') ?? [];
        $infractionIds = $request->request->all('infraction_ids') ?? [];

        $citoyen = $userRepo->find($citoyenId);

        if (!$citoyen) {
            $this->addFlash('danger', 'Citoyen introuvable.');
            return $this->redirectToRoute('kbadha_citoyen_recherche');
        }

        if (empty($taxeIds) && empty($infractionIds)) {
            $this->addFlash('warning', 'Veuillez sélectionner au moins une taxe ou une amende à régler.');
            return $this->redirectToRoute('kbadha_citoyen', ['id' => $citoyenId]);
        }

        $paiementIds = [];
        $now         = new \DateTime();

        // ── Traitement des taxes ──────────────────────────────────────
        foreach ($taxeIds as $taxeId) {
            $taxe = $taxeRepo->find((int) $taxeId);
            if (!$taxe) {
                continue;
            }

            $paiement = new Paiement();
            $paiement
                ->setUser($citoyen)
                ->setTaxe($taxe)
                ->setMontant($taxe->getMontant())
                ->setModePaiement($mode)
                ->setStatut('paye')
                ->setReference('PAY-G' . strtoupper(uniqid()))
                ->setDatePaiement($now)
                ->setEncaissePar($agent)
                ->setSujet('Paiement taxe : ' . $taxe->getNomTaxe())
                ->setDateSoumission($now);

            $em->persist($paiement);
            $em->flush(); // flush ici pour obtenir l'id

            $paiementIds[] = $paiement->getId();
        }

        // ── Traitement des infractions ────────────────────────────────
        foreach ($infractionIds as $infraId) {
            $infraction = $infraRepo->find((int) $infraId);
            if (!$infraction) {
                continue;
            }

            $paiement = new Paiement();
            $paiement
                ->setUser($citoyen)
                ->setInfraction($infraction)
                ->setMontant($infraction->getMontantAmende())
                ->setModePaiement($mode)
                ->setStatut('paye')
                ->setReference('PAY-G' . strtoupper(uniqid()))
                ->setDatePaiement($now)
                ->setEncaissePar($agent)
                ->setSujet('Amende : ' . $infraction->getTypeInfraction())
                ->setDateSoumission($now);

            // Marquer l'infraction comme payée
            $infraction->setStatut('paye');

            $em->persist($paiement);
            $em->persist($infraction);
            $em->flush();

            $paiementIds[] = $paiement->getId();
        }

        if (empty($paiementIds)) {
            $this->addFlash('danger', 'Aucun paiement n\'a pu être traité.');
            return $this->redirectToRoute('kbadha_citoyen', ['id' => $citoyenId]);
        }

        // Rediriger vers le dernier reçu (ou le premier — ici le premier)
        $this->addFlash('success', sprintf('%d paiement(s) enregistré(s) avec succès.', count($paiementIds)));

        return $this->redirectToRoute('kbadha_recu', ['id' => $paiementIds[0]]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 5. REÇU DE PAIEMENT
    // ─────────────────────────────────────────────────────────────────
    #[Route('/recu/{id}', name: 'recu', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function recu(int $id, PaiementRepository $paiementRepo): Response
    {
        $paiement = $paiementRepo->find($id);

        if (!$paiement) {
            $this->addFlash('danger', 'Reçu introuvable.');
            return $this->redirectToRoute('kbadha_dashboard');
        }

        return $this->render('kbadha/recu.html.twig', [
            'paiement' => $paiement,
        ]);
    }
}
