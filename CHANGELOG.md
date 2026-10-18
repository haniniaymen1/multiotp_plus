# Changelog

Historique des versions de la console enrichie et des paquets associés.
Format inspiré de [Keep a Changelog](https://keepachangelog.com/) ; dates au format AAAA-MM-JJ.

## [Dépôt v1.1] — 2026-10-17

### Ajouté — README bilingue English / Français
- `README.md` : version **anglaise** complète (GitHub affiche par défaut la version anglaise).
- `README.fr.md` : version **française** (l'ancien README), avec sélecteur de langue croisé
  en tête des deux fichiers.
- Nouvelle section commune **« Ce qui change par rapport au multiOTP d'origine » /
  "What changes compared to the original multiOTP"** : tableau avant/après des 8 zones
  d'interface modifiées (comptes admin, rôles, stockage chiffré, panneau HA, réplication,
  sonde Probe URLs, recherche utilisateurs, moteur intouché) + liste résumée des 5 familles
  de fonctions ajoutées — comparaison vérifiée ligne à ligne contre la console officielle
  5.10.2.2 (md5 `92e1f9676031be9c9a7c6ae5fc39018f`), qui ne contient aucun des libellés
  des extensions.

## [Console v4.0.2] — 2026-10-02

### Correctif « phantom https » (3ᵉ défaillance terrain neutralisée)
- **FIX C** : l'`own_url` candidate doit **répondre à un vrai HaPing** au `Save` (toute
  réponse, même `bad_token`, prouve la joignabilité). Si la forme `https` reste muette alors
  que la même host/port répond en http simple → la forme **http est enregistrée**
  (auto-guérison). Si rien ne répond → la valeur précédente est conservée + log explicite.
- **FIX D/E** : si le fetch d'un pair `https` échoue, une **nouvelle tentative prouvée en
  http** sauve le pull (merge cluster et pull notifié mono-pair).

## [Console v4.0.1] — 2026-09-30 (approx.)

### Correctifs de robustesse (2 défaillances terrain)
- **URLs collées sans séparateur** : `MultiotpHaSlaveUrlList()` détecte toute URL contenant
  une 2ᵉ occurrence de `http` et la re-découpe aux frontières `http(s)://` (le navigateur
  supprime les retours à la ligne du champ *Peer Console URLs*).
- **Cible loopback refusée** : le `Save` refuse d'enregistrer un `own_url` en
  `127.0.0.1`/`localhost` (l'ancienne valeur LAN est conservée) — cause des annonces
  rejetées `loopback_url` après consultation via localhost.

## [Console v4.0] — 2026-09 (cluster multi-maître)

### Ajouté — rôle **Cluster (multi-master, AD-like)**
- Écriture acceptée sur **toute** console du cluster (modèle AD multi-DC).
- Fusion **LWW** (Last-Writer-Wins) déterministe et symétrique (mtime, puis contenu) —
  convergence garantie.
- **Tombstones** : les suppressions se répliquent ; une recréation plus récente l'emporte
  (résurrection).
- **Réplication de la topologie** : la liste des peers se fusionne (union validée) —
  ajouter un nœud n'importe où le propage partout.
- Anti-boucle : notification uniquement si changement local + fusion idempotente +
  auto-exclusion de soi.
- Annonce du `own_url` aux pairs au `Save` + `own_urls_effective` dans `ha-status`.

## [Console v3.10] — 2026-09 (audit sécurité)

### Sécurité
- Jetons HA en **HMAC-SHA256** (`multiotp-ha|action|ts`) — compatibilité dans les deux sens
  avec les consoles v3.9 et antérieures (jeton md5 legacy accepté).
- Le secret partagé n'est **jamais affiché**, jamais stocké en clair, jamais transmis ;
  les jetons peer ne peuvent plus servir d'attaque hors-ligne.
- Audit complet des emplacements des secrets (console, disque, réseau) documenté.

## [Console v3.9] — 2026-09 (deadlock mono-worker)

### Corrigé
- L'esclave répond au push **instantanément** (ACK) et exécute le pull dans un **processus
  détaché** — élimine la cause exacte des « unreachable (Failed to open stream: HTTP request
  failed!) » sur consoles à un seul worker.
- *Send test push* affiche le verdict complet : « ACK + réplication confirmée en X s ».

## [Console v3.8] — 2026-09 (sonde corrective)

- La sonde *Probe URLs* propose et **applique les corrections d'URL** (bouton
  *Apply suggested URLs*), interprète chaque réponse en clair (lignes `hint`), tente le HTTP
  simple quand le TLS est refusé, refuse l'auto-enregistrement d'une cible loopback parasite.

## [Console v3.7] — 2026-09 (correctif bloquant)

### Corrigé — CONSOLE FIGÉE (bug v3.6)
- Des séquences `\n` converties en vrais retours à la ligne par PHP coupaient un regex
  JavaScript → le navigateur rejetait **tout** le bloc script (page figée, Login inerte).
- JS de la sonde réécrit **sans antislash** (`String.fromCharCode(10)`) ; à chaque version :
  JS **rendu** vérifié syntaxiquement (`node --check`) + test navigateur complet.
- Bonus : flèches de menu « NAKB8 » réparées (défaut d'échappement CSS d'origine).

## [Console v3.6] — 2026-09 (sonde de diagnostic)

- Bouton **Probe URLs (diagnostic)** : pour chaque URL peer, teste TCP brut → handshake TLS
  → HTTP réel **depuis le PHP du nœud**, et affiche où la chaîne casse — même sans php_curl.
- `env` : état de curl / allow_url_fopen / openssl.

## [Console v3.1 → v3.5] — 2026-08/09

- **v3.5** : HTTPS auto-signé accepté en secours stream (même politique que cURL) ;
  avertissement cible loopback.
- **v3.4** : cause d'échec lisible (plus masquée par l'URL longue) ; **URL peer normalisée**
  (chemin `/multiotp.server.php` ajouté automatiquement, sans doublon).
- **v3.3** : un push échoué **n'est plus perdu** — révision mémorisée seulement quand
  chaque esclave a répondu (`MultiotpHaNotifyAllReached`), retenté chaque minute,
  avertissement orange « push NOT confirmed » dans le panneau.
- **v3.2** : la cause transport exacte est affichée après « unreachable » (connection
  refused / timed out / no route / DNS).
- **v3.1** : **auto-enregistrement des esclaves** sur le maître (endpoint `HaRegisterSlave`,
  jeton `register`, URLs http(s) uniquement, max 16, dédoublonnées) + avertissement rouge
  « Instant push NOT ready » ; `clearstatcache()` pour la fiabilité de l'empreinte.

## [Console v3.0] — 2026-08 (push événementiel)

### Ajouté
- **PUSH ÉVÉMENTIEL** : tout changement utilisateurs/tokens sur le maître est répliqué
  **immédiatement** (~1 s) sur les esclaves, sans attendre le cron.
- Veille des requêtes console (hook fin de requête) + veille maître `ha-push-check` (1/min,
  couvre les changements hors console : CLI, imports) + cron esclave (filet).
- Empreinte (fingerprint) `users/*.db` + `tokens/*.db` (nom:taille:date) — déduplication
  naturelle des notifications.
- Boutons rôle-aware : *Create/update scheduled task* installe la tâche du rôle courant.
- **Réplique complète** : les suppressions se propagent (fichiers `.db` absents du maître
  retirés de l'esclave, compteur « deleted »).
- Endpoints `HaGetRevision` (`rev|<empreinte>`) et `HaNotifyPull` (idempotent).

## [Console v2.0] — 2026-08 (intervalle fonctionnel)

- **Intervalle cron esclave réellement appliqué** (défaut v1 : réglage sans effet) :
  tâche planifiée `HaInstallTask` (schtasks / cron.d) chaque minute, comparaison de
  l'intervalle à chaque tir (changer l'intervalle sans recréer la tâche — test T10),
  filet « lazy » au chargement de page.
- CLI dédiée : `ha-cron-pull`, `ha-push-check`, `ha-sync-now`, `ha-status`,
  `ha-install-task`.
- Sécurité : jetons `md5(action|secret|ts)` ±5 min, payload AES-256-CBC, noms de fichiers
  validés, empreinte de clé vérifiée.
- **130/130 assertions** de revérification (HA 38/38, multi-admin 40/40, LDAP 19/19,
  intégrité 33/33).

## [Console v1.0 — multi-administrateurs] — 2026-07 (première extension)

### Ajouté
- Gestion multi-comptes administrateurs dans la console (section *Administrator accounts*).
- Rôles `super_admin` / `admin` ; verrouillage 5 échecs/600 s + Unlock ;
  garde `lastSuperAdmin` ; compte natif `admin` (built-in) non désactivable/supprimable ;
  anti-auto-suppression ; *Change my password* avec vérification du mot de passe actuel.
- Stockage chiffré `multiotp-admin-accounts.json` (AES-256-CBC), mots de passe bcrypt,
  journalisation avec IP.
- 9 méthodes AJAX (`AdminWhoAmI`, `ListAdmins`, `AddAdminAccount`, `ModifyAdminAccount`,
  `EnableAdminAccount`, `DisableAdminAccount`, `UnlockAdminAccount`,
  `DeleteAdminAccount`, `ChangeOwnAdminPassword`).
- Patch byte-level en 10 opérations ancrées (fichier original mixte CRLF/LF + octets
  latin-1) ; **57/57 assertions** + validation navigateur réel.

## [Recherche utilisateurs v1.0] — 2026-10 (extension GUI)

- Champ `Search a user...` live dans la barre « List of users » : filtre à la frappe,
  compteur `(n of N users)`, croix, `Échap`, message no-match, filtre persistant après
  rafraîchissement. 100 % navigateur (aucune requête AJAX).
- 4 ajouts localisés marqués `Users search extension` — **aucun autre octet ne change**
  (moteur HA, multi-admin, API intacts) ; 10/10 tests + navigateur réel
  (23 utilisateurs, filtre, compteur, Echap, no-match).

## [Kit diagnostic/réparation v2] — 2026-10

- 8 scripts PowerShell auto-localisants (`1_DIAG`, `2_FIX_NGINX` avec rollback auto,
  `3_STOP_HA_STORM`, `4_HA_API_TEST` aux tokens exacts v4.0, `5_WATCH_LOGS`,
  `6_FIND_SERVICES`, `7_POOL_PHP`, `8_CHECK_FINAL` à 10 contrôles verdictels) + runbook
  complet. Lint statique 9/9, ASCII pur, dry-run par défaut.

## [Paquet administration client v3.0] — 2026-09

- Administration client RADIUS/Credential Provider pour Windows : MSI + exe
  (variante espagnole masquée corrigée), `clients.conf` RADIUS corrigé (piège du client
  fantôme `222.222.222.222` documenté — `127.0.0.1` actif, catch-all supprimé), scripts
  de configuration LDAP (`1_configurer_ldap.cmd`) et de réparation (script 7),
  SHA-256 internes 17/17.

## Références de tests cumulées

| Suite | Script | Assertions |
|---|---|---|
| HA v2 (pull, intervalle, jetons) | `test_ha_pull.py` | 38/38 |
| Régression multi-admin | `test_userfile_multiadmin.py` | 40/40 |
| LDAP/RADIUS (chemin config, portes sync) | `verify_repair_logic.php` | 19/19 |
| Intégrité + faits du code officiel | `reverify_architecture.py` | 33/33 |
| Extension HA complète (v3.x→v4.x) | bancs dédiés | 281 vertes |
| Multi-admin v1 | `test_multiadmin.sh` | 57/57 |
| Recherche utilisateurs | `test_users_search.py` + navigateur | 10/10 |
