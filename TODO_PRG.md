# Plan d'exécution PRG (Post/Redirect/Get)

## Fichiers à corriger (traitent POST sans redirection)

### Espace Client (3 fichiers)
- [ ] `espace/client/reservations.php` - 3 actions: new_resa, cancel, temoignage
- [ ] `espace/client/parametres.php` - 4 actions: update_info, update_password, update_photo, delete_photo
- [ ] `espace/client/contrats.php` - 2 actions: accept, reject

### Espace Admin (9 fichiers)
- [ ] `espace/admin/blog.php` - CRUD: create, edit, publish, draft, delete
- [ ] `espace/admin/prestations.php` - CRUD prestations
- [ ] `espace/admin/parametres.php` - Paramètres admin
- [ ] `espace/admin/medias.php` - Upload/suppression médias
- [ ] `espace/admin/factures.php` - CRUD factures
- [ ] `espace/admin/devis.php` - Actions devis
- [ ] `espace/admin/contrats.php` - CRUD contrats
- [ ] `espace/admin/categories.php` - CRUD catégories
- [ ] `espace/admin/reservations.php` - mark_notif (déjà OK via AJAX, mais GET redirect à vérifier)

### Fichiers déjà OK (respectent PRG)
- ✅ `traitement_devis.php` - Utilise redirectVersIndex()
- ✅ `authentification/auth.php` - Utilise redirectionClient()
- ✅ `authentification/registre.php` - Utilise redirectionClient()