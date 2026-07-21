<?php
declare(strict_types=1);

/**
 * Helper PRG (Post/Redirect/Get) pour éviter la ré-exécution des formulaires au refresh (F5).
 * 
 * Usage:
 * 1. En tout début de fichier (avant tout HTML) :
 *    require_once __DIR__ . '/../util/prg_helper.php';
 *    session_start();
 *    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 *        // Traitement...
 *        if ($ok) prg_set_message('success', 'Succès !');
 *        else prg_set_message('error', 'Erreur !');
 *        prg_redirect();
 *        exit;
 *    }
 *    $prgMessages = prg_get_messages();
 * 
 * 2. Dans le HTML (head ou avant </body>) :
 *    <?php if (!empty($prgMessages)): ?>
 *    <script>
 *    window.addEventListener('DOMContentLoaded', () => {
 *        <?= prg_render_toasts($prgMessages) ?>
 *        const url = new URL(window.location);
 *        url.searchParams.delete('action'); // nettoyer params si besoin
 *        window.history.replaceState({}, '', url);
 *    });
 *    </script>
 *    <?php endif; ?>
 */

const PRG_SESSION_KEY = 'prg_messages';

/**
 * Stocke un message (succès ou erreur) en session pour affichage après redirection.
 * 
 * @param string $type 'success' | 'error' | 'warning' | 'info'
 * @param string $message Message à afficher
 * @return void
 */
function prg_set_message(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $messages = $_SESSION[PRG_SESSION_KEY] ?? [];
    $messages[] = ['type' => $type, 'message' => $message];
    $_SESSION[PRG_SESSION_KEY] = $messages;
}

/**
 * Récupère et supprime les messages PRG de la session.
 * 
 * @return array Liste des messages [['type' => '', 'message' => ''], ...]
 */
function prg_get_messages(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $messages = $_SESSION[PRG_SESSION_KEY] ?? [];
    unset($_SESSION[PRG_SESSION_KEY]);
    return $messages;
}

/**
 * Redirige vers l'URL fournie ou vers la page courante (REQUEST_URI).
 * Utilise header('Location: ...') + exit.
 * 
 * @param string $url URL cible (relative ou absolue). Si vide, utilise REQUEST_URI.
 * @return void
 */
function prg_redirect(string $url = ''): void
{
    if ($url === '') {
        // Utiliser l'URL courante sans les paramètres de query string pour éviter les boucles
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        // Optionnel : retirer les paramètres GET pour une URL propre
        if (($pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $pos);
        }
    }
    
    header('Location: ' . $url);
    exit;
}

/**
 * Génère le code JavaScript Toastify pour afficher les messages PRG.
 * 
 * @param array $messages Résultat de prg_get_messages()
 * @return string Code JavaScript à insérer dans <script>
 */
function prg_render_toasts(array $messages): string
{
    if (empty($messages)) {
        return '';
    }
    
    $jsParts = [];
    
    foreach ($messages as $msg) {
        $type = $msg['type'] ?? 'info';
        $text = json_encode($msg['message'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        
        $bgColor = match ($type) {
            'success' => "'linear-gradient(135deg, #377d49, #2a5c3a)'",
            'error'   => "'linear-gradient(135deg, #d93d3d, #a82c2c)'",
            'warning' => "'linear-gradient(135deg, #f39c12, #d4880e)'",
            'info'    => "'linear-gradient(135deg, #2980b9, #1f639b)'",
            default   => "'linear-gradient(135deg, #377d49, #2a5c3a)'",
        };
        
        $jsParts[] = <<<JS
            Toastify({
                text: {$text},
                duration: 6000,
                gravity: "top",
                position: "right",
                close: true,
                stopOnFocus: true,
                style: {
                    background: {$bgColor},
                    borderRadius: "6px",
                    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
                    fontWeight: "600",
                    boxShadow: "0 10px 30px rgba(0, 0, 0, 0.25)",
                },
            }).showToast();
JS;
    }
    
    return implode("\n", $jsParts);
}

/**
 * Version simplifiée : traite un formulaire POST et fait le redirect PRG automatiquement.
 * À utiliser dans les fichiers qui ne font QUE du traitement de formulaire (pas d'affichage HTML).
 * 
 * @param callable $handler Fonction qui retourne true (succès) ou false (échec), ou throw Exception
 * @param string $successMsg Message en cas de succès
 * @param string $errorMsg Message en cas d'erreur (optionnel, sinon message de l'exception)
 * @param string $redirectUrl URL de redirection (optionnel, défaut: page courante)
 * @return void
 */
function prg_handle_post(
    callable $handler,
    string $successMsg,
    string $errorMsg = '',
    string $redirectUrl = ''
): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return; // Ne rien faire si pas POST
    }
    
    try {
        $result = $handler();
        
        if ($result === true || $result === null) {
            prg_set_message('success', $successMsg);
        } else {
            prg_set_message('error', $errorMsg ?: 'Une erreur est survenue.');
        }
    } catch (Throwable $e) {
        error_log('[PRG] ' . $e->getMessage());
        prg_set_message('error', $errorMsg ?: 'Une erreur technique est survenue.');
    }
    
    prg_redirect($redirectUrl);
}