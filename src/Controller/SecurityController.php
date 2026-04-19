<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Déjà connecté → rediriger directement
        if ($this->getUser()) {
            return $this->redirectToRoute('app_redirect_role');
        }

        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    // ─────────────────────────────────────────────────────────────────
    // Redirection post-connexion selon le rôle
    // ─────────────────────────────────────────────────────────────────
    #[Route(path: '/redirect-role', name: 'app_redirect_role')]
    public function redirectRole(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($this->isGranted('ROLE_POLICE')) {
            return $this->redirectToRoute('police_dashboard');
        }

        if ($this->isGranted('ROLE_KBADHA')) {
            return $this->redirectToRoute('kbadha_dashboard');
        }

        // ROLE_CITOYEN et tout autre rôle
        return $this->redirectToRoute('citizen_dashboard');
    }
}
