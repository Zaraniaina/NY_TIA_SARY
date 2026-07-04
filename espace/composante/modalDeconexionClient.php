<!-- Modal de confirmation de déconnexion -->
<div class="dash-modal" id="logoutModal">
    <div class="dash-modal-content">
        <button class="dash-modal-close" id="logoutModalClose">&times;</button>
        <h3>Confirmation</h3>
        <p>Êtes-vous sûr de vouloir vous déconnecter ?</p>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
            <!-- Le lien de confirmation utilise le même href que le lien de déconnexion original -->
            <a href="<?= $root ?>espace/client/logout.php?action=deconnexion" class="btn-dash btn-dash-danger" id="confirmLogout">Oui, me déconnecter</a>
            <button class="btn-dash btn-dash-outline" id="cancelLogout">Annuler</button>
      </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const logoutLink = document.querySelector('.logout-link');
    const modal = document.getElementById('logoutModal');
    const closeBtn = document.getElementById('logoutModalClose');
    const cancelBtn = document.getElementById('cancelLogout');

    if (!logoutLink) return;

    // Ouvrir le modal
    logoutLink.addEventListener('click', function (e) {
       e.preventDefault();
        modal.classList.add('open');
    });

    // Fermer le modal
    function closeModal() {
       modal.classList.remove('open');
    }

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Fermer en cliquant à l'extérieur du contenu
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
});
</script>