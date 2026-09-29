# 📸 NY TIA SARY — Studio Photo & Vidéo

> Plateforme web de gestion complète d'un studio de photographie et de production vidéo
> basé à **Toamasina, Madagascar**.
> Site vitrine public + Espace administrateur + Espace client.

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://dev.mysql.com/)
[![License](https://img.shields.io/badge/License-Propri%C3%A9-orange.svg)](#)

---

## 📑 Table des matières

1. [Présentation](#1--présentation)
2. [Fonctionnalités](#2--fonctionnalités)
3. [Stack technique](#3--stack-technique)
4. [Architecture du projet](#4--architecture-du-projet)
5. [Modèle de données](#5--modèle-de-données)
6. [Parcours métier](#6--parcours-métier)
7. [Navigation](#7--navigation)
8. [Installation locale (XAMPP)](#8--installation-locale-xampp)
9. [Déploiement en production](#9--déploiement-en-production)
10. [Configuration](#10--configuration)
11. [API](#11--api)
12. [Sécurité](#12--sécurité)
13. [Anomalies connues](#13--anomalies-connues)
14. [Bonnes pratiques de développement](#14--bonnes-pratiques-de-développement)
15. [FAQ / Dépannage](#15--faq--dépannage)

---

## 1. Présentation

**NY TIA SARY** est une application web monolithic en PHP qui couvre l'ensemble de la
chaîne opérationnelle d'un studio photo/vidéo :

| Espace | URL | Accès | Description |
|--------|-----|-------|-------------|
| **Site vitrine** | `/index.php` | Public | Accueil, À propos, Services, Portfolio, Blog, Contact, demande de devis |
| **Authentification** | `/login/login.php` | Public | Connexion, inscription, récupération de mot de passe |
| **Espace Admin** | `/espace/admin/home.php` | Rôle `ADMIN` | Pilotage complet du studio (15 pages) |
| **Espace Client** | `/espace/client/home.php` | Rôle `CLIENT` | Suivi personnel du client (14 pages) |

**Montée en charge réelle :** ~73 fichiers applicatifs, ~24 000 lignes de code
(hors bibliothèques tierces).

---

## 2. Fonctionnalités

### 2.1 Site vitrine (public)

| Page | Fichier | Contenu |
|------|---------|---------|
| Accueil | `index.php` | Hero, À propos, Services, Portfolio, Témoignages, Partenaires, CTA devis |
| À propos | `apropos.php` | Présentation de l'équipe et du matériel |
| Services | `service.php` | 7 prestations avec tarifs par catégorie |
| Portfolio | `portfolio.php` | Galerie filtrable + lightbox |
| Blog | `blog.php` | Articles publiés, filtrage par type, pagination |
| Contact | `contact.php` | Formulaire + carte Google Maps + coordonnées depuis la BDD |
| Modal devis | `composante/devis_visiteur.php` | Formulaire de devis avec catégories dynamiques |

- Animations d'entrée au scroll via **ScrollReveal 4.0.9**
- Notifications via **Toastify**
- Icônes via **Font Awesome 6.4.0**
- Contenu de démonstration (témoignages, portfolio) sourced depuis la base avec fallback statique

### 2.2 Authentification

| Fonctionnalité | Détail |
|----------------|--------|
| Inscription | Création transactionnelle `AUTHENTIFICATION` + `CLIENT` + `SECUTITE` |
| Connexion | Routage automatique selon `ROLE_AUTH` (`ADMIN` / `CLIENT`) |
| Hash mot de passe | `password_hash()` / `password_verify()` en **bcrypt** |
| Question de sécurité | Stockée hashée en bcrypt (table `SECUTITE`) |
| Réinitialisation MDP | Assistant en **4 étapes** (email → méthode → vérification → nouveau MDP) |
| Anti-énumération | Réponse identique que l'email existe ou non |
| Normalisation téléphone | Ajout automatique de l'indicatif `+261` |

### 2.3 Espace Administrateur — 15 pages

| Page | Rôle | Écrit en base |
|------|------|---------------|
| `home.php` | Dashboard : 5 tuiles KPI, graphique 12 mois (Chart.js), 5 dernières réservations | — (lecture) |
| `reservations.php` | Liste + filtres, changement de statut **AJAX**, génération auto du contrat à la confirmation | `RESERVATION`, `CONTRAT`, `notification` |
| `contrats.php` | Création / suppression de contrats, liste avec facture liée | `CONTRAT`, `RESERVATION`, `notification` |
| `factures.php` | CRUD factures, numérotation `FAC-YYYY-NNNN`, ledger de paiements, stats financières | `FACTURE`, `PAIEMENT`, `notification` |
| `paiements.php` | Journal des encaissements, filtre par mois, stats de recouvrement | — (lecture) |
| `clients.php` | Annuaire clients avec recherche + fiche détaillée (profil + réservations) | — (lecture) |
| `devis.php` | Boîte de réception des devis, suppression, **envoi de la réponse par email** | `DEVIS`, `DEVIS_CATEGORIES`, `PIECES_JOINTES`, `notification` |
| `calendrier.php` | Vue calendrier mensuelle (FullCalendar 6.1.11) colorée par statut | — (lecture) |
| `medias.php` | Livraison des photos/vidéos clients (upload 2 Go) + suppression | `MEDIA`, `notification` |
| `blog.php` | CRUD articles + upload de couverture + publier/brouillon | `BLOG` |
| `temoignages.php` | Liste des avis clients + note moyenne + suppression | `TEMOIGNAGE` |
| `prestations.php` | CRUD des 7 prestations (suppression verrouillée si utilisée) | `PRESTATIONS` |
| `categories.php` | CRUD des formules/tarifs, groupés par prestation | `CATEGORIE` |
| `notifications.php` | Centre de notifications (version pleine page) | — (lecture) |
| `parametres.php` | Compte admin + **gestion du contenu** : types de blog, partenaires, coordonnées | `AUTHENTIFICATION`, `CLIENT`, `TYPE_BLOG`, `partenaire`, `contact` |
| `logout.php` | Déconnexion (endpoint, pas une page) | — |

**Les 5 KPI du dashboard** (`home.php`) :

```sql
SELECT COUNT(*) FROM AUTHENTIFICATION WHERE ROLE_AUTH = 'CLIENT'            -- Clients inscrits
SELECT COUNT(*) FROM RESERVATION                                            -- Total réservations
SELECT COUNT(*) FROM RESERVATION WHERE STATUS_RESERVATION = 'EN ATTENTE'   -- En attente
SELECT COUNT(*) FROM DEVIS                                                  -- Devis reçus
SELECT COUNT(*) FROM RESERVATION                                            -- Ce mois
  WHERE MONTH(DATE_RESERVATION) = MONTH(CURDATE())
    AND YEAR(DATE_RESERVATION)  = YEAR(CURDATE())
```

**Machine à statut d'une réservation (admin) :**

```
ajax_statut POST ──► whitelist ['EN ATTENTE','CONFIRMEE','ANNULEE','TERMINEE']
                        │
                        └── si CONFIRMEE et pas déjà de CONTRAT :
                              INSERT CONTRAT (EN ATTENTE, CURDATE())
                              INSERT notification ('client_contrat')
                              MailService::sendReservationConfirmed() ──► email client
```

### 2.4 Espace Client — 14 pages

| Page | Rôle | Écrit en base |
|------|------|---------------|
| `home.php` | Dashboard : 4 tuiles KPI (réservations / devis / photos / vidéos) + 5 dernières réservations | — (lecture) |
| `reservations.php` | Créer / annuler une réservation, laisser un témoignage (note + message) | `RESERVATION`, `RESERVATION_CATEGORIE`, `TEMOIGNAGE`, `notification` |
| `devis.php` | Formulaire de devis pré-rempli (champs verrouillés) + historique + téléchargement de la réponse | `notification` (lu) |
| `contrats.php` | **Accepter** un contrat (→ génère la facture) ou le **refuser** (→ annule la réservation) | `CONTRAT`, `FACTURE`, `RESERVATION`, `notification` |
| `factures.php` | Historique des factures, solde restant, barre de progression | `notification` (lu) |
| `generer_facture_pdf.php` | Génération et **téléchargement** du PDF de la facture (dompdf) | — (lecture) |
| `paiements.php` | Journal des paiements + totaux | `notification` (lu) |
| `mes_photos.php` | Index des photos livrées, groupées par réservation | `notification` (lu) |
| `mes_videos.php` | Index des vidéos livrées, groupées par réservation | `notification` (lu) |
| `galerie_reservation.php` | Galerie lightbox (photos + vidéos) d'une réservation, avec téléchargement | — (lecture) |
| `notifications.php` | Liste complète des notifications (version mobile) | — (lecture) |
| `parametres.php` | Modifier ses informations, son mot de passe, sa photo de profil | `AUTHENTIFICATION`, `CLIENT` |
| `logout.php` | Déconnexion (endpoint, pas une page) | — |
| `composante/*` | `sidebar.php`, `tolbar.php`, `tolbarDto.php`, `notificationDropdown.php` | — |

**Action clé — accepter un contrat :**

```
Client clique "Accepter"
   └─► contrats.php?action=accept&id_contrat=N
         1. Vérifie : SELECT … WHERE ct.ID_CONTRAT = ? AND r.ID_CLIENT = ?  (ownership)
         2. Vérifie : STATUS_CONTRAT === 'EN ATTENTE'                          (anti-rejeu)
         3. UPDATE CONTRAT SET STATUS_CONTRAT = 'CONFIRME'
         4. INSERT FACTURE (NUM_FACTURE = FAC-<année>-<COUNT+1>, 'NON PAYEE')
         5. INSERT notification (TYPE_NOTIF = 'client_facture')
         6. prg_redirect()
```

**Génération du PDF de facture** (`generer_facture_pdf.php`) :

```php
require_once __DIR__ . '/../../dompdf/autoload.inc.php';   // dompdf 3.1.5 vendorisé

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);              // HTML assemblé en chaîne PHP
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('Facture_' . $facture['NUM_FACTURE'] . '.pdf', ['Attachment' => true]);
exit();
```

Le PDF contient : coordonnées du studio (table `contact`), bloc client, informations
facture, lignes de détail (`RESERVATION_CATEGORIE ⋈ CATEGORIE`), totaux, barre de
progression du règlement, badge de statut recalculé à la volée.
**Aucun fichier n'est écrit sur le disque** — le PDF est streamé en téléchargement.

---

## 3. Stack technique

### Backend

| Technologie | Version | Rôle |
|-------------|---------|------|
| **PHP** | 8.0+ | Langage applicatif (`declare(strict_types=1)`) |
| **PDO MySQL** | — | Accès base de données (singleton `getPDO()`) |
| **Apache** (XAMPP) | — | Serveur web local / production |
| **PHPMailer** | 7.1.1 | Envoi d'emails SMTP (vendorisé dans `phpMailer/`) |
| **dompdf** | 3.1.5 | Génération de factures PDF (vendorisé dans `dompdf/`) |
| **Mailpit** (dev) | — | Serveur SMTP local de test (port 1025) |

### Frontend

| Technologie | Version | Rôle |
|-------------|---------|------|
| HTML5 | — | Structure |
| CSS3 (vanilla) | — | `css/mainStyle.css` (88 Ko), `css/dashboard.css` (47 Ko), `login/css/style.css` (21 Ko) |
| JavaScript (vanilla) | — | `script.js` (409 lignes) — aucune dépendance de build |
| ScrollReveal | 4.0.9 | Animations au scroll (CDN) |
| Toastify | — | Notifications (CDN) |
| Font Awesome | 6.4.0 / 6.5.0 | Icônes (CDN) |
| Chart.js | 4.4.0 | Graphique du dashboard admin (CDN) |
| FullCalendar | 6.1.11 | Vue calendrier admin (CDN) |
| Flatpickr | — | Sélecteur de date (CDN, réservation client) |

> **Aucun framework, aucun bundler, aucun `composer install`.** Les deux bibliothèques
> PHP sont vendorisées directement dans le dépôt (`dompdf/`, `phpMailer/`). Le projet se
> déploie tel quel sur n'importe quel hébergeur PHP.

### Extensions PHP requises

```
pdo_mysql   fileinfo   mbstring   openssl
```

---

## 4. Architecture du projet

### 4.1 Vue d'ensemble

```
NY_TIA_SARY/
│
├── 📄 Pages publiques (racine)
│   ├── index.php                 Accueil
│   ├── apropos.php               À propos
│   ├── service.php               Services
│   ├── portfolio.php             Portfolio
│   ├── blog.php                  Blog
│   ├── contact.php               Contact
│   ├── categorie.php             ⚡ Endpoint AJAX catégories (public)
│   ├── traitement_contact.php    🔄 Handler POST formulaire contact
│   ├── traitement_devis.php      🔄 Handler POST demande de devis
│   └── script.js                 Logique JS globale du site public
│
├── ⚙️ config/                     Configuration (⚠️ à modifier en production)
│   ├── database.php              Connexion PDO — singleton getPDO()
│   ├── mail.php                  Configuration SMTP PHPMailer
│   └── site.php                  Auto-détection de l'URL de base du site
│
├── 🔐 authentification/           Handlers d'authentification (POST)
│   ├── auth.php                  Connexion + routage par rôle
│   └── registre.php              Inscription (transaction 3 tables)
│
├── 🔑 login/                     Pages d'authentification
│   ├── login.php                 Formulaire de connexion
│   ├── register.php              Formulaire d'inscription
│   ├── mdpOublier.php            Assistant de réinitialisation (4 étapes)
│   └── css/style.css             Styles dédiés
│
├── 🧰 util/                       Bibliothèques internes (le « cœur logique »)
│   ├── auth_guard.php            Middleware requireAdmin() / requireClient()
│   ├── redirectionpage.php       redirectionClient() + isSafeRedirect()
│   ├── prg_helper.php            Pattern Post/Redirect/Get + Toasts
│   ├── mailService.php           Classe MailService (8 méthodes d'envoi)
│   ├── file_upload.php           Upload sécurisé (10 Mo, MIME réel)
│   ├── large_upload.php          Upload vidéo (2 Go) / image (50 Mo)
│   └── delete_file.php           Suppression sécurisée (anti path traversal)
│
├── 📊 espace/                     Espaces authentifiés
│   ├── admin/                    15 pages + 4 composants
│   ├── client/                   14 pages + 4 composants
│   └── composante/               Modales de confirmation de déconnexion
│
├── 🧩 composante/                 Composants partagés du site public
│   ├── header.php                Navigation principale
│   ├── footer.php                Pied de page + chargement JS + lightbox
│   ├── csslink.php               Chargement CSS (Font Awesome, Toastify)
│   └── devis_visiteur.php        Modal de demande de devis
│
├── 🔌 api/                        Endpoints JSON
│   └── categories.php            GET ?id_prestation=N (session requise)
│
├── 🎨 css/                        Styles globaux
├── 📁 assets/                    Ressources statiques
│   ├── images/  videos/  icones/
│   └── uploads/                  ⬇️ Fichiers utilisateurs (ignoré par Git)
│       ├── avatars/  blog/  devis_reponses/  medias/
│       └── partenaires/  pieces_joint/
│
├── 🗄️ database/
│   └── ny_tia_sary_db.sql        Dump complet (814 lignes, schéma + démo)
│
├── dompdf/                       Bibliothèque PDF (vendorisée)
├── phpMailer/                    Bibliothèque email (vendorisée)
└── graphify-out/                 Graphe de connaissances du projet
```

### 4.2 Pattern d'architecture

L'application suit un pattern **« pages + includes »** classique de PHP :

```
Page (.php)
  ├── require_once  config/database.php   →  connexion PDO
  ├── require_once  util/auth_guard.php   →  requireAdmin() / requireClient()
  ├── require_once  util/prg_helper.php   →  gestion PRG
  ├── require_once  espace/admin/composante/tolbarDto.php  →  fournit $pdo + identité
  ├── include       espace/admin/composante/sidebar.php
  ├── include       espace/admin/composante/tolbar.php
  ├── ... traitement métier (PDO)
  └── ... HTML
```

**Aucun autoloader, aucune architecture MVC stricte.** Les requêtes SQL sont écrites
directement dans les pages, ce qui rend le code très lisible et facile à déboguer.

> ⚠️ Dans les dashboards, **`$pdo` n'est jamais instancié dans la page** : il est injecté
> par `composante/tolbarDto.php`, qui fournit aussi `$adminId` / `$clientId`, le nom,
> la photo et le compteur de notifications non lues.

### 4.3 Les couches fonctionnelles

| Couche | Emplacement | Responsabilité |
|--------|-------------|----------------|
| **Présentation** | Pages + `css/` + `script.js` | HTML, CSS, interactions |
| **Contrôle** | Pages métier + `traitement_*.php` + `authentification/` | Traitement POST, validation, CRUD |
| **Protection** | `util/auth_guard.php`, `util/redirectionpage.php`, `util/prg_helper.php` | Autorisation, redirections sûres, PRG |
| **Services** | `util/mailService.php`, `util/file_upload.php`, `util/large_upload.php`, `util/delete_file.php` | Email, fichiers |
| **Données** | `config/database.php` + `database/ny_tia_sary_db.sql` | PDO singleton + schéma |

---

## 5. Modèle de données

Base : **`ny_tia_sary_db`** — 20 tables, 17 contraintes d'intégrité référentielle.

### 5.1 Schéma relationnel

```
                    ┌──────────────────┐
                    │  authentification │  (ID_AUTH, EMAIL_AUTH, MDP_AUTH, ROLE_AUTH)
                    └────────┬─────────┘
                             │ 1,1
              ┌──────────────┼──────────────┐
              │ 1,1                          │ 0,1
      ┌───────▼────────┐            ┌────────▼──────┐
      │     client     │            │   secutite    │ (question de sécurité)
      │ (ID_CLIENT,    │            └───────────────┘
      │  ID_AUTH,      │
      │  NOM, PRENOM,  │
      │  TEL, TYPE,    │
      │  PHOTO)        │
      └───────┬────────┘
              │ 1,n
   ┌──────────┼───────────────────────────────┐
   │ 1,n      │ 1,n                          │
┌──▼───────┐  │                        ┌──────▼────────┐
│temoignage│  │                        │  notification │
└────┬─────┘  │                        └──────┬────────┘
     │ 1,n    │ 1,n                               │ n,0..1
     └────────┤                                   └──► client
        ┌─────▼─────────┐   n,n    ┌───────────────────┐
        │  reservation  ├──────────►│ reservation_categorie│
        │ (ID_RES,      │           │ (ID_RES, ID_CAT,    │
        │  ID_CLIENT,   │           │  PRIX)              │
        │  DATE, HEURE, │           └─────────┬─────────┘
        │  LIEU, COMME, │                     │ n,1
        │  STATUS,      │            ┌────────▼────────┐
        │  ID_PREST.)   │            │    categorie    │ (ID_CAT, LIB, TARIF)
        └─┬────┬───┬────┘            └────────▲────────┘
          │1,n │1,n │1,n                      │ n,1
          │    │    │                 ┌───────┴────────┐
   ┌──────▼┐ ┌▼───────▼──┐  ┌──────────▼─┐   ┌──────────────┐
   │ media │ │  contrat  │  │reservation │   │  prestations │
   └───────┘ └─────┬─────┘  │  _categorie│   │ (ID_PREST.,  │
                    │ 1,1    └────────────┘   │  LIB_PREST.) │
            ┌───────▼────────┐                └──────┬───────┘
            │    facture     │◄──── n,n ────┐       │ n,1
            │ (ID_FACT,      │              │  ┌────▼──────────────┐
            │  NUM, STATUS,  │              │  │  devis_categories │
            │  MONTANT, DATE)│              │  │ (ID_DEVIS,        │
            └───────┬────────┘              │  │  ID_CATEGORIE)    │
                    │ 1,n                    │  └────┬──────────────┘
            ┌───────▼────────┐              │       │ n,1
            │    paiement    │              └───────┘
            └────────────────┘              ┌────────▼────────┐
                                            │      devis      │
                ┌───────────────┐           │ (ID, NOM, PREN,  │
                │   partenaire  │           │  EMAIL, TEL,     │  ← 0,n
                │ (logo, desc,  │           │  TYPE_VISITEUR,  │     │
                │  lien)        │           │  BUGET, DATE_SOUH,│
                └───────────────┘           │  DESCRIPTION,    │
                                            │  ID_PRESTATION,  │
   ┌───────────────┐  n,1    ┌────────────┐  │  FICHIER_REPONSE)│
   │  type_blog   ├─────────►│    blog    │  └───┬─────────┬──┘
   │ (LIB_TYPE)   │          │(titre,     │      │ 1,n     │ 1,n
   └───────────────┘          │ contenu,   │  ┌───▼──────┐  │
                              │ img, date, │  │  pieces  │  │
                              │ statut)    │  │_jointes  │  │
                              └────────────┘  └──────────┘  │
                                                             │
   ┌───────────────┐                                          │
   │    contact    │  (coordonnées du studio : adresse, tel,  │
   └───────────────┘   WhatsApp, Messenger, email, horaires)
```

### 5.2 Description des tables

| Table | Rôle | Colonnes clés |
|-------|------|---------------|
| `authentification` | Comptes + rôle | `ID_AUTH`, `EMAIL_AUTH` (unique), `MDP_AUTH` (bcrypt), `ROLE_AUTH` ENUM('ADMIN','CLIENT') |
| `client` | Profil client lié au compte | `ID_CLIENT`, `ID_AUTH`→auth, `NOM_CLIENT`, `PRENOM_CLIENT`, `TEL_CLIENT`, `TYPE_CLIENT`, `PHOTO_CLIENT` |
| `secutite` | Question de sécurité | `ID_SECURITE`, `ID_AUTH`→auth, `QUESTION`, `REPONSE` (bcrypt) |
| `prestations` | Catalogue des 7 prestations | `ID_PRESTATION`, `LIB_PRESTATION` |
| `categorie` | Formules/formats avec tarif | `ID_CATEGORIE`, `LIB_CATEGORIE`, `TARIF_CATEGORIE`, `ID_PRESTATION`→prestations |
| `reservation` | Rendez-vous du client | `ID_RESERVATION`, `ID_CLIENT`, `DATE_RESERVATION`, `HEURE_RESERVATION`, `LIEU_RESERVATION`, `COMME_RESERVATION`, `STATUS_RESERVATION` ENUM, `ID_PRESTATION` |
| `reservation_categorie` | Détail chiffré d'une réservation | `ID_RESERVATION`, `ID_CATEGORIE`, `PRIX` |
| `contrat` | Contrat généré après confirmation | `ID_CONTRAT`, `ID_RESERVATION`, `STATUS_CONTRAT`, `DATE_CONTRAT` |
| `facture` | Facture rattachée à un contrat | `ID_FACTURE`, `ID_CONTRAT`, `NUM_FACTURE`, `STATUS_FACTURE`, `MONTANT_FACTURE`, `DATE_FACTURE` |
| `paiement` | Règlements (N par facture) | `ID_PAIEMENT`, `ID_FACTURE`, `DATE_PAIEMENT`, `MONTANT_PAIEMENT` |
| `devis` | Demande de devis (public ou client connecté) | `ID`, `NOM`, `PRENOMS`, `EMAIL`, `TELEPHONE`, `TYPE_VISITEUR`, `BUGET_ESTIMATIF`, `DATE_SOUHAITE`, `DESCRIPTION`, `ID_PRESTATION`, `FICHIER_REPONSE` |
| `devis_categories` | Formules souhaitées pour un devis | `ID_DEVIS`→devis, `ID_CATEGORIE`→categorie |
| `pieces_jointes` | Fichier joint à un devis | `ID_PIECE`, `ID`→devis, `PATH_PIECE` (`'aucun'` si absent) |
| `media` | Livraison photo/vidéo au client | `ID_MEDIA`, `ID_RESERVATION`, `PATH_MEDIA`, `TYPE_MEDIA` |
| `notification` | Notifications in-app | `ID_NOTIF`, `TYPE_NOTIF`, `ID_REF_NOTIF`, `TITRE_NOTIF`, `MESS_NOTIF`, `LU_NOTIF`, `SUP_NOTIF`, `DATE_NOTIF`, `ID_CLIENT` (**nul** = notification admin) |
| `blog` / `type_blog` | Articles et catégories du blog | `ID_BLOG`, `ID_TYPE_BLOG`, `TITRE_BLOG`, `CONTENU`, `IMAGE_COURVERTURE`, `DATE_PUBLICATION`, `DATE_MODIFICATION`, `STATUS_BLOG` |
| `temoignage` | Avis clients (note + message) | `ID_TEMOIGNAGE`, `ID_RESERVATION`, `MESS_RESERVATION`, `NOTE` |
| `partenaire` | Logos partenaires du site | `ID_PARTENAIRE`, `PATH_LOGO`, `DESCRIPTIONS`, `LIEN_PARTENAIRE` |
| `contact` | Coordonnées du studio (ligne unique) | `ADRESSE_CONTACT`, `TEL_CONTACT`, `WHATSAPP_LIEN`, `MESSENGER_LIEN`, `EMAIL_CONTACT`, `HORAIRE_CONTACT` |

### 5.3 Les 7 prestations

| ID | Libellé |
|----|---------|
| 1 | Photographie Corporate |
| 2 | Photographie Évènementielle |
| 3 | Mariage |
| 4 | Mode |
| 5 | Photographie produits |
| 6 | Production Vidéo |
| 7 | Drone |

### 5.4 Machine à états

**Réservation** — `reservation.STATUS_RESERVATION`

```
EN ATTENTE ──► CONFIRMEE ──► TERMINEE
      │              │
      └──────► ANNULEE ◄──────┘
```

**Facture** — recalculée à chaque ajout/suppression de paiement

```
payé >= montant        ──► PAYEE
0 < payé < montant     ──► PARTIELLEMENT PAYEE
payé = 0               ──► NON PAYEE
```

**Rôle** — `authentification.ROLE_AUTH`

```
   inscription
       │
       ▼
    CLIENT ──(promotion manuelle en BDD)──► ADMIN
```

> 📌 `ID_CLIENT IS NULL` dans la table `notification` distingue les notifications
> destinées à l'**admin** de celles destinées à un **client**.

---

## 6. Parcours métier

### 6.1 Demande de devis (public)

```
Visiteur                    index.php                    traitement_devis.php              Admin
    │                            │                              │                              │
    │── clic "Demande devis" ───►│                              │                              │
    │                            │── modal devis ──────────────►│                              │
    │                            │   GET categorie.php         │                              │
    │                            │   (catégories de la prest.) │                              │
    │                            │                              │                              │
    │── POST multipart ──────────────────────────────────────►  │                              │
    │                            │              1. Validation (nom, email, tél, type, desc.)   │
    │                            │              2. Validation pièce jointe (≤ 10 Mo,         │
    │                            │                 pdf/jpg/jpeg/png/webp/docx)                │
    │                            │              3. BEGIN TRANSACTION                           │
    │                            │                 INSERT devis                                │
    │                            │                 INSERT devis_categories (N lignes)         │
    │                            │                 move_uploaded_file → pieces_joint/          │
    │                            │                 INSERT pieces_jointes ('aucun' si vide)    │
    │                            │                 INSERT notification (type='devis')        │
    │                            │              4. COMMIT                                      │
    │                            │              5. MailService::sendDevisNew() ──────────────►│
    │◄── redirect index.php?devis=success&message=… ───────────│                              │
```

> **Note :** la réponse au devis est produite par l'admin dans
> `espace/admin/devis.php` → `action=envoyer_reponse`. Selon que le demandeur ait
> un compte ou non, l'email contient soit un **lien** vers son espace client
> (`sendDevisResponseToClient`) soit le **PDF en pièce jointe**
> (`sendDevisResponseWithAttachment`).

### 6.2 Réservation → Contrat → Facture → Paiement

```
CLIENT                                    ADMIN
  │                                         │
  │── crée une réservation ────────────────►│  INSERT reservation
  │   (statut = EN ATTENTE)                 │  INSERT reservation_categorie
  │                                         │  INSERT notification (admin)
  │                                         │── MailService::sendReservationNew() ──► email admin
  │◄── notification in-app + email ─────────│
  │                                         │
  │                                         │── ajax_statut POST → CONFIRMEE
  │                                         │   (ou contrats.php → action=create)
  │                                         │   INSERT contrat
  │                                         │   INSERT notification ('client_contrat')
  │                                         │── MailService::sendReservationConfirmed() ──► email client
  │◄── notification « contrat prêt » ───────│
  │                                         │
  │── accepte le contrat ──────────────────►│  (facture générée côté client, cf. §2.4)
  │                                         │
  │── télécharge facture PDF ───────────────│
  │   (generer_facture_pdf.php / dompdf)    │
  │                                         │
  │                                         │── factures.php → action=add_payment
  │                                         │   INSERT paiement
  │                                         │   recalcule STATUS_FACTURE
  │                                         │   INSERT notification ('client_paiement')
  │◄── notification « paiement reçu » ──────│
```

### 6.3 Livraison des médias

```
Admin                          Fichiers
  │── upload photo/vidéo ─────►  assets/uploads/medias/<nom_aléatoire>.<ext>
  │   (large_upload.php)        (2 Go vidéo / 50 Mo image)
  │── INSERT media ──────────►  table `media` (TYPE_MEDIA = 'IMAGE' | 'VIDEO')
  │── INSERT notification ───►  table `notification` (TYPE_NOTIF = 'client_media')
  │
  │── le client voit ses médias ──────►  espace/client/mes_photos.php
  │                                    espace/client/mes_videos.php
  │                                    → galerie_reservation.php?id=…&type=…
```

### 6.4 Les 8 emails du système

Tous transactionnels, envoyés via `MailService` (PHPMailer). **Un échec d'email ne fait
jamais échouer l'opération métier** — les notifications in-app (`table notification`)
restent le canal de référence.

| # | Méthode | Destinataire | Déclencheur |
|---|---------|--------------|-------------|
| 1 | `sendReservationNew()` | Admin | Nouvelle réservation |
| 2 | `sendReservationConfirmed()` | Client | Réservation confirmée + contrat |
| 3 | `sendDevisNew()` | Admin | Nouvelle demande de devis (avec pièce jointe éventuelle) |
| 4 | `sendDevisResponseToClient()` | Client | Devis prêt (lien vers l'espace client) |
| 5 | `sendDevisResponseWithAttachment()` | Visiteur | Devis prêt (PDF en pièce jointe) |
| 6 | `sendContactMessage()` | Admin | Formulaire de contact (avec `Reply-To` vers le visiteur) |
| 7 | `send()` | Libre | Envoi générique |
| 8 | `sendWithAttachments()` | Libre | Envoi générique avec pièces jointes |

---

## 7. Navigation

### 7.1 Site public (`composante/header.php`)

| # | Libellé | Icône | Cible |
|---|---------|-------|-------|
| 1 | Accueil | `fa-home` | `index.php` |
| 2 | À Propos | `fa-info-circle` | `apropos.php` |
| 3 | Services | `fa-camera-retro` | `service.php` |
| 4 | Portfolio | `fa-images` | `portfolio.php` |
| 5 | Blog | `fa-newspaper` | `blog.php` |
| 6 | Contact | `fa-phone` | `contact.php` |
| 7 | Se connecter | `fa-sign-in-alt` | `login/login.php` |

### 7.2 Espace Admin (`espace/admin/composante/sidebar.php`)

| # | Section | Libellé | Icône | Cible |
|---|---------|---------|-------|-------|
| — | *Tableau de bord* | | | |
| 1 | | **Dashboard** | `fa-th-large` | `home.php` |
| — | *Gestion* | | | |
| 2 | | **Réservations** | `fa-calendar-check` | `reservations.php` |
| 3 | | ↳ Contrats | `fa-file-signature` | `contrats.php` |
| 4 | | ↳ Factures | `fa-file-invoice` | `factures.php` |
| 5 | | ↳ Paiements | `fa-coins` | `paiements.php` |
| 6 | | Clients | `fa-users` | `clients.php` |
| 7 | | Devis | `fa-file-alt` | `devis.php` |
| 8 | | Calendrier | `fa-calendar` | `calendrier.php` |
| — | *Contenu* | | | |
| 9 | | Blog | `fa-newspaper` | `blog.php` |
| 10 | | Témoignages | `fa-star` | `temoignages.php` |
| 11 | | Médias livrés | `fa-photo-video` | `medias.php` |
| 12 | | Prestations | `fa-concierge-bell` | `prestations.php` |
| 13 | | ↳ Catégories & tarifs | `fa-tags` | `categories.php` |
| 14 | *Compte* | Paramètres | `fa-cogs` | `parametres.php` |
| 15 | | Déconnexion | `fa-sign-out-alt` | modale → `logout.php?action=deconnexion` |

> 📌 `notifications.php` **n'apparaît pas** dans le menu : c'est la version pleine page
> du menu déroulant, atteinte via la cloche de la barre supérieure (viewport ≤ 1024 px).
> Les 4 items en retrait (Contrats, Factures, Paiements, Catégories) sont un
> **simple habillage CSS**, sans parent repliable.

### 7.3 Espace Client (`espace/client/composante/sidebar.php`)

| # | Section | Libellé | Icône | Cible |
|---|---------|---------|-------|-------|
| — | *Navigation* | | | |
| 1 | | Dashboard | `fa-th-large` | `home.php` |
| 2 | | Mes Réservations | `fa-calendar-alt` | `reservations.php` |
| 3 | | Demande de Devis | `fa-file-invoice` | `devis.php` |
| — | *Documents* | | | |
| 4 | | Mes Contrats | `fa-file-signature` | `contrats.php` |
| 5 | | Mes Factures | `fa-file-invoice-dollar` | `factures.php` |
| 6 | | ↳ Mes Paiements | `fa-coins` | `paiements.php` |
| — | *Media* | | | |
| 7 | | Mes Photos | `fa-images` | `mes_photos.php` |
| 8 | | Vidéos | `fa-video` | `mes_videos.php` |
| — | *Compte* | | | |
| 9 | | Paramètres | `fa-cogs` | `parametres.php` |
| 10 | | Déconnexion | `fa-sign-out-alt` | modale → `logout.php?action=deconnexion` |

### 7.4 Chaîne d'inclusion commune aux deux dashboards

Les 15 pages admin et 11 pages client partagent exactement la même structure :

```php
require_once __DIR__.'/../../util/auth_guard.php';
requireAdmin();                                     // ou requireClient()
require_once __DIR__.'/composante/tolbarDto.php';   // fournit $pdo, $id, $nom, $photo, $notifCount
$titre = 'Titre de la page';                        // obligatoire : affiché dans la topbar
include __DIR__.'/composante/sidebar.php';          // → include modalDeconexion*.php
include __DIR__.'/composante/tolbar.php';           // → require notificationDropdown.php
```

> `$pdo` n'est **jamais** instancié directement dans les pages : il est injecté par
> `composante/tolbarDto.php`, qui fournit aussi l'identité et le compteur de
> notifications non lues. Le titre de la topbar est également injecté par la page
> via `$titre`.

---

## 8. Installation locale (XAMPP)

### 8.1 Prérequis

- **XAMPP** avec **PHP 8.0+** ([téléchargement](https://www.apachefriends.org/fr/))
- Extension `fileinfo` activée dans `php.ini` (`extension=fileinfo`)

### 8.2 Étape 1 — Récupérer le projet

```bash
# Option A — cloner le dépôt
cd C:\xampp\htdocs
git clone https://github.com/Zaraniaina/NY_TIA_SARY.git

# Option B — copier manuellement le dossier dans C:\xampp\htdocs\NY_TIA_SARY
```

### 8.3 Étape 2 — Démarrer les services

1. Ouvrir le **Panneau de contrôle XAMPP**
2. Démarrer **Apache** ✅
3. Démarrer **MySQL** ✅

### 8.4 Étape 3 — Créer la base de données

> ⚠️ Le fichier `database/ny_tia_sary_db.sql` **ne contient pas** de `CREATE DATABASE` /
> `USE`. Il faut donc créer la base au préalable.

**Méthode 1 — phpMyAdmin (http://localhost/phpmyadmin)**

1. Onglet **Bases de données** → **Nouvelle base de données**
2. Nom : `ny_tia_sary_db`
3. Interclassement : **`utf8mb4_general_ci`**
4. **Créer**
5. Sélectionner `ny_tia_sary_db` → onglet **Importer**
6. Choisir `database/ny_tia_sary_db.sql` → **Exécuter**

**Méthode 2 — ligne de commande**

```bash
mysql -u root -e "CREATE DATABASE ny_tia_sary_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root ny_tia_sary_db < C:\xampp\htdocs\NY_TIA_SARY\database\ny_tia_sary_db.sql
```

**Résultat attendu :** 20 tables créées + ~20 enregistrements de démonstration.

### 8.5 Étape 4 — Vérifier la configuration

`config/database.php` est **déjà configuré pour XAMPP** — aucune modification nécessaire :

```php
$host    = 'localhost';
$db      = 'ny_tia_sary_db';
$user    = 'root';
$pass    = '';            // XAMPP n'a pas de mot de passe root par défaut
$charset = 'utf8mb4';
```

`config/site.php` **s'auto-configure** : il reconstruit l'URL de base à partir de
`$_SERVER['HTTP_HOST']` et `$_SERVER['DOCUMENT_ROOT']`. Aucune action requise.

### 8.6 Étape 5 — Configurer les emails (recommandé)

Le projet est configuré pour **[Mailpit](https://mailpit.axllent.org/)**, un serveur SMTP
local qui intercepte les emails sans les envoyer. Sans lui, les emails échouent
silencieusement (les autres opérations continuent).

```bash
# Téléchargement de Mailpit (Windows)
winget install axllent.mailpit
mailpit
```

> Mailpit écoute sur `http://localhost:8025` : tous les emails envoyés par l'application
> (y compris ceux adressés à `nytiasary@gmail.com`) y sont consultables.

Alternative : modifier `config/mail.php` pour pointer vers le SMTP de votre fournisseur.

### 8.7 Étape 6 — Créer le compte administrateur

> **Important :** l'inscription publique crée **toujours** un compte `CLIENT`.
> Il n'existe pas d'auto-promotion admin (délibéré, pour des raisons de sécurité).

1. Ouvrir `http://localhost/NY_TIA_SARY/login/register.php` et créer un compte
2. Passer le rôle en `ADMIN` :
   ```sql
   UPDATE authentification SET ROLE_AUTH = 'ADMIN'
   WHERE EMAIL_AUTH = 'votre@email.com';
   ```
   ou via phpMyAdmin → table `authentification` → champ `ROLE_AUTH` → `ADMIN`
3. Se connecter sur `http://localhost/NY_TIA_SARY/login/login.php`

### 8.8 Étape 7 — Régler les limites d'upload

Pour les **uploads vidéo** (jusqu'à 2 Go), augmenter les limites dans
`C:\xampp\php\php.ini` :

```ini
file_uploads        = On
upload_max_filesize = 2048M
post_max_size       = 2100M
max_execution_time  = 600
max_input_time      = 600
memory_limit        = 512M
```

Puis **redémarrer Apache** dans le panneau XAMPP.

### 8.9 URLs de vérification

| Page | URL |
|------|-----|
| Site public | http://localhost/NY_TIA_SARY/ |
| Connexion | http://localhost/NY_TIA_SARY/login/login.php |
| Inscription | http://localhost/NY_TIA_SARY/login/register.php |
| Mot de passe oublié | http://localhost/NY_TIA_SARY/login/mdpOublier.php |
| Espace Admin | http://localhost/NY_TIA_SARY/espace/admin/home.php |
| Espace Client | http://localhost/NY_TIA_SARY/espace/client/home.php |
| Boîte Mailpit | http://localhost:8025 |

---

## 9. Déploiement en production

### 9.1 Prérequis serveur

- **PHP 8.0+** avec `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`
- **MySQL 5.7+** ou **MariaDB 10.4+**
- **Apache** avec `mod_rewrite` (ou Nginx)
- **HTTPS** (obligatoire : les mots de passe transitent en clair sinon)
- Accès SSH ou FTP

### 9.2 Étape 1 — Transférer les fichiers

```bash
# Serveur
cd /var/www/html
git clone https://github.com/Zaraniaina/NY_TIA_SARY.git ny_tia_sary
chown -R www-data:www-data ny_tia_sary
```

> ⚠️ Si FTP : ne pas transférer `.git/` ni `graphify-out/` (voir `.gitignore`).

### 9.3 Étape 2 — Créer la base de données de production

```sql
CREATE DATABASE ny_tia_sary_prod
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE USER 'ny_user'@'localhost' IDENTIFIED BY '<MOT_DE_PASSE_FORT>';

GRANT SELECT, INSERT, UPDATE, DELETE
  ON ny_tia_sary_prod.*
  TO 'ny_user'@'localhost';

FLUSH PRIVILEGES;
```

```bash
mysql -u ny_user -p ny_tia_sary_prod < database/ny_tia_sary_db.sql
```

> 💡 **Recommandé :** nettoyer les données de démonstration avant la mise en production :
> ```sql
> DELETE FROM reservation WHERE ID_RESERVATION > 3;
> DELETE FROM notification;
> DELETE FROM authentification WHERE ID_AUTH > 1;
> ```
> Le compte `ID_AUTH = 1` (`admin@gmail.com`, rôle `ADMIN`) est conservé.

### 9.4 Étape 3 — ⚙️ Adapter `config/database.php`

> 🔴 **Fichier le plus critique.** Les valeurs XAMPP ne fonctionneront **pas** en production.

```php
function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $host    = 'localhost';           // ou l'IP du serveur de BDD
        $db      = 'ny_tia_sary_prod';    // ← nom de la BDD de production
        $user    = 'ny_user';             // ← utilisateur MySQL
        $pass    = '<MOT_DE_PASSE_FORT>'; // ← JAMAIS vide en production
        $charset = 'utf8mb4';
        // … inchangé
    }
    return $pdo;
}
```

### 9.5 Étape 4 — ⚙️ Adapter `config/mail.php`

Remplacer la configuration Mailpit (dev) par les vrais paramètres SMTP :

```php
return [
    "host"       => "smtp.votredomaine.mg",
    "port"       => 587,                        // 587 = STARTTLS, 465 = SSL
    "username"   => "nytiasary@votredomaine.mg",
    "password"   => "<MOT_DE_PASSE_SMTP>",
    "encryption" => "tls",                     // 'tls' (587) ou 'ssl' (465)
    "from_email" => "nytiasary@votredomaine.mg",
    "from_name"  => "NY TIA SARY",
];
```

**Si vous utilisez Gmail :** activer la validation en deux étapes → générer un
**« App Password »** dans Sécurité du compte Google → l'utiliser comme `password`
(pas votre mot de passe Gmail).

**Si `encryption` vaut `null` :** `MailService` désactive explicitement STARTTLS
opportuniste et `SMTPAutoTLS` — c'est le mode Mailpit local.

### 9.6 Étape 5 — Permissions des dossiers d'upload

```bash
chown -R www-data:www-data /var/www/html/ny_tia_sary/assets/
chmod -R 755 /var/www/html/ny_tia_sary/assets/uploads/
find /var/www/html/ny_tia_sary/assets/uploads/ -type d -exec chmod 755 {} \;
find /var/www/html/ny_tia_sary/assets/uploads/ -type f -exec chmod 644 {} \;
```

> ⚠️ Le dossier `assets/uploads/` doit être **accessible en écriture** par Apache
> (photos clients, vidéos et PDF y sont déposés).
> ⚠️ **Éviter `777` à tout prix.** Sur hébergement mutualisé, `775` peut être nécessaire.

### 9.7 Étape 6 — Créer le `.htaccess` racine

```apache
# ═══ Protection des fichiers de configuration ═══
<FilesMatch "^(database|mail|site)\.php$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

# ═══ Masquer les erreurs PHP ═══
<IfModule mod_php.c>
    php_flag display_errors Off
    php_flag log_errors On
</IfModule>

# ═══ Pas d'index de répertoire ═══
Options -Indexes

# ═══ Forcer HTTPS (décommenter une fois le SSL actif) ═══
# RewriteEngine On
# RewriteCond %{HTTPS} !=on
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 9.8 Étape 7 — Réglages PHP de production

```ini
; php.ini
display_errors = Off
log_errors = On
error_reporting = E_ALL

; uploads
file_uploads = On
upload_max_filesize = 2048M
post_max_size = 2100M
max_execution_time = 600
max_input_time = 600
memory_limit = 512M
```

> 🔐 Sur un hébergement mutualisé, ces valeurs se règlent souvent via un `.user.ini`
> à la racine du projet :
> ```ini
> upload_max_filesize = 2048M
> post_max_size = 2100M
> max_execution_time = 600
> ```

### 9.9 Étape 8 — Créer le `.gitignore` de production

Si vous versionnez le projet, `.gitignore` exclut déjà `assets/uploads/`.
Si vous déployez par archive, il faut **recréer** les 6 sous-dossiers d'uploads :

```bash
cd /var/www/html/ny_tia_sary/assets
mkdir -p uploads/{avatars,blog,devis_reponses,medias,partenaires,pieces_joint}
chown -R www-data:www-data uploads
```

### 9.10 Récapitulatif — fichiers à modifier en production

| Fichier / action | Ce qui change | Criticité |
|------------------|---------------|-----------|
| `config/database.php` | Hôte, nom BDD, utilisateur, mot de passe | 🔴 Critique |
| `config/mail.php` | Hôte SMTP, port, username, password, encryption | 🔴 Critique |
| `.htaccess` *(à créer)* | Blocage config/, `display_errors Off`, HTTPS | 🔴 Critique |
| `traitement_devis.php:299` | Retirer le message `DEBUG:` | 🔴 Critique |
| `php.ini` / `.user.ini` | Limites d'upload vidéo (2 Go) | 🟠 Important |
| `assets/uploads/` | `chown www-data` + `chmod 755` + 6 sous-dossiers | 🟠 Important |
| `authentification.ROLE_AUTH` | Promouvoir le compte admin | 🟠 Important |
| Certificat SSL | Activer HTTPS + redirection `.htaccess` | 🟠 Important |
| Nettoyage BDD | Supprimer les données de démonstration | 🟡 Recommandé |

---

## 10. Configuration

### 10.1 `config/database.php`

Singleton PDO. Connexion paresseuse (lazy) — la connexion n'est ouverte qu'au premier
appel de `getPDO()`.

```php
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Exceptions sur erreur
    PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,       // Tableau associatif
    PDO::ATTR_EMULATE_PREPARES    => false,                  // Vrais prepared statements
];
```

> 🔒 `ATTR_EMULATE_PREPARES => false` est **essentiel** : les vraies requêtes
> préparées sont envoyées au serveur MySQL, rendant l'injection SQL impossible.

**Appeler partout :**
```php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();
```

### 10.2 `config/site.php`

Auto-détection de l'URL de base — **ne nécessite aucune configuration** :

```php
$scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
$projectDir = realpath(__DIR__ . '/..');
$docRoot    = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$webBase    = $docRoot ? str_replace('\\', '/', str_replace($docRoot, '', $projectDir)) : '';

return ['url' => $scheme . '://' . $host . $webBase . '/'];
```

> 📌 `config/site.php` et `config/mail.php` **retournent un tableau**. Il faut utiliser
> `require` et **jamais** `require_once` (qui renvoie `true` au 2ᵉ appel) — c'est
> explicitement commenté dans `mailService.php`.

### 10.3 `config/mail.php`

| Clé | Description | Valeur dev par défaut |
|-----|-------------|----------------------|
| `host` | Serveur SMTP | `localhost` |
| `port` | Port SMTP | `1025` (Mailpit) |
| `username` | Login SMTP | `''` (vide = pas d'auth) |
| `password` | Mot de passe SMTP | `''` |
| `encryption` | `tls` / `ssl` / `null` | `null` |
| `from_email` | Adresse expéditeur | `nytiasary@gmail.com` |
| `from_name` | Nom expéditeur | `Ny tia sary` |

### 10.4 `util/` — Référence des fonctions

#### `auth_guard.php`

| Fonction | Signature | Rôle |
|----------|-----------|------|
| `getLoginUrl()` | `: string` | Construit l'URL absolue de la page de login (compatible RFC 7231) |
| `requireAdmin()` | `: void` | Exige `$_SESSION['admin_id']`, sinon redirige vers le login |
| `requireClient()` | `: void` | Exige `$_SESSION['client_id']`, sinon redirige vers le login |
| `getInitiales($nom, $prenom = '')` | `: string` | Initiales d'un nom complet (« Jean Dupont » → « JD ») |
| `clearSessionAndCache()` | `: void` | Vide + détruit la session et envoie des headers anti-cache |

#### `redirectionpage.php`

| Fonction | Signature | Rôle |
|----------|-----------|------|
| `redirectionClient($url)` | `: void` | `header('Location: ...')` + `exit` |
| `isSafeRedirect($url)` | `: bool` | Empêche les **open redirects** (URL locale uniquement) |

#### `prg_helper.php` — Pattern Post/Redirect/Get

| Fonction | Rôle |
|----------|------|
| `prg_set_message($type, $message)` | Stocke un message flash en session |
| `prg_get_messages()` | Récupère **et supprime** les messages flash |
| `prg_redirect($url = '')` | Redirige (par défaut : URL courante sans query string) |
| `prg_render_toasts($messages)` | Génère le JS Toastify (JSON échappé contre l'injection) |
| `prg_handle_post($handler, $ok, $err, $url)` | Version tout-en-un : exécute, capture les exceptions, redirige |

**Usage :**
```php
require_once __DIR__ . '/util/prg_helper.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // … traitement
        prg_set_message('success', 'Enregistré !');
    } catch (Throwable $e) {
        error_log($e->getMessage());
        prg_set_message('error', 'Une erreur est survenue.');
    }
    prg_redirect();
}
$messages = prg_get_messages();
```

Types de messages : `success` (vert `#377d49`), `error` (rouge `#d93d3d`),
`warning` (orange `#f39c12`), `info` (bleu `#2980b9`).

#### `file_upload.php` — Upload standard

| Constante / Fonction | Valeur / Rôle |
|----------------------|---------------|
| `UPLOAD_BASE_DIR` | `assets/uploads/` |
| `MAX_FILE_SIZE` | **10 Mo** |
| `ALLOWED_TYPES['image']` | `image/jpeg`, `image/png`, `image/webp`, `image/gif` |
| `ALLOWED_TYPES['document']` | `application/pdf`, `application/msword`, `…wordprocessingml.document` |
| `uploadFile($file, $subDir, $category)` | Upload un fichier → `{success, path, error}` |
| `uploadMultipleFiles($files, $subDir, $category)` | Upload multiple |
| `deleteUploadedFile($relativePath)` | Délègue à `delete_file.php` |
| `getAllowedMimes($category)` | MIME autorisés pour une catégorie |
| `getExtensionFromMime($mime)` | MIME → extension (whitelist, jamais le nom d'origine) |
| `uploadErrorMessage($code)` | Message lisible pour un code d'erreur PHP |

**Processus en 7 étapes :** erreur PHP → taille → **MIME réel** (`finfo`) → répertoire →
nom aléatoire (`bin2hex(random_bytes(16))`) → `move_uploaded_file()` → chemin relatif.

#### `large_upload.php` — Upload médias (vidéos)

| Constante / Fonction | Valeur / Rôle |
|----------------------|---------------|
| `LARGE_UPLOAD_MAX_SIZE` | **2 Go** (vidéo) |
| `LARGE_UPLOAD_IMAGE_MAX_SIZE` | **50 Mo** (image) |
| `ALLOWED_VIDEO_MIMES` | `video/mp4`, `webm`, `ogg`, `quicktime`, `x-msvideo`, `x-matroska` |
| `uploadLargeVideo($file, $subDir)` | Upload vidéo → `{success, path, type, error}` |
| `uploadMediaImage($file, $subDir)` | Upload image (limite 50 Mo) |
| `uploadMedia($file, $subDir)` | Détection automatique image/vidéo |
| `uploadMultipleMedias($files, $subDir)` | Upload multiple (ignore les champs vides) |

#### `delete_file.php` — Suppression sécurisée

| Fonction | Rôle |
|----------|------|
| `deleteFile($relativePath, $protectedPaths = [])` | Supprime un fichier sous `assets/uploads/` |
| `deleteFiles($relativePaths, $protectedPaths = [])` | Supprime N fichiers, retourne le compteur |

**Protection path traversal :** le chemin est résolu avec `realpath()` puis vérifié
avec `str_starts_with($realFull, DELETE_BASE_DIR . DIRECTORY_SEPARATOR)`. Toute
tentative de sortie est journalisée et refusée.

---

## 11. API

### 11.1 `GET /api/categories.php?id_prestation=N`

Retourne les catégories (formules + tarifs) d'une prestation, en JSON.
Utilisé par `espace/client/reservations.php`.

**Authentification :** session requise (client **ou** admin) → `403` sinon.

```bash
curl "http://localhost/NY_TIA_SARY/api/categories.php?id_prestation=1" \
     -b "PHPSESSID=..."
```

**Réponse :**
```json
[
  { "id": 1, "lib": "Equipe", "tarif": 500000, "tarif_fmt": "500 000" },
  { "id": 2, "lib": "Portrait professionnel", "tarif": 200000, "tarif_fmt": "200 000" }
]
```

| Code | Condition |
|------|-----------|
| `200` | Succès (`[]` si aucune catégorie) |
| `403` | Aucune session client/admin |

### 11.2 `GET /categorie.php?id_prestation=N`

**Variante publique** (utilisée par le modal de devis du site vitrine et par
`espace/client/devis.php`, sans contrôle de session).

```json
[
  { "ID_CATEGORIE": 1, "LIB_CATEGORIE": "Equipe" }
]
```

> 💡 Cet endpoint ne renvoie que l'ID et le libellé (pas les tarifs). Il n'a **pas**
> de contrôle de session, contrairement à `api/categories.php`.

---

## 12. Sécurité

### 12.1 Mesures en place

| Domaine | Mesure | Implémentation |
|---------|--------|----------------|
| **Mots de passe** | bcrypt | `password_hash($pwd, PASSWORD_BCRYPT)` / `password_verify()` |
| **Question de sécurité** | bcrypt + minuscule | `password_hash(strtolower($reponse), PASSWORD_BCRYPT)` |
| **Injection SQL** | Prepared statements | PDO avec `ATTR_EMULATE_PREPARES => false` |
| **XSS en sortie** | Échappement | `htmlspecialchars()` sur les données affichées |
| **XSS dans les toasts** | Échappement JSON | `JSON_HEX_TAG\|JSON_HEX_APOS\|JSON_HEX_QUOT\|JSON_HEX_AMP` |
| **Upload de fichiers** | MIME réel + nom aléatoire | `finfo(FILEINFO_MIME_TYPE)` + `bin2hex(random_bytes(16))` |
| **Path traversal** | Résolution + confinement | `realpath()` + `str_starts_with()` sur `DELETE_BASE_DIR` |
| **Open redirect** | Validation d'URL | `isSafeRedirect()` : URL locale uniquement, ni schéma ni hôte |
| **Contrôle d'accès** | Middleware | `requireAdmin()` / `requireClient()` en tête de chaque page protégée |
| **Séparation des sessions** | Clés distinctes | `$_SESSION['admin_id']` vs `$_SESSION['client_id']` |
| **Élévation de privilèges** | Bloquée par rôle | Un admin ne peut pas être redirigé vers `/espace/client/` (et inversement) |
| **Double soumission** | Pattern PRG | `prg_redirect()` : le POST n'est jamais rejoué au refresh |
| **Anti-rejeu** | Garde de statut | Les actions sensibles exigent un statut précis (`EN ATTENTE`, `CONFIRMEE`…) |
| **Ownership (IDOR)** | Paramétré | `WHERE r.ID_CLIENT = ?` sur chaque requête client ; les actions mutantes revérifient dans le `WHERE` |
| **Énumération de compte** | Message générique | Réponse identique que l'email existe ou non |
| **Fuite d'erreur SQL** | Masquée | Détail dans `error_log()`, message générique à l'utilisateur |
| **Cache après déconnexion** | Headers anti-cache | `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` |
| **Surpaiement** | Bloqué serveur | Refus si `payé + nouveau > MONTANT_FACTURE` |

### 12.2 Points de vigilance

> ⚠️ **Ne pas considérer comme un projet « blindé ».** Voici les axes d'amélioration
> identifiés par l'analyse du code, à traiter avant ou pendant la mise en production.

| Axe | Constat | Recommandation |
|-----|---------|----------------|
| **CSRF** | 🔴 **Aucun token anti-CSRF** sur les ~25 formulaires POST et l'endpoint AJAX `ajax_statut` | Ajouter un token en session + vérification sur chaque POST |
| **Upload non contrôlé** | 🔴 `espace/admin/devis.php` utilise `move_uploaded_file()` **en direct**, sans MIME check, sans whitelist d'extension, sans limite de taille, et **conserve l'extension d'origine** | Passer par `util/file_upload.php` (déjà écrit et sécurisé) |
| **Fuite d'erreur** | 🔴 `traitement_devis.php:299` renvoie `"DEBUG: " . $e->getMessage()` au visiteur | Remplacer par un message générique |
| **Rate limiting** | Aucun sur le login ni sur la réinitialisation de mot de passe | Limiter les tentatives (session/APCu) |
| **HTTPS** | Non forcé par défaut | Activer le certificat + redirection `.htaccess` |
| **XSS stocké** | `GROUP_CONCAT(LIB_CATEGORIE)` affiché **sans échappement** dans `client/factures.php`, `client/reservations.php`, `client/contrats.php` | Appliquer `htmlspecialchars()` sur `LIBS_CATEGORIES` |
| **XSS JSON** | `admin/calendrier.php` encode en JSON **sans `JSON_HEX_TAG`** → une valeur contenant `</script>` casse le contexte | Ajouter `JSON_HEX_TAG` |
| **Fichiers statiques** | Les médias et les réponses de devis sont servis en `<img src>` / `<a href>` **directement depuis `assets/`** : le contrôle d'accès est sur la page, pas sur le fichier | Servir via un script PHP avec `Content-Disposition` + vérification d'ownership |
| **IDOR** | `client/home.php` compte les devis sur `NOM = ? OR PRENOMS = ?` ; `client/devis.php` scope l'historique sur `d.EMAIL = ?` | Utiliser `ID_CLIENT` partout |
| **IDOR (upload)** | `admin/medias.php` ne revérifie pas que `id_reservation` est bien `TERMINEE` | Revalider côté serveur |
| **Suppression en cascade** | `admin/factures.php` `action=delete` supprime sans vérifier les `PAIEMENT` enfants | Ajouter un garde (comme `prestations.php` et `contrats.php`) |
| **GET mutating** | `?mark_notif=` déclenche un `UPDATE` sur une requête GET | Passer en POST ou assumer le risque |
| **Numérotation** | `FAC-<année>-<COUNT+1>` n'est pas verrouillé → collision possible en cas de créations concurrentes | Utiliser une séquence ou un verrou |
| **Mots de passe** | Aucune politique de complexité | Ajouter une validation de robustesse |
| **Secrets en clair** | Mots de passe DB/SMTP en dur dans `config/*.php` ; email admin codé en dur dans `client/reservations.php:105` et en fallback dans `traitement_contact.php:30` | Variables d'environnement / `config/site.php` |
| **URL en dur** | `admin/devis.php:70` construit `'http://' . $_SERVER['HTTP_HOST'] . '/NY_TIA_SARY/…'` (dossier figé, HTTP imposé) | Utiliser `$siteConfig['url']` |
| **Upload** | Pas de scan antivirus, pas de réécriture d'image | Antimalware + redimensionnement côté serveur |

---

## 13. Anomalies connues

Bugs identifiés dans le code, à corriger. Documentés pour ne pas être redécouverts.

### 13.1 🔴 Bloquant — `espace/admin/parametres.php` : `$authId` indéfini

`$authId` est **utilisé** aux lignes 64, 70, 108, 113 (dans le bloc POST, lignes 14–284)
mais **assigné** seulement aux lignes 290–292, après ce bloc.

Conséquence : sur toute requête POST, `$authId` vaut `null` → les actions
`update_info` et `update_password` s'exécutent avec `WHERE ID_AUTH = NULL`,
qui ne matche **aucune ligne**. Le message « mis à jour » s'affiche pourtant.

> ⚠️ **Impact :** un administrateur ne peut pas modifier ses informations ni son mot
> de passe depuis l'interface.

**Correction :** remonter l'assignation de `$authId` **avant** le bloc POST.

### 13.2 🔴 Bloquant — `traitement_devis.php:299` : fuite d'erreur SQL

```php
// TEMPORAIRE — À RETIRER après debug
redirectVersIndex(false, "DEBUG: " . $e->getMessage());
```

Expose le message d'erreur MySQL complet au visiteur. À remplacer par :

```php
redirectVersIndex(false, "Une erreur technique est survenue. Veuillez réessayer plus tard.");
```

### 13.3 🟠 `espace/admin/devis.php` : upload non sécurisé

L'action `envoyer_reponse` dépose le PDF de réponse via un `move_uploaded_file()`
**direct**, sans passer par `util/file_upload.php` :

- pas de vérification MIME réelle
- pas de liste blanche d'extension
- pas de limite de taille
- **l'extension d'origine envoyée par le client est conservée telle quelle**

Le nom généré est `devis_<id>_<hex>.<ext>`.

**Correction :** remplacer par
`uploadFile($_FILES['fichier_reponse'], 'devis_reponses', 'document')`.

### 13.4 🟠 `espace/admin/blog.php` : garde inversée sur l'upload

```php
if (!$imgPath || $_POST['img_actuelle'] || empty($_FILES['image_couverture']['name'])) {
```

`empty($_FILES[...]['name'])` est vrai quand **aucun** fichier n'est envoyé. La
condition est donc satisfaite dans le cas normal, et le corps `create` / `edit`
n'est atteint que si le chemin a bien été fourni. Un nouvel upload réussi suivi d'un
envoi sans fichier retombe sur `prg_redirect()` **sans INSERT ni UPDATE**.

### 13.5 🟠 Totaux financiers faussés (`fan-out` de jointure)

Dans `espace/admin/factures.php` et `espace/admin/paiements.php`, les requêtes de
statistiques globales font :

```sql
SELECT COUNT(f.ID_FACTURE),
       COALESCE(SUM(f.MONTANT_FACTURE),0) AS total_facture,
       COALESCE(SUM(pay.MONTANT_PAIEMENT),0) AS total_paye
FROM FACTURE f
LEFT JOIN PAIEMENT pay ON pay.ID_FACTURE = f.ID_FACTURE
```

`MONTANT_FACTURE` est sommé **autant de fois qu'il y a de paiements** : une facture avec
3 règlements est comptée 3 fois. Le total facturé affiché est donc **surévalué** pour
toute facture ayant plus d'un paiement.

**Correction :** passer par une table dérivée, comme le fait déjà `paiements.php` pour
ses totaux par ligne :

```sql
LEFT JOIN (SELECT ID_FACTURE, SUM(MONTANT_PAIEMENT) AS total_paye
           FROM PAIEMENT GROUP BY ID_FACTURE) AS all_pay
       ON all_pay.ID_FACTURE = f.ID_FACTURE
```

Le même défaut affecte le récapitulatif de `espace/client/paiements.php`.

### 13.6 🟡 `espace/admin/paiements.php` : filtre `?date=` inopérant

```php
$filterDate = trim($_GET['date'] ?? '');   // ligne 13 — lu…
```

…mais jamais ajouté à `$conditions` ni à `$params`. Le filtre par date exacte est donc
silencieusement ignoré (seul le filtre par mois `?mois=` fonctionne).

### 13.7 🟡 `espace/admin/devis.php` : branche morte `action=valider`

Le handler `POST action=valider` existe (ligne ~17) mais **aucun formulaire de la page
ne le soumet**. Code à supprimer ou formulaire à ajouter.

### 13.8 🟡 `espace/client/notifications.php` : double requête

`notificationDropdown.php` est inclus via `tolbar.php` **et** la page rejoue la même
requête : la liste des notifications est chargée **deux fois** par affichage.

### 13.9 🟡 Variables mortes

| Fichier | Variable |
|---------|----------|
| `client/mes_photos.php`, `client/mes_videos.php`, `client/galerie_reservation.php` | `$imgExts`, `$videoExts` (déclarés, jamais utilisés) |
| `client/generer_facture_pdf.php` | `$adresse`, `$telephone`, `$email` (recalculés puis jamais utilisés) |
| `client/generer_facture_pdf.php` | CSS `.pay-section` + table des paiements : la requête `$paiements` est exécutée mais **le tableau n'est jamais rendu** dans le HTML |
| `admin/clients.php` | `$isDefaultClientPhoto` |
| `admin/reservations.php`, `admin/paiements.php` | `prg_helper.php` est chargé mais jamais utilisé |

### 13.10 🟡 Asymétrie des endpoints catégories

| Fichier | Contrôle de session | Utilisé par |
|---------|---------------------|-------------|
| `api/categories.php` | ✅ 403 si non connecté | `client/reservations.php` |
| `categorie.php` (racine) | ❌ aucun | Site public, `client/devis.php` |

Les deux exposent le catalogue public `CATEGORIE` — impact faible, mais l'asymétrie
mérite d'être documentée et alignée.

---

## 14. Bonnes pratiques de développement

### 14.1 Ajouter une page protégée

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../util/auth_guard.php';
require_once __DIR__ . '/../util/prg_helper.php';

requireAdmin();                       // 1. Protection
require_once __DIR__ . '/composante/tolbarDto.php';   // fournit $pdo + identité

$titre = 'Ma page';                   // 2. Titre de la topbar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = getPDO()->prepare('SELECT * FROM ma_table WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        prg_set_message('success', 'Enregistré !');
    } catch (Throwable $e) {
        error_log($e->getMessage());
        prg_set_message('error', 'Une erreur est survenue.');
    }
    prg_redirect();
}
$messages = prg_get_messages();        // 3. Messages flash

include __DIR__ . '/composante/sidebar.php';
include __DIR__ . '/composante/tolbar.php';
// … HTML
```

### 14.2 Conventions de code

- `declare(strict_types=1);` en tête de **chaque** fichier
- Chemins d'inclusion via `__DIR__` (jamais de chemins relatifs)
- Requêtes SQL : toujours prepared statements avec `:named`
- Sortie HTML : toujours `htmlspecialchars()`
- Erreurs : `error_log()` + message utilisateur générique
- Nommage : `BASE` (constantes), `get*()` (lecteurs), `set*()` (écritures), `prg_*` (PRG)

### 14.3 Conventions de nommage des tables

Colonnes `MAJUSCULES_SNAKE_CASE` avec préfixe de la table :

```
authentification → ID_AUTH, EMAIL_AUTH, MDP_AUTH, ROLE_AUTH
reservation      → ID_RESERVATION, DATE_RESERVATION, STATUS_RESERVATION
```

> ⚠️ Exception historique : `devis` utilise `ID` (et non `ID_DEVIS`) comme clé primaire —
> sa table de liaison s'appelle `devis_categories` avec `ID_DEVIS`.

### 14.4 Dossiers d'uploads

| Dossier | Usage | Limite | Fonction |
|---------|-------|--------|----------|
| `assets/uploads/avatars/` | Photos de profil | 10 Mo | `uploadFile()` |
| `assets/uploads/blog/` | Couverture des articles | 10 Mo | `uploadFile()` |
| `assets/uploads/devis_reponses/` | Réponse PDF à un devis | 10 Mo | `uploadFile()` *(à corriger — cf. §13.3)* |
| `assets/uploads/medias/` | Photos & vidéos livrées | **2 Go / 50 Mo** | `uploadLargeVideo()` / `uploadMediaImage()` |
| `assets/uploads/partenaires/` | Logos partenaires | 10 Mo | `uploadFile()` |
| `assets/uploads/pieces_joint/` | Pièces jointes des devis | 10 Mo | `traitement_devis.php` |

> Tous les dossiers sont **ignorés par Git** (`.gitignore` : `assets/uploads/`).
> Il faut les créer manuellement après un premier clone (cf. §9.9).

### 14.5 Régénérer le graphe de connaissances

Le projet embarque un graphe de connaissances dans `graphify-out/` :

```bash
graphify update .                    # re-extraire les fichiers modifiés (gratuit, sans LLM)
graphify query "reservation workflow" # interroger le graphe
```

Ouvrir `graphify-out/graph.html` dans un navigateur pour visualiser le graphe.

---

## 15. FAQ / Dépannage

<details>
<summary><b>« Erreur de connexion à la base de données »</b></summary>

Vérifiez que MySQL est démarré dans XAMPP, que la base `ny_tia_sary_db` existe
et que `config/database.php` correspond à vos identifiants.
</details>

<details>
<summary><b>La base est créée mais les tables sont absentes</b></summary>

Le dump SQL ne contient **pas** de `CREATE DATABASE`. Il faut sélectionner la base
dans phpMyAdmin avant d'importer, ou utiliser
`mysql -u root ny_tia_sary_db < fichier.sql`.
</details>

<details>
<summary><b>« Call to a member function … on null » sur une page admin/client</b></summary>

La session n'est pas démarrée. Ajoutez `session_start();` avant d'utiliser `$_SESSION`,
et appelez `requireAdmin()` / `requireClient()` en tête de page.
</details>

<details>
<summary><b>Je suis redirigé vers le login en boucle</b></summary>

La clé de session attendue est absente. Vérifiez que la connexion a bien mis
`$_SESSION['admin_id']` (admin) ou `$_SESSION['client_id']` (client).
Si vous venez d'être promu en `ADMIN`, **déconnectez-vous et reconnectez-vous**
(la session existante conserve l'ancien rôle).
</details>

<details>
<summary><b>Je n'arrive pas à modifier mes infos admin ni mon mot de passe</b></summary>

C'est l'anomalie connue **§13.1** : `$authId` est indéfini au moment du traitement
du POST dans `espace/admin/parametres.php`. En attendant la correction, utilisez
directement le SQL :

```sql
UPDATE authentification SET MDP_AUTH = '<hash bcrypt>' WHERE EMAIL_AUTH = 'admin@…';
```
</details>

<details>
<summary><b>« Votre fichier dépasse la taille autorisée par le serveur »</b></summary>

C'est une limite `php.ini`, pas une limite applicative. Augmentez `upload_max_filesize`
et `post_max_size` (voir §8.8 et §9.8), puis redémarrez Apache.
</details>

<details>
<summary><b>Les emails ne partent pas</b></summary>

- Vérifiez que **Mailpit tourne** (défaut : `localhost:1025`), ou adaptez `config/mail.php`.
- Consultez `http://localhost:8025` pour les emails capturés.
- Les échecs sont journalisés : `error_log('Erreur d\'envoi d\'email: …')`.
- Un échec d'email **ne bloque jamais** l'opération métier — vérifiez les notifications in-app.
</details>

<details>
<summary><b>Les URLs pointent vers `localhost` après déploiement</b></summary>

`config/site.php` se base sur `$_SERVER['HTTP_HOST']` et `DOCUMENT_ROOT`. Vérifiez que
le virtual host Apache pointe correctement sur le dossier du projet et que le
`ServerName` est défini.
</details>

<details>
<summary><b>Les catégories ne s'affichent pas dans le modal de devis</b></summary>

Le modal appelle `categorie.php` (public) ou `api/categories.php` (session requise)
selon la page. Vérifiez la console navigateur pour l'URL appelée, et vérifiez qu'il
existe bien des catégories en base pour la prestation sélectionnée
(`SELECT * FROM categorie WHERE ID_PRESTATION = ?`).
</details>

<details>
<summary><b>Comment devenir administrateur ?</b></summary>

Il n'existe volontairement **aucune** auto-promotion. Inscrivez-vous puis :
`UPDATE authentification SET ROLE_AUTH = 'ADMIN' WHERE EMAIL_AUTH = 'votre@email.com';`
</details>

<details>
<summary><b>Comment ajouter une prestation ou une catégorie ?</b></summary>

```sql
INSERT INTO prestations (ID_PRESTATION, LIB_PRESTATION) VALUES (8, 'Photographie architecture');
INSERT INTO categorie (ID_CATEGORIE, LIB_CATEGORIE, TARIF_CATEGORIE, ID_PRESTATION)
VALUES (30, 'Extérieur', 350000, 8);
```
Les catégories apparaissent automatiquement dans le modal de devis et dans le
formulaire de réservation (chargées en AJAX). La suppression d'une prestation est
refusée par `espace/admin/prestations.php` si des réservations y sont rattachées.
</details>

<details>
<summary><b>Le dossier `assets/uploads/` est vide après un git clone</b></summary>

Normal : `.gitignore` exclut ce dossier. Créer les 6 sous-dossiers (voir §9.9).
</details>

---

## 📄 Crédits

- **PHPMailer 7.1.1** — LGPL-2.1 — [github.com/PHPMailer/PHPMailer](https://github.com/PHPMailer/PHPMailer)
- **dompdf 3.1.5** — LGPL-2.1 — [github.com/dompdf/dompdf](https://github.com/dompdf/dompdf)
- **ScrollReveal 4.0.9**, **Toastify**, **Font Awesome 6.4/6.5**, **Chart.js 4.4.0**,
  **FullCalendar 6.1.11**, **Flatpickr** — CDN

---

*Studio NY TIA SARY — Mangarano, Toamasina, Madagascar*
*Hatsarao sy ho Tiava. Professionnel sy Mendrika.*
