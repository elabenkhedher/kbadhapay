<?php

namespace App\Controller\Admin;

use App\Entity\Infraction;
use App\Entity\Paiement;
use App\Entity\Reclamation;
use App\Entity\Taxe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    // ─────────────────────────────────────────────────────────────────
    // 1. TABLEAU DE BORD ADMIN
    // ─────────────────────────────────────────────────────────────────
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $taxeRepo    = $em->getRepository(Taxe::class);
        $infraRepo   = $em->getRepository(Infraction::class);
        $paiRepo     = $em->getRepository(Paiement::class);
        $reclRepo    = $em->getRepository(Reclamation::class);

        // ── KPI 1 : Taxes actives ─────────────────────────────────────
        $nbTaxes = (int) $taxeRepo->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.actif = true')
            ->getQuery()
            ->getSingleScalarResult();

        // ── KPI 2 : Infractions à payer ──────────────────────────────
        $nbInfractions = (int) $infraRepo->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.statut = :statut')
            ->setParameter('statut', 'a_payer')
            ->getQuery()
            ->getSingleScalarResult();

        // ── KPI 3 : Paiements ce mois ─────────────────────────────────
        $debutMois = new \DateTime('first day of this month midnight');
        $finMois   = new \DateTime('first day of next month midnight');

        $nbPaiements = (int) $paiRepo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.date_paiement >= :debut')
            ->andWhere('p.date_paiement < :fin')
            ->setParameter('debut', $debutMois)
            ->setParameter('fin', $finMois)
            ->getQuery()
            ->getSingleScalarResult();

        // ── KPI 4 : Réclamations en cours ─────────────────────────────
        $nbReclamations = (int) $reclRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.statut = :statut')
            ->setParameter('statut', 'en_cours')
            ->getQuery()
            ->getSingleScalarResult();

        // ── 5 derniers paiements ──────────────────────────────────────
        $derniersPaiements = $paiRepo->findBy([], ['date_soumission' => 'DESC'], 5);

        // ── 5 dernières infractions ───────────────────────────────────
        $dernieresInfractions = $infraRepo->findBy([], ['date_infraction' => 'DESC'], 5);

        // ── 5 dernières réclamations ──────────────────────────────────
        $dernieresReclamations = $reclRepo->findBy([], ['date_soumission' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'nbTaxes'               => $nbTaxes,
            'nbInfractions'         => $nbInfractions,
            'nbPaiements'           => $nbPaiements,
            'nbReclamations'        => $nbReclamations,
            'derniersPaiements'     => $derniersPaiements,
            'dernieresInfractions'  => $dernieresInfractions,
            'dernieresReclamations' => $dernieresReclamations,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. REPORTING FINANCIER
    // ─────────────────────────────────────────────────────────────────
    #[Route('/reporting', name: 'reporting', methods: ['GET'])]
    public function reporting(EntityManagerInterface $em): Response
    {
        $paiRepo = $em->getRepository(Paiement::class);

        // ── Totaux par mode de paiement ───────────────────────────────
        $modeRows = $paiRepo->createQueryBuilder('p')
            ->select('p.mode_paiement AS mode, SUM(p.montant) AS total, COUNT(p.id) AS nb')
            ->where('p.statut = :paye')
            ->setParameter('paye', 'paye')
            ->groupBy('p.mode_paiement')
            ->getQuery()
            ->getResult();

        $totalEnLigne = 0.0;
        $totalEspeces = 0.0;
        $totalCheque  = 0.0;

        foreach ($modeRows as $row) {
            $val = (float) ($row['total'] ?? 0);
            match ($row['mode']) {
                'en_ligne' => $totalEnLigne += $val,
                'especes'  => $totalEspeces += $val,
                'cheque'   => $totalCheque  += $val,
                default    => null,
            };
        }

        // ── Encaissements par agent Kbadha ────────────────────────────
        $parAgentRows = $paiRepo->createQueryBuilder('p')
            ->select(
                'IDENTITY(p.encaissePar) AS agent_id',
                'COUNT(p.id)                        AS nb',
                "SUM(CASE WHEN p.mode_paiement = 'especes'  THEN p.montant ELSE 0 END) AS totalEspeces",
                "SUM(CASE WHEN p.mode_paiement = 'cheque'   THEN p.montant ELSE 0 END) AS totalCheque",
                'SUM(p.montant)                     AS totalGeneral'
            )
            ->where('p.statut = :paye')
            ->andWhere('p.encaissePar IS NOT NULL')
            ->setParameter('paye', 'paye')
            ->groupBy('p.encaissePar')
            ->getQuery()
            ->getResult();

        // Enrichir avec les objets User
        $userRepo = $em->getRepository(\App\Entity\User::class);
        $parAgent = [];
        foreach ($parAgentRows as $row) {
            $agent = $row['agent_id'] ? $userRepo->find($row['agent_id']) : null;
            $parAgent[] = [
                'agent'        => $agent,
                'nb'           => (int) $row['nb'],
                'totalEspeces' => (float) $row['totalEspeces'],
                'totalCheque'  => (float) $row['totalCheque'],
                'totalGeneral' => (float) $row['totalGeneral'],
            ];
        }

        // ── Encaissements par mois (SQL Natif pour éviter erreur DQL YEAR/MONTH) ──
        $conn = $em->getConnection();
        $sql = "
            SELECT 
                strftime('%Y', date_paiement) as annee, 
                strftime('%m', date_paiement) as mois, 
                COUNT(id) as nb, 
                SUM(montant) as total 
            FROM paiement 
            WHERE statut = 'paye' AND date_paiement IS NOT NULL
            GROUP BY annee, mois 
            ORDER BY annee DESC, mois DESC
        ";
        $parMoisRows = $conn->fetchAllAssociative($sql);

        $moisLabels = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
            4 => 'Avril',   5 => 'Mai',     6 => 'Juin',
            7 => 'Juillet', 8 => 'Août',    9 => 'Septembre',
            10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        $parMois = array_map(function (array $row) use ($moisLabels) {
            return [
                'label' => ($moisLabels[(int) $row['mois']] ?? '?') . ' ' . $row['annee'],
                'nb'    => (int) $row['nb'],
                'total' => (float) $row['total'],
            ];
        }, $parMoisRows);

        return $this->render('admin/reporting.html.twig', [
            'totalEnLigne' => $totalEnLigne,
            'totalEspeces' => $totalEspeces,
            'totalCheque'  => $totalCheque,
            'parAgent'     => $parAgent,
            'parMois'      => $parMois,
        ]);
    }

    #[Route('/reporting/download', name: 'reporting_download', methods: ['GET'])]
    public function reportingDownload(EntityManagerInterface $em): Response
    {
        // Augmenter le temps d'exécution pour la génération de PDF
        set_time_limit(120);

        $paiRepo = $em->getRepository(Paiement::class);

        // ── Totaux par mode de paiement ──
        $modeRows = $paiRepo->createQueryBuilder('p')
            ->select('p.mode_paiement AS mode, SUM(p.montant) AS total, COUNT(p.id) AS nb')
            ->where('p.statut = :paye')
            ->setParameter('paye', 'paye')
            ->groupBy('p.mode_paiement')
            ->getQuery()
            ->getResult();

        $totalEnLigne = 0.0;
        $totalEspeces = 0.0;
        $totalCheque  = 0.0;

        foreach ($modeRows as $row) {
            $val = (float) ($row['total'] ?? 0);
            match ($row['mode']) {
                'en_ligne' => $totalEnLigne += $val,
                'especes'  => $totalEspeces += $val,
                'cheque'   => $totalCheque  += $val,
                default    => null,
            };
        }

        // ── Encaissements par agent Kbadha ──
        $parAgentRows = $paiRepo->createQueryBuilder('p')
            ->select(
                'IDENTITY(p.encaissePar) AS agent_id',
                'COUNT(p.id)                        AS nb',
                "SUM(CASE WHEN p.mode_paiement = 'especes'  THEN p.montant ELSE 0 END) AS totalEspeces",
                "SUM(CASE WHEN p.mode_paiement = 'cheque'   THEN p.montant ELSE 0 END) AS totalCheque",
                'SUM(p.montant)                     AS totalGeneral'
            )
            ->where('p.statut = :paye')
            ->andWhere('p.encaissePar IS NOT NULL')
            ->setParameter('paye', 'paye')
            ->groupBy('p.encaissePar')
            ->getQuery()
            ->getResult();

        $userRepo = $em->getRepository(\App\Entity\User::class);
        $parAgent = [];
        foreach ($parAgentRows as $row) {
            $agent = $row['agent_id'] ? $userRepo->find($row['agent_id']) : null;
            $parAgent[] = [
                'agent'        => $agent,
                'nb'           => (int) $row['nb'],
                'totalEspeces' => (float) $row['totalEspeces'],
                'totalCheque'  => (float) $row['totalCheque'],
                'totalGeneral' => (float) $row['totalGeneral'],
            ];
        }

        // ── Encaissements par mois ──
        $conn = $em->getConnection();
        $sql = "
            SELECT 
                strftime('%Y', date_paiement) as annee, 
                strftime('%m', date_paiement) as mois, 
                COUNT(id) as nb, 
                SUM(montant) as total 
            FROM paiement 
            WHERE statut = 'paye' AND date_paiement IS NOT NULL
            GROUP BY annee, mois 
            ORDER BY annee DESC, mois DESC
        ";
        $parMoisRows = $conn->fetchAllAssociative($sql);

        $moisLabels = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
            4 => 'Avril',   5 => 'Mai',     6 => 'Juin',
            7 => 'Juillet', 8 => 'Août',    9 => 'Septembre',
            10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        $parMois = array_map(function (array $row) use ($moisLabels) {
            return [
                'label' => ($moisLabels[(int) $row['mois']] ?? '?') . ' ' . $row['annee'],
                'nb'    => (int) $row['nb'],
                'total' => (float) $row['total'],
            ];
        }, $parMoisRows);

        // ── Génération du PDF avec Dompdf ──
        $html = $this->renderView('admin/reporting_pdf.html.twig', [
            'totalEnLigne' => $totalEnLigne,
            'totalEspeces' => $totalEspeces,
            'totalCheque'  => $totalCheque,
            'parAgent'     => $parAgent,
            'parMois'      => $parMois,
        ]);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Rapport_Financier_' . date('Y-m-d_H-i') . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}
