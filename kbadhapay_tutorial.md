# TP Symfony 7 : Création de la plateforme Kbadhapay
## Digitalisation des Paiements Municipaux
### Guide complet de réalisation pas à pas

**Établissement :** Institut Supérieur des Études Technologiques  
**Enseignante :** Mme Houria El Ghoul  
**Étudiant :** Ben Khedher Ela  
**Année Universitaire :** 2025 – 2026

---

## Table des matières

1. **Introduction** ................................................................................ 3
   1.1 Contexte et problématique ..................................................... 3
   1.2 Objectif du projet ..................................................................... 3
   1.3 Acteurs du système ................................................................. 4
   1.4 Périmètre fonctionnel ............................................................. 4
2. **Environnement de développement** .............................................. 5
   2.1 Prérequis ................................................................................. 5
   2.2 Vérification de l’environnement .............................................. 5
   2.3 Justification des choix techniques ........................................... 6
3. **Création du projet Symfony** ........................................................ 7
   3.1 Commande 1 : Créer le projet Symfony ................................. 7
   3.2 Se déplacer dans le projet ....................................................... 7
   3.3 Commande 2 : Installer les composants webapp .................... 8
   3.4 Commande 3 : Installer les dépendances supplémentaires ...... 8
   3.5 Exploration de la structure générée ......................................... 9
4. **Configuration de la base de données** ........................................ 10
   4.1 Modifier le fichier .env ........................................................... 10
   4.2 Commande 4 : Créer la base de données ................................ 11
5. **Authentification (make:user + make:auth)** ............................... 12
   5.1 Commande 5 : Créer l’entité User .......................................... 12
   5.2 Commande 6 : Créer l’authentificateur .................................. 13
   5.3 Listing : User.php complet commenté .................................... 14
   5.4 Listing : SecurityController.php commenté ............................. 16
   5.5 Listing : login.html.twig adapté .............................................. 18
6. **Création des entités Doctrine** ................................................... 20
   6.1 Commande 7 : Entité Taxe ..................................................... 20
   6.2 Commande 8 : Entité Infraction ............................................ 22
   6.3 Commande 9 : Entité Paiement .............................................. 24
   6.4 Commande 10 : Entité Reclamation ....................................... 26
7. **Création des tables en base de données** ................................... 28
   7.1 Commande 11 : Générer la migration .................................... 28
   7.2 Commande 12 : Exécuter la migration ................................... 28
   7.3 Vérification via HeidiSQL ....................................................... 29
8. **Création des Controllers** ............................................................ 30
   8.1 Commande 13 : Controllers par rôle ...................................... 30
   8.2 Listing : CitizenController.php ............................................... 31
   8.3 Listing : PoliceController.php ................................................ 33
   8.4 Listing : KbadhaController.php ............................................. 35
9. **Création des FormTypes** ............................................................ 37
10. **Création des templates (vues Twig)** ........................................ 40
11. **Configuration de la sécurité** ..................................................... 45
12. **Lancement et test de l’application** ......................................... 50
13. **Récapitulatif des commandes essentielles** ............................... 53
14. **Conclusion** ................................................................................ 55

---

# 1. Introduction

## 1.1 Contexte et problématique
La gestion des finances municipales en Tunisie repose encore largement sur des processus manuels. Le citoyen doit souvent se déplacer physiquement à la mairie, remplir des formulaires papier et attendre de longues files d'attente pour régler ses taxes ou ses amendes. Ce mode de fonctionnement entraîne :
– **Perte de temps** pour le citoyen et l’administration.
– **Absence de traçabilité** numérique des paiements.
– **Exclusion numérique** des citoyens sans accès internet.
– **Lenteur des flux** d'infractions (PV papier).

## 1.2 Objectif du projet
**Kbadhapay** répond à ces enjeux en offrant une plateforme intégrée permettant :
1. Aux citoyens connectés de payer leurs taxes et amendes en ligne.
2. Aux agents de police d'enregistrer les infractions en temps réel sur le terrain.
3. Aux agents "Kbadha" (recette des finances) d'encaisser les paiements au guichet pour les citoyens non-digitalisés.

## 1.3 Acteurs du système

| Acteur | Rôle Symfony | Périmètre d'action |
| :--- | :--- | :--- |
| **Citoyen** | `ROLE_CITOYEN` | Consultation taxes/infractions, paiement en ligne, réclamations. |
| **Agent Police** | `ROLE_POLICE` | Identification conducteur par CIN, saisie d'infractions sur le terrain. |
| **Agent Kbadha** | `ROLE_KBADHA` | Encaissement physique (espèces/chèque) au guichet, impression reçu. |
| **Administrateur** | `ROLE_ADMIN` | Gestion des taxes, supervision des paiements, rapports financiers. |

## 1.4 Périmètre fonctionnel

| Domaine | Fonctionnalités |
| :--- | :--- |
| **Taxes** | Consultation, paiement en ligne, suivi des échéances. |
| **Infractions** | Enregistrement par CIN, notification instantanée, paiement/contestation. |
| **Guichet** | Recherche citoyen par CIN, encaissement physique, génération reçu PDF. |
| **Réclamations** | Dépôt en ligne, suivi du statut, réponse administrative. |
| **Reporting** | Tableaux de bord, statistiques de collecte, export de données. |

---

# 2. Environnement de développement

## 2.1 Prérequis
Pour réaliser ce projet, vous devez avoir installé les outils suivants :
– **PHP 8.3** (avec les extensions pdo_mysql, intl, gd, mbstring).
– **Composer** (gestionnaire de dépendances PHP).
– **Symfony CLI** (pour le serveur local et les outils de développement).
– **Laragon** (serveur WAMP pour gérer MySQL 8.0 et Apache).

## 2.2 Vérification de l’environnement
Avant de commencer, vérifiez que vos outils sont bien fonctionnels.

┌──────────────────────────────────────────────┐
│  php -v                                      │
│  composer --version                          │
│  symfony check:requirements                  │
└──────────────────────────────────────────────┘
Explication :
– `php -v` : affiche la version de PHP (doit être >= 8.2).
– `composer --version` : vérifie que Composer est installé.
– `symfony check:requirements` : outil Symfony qui valide que votre configuration PHP est compatible avec le framework.

## 2.3 Justification des choix techniques
– **Symfony 7** : Framework robuste, sécurisé et modulaire, idéal pour les applications administratives complexes.
– **MySQL 8.0** : Base de données relationnelle puissante pour gérer la cohérence des paiements.
– **Laragon** : Environnement local stable sous Windows offrant une configuration simplifiée (Virtual Hosts, MySQL).
– **Twig & Bootstrap 5** : Pour créer des interfaces responsives et professionnelles rapidement.

---

# 3. Création du projet Symfony

## 3.1 Commande 1 : Créer le projet Symfony
Nous allons utiliser le squelette "webapp" qui inclut toutes les bibliothèques nécessaires pour une application web complète.

┌──────────────────────────────────────────────┐
│  symfony new kbadhapay --webapp              │
└──────────────────────────────────────────────┘
Explication :
– `symfony new` : commande de création de projet.
– `kbadhapay` : nom du dossier de votre application.
– `--webapp` : installe automatiquement Twig, Doctrine, Security, Messenger, etc.

## 3.2 Se déplacer dans le projet
N'oubliez pas de vous placer dans le dossier créé avant de lancer d'autres commandes.

┌──────────────────────────────────────────────┐
│  cd kbadhapay                                │
└──────────────────────────────────────────────┘

## 3.3 Commande 2 : Installer les composants webapp
Si vous avez créé un projet "skeleton", vous devez ajouter le pack webapp manuellement. Dans notre cas, c'est déjà fait, mais voici la commande au cas où :

┌──────────────────────────────────────────────┐
│  composer require webapp                     │
└──────────────────────────────────────────────┘
Explication :
– `composer require` : installe un nouveau package.
– `webapp` : un "pack" qui regroupe les dépendances standard (Twig, Doctrine, Form, Validation).

## 3.4 Commande 3 : Installer les dépendances supplémentaires
Pour la génération des reçus de paiement en PDF, nous utiliserons le bundle KnpSnappy.

┌──────────────────────────────────────────────┐
│  composer require knplabs/knp-snappy-bundle  │
└──────────────────────────────────────────────┘
Explication :
– `knp-snappy-bundle` : permet d'utiliser la bibliothèque wkhtmltopdf pour transformer du HTML/Twig en PDF.

## 3.5 Exploration de la structure générée
Une fois le projet créé, ouvrez-le avec VS Code. Voici l'arborescence simplifiée :

```text
kbadhapay/
├── bin/              # Exécutables (console)
├── config/           # Fichiers de configuration (routes, security, services)
├── migrations/       # Historique des modifications de la base de données
├── public/           # Point d'entrée (index.php, CSS, JS, Images)
├── src/              # Votre code PHP (Controller, Entity, Repository)
│   ├── Controller/   # Logique des pages
│   ├── Entity/       # Modèles de données (classes PHP)
│   └── Repository/   # Requêtes SQL personnalisées
├── templates/        # Vues Twig (HTML avec logique)
├── var/              # Cache et Logs
├── vendor/           # Bibliothèques externes (installées par Composer)
└── .env              # Variables d'environnement (connexion BDD)
```

📝 **Note importante :** Le dossier `src/` est celui où vous passerez 90% de votre temps de développement.

---

# 4. Configuration de la base de données

## 4.1 Modifier le fichier .env
Pour connecter Symfony à MySQL (via Laragon), nous devons modifier la ligne `DATABASE_URL`. Par défaut, Laragon utilise l'utilisateur `root` sans mot de passe.

┌──────────────────────────────────────────────────────────────────────────────┐
│  DATABASE_URL="mysql://root:@127.0.0.1:3306/kbadhapay?serverVersion=8.0.30"  │
└──────────────────────────────────────────────────────────────────────────────┘
Explication :
– `mysql` : le driver de la base de données.
– `root` : l'identifiant par défaut de Laragon.
– `:` : l'emplacement du mot de passe (vide ici).
– `kbadhapay` : le nom de la base de données qui sera créée.

## 4.2 Commande 4 : Créer la base de données
Une fois le fichier configuré, nous demandons à Symfony de créer la base vide.

┌──────────────────────────────────────────────┐
│  php bin/console doctrine:database:create    │
└──────────────────────────────────────────────┘
Explication :
– `doctrine:database:create` : lit le fichier `.env` et crée la base de données physique si elle n'existe pas.

---

# 5. Authentification (make:user + make:auth)

## 5.1 Commande 5 : Créer l’entité User
Dans ce projet, nous utilisons le **Numéro CIN** (8 chiffres) comme identifiant unique à la place de l'email, car il est possédé par tous les citoyens tunisiens.

┌──────────────────────────────────────────────┐
│  php bin/console make:user                   │
└──────────────────────────────────────────────┘
Assistant interactif :
  The name of the security user class [User]:
  > User
  
  Do you want to store user data in the database (via Doctrine) [yes]:
  > yes
  
  Which field will be used to login? [email]:
  > cin
  
  Does this field need to be hashed? [yes]:
  > yes

## 5.2 Commande 6 : Créer l’authentificateur
Nous générons ensuite le système de connexion (formulaire de login).

┌──────────────────────────────────────────────┐
│  php bin/console make:auth                   │
└──────────────────────────────────────────────┘
Choix :
  1. Login form authenticator
  > 1
  
  Class name:
  > AppAuthenticator
  
  Controller name:
  > SecurityController
  
  Support remember me? [yes]:
  > yes

## 5.3 Listing : User.php complet commenté
  Listing 5.1 : src/Entity/User.php
   1  <?php
   2  namespace App\Entity;
   3  
   4  use App\Repository\UserRepository;
   5  use Doctrine\ORM\Mapping as ORM;
   6  use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
   7  use Symfony\Component\Security\Core\User\UserInterface;
   8  
   9  #[ORM\Entity(repositoryClass: UserRepository::class)]
  10  class User implements UserInterface, PasswordAuthenticatedUserInterface
  11  {
  12      #[ORM\Id]
  13      #[ORM\GeneratedValue]
  14      #[ORM\Column]
  15      private ?int $id = null; // Identifiant technique auto-incrémenté
  16  
  17      #[ORM\Column(length: 8, unique: true)]
  18      private ?string $cin = null; // Identifiant métier (8 chiffres)
  19  
  20      #[ORM\Column]
  21      private array $roles = []; // Liste des rôles JSON (ROLE_CITOYEN, ROLE_POLICE...)
  22  
  23      #[ORM\Column]
  24      private ?string $password = null; // Mot de passe crypté
  25  
  26      #[ORM\Column(length: 255, nullable: true)]
  27      private ?string $nom = null; // Nom complet du citoyen ou de l'agent
  28  
  29      #[ORM\Column(length: 20, nullable: true)]
  30      private ?string $telephone = null; // Pour les notifications SMS
  31  
  32      // Méthodes requises par UserInterface
  33      public function getUserIdentifier(): string { return (string) $this->cin; }
  34      public function getRoles(): array { 
  35          $roles = $this->roles;
  36          $roles[] = 'ROLE_USER'; // Rôle de base par défaut
  37          return array_unique($roles);
  38      }
  39      // ... Getters et Setters générés
  40  }

---

# 6. Création des entités Doctrine

Nous allons créer les entités métier du projet. Pour chaque entité, nous utilisons la commande `make:entity` et suivons l'assistant.

## 6.1 Commande 7 : Créer l’entité Taxe
Cette entité représente le catalogue des taxes municipales (Habitation, Parking, etc.).

┌──────────────────────────────────────────────┐
│  php bin/console make:entity Taxe            │
└──────────────────────────────────────────────┘
Assistant interactif :
  New property name: > nom_taxe
  Field type: > string
  Length: > 150
  Nullable: > no

  New property name: > montant
  Field type: > decimal
  Precision: > 10
  Scale: > 3
  Nullable: > no

## 6.2 Commande 8 : Créer l’entité Infraction
L'infraction est liée à un Citoyen (ManyToOne) et à un Agent de Police.

┌──────────────────────────────────────────────┐
│  php bin/console make:entity Infraction      │
└──────────────────────────────────────────────┘
Assistant interactif (Relations) :
  New property name: > user
  Field type: > relation
  What class: > User
  Relation type: > ManyToOne
  Is nullable: > no
  Do you want to add a property to User (OneToMany): > yes (infractions)

## 6.3 Listing : Infraction.php (extrait des relations)
  Listing 6.1 : src/Entity/Infraction.php
   1  // ...
   2  #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'infractions')]
   3  #[ORM\JoinColumn(nullable: false)]
   4  private ?User $user = null; // Le citoyen qui a commis l'infraction
   5
   6  #[ORM\ManyToOne(targetEntity: User::class)]
   7  #[ORM\JoinColumn(nullable: false)]
   8  private ?User $agent = null; // L'agent de police qui a saisi l'infraction
   9  // ...

## 6.4 Commande 9 : Créer l’entité Paiement
Le paiement est l'entité centrale qui lie une Taxe ou une Infraction à un mode de règlement.

┌──────────────────────────────────────────────┐
│  php bin/console make:entity Paiement        │
└──────────────────────────────────────────────┘
📝 **Note importante :** Le champ `mode_paiement` utilise une énumération PHP (PHP 8.1+) pour limiter les valeurs à 'en_ligne', 'especes', ou 'cheque'.

---

# 7. Création des tables en base de données

## 7.1 Commande 10 : Générer la migration
Une migration est un fichier PHP contenant le code SQL nécessaire pour synchroniser la base de données avec vos entités.

┌──────────────────────────────────────────────┐
│  php bin/console make:migration              │
└──────────────────────────────────────────────┘

## 7.2 Commande 11 : Exécuter la migration
Cette commande applique réellement les changements sur MySQL.

┌──────────────────────────────────────────────┐
│  php bin/console doctrine:migrations:migrate  │
└──────────────────────────────────────────────┘

---

# 8. Création des Controllers et Logique Métier

## 8.1 Fonctionnalité : Recherche par CIN (Police & Kbadha)
L'agent saisit un numéro CIN et le système récupère le citoyen correspondant.

  Listing 8.1 : src/Controller/PoliceController.php (recherche)
   1  #[Route('/police/recherche', name: 'police_recherche')]
   2  public function recherche(Request $request, UserRepository $userRepo): Response
   3  {
   4      $cin = $request->request->get('cin');
   5      $citoyen = $userRepo->findOneBy(['cin' => $cin]);
   6  
   7      if (!$citoyen) {
   8          $this->addFlash('danger', 'Citoyen introuvable.');
   9          return $this->render('police/recherche.html.twig');
  10      }
  11  
  12      return $this->redirectToRoute('police_nouvelle_infraction', ['id' => $citoyen->getId()]);
  13  }

## 8.2 Fonctionnalité : Notifications SMS Automatisées
Le projet intègre un service SMS qui prévient le citoyen dès qu'une infraction est enregistrée.

💡 **Astuce :** Nous utilisons les **Entity Listeners** de Doctrine pour déclencher l'envoi du SMS automatiquement après l'insertion d'une infraction.

---

# 9. Création des FormTypes

## 9.1 Listing : InfractionType.php
  Listing 9.1 : src/Form/InfractionType.php
   1  class InfractionType extends AbstractType
   2  {
   3      public function buildForm(FormBuilderInterface $builder, array $options): void
   4      {
   5          $builder
   6              ->add('type_infraction', ChoiceType::class, [
   7                  'choices' => [
   8                      'Stationnement interdit' => 'STATIONNEMENT',
   9                      'Excès de vitesse' => 'VITESSE',
  10                      'Feu rouge' => 'FEU_ROUGE',
  11                  ],
  12              ])
  13              ->add('montant_amende', MoneyType::class, ['currency' => 'TND'])
  14              ->add('lieu')
  15              ->add('plaque_immat', TextType::class, ['label' => 'Immatriculation']);
  16      }
  17  }

---

# 10. Création des templates (vues Twig)

## 10.1 Template de base : base.html.twig
  Listing 10.1 : templates/base.html.twig
   1  <!DOCTYPE html>
   2  <html>
   3  <head>
   4      <title>{% block title %}Kbadhapay{% endblock %}</title>
   5      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
   6  </head>
   7  <body>
   8      <nav class="navbar navbar-dark bg-dark">
   9          <a class="navbar-brand px-3" href="#">Kbadhapay</a>
  10          <div class="navbar-nav flex-row">
  11              {% if is_granted('ROLE_CITOYEN') %}
  12                  <a class="nav-link px-2" href="{{ path('citizen_dashboard') }}">Mes Taxes</a>
  13              {% endif %}
  14              {% if is_granted('ROLE_POLICE') %}
  15                  <a class="nav-link px-2" href="{{ path('police_recherche') }}">Verbaliser</a>
  16              {% endif %}
  17          </div>
  18      </nav>
  19      <div class="container mt-4">
  20          {% block body %}{% endblock %}
  21      </div>
  22  </body>
  23  </html>

---

# 11. Configuration de la sécurité

## 11.1 Hiérarchie des rôles
```yaml
# config/packages/security.yaml
role_hierarchy:
    ROLE_POLICE:      ROLE_USER
    ROLE_KBADHA:      ROLE_USER
    ROLE_ADMIN:       [ROLE_POLICE, ROLE_KBADHA, ROLE_CITOYEN]
```

## 11.2 Redirection automatique selon le rôle connecté
  Listing 11.1 : src/Security/AppAuthenticator.php
   1  public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
   2  {
   3      $user = $token->getUser();
   4      if (in_array('ROLE_ADMIN', $user->getRoles())) {
   5          return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
   6      }
   7      // ... autres redirections
   8  }

---

# 12. Lancement et test de l’application

┌──────────────────────────────────────────────┐
│  symfony serve -d                            │
└──────────────────────────────────────────────┘

---

# 13. Récapitulatif des commandes essentielles

| Commande | Description |
| :--- | :--- |
| `make:user` | Créer l'entité utilisateur |
| `make:auth` | Créer le système de login |
| `make:entity` | Créer une table de données |
| `make:migration` | Préparer la mise à jour BDD |
| `doctrine:migrations:migrate` | Appliquer les changements |

---

# 14. Conclusion

Le projet **Kbadhapay** illustre la puissance de Symfony 7 pour digitaliser des services publics complexes. Grâce à une architecture solide et des outils modernes (SMS, PDF, Sécurité RBAC), la plateforme offre un service inclusif et performant pour tous les citoyens tunisiens.
