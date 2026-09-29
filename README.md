# 📸 NY TIA SARY — Studio Photo & Vidéo Professionnel

Plateforme web complète de gestion d'un studio photo/vidéo basée à Madagascar.  
Elle comprend un **site vitrine public**, un **espace administrateur** et un **espace client**.

---

## 📋 Table des matières

- [Fonctionnalités](#-fonctionnalités)
- [Stack technique](#-stack-technique)
- [Installation locale avec XAMPP](#-installation-locale-avec-xampp)
- [Déploiement en production](#-déploiement-en-production)
- [Structure du projet](#-structure-du-projet)
- [Sécurité](#-sécurité)

---

## ✨ Fonctionnalités

| Module | Description |
|--------|-------------|
| Site vitrine | Présentation du studio, portfolio, services, blog, contact |
| Devis en ligne | Formulaire public ou depuis l'espace client |
| Espace Admin | Gestion réservations, devis, clients, contrats, factures, médias, blog |
| Espace Client | Suivi réservations, contrats, factures PDF, galerie photos/vidéos |
| Notifications | Système in-app pour les clients (contrat prêt, confirmation…) |
| Email automatique | Confirmation de réservation envoyée par email (PHPMailer) |

---

## 🛠 Stack technique

- **Backend** : PHP 8+ (strict_types, PDO, sessions)
- **Base de données** : MySQL 5.7+ / MariaDB 10.4+
- **Email** : PHPMailer (SMTP configurable)
- **PDF** : dompdf
- **Frontend** : HTML5, Vanilla CSS, JavaScript (sans framework)
- **Serveur local** : XAMPP (Apache + MySQL)

---

## 💻 Installation locale avec XAMPP

### Prérequis

- [XAMPP](https://www.apachefriends.org/) installé (version avec PHP 8.0+ recommandée)
- Git (optionnel, pour cloner le projet)

---

### Étape 1 — Copier le projet dans htdocs

Placer le dossier du projet dans le répertoire `htdocs` de XAMPP :

```
C:\xampp\htdocs\NY_TIA_SARY\
```

Si vous avez Git :
```bash
cd C:\xampp\htdocs
git clone <url-du-repo> NY_TIA_SARY
```

---

### Étape 2 — Démarrer XAMPP

1. Ouvrir le **Panneau de contrôle XAMPP**
2. Démarrer **Apache** et **MySQL**

---

### Étape 3 — Créer la base de données

1. Ouvrir **phpMyAdmin** : http://localhost/phpmyadmin
2. Cliquer sur **Nouvelle base de données**
3. Nom : `ny_tia_sary_db` — Encodage : `utf8mb4_general_ci`
4. Cliquer **Créer**
5. Sélectionner la base `ny_tia_sary_db` → onglet **Importer**
6. Importer le fichier SQL du schéma fourni séparément

---

### Étape 4 — Vérifier la configuration de la base de données

Le fichier `config/database.php` est **déjà configuré pour XAMPP** avec les valeurs par défaut :

```php
$host    = 'localhost';
$db      = 'ny_tia_sary_db';
$user    = 'root';
$pass    = '';          // Pas de mot de passe par défaut sur XAMPP
$charset = 'utf8mb4';
```

> ✅ Aucune modification nécessaire pour un XAMPP standard.

---

### Étape 5 — Configurer l'envoi d'emails (développement)

Le fichier `config/mail.php` est configuré pour un serveur mail local.
En développement, on recommande **[Mailpit](https://mailpit.axllent.org/)** (intercepte les emails sans les envoyer).

```php
// config/mail.php — configuration actuelle (DEV)
"host"       => "localhost",
"port"       => 1025,        // Port Mailpit par défaut
"username"   => "",
"password"   => "",
"encryption" => null,
"from_email" => "nytiasary@gmail.com",
"from_name"  => "Ny tia sary"
```

> Si vous n'utilisez pas Mailpit, les emails échoueront silencieusement (les autres opérations continuent quand même).

---

### Étape 6 — Accéder au site

| Page | URL |
|------|-----|
| Site public | http://localhost/NY_TIA_SARY/ |
| Connexion | http://localhost/NY_TIA_SARY/login/login.php |
| Inscription | http://localhost/NY_TIA_SARY/login/register.php |
| Espace Admin | http://localhost/NY_TIA_SARY/espace/admin/home.php |
| Espace Client | http://localhost/NY_TIA_SARY/espace/client/home.php |

> **Note** : Pour accéder à l'espace admin, créez d'abord un compte via l'inscription, puis changez manuellement le `ROLE_AUTH` à `ADMIN` dans phpMyAdmin (table `AUTHENTIFICATION`).

---

## 🚀 Déploiement en production

### Prérequis serveur

- PHP **8.0+** avec les extensions : `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`
- MySQL **5.7+** ou MariaDB **10.4+**
- Serveur web Apache (avec `mod_rewrite`) ou Nginx
- Accès SSH ou FTP

---

### Étape 1 — Transférer les fichiers

Uploader l'intégralité du projet sur le serveur via FTP ou Git :

```bash
# Exemple avec Git sur le serveur
git clone <url-du-repo> /var/www/html/ny_tia_sary
```

> ⚠️ Ne pas transférer le dossier `.git/` en production si vous utilisez FTP.

---

### Étape 2 — Créer la base de données de production

Via le panneau d'hébergement (cPanel, Plesk…) ou en ligne de commande :

```sql
CREATE DATABASE ny_tia_sary_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'ny_user'@'localhost' IDENTIFIED BY 'MotDePasseFort!';
GRANT ALL PRIVILEGES ON ny_tia_sary_prod.* TO 'ny_user'@'localhost';
FLUSH PRIVILEGES;
```

Importer le schéma SQL :

```bash
mysql -u ny_user -p ny_tia_sary_prod < schema.sql
```

---

### Étape 3 — ⚙️ Modifier `config/database.php`

> ⛔ C'est le fichier le plus critique. Les valeurs XAMPP ne fonctionneront PAS en production.

```php
// config/database.php — PRODUCTION
$host    = 'localhost';             // Souvent 'localhost', parfois l'IP du serveur DB
$db      = 'ny_tia_sary_prod';     // ← Nom de votre base de données de production
$user    = 'ny_user';              // ← Votre utilisateur MySQL de production
$pass    = 'MotDePasseFort!';      // ← Mot de passe sécurisé (jamais vide en prod !)
$charset = 'utf8mb4';
```

---

### Étape 4 — ⚙️ Modifier `config/mail.php`

Remplacer la configuration Mailpit (dev) par les vrais paramètres SMTP :

```php
// config/mail.php — PRODUCTION (exemple avec Gmail)
"host"       => "smtp.gmail.com",          // ← Hôte SMTP de votre fournisseur
"port"       => 587,                        // ← 587 (STARTTLS) ou 465 (SSL)
"username"   => "nytiasary@gmail.com",     // ← Adresse email expéditeur
"password"   => "votre_app_password",      // ← App Password Google (pas votre mdp Gmail !)
"encryption" => "tls",                     // ← 'tls' (port 587) ou 'ssl' (port 465)
"from_email" => "nytiasary@gmail.com",
"from_name"  => "Ny tia sary"
```

> **Gmail** : Activer la validation en deux étapes → générer un **"App Password"** dans Sécurité du compte Google → utiliser ce code de 16 caractères comme `password`.

---

### Étape 5 — Permissions des dossiers d'upload

```bash
chmod -R 755 /var/www/html/ny_tia_sary/assets/uploads/
chown -R www-data:www-data /var/www/html/ny_tia_sary/assets/uploads/
```

> Sur certains hébergements partagés, `775` peut être nécessaire. Évitez `777` autant que possible.

---

### Étape 6 — Créer le fichier `.htaccess` (Apache)

Créer un fichier `.htaccess` à la racine du projet :

```apache
# Sécurité : interdire l'accès direct aux fichiers de configuration
<FilesMatch "^(database|mail|site)\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Masquer les erreurs PHP en production
php_flag display_errors Off
php_flag log_errors On

# Interdire la navigation dans les dossiers
Options -Indexes

# Forcer HTTPS (décommenter si SSL est installé)
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

### Étape 7 — Désactiver l'affichage des erreurs PHP

Dans le `php.ini` du serveur ou via `.htaccess` :

```ini
display_errors = Off
log_errors = On
error_reporting = E_ALL
```

---

### Étape 8 — Créer le compte administrateur

1. Aller sur la page d'inscription et créer un compte
2. Changer le rôle dans la base de données :

```sql
UPDATE AUTHENTIFICATION SET ROLE_AUTH = 'ADMIN' WHERE EMAIL_AUTH = 'votre@email.com';
```

---

### ✅ Récapitulatif des fichiers à modifier en production

| Fichier | Ce qui change |
|---------|--------------|
| `config/database.php` | Host, nom DB, utilisateur, mot de passe |
| `config/mail.php` | Hôte SMTP, port, username, password, encryption |
| `.htaccess` *(à créer)* | Masquer les erreurs, sécuriser config/, forcer HTTPS |
| `assets/uploads/` *(permissions)* | `chmod 755` + `chown www-data` |

---

## 📁 Structure du projet

```
NY_TIA_SARY/
├── index.php                        # Page d'accueil publique
├── service.php                      # Page des services
├── blog.php                         # Blog public
├── contact.php                      # Formulaire de contact
├── apropos.php                      # Page À propos
├── portfolio.php                    # Portfolio
├── traitement_contact.php           # Traitement formulaire contact
├── traitement_devis.php             # Traitement formulaire devis
├── script.js                        # JS global du site public
│
├── config/
│   ├── database.php                 # ⚙️ Connexion PDO (singleton)
│   ├── mail.php                     # ⚙️ Config SMTP PHPMailer
│   └── site.php                     # URL de base (auto-détectée)
│
├── authentification/
│   ├── auth.php                     # Logique de connexion (POST)
│   └── registre.php                 # Logique d'inscription (POST)
│
├── login/
│   ├── login.php                    # Page de connexion
│   ├── register.php                 # Page d'inscription
│   └── mdpOublier.php               # Récupération de mot de passe
│
├── util/
│   ├── auth_guard.php               # Middleware requireAdmin() / requireClient()
│   ├── mailService.php              # Classe MailService (envoi d'emails)
│   ├── file_upload.php              # Upload sécurisé (vérif MIME réelle)
│   ├── large_upload.php             # Upload de fichiers volumineux
│   ├── delete_file.php              # Suppression sécurisée de fichiers
│   ├── prg_helper.php               # Pattern Post/Redirect/Get
│   └── redirectionpage.php          # Helpers de redirection sécurisée
│
├── espace/
│   ├── admin/                       # Tableau de bord administrateur
│   │   ├── home.php                 # Dashboard KPI + graphiques
│   │   ├── reservations.php         # Gestion des réservations
│   │   ├── devis.php                # Gestion des devis
│   │   ├── clients.php              # Gestion des clients
│   │   ├── factures.php             # Génération et suivi des factures
│   │   ├── contrats.php             # Suivi des contrats
│   │   ├── paiements.php            # Suivi des paiements
│   │   ├── medias.php               # Gestion des médias livrés
│   │   ├── blog.php                 # Gestion du blog
│   │   ├── calendrier.php           # Vue calendrier des réservations
│   │   ├── prestations.php          # Catalogue des prestations
│   │   ├── categories.php           # Catégories de prestations
│   │   ├── temoignages.php          # Modération des témoignages
│   │   ├── notifications.php        # Gestion des notifications
│   │   └── parametres.php           # Paramètres du studio
│   │
│   └── client/                      # Espace personnel du client
│       ├── home.php                 # Dashboard (KPIs)
│       ├── reservations.php         # Mes réservations
│       ├── devis.php                # Mes demandes de devis
│       ├── contrats.php             # Mes contrats
│       ├── factures.php             # Mes factures
│       ├── generer_facture_pdf.php  # Télécharger facture PDF
│       ├── paiements.php            # Historique de paiements
│       ├── mes_photos.php           # Ma galerie photos
│       ├── mes_videos.php           # Ma galerie vidéos
│       ├── notifications.php        # Mes notifications
│       └── parametres.php           # Paramètres du compte
│
├── api/
│   └── categories.php               # Endpoint REST (catégories par prestation)
│
├── composante/                      # Composants partagés (site public)
│   ├── header.php
│   ├── footer.php
│   ├── csslink.php
│   └── devis_visiteur.php
│
├── css/
│   ├── dashboard.css                # Styles espace admin/client
│   └── mainStyle.css                # Styles site public
│
└── assets/
    └── uploads/                     # 📁 Fichiers uploadés (images, PDFs…)
```

---

## 🔐 Sécurité

- Mots de passe hashés avec **bcrypt** (`password_hash` / `password_verify`)
- Toutes les requêtes SQL utilisent des **prepared statements PDO** (protection contre l'injection SQL)
- Les fichiers uploadés sont vérifiés sur le **type MIME réel** (pas celui déclaré par le navigateur)
- Les redirections utilisent `isSafeRedirect()` pour prévenir les **open redirects**
- Le pattern **PRG** (Post/Redirect/Get) est appliqué pour éviter les double-soumissions de formulaires
- Les sessions admin et client sont **strictement séparées** (`$_SESSION['admin_id']` vs `$_SESSION['client_id']`)

---

*Studio NY TIA SARY — Documentation*
