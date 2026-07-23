<?php 
require_once __DIR__ . '/../config/database.php';
$pdo = getPDO();
 // fournit $pdo — adapte le chemin si besoin
$prestationsList = $pdo->query(
    "SELECT ID_PRESTATION, LIB_PRESTATION FROM prestations ORDER BY LIB_PRESTATION ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$devisStatus  = $_GET['devis'] ?? null;
$devisMessage = $_GET['message'] ?? null;
?>

<!-- MODAL DEMANDE DE DEVIS -->
<div class="devis-modal" id="devis-modal">
    <div class="devis-modal-overlay" id="devis-modal-overlay"></div>
    <div class="devis-modal-content">
        <div class="devis-modal-header">
            <h3>Demande de Devis</h3>
            <span class="devis-modal-close" id="devis-modal-close">&times;</span>
        </div>

        <form id="devis-form" class="devis-form" action="traitement_devis.php" method="POST" enctype="multipart/form-data">
            <div class="devis-form-row">
                <div class="devis-form-group">
                    <label for="devis-nom">Nom <span class="required">*</span></label>
                    <input type="text" id="devis-nom" name="nom" required placeholder="Ex: RAKOTONDRABE">
                </div>
                <div class="devis-form-group">
                    <label for="devis-prenom">Prénom <span class="required">*</span></label>
                    <input type="text" id="devis-prenom" name="prenom" required placeholder="Ex: Ranja">
                </div>
            </div>

            <div class="devis-form-row">
                <div class="devis-form-group">
                    <label for="devis-email">Email <span class="required">*</span></label>
                    <input type="email" id="devis-email" name="email" required placeholder="Ex: ranja@gmail.com">
                </div>
                <div class="devis-form-group">
                    <label for="devis-telephone">Téléphone <span class="required">*</span></label>
                    <input type="tel" id="devis-telephone" name="telephone" required placeholder="Ex: +261 34 12 345 67">
                </div>
            </div>
            <div class="devis-form-group devis-form-full">
    <label for="devis-prestation">Prestation souhaitée <span class="required">*</span></label>
    <select id="devis-prestation" name="id_prestation" required>
        <option value="" disabled selected>Sélectionnez une prestation</option>
        <?php foreach ($prestationsList as $prestation): ?>
            <option value="<?= htmlspecialchars($prestation['ID_PRESTATION']) ?>">
                <?= htmlspecialchars($prestation['LIB_PRESTATION']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<fieldset class="devis-categories-fieldset" id="devis-categories-fieldset">
    <legend>Categories</legend>
    <small class="devis-categories-hint">Cochez une ou plusieurs catégories</small>
    <div class="devis-categories-list" id="devis-categories-list">
        <!-- rempli dynamiquement en JS, vide au départ -->
    </div>
</fieldset>

            <div class="devis-form-row">
                <div class="devis-form-group">
                    <label for="devis-type">Type de visiteur <span class="required">*</span></label>
                    <select id="devis-type" name="type_visiteur" required>
                        <option value="" disabled selected>Sélectionnez une option</option>
                        <option value="Entreprise">Entreprise</option>
                        <option value="ONG">ONG</option>
                        <option value="Institution">Institution</option>
                        <option value="Collectivités">Collectivités</option>
                        <option value="Couples">Couples</option>
                        <option value="Familles">Familles</option>
                        <option value="Organisateurs d'événement">Organisateurs d'événement</option>
                        <option value="Artistes">Artistes</option>
                        <option value="Agences de communication">Agences de communication</option>
                        <option value="Particuliers">Particuliers</option>
                    </select>
                </div>
                <div class="devis-form-group">
                    <label for="devis-budget">Budget estimatif (Ar)</label>
                    <input type="number" id="devis-budget" name="budget" min="0" step="1000" placeholder="Ex: 500000">
                </div>
            </div>

            <div class="devis-form-row">
                <div class="devis-form-group">
                    <label for="devis-date">Date souhaitée</label>
                    <input type="date" id="devis-date" name="date_souhaitee">
                </div>
                <div class="devis-form-group">
                    <label for="devis-fichier">Pièce jointe</label>
                    <input type="file" id="devis-fichier" name="piece_jointe">
                </div>
            </div>

            <div class="devis-form-group devis-form-full">
                <label for="devis-description">Description du projet <span class="required">*</span></label>
                <textarea id="devis-description" name="description" rows="4" required placeholder="Décrivez votre besoin, le lieu, le nombre de personnes concernées, etc."></textarea>
            </div>

            <div class="devis-form-actions">
                <button type="button" class="btn btn-outline" id="devis-annuler">Annuler</button>
                <button type="submit" class="btn btn-green">Confirmer</button>
            </div>
        </form>
    </div>
</div>
<?php if ($devisStatus && $devisMessage): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        Toastify({
            text: <?= json_encode($devisMessage, JSON_UNESCAPED_UNICODE) ?>,
            duration: 6000,
            gravity: "top",
            position: "right",
            close: true,
            stopOnFocus: true,
            style: {
                background: <?= $devisStatus === 'success'
                    ? "'linear-gradient(135deg, #377d49, #2a5c3a)'"
                    : "'linear-gradient(135deg, #d93d3d, #a82c2c)'" ?>,
                borderRadius: "6px",
                fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
                fontWeight: "600",
                boxShadow: "0 10px 30px rgba(0, 0, 0, 0.15)",
            },
        }).showToast();

        // Nettoie l'URL pour qu'un rafraîchissement ne réaffiche pas le toast
        const url = new URL(window.location);
        url.searchParams.delete('devis');
        url.searchParams.delete('message');
        url.searchParams.delete('id_devis');
        window.history.replaceState({}, '', url);
    });
</script>
<?php endif; ?> 