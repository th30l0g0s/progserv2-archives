# site4night

> Projet réalisé dans le cadre du cours _Programmation serveur 2 (ProgServ2)_ à la [HEIG-VD](https://heig-vd.ch/), année académique 2026-2027.

**En ligne :** <https://progserv2.laurenternst.ch>

## Table des matières

- [Présentation](#présentation)
- [Équipe](#équipe)
- [Documentation du projet](#documentation-du-projet)
- [Structure du dépôt](#structure-du-dépôt)
- [Développement local](#développement-local)
- [Déploiement en production](#déploiement-en-production)
- [Workflow Git](#workflow-git)
- [Utilisation d'outils d'intelligence artificielle](#utilisation-doutils-dintelligence-artificielle)
- [Conclusion](#conclusion)

## Présentation

Trouver un endroit où passer la nuit en tente, en 4x4 aménagé ou en camping-car est souvent compliqué : les informations sont dispersées (forums, réseaux sociaux, bouche-à-oreille) et rarement à jour.

**site4night** permet à une communauté de voyageurs de **référencer, rechercher et évaluer des lieux où dormir** en Suisse, en précisant pour chaque lieu :

- les types d'hébergement adaptés (tente, 4x4, camping-car) ;
- les équipements disponibles (eau, toilettes, feu autorisé, électricité…) ;
- le type d'accès (route goudronnée, piste, sentier) ;
- la note moyenne et les avis des autres voyageurs.

Les lieux proposés par les utilisateurs sont validés par un administrateur avant publication. L'application ne garantit pas la légalité d'un séjour : chaque fiche rappelle que la réglementation locale doit être vérifiée.

### Rôles

| Rôle | Ce qu'il peut faire |
|---|---|
| Visiteur | Consulter et rechercher les lieux publiés. |
| Utilisateur | Proposer des lieux, rédiger des avis, gérer ses favoris et son profil. |
| Administrateur | Valider ou refuser les lieux, modérer les avis, gérer les utilisateurs. |

### Technologies

- PHP 8.5 orienté objet, **sans framework**
- MariaDB 11.4 (MySQL compatible), accès par PDO et requêtes préparées
- Docker Compose pour le développement local
- Hébergement Infomaniak pour la production

## Équipe

| Membre | Rôle | Contributions |
|---|---|---|
| Laurent Ernst | Développeur unique (depuis novembre 2026) | Cahier des charges, maquette Figma, configuration Docker, déploiement Infomaniak, développement de l'application |
| Samuel Develey | Membre jusqu'en octobre 2026 (a quitté la formation) | Infrastructure Docker initiale, structure du dépôt, schémas MCD / MLD / MPD, script SQL avec données de test, wireframes PDF |

## Documentation du projet

| Document | Contenu |
|---|---|
| [`cahier_des_charges.md`](./cahier_des_charges.md) | Fonctionnalités principales et optionnelles, pages prévues, modèle de données, planification |
| [`maquette/`](./maquette/) | Maquette de l'interface (fichier Figma `.fig`, export HTML et 12 wireframes PDF) |
| [`bdd/`](./bdd/) | Modèles conceptuel (MCD), logique (MLD) et physique (MPD), en `.drawio` et `.pdf` |
| [`DEPLOIEMENT.md`](./DEPLOIEMENT.md) | Guide pas à pas du déploiement sur Infomaniak |

## Structure du dépôt

```text
.
├── public/                  # Racine web : seul dossier accessible depuis Internet
│   ├── index.php            # Point d'entrée
│   └── .htaccess            # HTTPS forcé, fichiers cachés bloqués
├── src/                     # Code PHP non accessible depuis le web
│   ├── utils/
│   │   └── autoloader.php   # Chargement automatique des classes
│   └── classes/             # Un namespace = un dossier, une classe = un fichier
│       ├── Config.php       # Lecture de la configuration (env Docker ou config.local.php)
│       ├── Database.php     # Connexion PDO partagée (+ DatabaseInterface)
│       ├── Spots/           # Spot (lieu), SpotsManager (+ interfaces)
│       ├── Users/           # User (compte), UsersManager (+ interfaces)
│       └── References/      # ReferencesManager : cantons, types, équipements
├── docker/
│   ├── php/                 # Image Apache + PHP 8.5 (Dockerfile, php.ini)
│   └── db/init/             # Scripts SQL exécutés à la création de la BDD locale
├── bdd/                     # Schémas de la base de données
├── maquette/                # Maquette de l'interface utilisateur
├── deploy/
│   ├── deploy.sh            # Envoi du code sur Infomaniak (rsync / SSH)
│   └── deploy.env.example   # Modèle des identifiants SSH (copie locale non versionnée)
├── docker-compose.yml       # Environnement de développement local
├── .env.example             # Modèle des variables d'environnement locales
├── config.local.example.php # Modèle de configuration pour la production
├── cahier_des_charges.md
└── DEPLOIEMENT.md
```

**Principe de sécurité :** seul `public/` est exposé par le serveur web. Le code (`src/`) et les identifiants (`config.local.php`, `.env`) restent hors de la racine web et ne sont jamais versionnés.

## Développement local

### Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Mac Apple Silicon compatible)
- Git

### Lancer le projet

1. Cloner le dépôt :

   ```sh
   git clone https://github.com/th30l0g0s/progserv2.git
   cd progserv2
   ```

2. Créer le fichier `.env` à partir du modèle, puis choisir des mots de passe :

   ```sh
   cp .env.example .env
   ```

3. Démarrer les conteneurs :

   ```sh
   docker compose up -d
   ```

   Au premier démarrage, MariaDB exécute les scripts de `docker/db/init/` (schéma + données de test).

### Services disponibles

| Service | Adresse | Usage |
|---|---|---|
| Application | <http://localhost:8080> | Site PHP |
| phpMyAdmin | <http://localhost:9090> | Administration de la BDD |
| Mailpit | <http://localhost:8025> | Boîte de réception des e-mails de test |

### Commandes utiles

```sh
docker compose logs -f web   # Journaux Apache / PHP
docker compose down          # Arrêter
docker compose down -v       # Arrêter ET supprimer la BDD (réimport des scripts au prochain démarrage)
```

### Comptes de test

Le script `docker/db/init/02-site4night.sql` crée des comptes de test (dont un administrateur). Leur mot de passe commun est indiqué en commentaire dans ce fichier. **Ces comptes sont réservés au développement local** : en production, leur mot de passe doit être changé ou les comptes supprimés.

## Déploiement en production

L'application est hébergée chez Infomaniak à l'adresse <https://progserv2.laurenternst.ch>, avec une base MariaDB dédiée.

Résumé (détails dans [`DEPLOIEMENT.md`](./DEPLOIEMENT.md)) :

1. Le site pointe sur le dossier `sites/progserv2.laurenternst.ch/public` du serveur.
2. La base est importée via phpMyAdmin à partir de `docker/db/init/02-site4night.sql`.
3. Le fichier `config.local.php` (copié de `config.local.example.php`) est créé **directement sur le serveur**, à côté de `public/` et `src/`, avec les identifiants de la base.
4. Les identifiants SSH sont placés dans `deploy/deploy.env` (copie de `deploy/deploy.env.example`, jamais versionnée).
5. Le code est envoyé avec :

   ```sh
   ./deploy/deploy.sh --dry   # simulation
   ./deploy/deploy.sh         # envoi réel
   ```

### Fonctionnement de la configuration

La classe `Config` (`src/classes/Config.php`) cherche chaque valeur (hôte, nom de la base, utilisateur, mot de passe…) dans cet ordre :

1. les **variables d'environnement** : fournies par `docker-compose.yml` en local ;
2. le fichier **`config.local.php`** : utilisé en production, où Docker n'existe pas.

Le même code fonctionne ainsi dans les deux environnements, sans jamais écrire d'identifiant dans le dépôt.

## Architecture du code

L'application suit les principes de la programmation orientée objet vus en cours.

### Chargement automatique

Chaque page de `public/` commence par :

```php
require_once __DIR__ . '/../src/utils/autoloader.php';
```

`spl_autoload_register()` transforme le nom complet d'une classe en chemin de fichier : `Spots\SpotsManager` → `src/classes/Spots/SpotsManager.php`. Aucun autre `require` n'est nécessaire.

### Entités et managers

| Type | Rôle | Exemples |
|---|---|---|
| **Entité** | Représente une donnée. Les setters **valident** chaque valeur (`InvalidArgumentException` si invalide) : un objet est toujours dans un état correct. | `Spot`, `User` |
| **Manager** | Lit et écrit les entités dans la base, uniquement avec des **requêtes préparées**. | `SpotsManager`, `UsersManager`, `ReferencesManager` |
| **Interface** | Contrat que la classe doit respecter. | `SpotInterface`, `SpotsManagerInterface`… |

Les managers reçoivent la base de données par leur constructeur (`DatabaseInterface`) : une seule connexion PDO est ouverte par requête HTTP.

### Exemple d'utilisation

```php
use Spots\SpotsManager;

$spotsManager = new SpotsManager();

// Lieux publiés en Valais, accessibles en 4x4 (id 2), avec eau (id 1)
$spots = $spotsManager->getPublishedSpots([
    'canton' => 'VS',
    'accommodation' => 2,
    'amenities' => [1],
]);
```

### Sécurité déjà en place

- **Injection SQL :** requêtes préparées partout, `PDO::ATTR_EMULATE_PREPARES` désactivé.
- **XSS :** toute valeur affichée passe par `htmlspecialchars()`.
- **Mots de passe :** `password_hash()` / `password_verify()`, jamais stockés en clair.
- **Erreurs :** le détail technique va dans le journal du serveur, jamais à l'écran en production.
- **Validation côté serveur :** dans les setters des entités.

## Workflow Git

- Une **issue** par fonctionnalité ou correction.
- Une **branche** par issue (`feature/…`, `fix/…`, `docs/…`).
- Une **pull request** vers `main`, qui référence l'issue (`Closes #N`), puis fusion.
- Pas de commit direct sur `main`.

## Utilisation d'outils d'intelligence artificielle

Conformément aux consignes du cours, cette section indique **quand**, **comment** et **pourquoi** une aide externe a été utilisée.

### Claude (Anthropic) : préparation du déploiement, octobre 2026

- **Quand :** 26 octobre 2026, commit `7d41cdd`.
- **Fichiers concernés :** `src/config.php`, `config.local.example.php`, `public/.htaccess`, `deploy/deploy.sh`, `DEPLOIEMENT.md`, adaptation de `public/index.php`.
- **Pourquoi :** le projet tournait uniquement sous Docker, où les identifiants de la base viennent de variables d'environnement. Infomaniak n'utilise pas Docker. Il fallait une solution qui fonctionne dans les deux environnements sans versionner de mot de passe.
- **Comment ça fonctionne :**
  - `src/config.php` définissait une fonction `cfg()` qui lisait d'abord la variable d'environnement (`getenv`), puis, si elle était absente, la valeur du tableau retourné par `config.local.php`. Ce fichier a ensuite été remplacé par la classe `Config` (voir ci-dessous), qui applique la même logique.
  - `public/.htaccess` redirige HTTP vers HTTPS (sauf sur `localhost`), désactive l'affichage des erreurs PHP et renvoie une 404 pour tout fichier caché (`.env`, `.git`).
  - `deploy/deploy.sh` utilise `rsync` via SSH pour envoyer seulement le code nécessaire (le reste est exclu : Docker, maquettes, Markdown, `config.local.php`). L'option `--dry` simule l'envoi sans rien modifier.
  - `public/index.php` masque le détail des erreurs de connexion à la BDD en production, car il pourrait révéler l'hôte ou l'utilisateur.
- **Intégration :** le code a été relu et testé en production (la page d'accueil affiche le nombre de lieux lus dans la base Infomaniak), puis nettoyé dans le commit `b571e21`.

### Claude (Anthropic) : rédaction du README, novembre 2026

- **Quand :** 18 novembre 2026.
- **Pourquoi :** reprise de la documentation après le départ du binôme, pour regrouper en un seul document la présentation du projet, l'installation locale et le déploiement.
- **Comment :** Claude a rédigé le texte à partir des fichiers existants du dépôt (cahier des charges, `DEPLOIEMENT.md`, `docker-compose.yml`). Le contenu a été relu et validé.

### Claude (Anthropic) : structure orientée objet et autoload, novembre 2026

- **Quand :** 18 novembre 2026, branche `feature/poo-autoload`.
- **Fichiers concernés :** `src/utils/autoloader.php` et tout le dossier `src/classes/`, réécriture de `public/index.php`.
- **Pourquoi :** passer d'un script de test procédural à une architecture orientée objet réutilisable par toutes les pages à venir, en suivant la convention du cours (dossier `classes/`, autoloader `spl_autoload_register`, paires entité / manager avec interfaces, comme dans l'exercice `Tools` du chapitre 4).
- **Comment ça fonctionne :**
  - `autoloader.php` convertit le namespace en chemin de fichier et l'inclut avec `require_once`.
  - `Config` lit la configuration dans les variables d'environnement, puis dans `config.local.php` (chargé une seule fois grâce à une propriété statique).
  - `Database` crée la connexion PDO à la première demande et la garde dans une propriété statique : tous les managers partagent la même connexion.
  - `Spot` et `User` valident leurs données dans les setters. `Spot::fromDatabaseRow()` reconstruit un objet à partir d'une ligne SQL.
  - `SpotsManager` récupère un lieu avec ses types d'hébergement et équipements grâce à des sous-requêtes `GROUP_CONCAT`, et calcule la note moyenne avec `AVG`. Les filtres de recherche ajoutent des conditions `EXISTS` avec un paramètre nommé par valeur. L'ajout et la modification utilisent une **transaction** : le lieu et ses liens sont enregistrés ensemble ou pas du tout.
  - `UsersManager` hache les mots de passe avec `password_hash()`. Il transforme l'erreur MariaDB 1062 (doublon) en message lisible, et `verifyCredentials()` refuse les comptes désactivés.
- **Intégration et vérification :** le code a été testé sur une base MariaDB 11 importée depuis `docker/db/init/02-site4night.sql`. Les tests couvrent la lecture, les filtres (comparés à des requêtes SQL écrites à la main), l'ajout, la modification, le refus et la suppression d'un lieu, la création de compte, la connexion, les doublons, la validation et une tentative d'injection SQL dans un filtre.

### Claude (Anthropic) : cahier des charges initial, septembre 2026

- **Quand :** 23 septembre 2026.
- **Pourquoi :** structurer rapidement un cahier des charges conforme aux exigences du cours, à partir de l'idée du projet (référencer des lieux pour dormir en tente, en 4x4 ou en camping-car).
- **Comment :** Claude a lu le README du projet libre sur le dépôt du cours, puis rédigé une première version complète du document en Markdown. Elle comprenait les rôles, les fonctionnalités principales et optionnelles, les pages prévues, une première ébauche du modèle de données, les contraintes techniques et la planification. Claude a aussi signalé les points à valider : les noms et rôles de l'équipe, l'autorisation d'une bibliothèque de cartographie et la question légale du camping sauvage, qui a conduit à la validation des lieux par un administrateur et à l'avertissement sur chaque fiche.
- **Intégration :** le document a été relu, puis complété (équipe, rôles) avant d'être versionné dans `cahier_des_charges.md`.

### Claude (Anthropic) : maquette Figma et page « en construction », octobre 2026

- **Quand :** 7 octobre 2026.
- **Pourquoi :** produire une maquette complète couvrant toutes les pages du cahier des charges, dans le délai de la séance 3.
- **Comment :** à partir du cahier des charges stocké dans Notion, Claude a créé dans Figma (via son intégration) une maquette desktop de 14 écrans : les pages publiques, privées et d'administration. Il a aussi créé un système de design réutilisable (16 variables de couleur, 3 variantes d'en-tête selon le rôle, un pied de page et une carte de lieu en composants). La palette bleu nuit et ambre, la police Inter et le logo en croissant de lune sont des propositions de Claude, faute de direction graphique fournie. Claude a ensuite écrit la page HTML/CSS « Site en construction » (`maquette/index.html`) dans le même style.
- **Intégration :** la maquette a été relue et sert de référence pour l'interface. Le fichier Figma est versionné dans `maquette/`. Les wireframes PDF de `maquette/All-wireframes/` ont été ajoutés par Samuel Develey.

### [OUTIL À PRÉCISER] : correction du script SQL, octobre 2026

- **Quand :** [DATE À PRÉCISER] octobre 2026.
- **Pourquoi :** corriger des erreurs dans le script `docker/db/init/02-site4night.sql` (schéma et données de test).
- **Comment :** [À PRÉCISER : quelles erreurs, et ce que l'outil a corrigé].
- **Intégration :** le script corrigé est importé automatiquement par Docker au premier démarrage et a servi à initialiser la base de production.

## Conclusion

_À compléter en fin de projet : fonctionnalités réellement implémentées, écarts avec le cahier des charges, difficultés rencontrées et solutions apportées._
