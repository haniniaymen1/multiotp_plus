<?php
/* check_deployment.php - diagnostic du déploiement multiOTP sur C:\multiotp\
   (correctif AD-DELETE-SYNC v4.0.9/v4.0.10)

   Copiez ce fichier dans C:\multiotp\ puis lancez (le PHP du paquet multiOTP):
       C:\multiotp\php\php.exe check_deployment.php
   (ou n'importe quel php.exe récent: php check_deployment.php)

   Le script ne modifie RIEN: il lit les fichiers et vous dit exactement quoi faire
   pour que la synchronisation AD SUPPRIME les utilisateurs disparus (ahmed10)
   au lieu de seulement les désactiver. */

error_reporting(E_ERROR | E_PARSE);
echo "=== multiOTP deployment check (correctif AD-DELETE-SYNC v4.0.10) ===\n\n";
$dir    = __DIR__;
$action = array();

function read_file($path) {
    if (!is_file($path)) { return FALSE; }
    $data = @file_get_contents($path);
    return (FALSE === $data) ? FALSE : $data;
}

/* --- 1. multiotp.class.php --- */
echo "[1] multiotp.class.php\n";
$class = read_file($dir . DIRECTORY_SEPARATOR . 'multiotp.class.php');
if (FALSE === $class) {
    echo "    MANQUANT: " . $dir . "\\multiotp.class.php introuvable\n";
    $action[] = "Recopiez multiotp.class.php (patché v4.0.9) dans " . $dir;
} else {
    $official = (FALSE !== strpos($class, '@version   5.10.2.2'));
    $guard    = (FALSE !== strpos($class, '$ldap_total_counter >= 1'));
    $graceful = (FALSE !== strpos($class, 'v4.0.9: graceful degradation'));
    echo "    base multiOTP 5.10.2.2: " . ($official ? "oui" : "NON (version inattendue)") . "\n";
    echo "    garde AD-DELETE-SYNC (suppression immédiate des disparus d'AD): " . ($guard ? "PRESENT (class patché)" : "ABSENT (class OFFICIEL)") . "\n";
    echo "    requires contrib dégradables (v4.0.9): " . ($graceful ? "PRESENT" : "ABSENT") . "\n";
    if (!$guard) {
        echo "    -> C'est la cause de votre log « automatically disabled »: le class officiel\n";
        echo "       désactive les utilisateurs disparus et ne les supprime qu'après la rétention.\n";
        $action[] = "REMPLACEZ " . $dir . "\\multiotp.class.php par le multiotp.class.php PATCHÉ du correctif (v4.0.9/4.0.10)";
    } else {
        echo "    OK: la prochaine passe qui voit des entrées LDAP supprimera immédiatement les disparus.\n";
    }
}

/* --- 2. multiotp.server.php (console web) --- */
echo "\n[2] multiotp.server.php (console web)\n";
$server = read_file($dir . DIRECTORY_SEPARATOR . 'multiotp.server.php');
if (FALSE === $server) {
    echo "    MANQUANT: " . $dir . "\\multiotp.server.php introuvable\n";
    $action[] = "Recopiez multiotp.server.php (enrichi v4.0.10) dans " . $dir;
} else {
    $enriched = (FALSE !== strpos($server, 'MultiotpLdapCleanMissingUsers'));
    $version  = (FALSE !== strpos($server, 'v4.0.10 (multi-master'));
    $button   = (FALSE !== strpos($server, '$ha_class_patched'));
    echo "    server enrichi (bouton Synchronize AD + tombstones HA): " . ($enriched ? "PRESENT" : "ABSENT (server officiel)") . "\n";
    echo "    version correctif: " . ($version ? "v4.0.10" : ($button ? "v4.0.9 (ancien)" : "officielle")) . "\n";
    echo "    bouton Sync AD avec détection du class patché: " . ($button ? "PRESENT" : "ABSENT") . "\n";
    if ($enriched && !$button) {
        $action[] = "REMPLACEZ " . $dir . "\\multiotp.server.php par la v4.0.10: le bouton passe alors delete_old_synced_users=TRUE au class patché (suppression immédiate aussi depuis la console)";
    } elseif ($enriched && $button) {
        echo "    OK: le bouton « Synchronize AD » supprimera immédiatement les disparus (class patché détecté).\n";
    } else {
        $action[] = "REMPLACEZ " . $dir . "\\multiotp.server.php par le server enrichi v4.0.10 (bouton Synchronize AD)";
    }
}

/* --- 3. dossier contrib/ --- */
echo "\n[3] dossier contrib/ (fichiers chargés par multiotp.class.php)\n";
$needed = array('MultiotpSms.php', 'MultiotpTools.php', 'MultiotpAdLdap.php', 'barcode.php',
                'MultiotpShowStatus.php', 'MultiotpSyslog.php', 'MultiotpXmlParser.php', 'MultiotpYubikey.php',
                'Random.php', 'BigInteger.php', 'Base.php', 'Hash.php', 'Rijndael.php', 'AES.php', 'DES.php',
                'TripleDES.php', 'RSA.php', 'Blowfish.php', 'Twofish.php', 'RC4.php', 'SSH2.php', 'SFTP.php',
                'Stream.php', 'ASN1.php', 'X509.php', 'FUNC5.php', 'MIME5.php', 'SMTP5.php', 'MAIL5.php',
                'FUNC4.php', 'MIME4.php', 'SMTP4.php', 'MAIL4.php', 'nusoap.php',
                'phpWebSocketServer/websocketCore.php');
$missing = array();
foreach ($needed as $one) {
    if (!is_file($dir . DIRECTORY_SEPARATOR . 'contrib' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $one))) {
        $missing[] = $one;
    }
}
if (empty($missing)) {
    echo "    OK: les " . count($needed) . " fichiers essentiels sont présents (dossier complet).\n";
} else {
    echo "    MANQUANTS (" . count($missing) . "):\n";
    foreach ($missing as $one) { echo "      - contrib/" . $one . "\n"; }
    $action[] = "RECOPIEZ le dossier contrib/ COMPLET fourni dans le correctif vers " . $dir . "\\contrib\\ (sans lui, le class fait un fatal au chargement)";
}

/* --- verdict --- */
echo "\n=== VERDICT ===\n";
if (empty($action)) {
    echo "Déploiement COMPLET. Lancez la synchronisation AD:\n";
    echo "  - tâche planifiée: multiotp.exe ldap-users-sync\n";
    echo "  - ou bouton « Synchronize AD » de la console\n";
    echo "Les utilisateurs disparus de l'AD (ahmed10) seront SUPPRIMÉS immédiatement\n";
    echo "(log: « deleted by the AD/LDAP synchronization ») au lieu d'être seulement désactivés.\n";
} else {
    echo "Actions à faire, dans l'ordre:\n";
    $i = 0;
    foreach ($action as $one) { $i++; echo "  $i. $one\n"; }
    echo "Puis relancez le service web et re-lancez la synchronisation AD.\n";
}
echo "\n=== fin du diagnostic ===\n";
