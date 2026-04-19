<?php

namespace App\Controller;

use App\Entity\Infraction;
use App\Form\InfractionPoliceType;
use App\Repository\InfractionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/police', name: 'police_')]
#[IsGranted('ROLE_POLICE')]
class PoliceController extends AbstractController
{
    // ─────────────────────────────────────────────────────────────────
    // 1. DASHBOARD AGENT POLICE
    // ─────────────────────────────────────────────────────────────────
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(InfractionRepository $infraRepo): Response
    {
        /** @var \App\Entity\User $agent */
        $agent = $this->getUser();

        // Toutes les infractions enregistrées par cet agent
        $toutesInfractions = $infraRepo->findBy(['agent' => $agent], ['date_infraction' => 'DESC']);

        // Infractions aujourd'hui
        $aujourdhui = new \DateTime('today');
        $demain     = new \DateTime('tomorrow');
        $infractionsAujourdhui = array_filter($toutesInfractions, function (Infraction $i) use ($aujourdhui, $demain) {
            $d = $i->getDateInfraction();
            return $d && $d >= $aujourdhui && $d < $demain;
        });

        // Infractions ce mois-ci
        $debutMois = new \DateTime('first day of this month midnight');
        $infractionsMonth = array_filter($toutesInfractions, function (Infraction $i) use ($debutMois) {
            $d = $i->getDateInfraction();
            return $d && $d >= $debutMois;
        });

        // Total amendes
        $totalAmende = array_sum(array_map(
            fn(Infraction $i) => (float) ($i->getMontantAmende() ?? 0),
            $toutesInfractions
        ));

        return $this->render('police/dashboard.html.twig', [
            'nbInfractionsAujourdhui' => count($infractionsAujourdhui),
            'nbInfractionsMois'       => count($infractionsMonth),
            'totalAmende'             => number_format($totalAmende, 3, ',', ' '),
            'dernieresInfractions'    => array_slice($toutesInfractions, 0, 5),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. RECHERCHE CITOYEN PAR CIN
    // ─────────────────────────────────────────────────────────────────
    #[Route('/citoyen/recherche', name: 'recherche_citoyen', methods: ['GET', 'POST'])]
    public function rechercherCitoyen(Request $request, UserRepository $userRepo): Response
    {
        $cin    = null;
        $erreur = null;

        $form = $this->createFormBuilder()
            ->add('cin', TextType::class, [
                'label' => 'Numéro CIN du conducteur',
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
                return $this->redirectToRoute('police_nouvelle_infraction', [
                    'citoyen_id' => $citoyen->getId(),
                ]);
            }

            $erreur = sprintf('Aucun citoyen enregistré avec le CIN "%s".', $cin);
        }

        return $this->render('police/recherche.html.twig', [
            'form'   => $form,
            'cin'    => $cin,
            'erreur' => $erreur,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. ENREGISTRER UNE INFRACTION POUR UN CITOYEN
    // ─────────────────────────────────────────────────────────────────
    #[Route('/infraction/new/{citoyen_id}', name: 'nouvelle_infraction', methods: ['GET', 'POST'], requirements: ['citoyen_id' => '\d+'])]
    public function nouvelleInfraction(
        int $citoyen_id,
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $citoyen = $userRepo->find($citoyen_id);

        if (!$citoyen) {
            $this->addFlash('danger', 'Citoyen introuvable.');
            return $this->redirectToRoute('police_recherche_citoyen');
        }

        $infraction = new Infraction();
        $form = $this->createForm(InfractionPoliceType::class, $infraction);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var \App\Entity\User $agent */
                $agent = $this->getUser();

                $infraction
                    ->setUser($citoyen)
                    ->setAgent($agent)
                    ->setDateInfraction(new \DateTime())
                    ->setStatut('a_payer');

                $em->persist($infraction);
                $em->flush();

                $this->addFlash('success', 'Infraction enregistrée avec succès. Le citoyen en a été notifié.');

                return $this->redirectToRoute('police_dashboard');
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
            }
        }

        return $this->render('police/nouvelle_infraction.html.twig', [
            'infraction' => $infraction,
            'citoyen'    => $citoyen,
            'form'       => $form,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 4. MES INFRACTIONS ENREGISTRÉES
    // ─────────────────────────────────────────────────────────────────
    #[Route('/infractions', name: 'infractions', methods: ['GET'])]
    public function mesInfractions(InfractionRepository $infraRepo): Response
    {
        /** @var \App\Entity\User $agent */
        $agent = $this->getUser();

        return $this->render('police/infractions.html.twig', [
            'infractions' => $infraRepo->findBy(['agent' => $agent], ['date_infraction' => 'DESC']),
        ]);
    }
}
