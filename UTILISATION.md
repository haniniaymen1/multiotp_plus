# Guide d'utilisation — Console multiOTP enrichie

> Ce guide couvre l'usage quotidien de la console d'administration enrichie
> (**multiOTP 5.10.2.2 + extension HA v4.0.2**) : gestion des administrateurs, des utilisateurs
> et des tokens, panneau de haute disponibilité, recherche, diagnostic et exploitation.
> Pour l'installation, voir [INSTALLATION.md](INSTALLATION.md) ; pour l'architecture,
> voir [docs/architecture.md](docs/architecture.md).

## 📋 Table des matières

1. [Connexion à la console](#1-connexion-à-la-console)
2. [Tour de l'interface](#2-tour-de-linterface)
3. [Gestion des administrateurs](#3-gestion-des-administrateurs)
4. [Matrice des rôles](#4-matrice-des-rôles)
5. [Gestion des utilisateurs et enrolment OTP](#5-gestion-des-utilisateurs-et-enrolment-otp)
6. [Le panneau High Availability](#6-le-panneau-high-availability)
7. [Recherche utilisateurs](#7-recherche-utilisateurs)
8. [Codes d'erreur et messages courants](#8-codes-derreur-et-messages-courants)
9. [Exploitation quotidienne](#9-exploitation-quotidienne)

---

## 1. Connexion à la console

- **URL** : `http://<IP-LAN-du-nœud>:8112/multiotp.server.php` — ⚠️ toujours par l'**IP LAN**
  du nœud, jamais via `localhost`/`127.0.0.1` (l'URL propre du nœud serait enregistrée en
  loopback et rejetée par les pairs).
- **Comptes acceptés** :
  - le compte natif **`admin`** (porte de secours permanente, mot de passe défini via
    *Change admin password*) ;
  - tout **compte administrateur additionnel** créé dans la section *Administrator accounts*
    (voir §3) — il utilise le même écran de connexion.
- **Verrouillage** : 5 échecs de mot de passe → compte verrouillé 600 secondes. Un
  super-administrateur peut débloquer immédiatement (bouton **Unlock**).
- En cas de page figée ou d'affichage bizarre après un changement de fichier :
  **Ctrl+F5** (le HTML de la console est mis en cache par le navigateur).

---

## 2. Tour de l'interface

La console reprend l'ergonomie multiOTP (menus latéraux, panneaux) enrichie de trois
apports visibles :

| Zone | Contenu | Ajout du projet |
|---|---|---|
| Menus latéraux | Users, Users' tokens, Configuration, Backup, Log… | + section **Administrator accounts** |
| Panneau « List of users » | liste des comptes (pills AD/LDAP, locked, delayed…) | + **champ de recherche live** dans la barre de titre |
| Panneau « High availability (master / slave / cluster) » | configuration et diagnostic de la réplication | entièrement enrichi (v2 → v4.0.2) |
| Ligne d'état HA | version de l'extension, rôle, derniers pull/push | `HA extension: v4.0.2` |

---

## 3. Gestion des administrateurs

Section **Administrator accounts** (entre « Change admin password » et « Import new hardware
tokens »). Visible uniquement des **super-administrateurs**.

### Actions disponibles

| Action | Description |
|---|---|
| **Add a new administrator account** | création : nom, rôle (`Super administrator` / `Administrator`), mot de passe + confirmation |
| **Modify** | changement du mot de passe et/ou du rôle d'un compte |
| **Disable / Enable** | désactivation (connexion refusée) / réactivation |
| **Unlock** | déverrouillage immédiat d'un compte verrouillé après échecs |
| **Delete** | suppression définitive du compte |
| **Change my password** | chaque administrateur change son propre mot de passe (vérification du mot de passe actuel) |

### Règles de protection

- Le compte natif `admin` est marqué **(built-in)** : il ne peut être ni désactivé ni supprimé
  (son mot de passe se change via *Change admin password*).
- Vous ne pouvez **ni désactiver ni supprimer votre propre compte** (marqué **(you)**).
- La console refuse toute opération qui laisserait **zéro super-administrateur actif**
  (protection `lastSuperAdmin`).
- Toute action est **journalisée** dans `multiotp.log` avec l'adresse IP source.
- Stockage : `config\multiotp-admin-accounts.json`, **chiffré AES-256-CBC** — aucun nom
  d'utilisateur ni hachage lisible en clair sur le disque.

---

## 4. Matrice des rôles

Quatre identités coexistent dans le système. La matrice ci-dessous résume **qui peut faire quoi** :

### 4.1 Console d'administration

| Capacité | Super administrator (super_admin) | Administrator (admin) | admin natif (built-in) |
|---|---|:-:|:-:|
| Voir la liste des utilisateurs | ✅ | ✅ | ✅ |
| Créer / modifier / supprimer un utilisateur | ✅ | ✅ | ✅ |
| Enroler un token (QR TOTP, hardware) | ✅ | ✅ | ✅ |
| Lancer un test d'authentification | ✅ | ✅ | ✅ |
| Modifier la configuration générale | ✅ | ✅ | ✅ |
| Sauvegarde / restauration (Backup) | ✅ | ✅ | ✅ |
| Consulter les logs | ✅ | ✅ | ✅ |
| Configurer le panneau HA (Save, test push, probe…) | ✅ | ✅ | ✅ |
| **Voir la section Administrator accounts** | ✅ | ❌ (liste refusée) | ✅ |
| **Créer / modifier / désactiver / supprimer un admin** | ✅ | ❌ | ✅ |
| **Débloquer un admin verrouillé** | ✅ | ❌ | ✅ |
| Changer son propre mot de passe | ✅ | ✅ | ✅ |
| Être verrouillé après 5 échecs | oui (Unlock par un autre super-admin) | oui | **non verrouillable** |
| Désactivation / suppression de son compte | interdit pour soi-même | interdit pour soi-même | impossible |

> ⚠️ **Invariant** : il doit **toujours** exister au moins un super-administrateur actif —
> toute rétrogradation/désactivation/suppression qui violerait cette règle est refusée par
> la console.

### 4.2 Comptes d'authentification (utilisateurs finaux)

| Capacité | Compte local multiOTP | Compte synchronisé AD/LDAP |
|---|---|---|
| Créé par | console / CLI / import CSV | **import LDAP** (`ldap-users-sync`) |
| Facteur 1 | **PIN** (ex. `4821`) | **mot de passe Windows** |
| Facteur 2 | code OTP | code OTP |
| Disponible si AD coupé | ✅ | le facteur 1 reste le dernier mot de passe AD connu côté multiOTP |
| Pills visibles dans la console | — | `AD` / `LDAP` |

---

## 5. Gestion des utilisateurs et enrolment OTP

### Créer un utilisateur et enroler son TOTP

1. Menu **Users** → **Add a new user** → saisir le nom d'utilisateur.
2. Ouvrir la fiche de l'utilisateur → **Create a new token** (software TOTP).
3. Scannez le **QR code** avec l'application (Google Authenticator, Microsoft Authenticator,
   FreeOTP…) → le code de 6 chiffres tourne toutes les 30 secondes.
4. Testez immédiatement : bouton **Check a user's OTP** → saisir `PIN` + code
   (compte local) ou `mot de passe AD` + code (compte AD).
5. En cluster : le compte est **répliqué aux pairs en ~1 seconde** (notification instantanée).

### Synchroniser les comptes AD

- Manuel : CLI `multiotp.exe -ldap-users-sync` sur un nœud → les nouveaux comptes AD sont
  importés puis **répliqués automatiquement** sur les pairs.
- Planifié : la tâche « multiOTP LDAP sync » (voir INSTALLATION.md §4) fait l'import
  périodique ; chaque changement détecté part en push.
- Dans la console, les comptes AD portent une pill **AD/LDAP** ; ils s'authentifient avec
  **mot de passe Windows + OTP** (jamais le PIN).

### Suppression

Supprimer un utilisateur dans la console → tombstone répliqué : il disparaît des pairs
en ~1 s. Une recréation **plus récente** « ressuscite » le compte partout (LWW).

---

## 6. Le panneau High Availability

Panneau central de la réplication. Tous les boutons agissent **sur le nœud que vous
consultez** — il est normal d'ouvrir deux onglets (un par nœud) pour vérifier les deux côtés.

### Champs de configuration

| Champ | Rôles | Description |
|---|---|---|
| **Role of this server** | les deux | `Disabled` / `Master` / `Slave` / **`Cluster (multi-master, AD-like)`** |
| **Master console URL** | Slave | URL complète de la console maître (mode maître/esclave classique) |
| **HA shared secret** | les deux | secret partagé — **identique partout** ; laisser vide = conserver (jamais réaffiché) |
| **Synchronization interval in minutes (slave cron)** | Slave | 1–1440 min entre deux pulls périodiques (filet de sécurité) |
| **Instant push on data change (master)** | Master | coché = notifier les esclaves dès un changement |
| **Peer Console URLs (Master/Slaves, Cluster Peers, comma-separated)** | Master / Cluster | URLs des **autres** nœuds, **séparées par des virgules**. Remplie automatiquement par l'auto-enregistrement des pairs (un `Save` côté pair suffit) — saisie manuelle possible. ⚠️ En mode Cluster, cette liste se **réplique aussi** (union validée) : ajoutez un nœud quelque part, il se propage partout |

> 💡 **Comportement attendu** : le `Save` doit **remplacer** la valeur saisie du champ
> *Peer Console URLs* — jamais la dupliquer. **Si votre version duplique la valeur à chaque
> clic** (le champ grossit à chaque Save), **contournement immédiat** : resaisissez la liste
> complète correcte puis `Save`, et vérifiez le résultat dans `ha-status`
> (`slave_urls_effective`, liste dédoublonnée). L'auto-enregistrement des pairs, lui, ajoute
> une URL seulement si elle manque (dédoublonnée, plafonnée à 16 cibles).

### Boutons

| Bouton | Ce qu'il fait | Quand l'utiliser |
|---|---|---|
| **Save** | enregistre la configuration du rôle ; capture et **vérifie** l'`own_url` (vrai HaPing, auto-guérison du schéma http/https, refus du loopback) | à chaque changement de réglage |
| **Test peer** | teste la joignabilité de l'URL maître/pair | diagnostic rapide |
| **Sync now** | pull **forcé** (sans attendre l'intervalle) | première réplique, rattrapage immédiat |
| **Send test push** | notifie chaque peer et **affiche sa réponse** (`up_to_date`, `applied:{...}`, `unreachable (cause)`, `bad_token`, `not_slave`) | vérification du push ; en cluster, montre le verdict complet « ACK + réplication confirmée en X s » |
| **Probe URLs (diagnostic)** | pour chaque URL peer : **TCP** → **TLS** → **HTTP** exécutés depuis le PHP du nœud ; affiche où la chaîne casse + lignes `hint` | quand le push échoue et qu'on veut savoir *pourquoi* |
| **Apply suggested URLs** | applique les corrections d'URL proposées par la sonde (ex. passer de `https` à `http` prouvé) | après un probe qui suggère un schéma |
| **Create/update scheduled task** | installe la tâche planifiée **adaptée au rôle** (veille 1/min : `ha-push-check` en master/cluster, `ha-cron-pull` en slave) | à l'installation et après tout changement de rôle |

### Ligne d'état

- `HA extension: v4.0.2` — version déployée (si vous voyez autre chose, le fichier n'a pas
  été remplacé ou le cache n'a pas été vidé : **Ctrl+F5**).
- Maître/Cluster : dernier push + **avertissement orange** « Data changed but push NOT
  confirmed by every peer – automatic retry every minute » tant qu'un pair est injoignable.
- Slave : dernier pull + révision appliquée.
- Avertissements rouges explicites : push non prêt, cible loopback détectée, secret manquant…

### Endpoints machine-à-machine (protégés par jeton, sans session)

| Endpoint | Servi par | Usage |
|---|---|---|
| `HaPing` | chaque nœud | test de vie (`pong` + révision) |
| `HaGetRevision` | chaque nœud | `rev|<empreinte>` — vérification économique |
| `HaGetBackup` | maître/pair | sauvegarde chiffrée AES-256-CBC |
| `HaNotifyPull` | chaque nœud | notification « change » → vérifie la révision → pull si besoin |
| `HaCronPull` | slave | pull pour planificateur HTTP |
| `HaRegisterSlave` | maître | auto-enregistrement d'un esclave (jeton `register`) |

Sécurité : jetons **HMAC-SHA256** (v3.10) + jeton md5 legacy accepté pour compatibilité
descendante ; validité **±5 min** ; payload chiffré ; noms de fichiers validés (aucune
traversée) ; secret jamais renvoyé en clair.

---

## 7. Recherche utilisateurs

Dans la barre **« List of users »**, champ `Search a user...` (côté droit) :

| Élément | Comportement |
|---|---|
| Saisie | filtre **à la frappe**, insensible à la casse, sous-chaîne du **nom d'utilisateur** |
| Compteur | `(23 users)` sans filtre → `(3 of 23 users)` avec filtre |
| Croix ✕ | apparaît dès qu'il y a du texte ; un clic efface |
| Touche `Échap` | efface et réaffiche tout |
| « No user matches the search… » | si aucun résultat (et seulement s'il existe des utilisateurs) |
| Persistance | le filtre **survit** aux rafraîchissements de la liste (ex. après suppression) |

100 % navigateur : aucune requête AJAX, aucun impact serveur, fonctionne sur des listes de
600 comptes.

---

## 8. Codes d'erreur et messages courants

### Authentification (logs RADIUS / multiOTP)

| Code | Signification | Action |
|---|---|---|
| `99` | échec d'authentification — **souvent erreur LDAP de configuration** | voir [docs/depannage.md](docs/depannage.md) § LDAP erreur 99 |
| `90` | échec d'authentification **LDAP** (liaison AD) | vérifier `ldap-bind-user/password`, port 389 ouvert |
| `26` | token **déjà utilisé** (anti-rejeu) | normal si resoumission du même code |
| `33` | **PIN manquant** | compte local : saisir `PIN` puis le code OTP |
| `-` /échec | compte inexistant sur ce serveur | réplication pas encore passée ? *Sync now* / vérifier le bassecour |

### Panneau HA / journaux

| Message | Signification | Correctif |
|---|---|---|
| `up_to_date` | le peer est déjà à la bonne révision | rien à faire (c'est le succès) |
| `applied:{...}` | le peer a appliqué le pull | succès |
| `unreachable (… Connection refused)` | rien n'écoute sur le port du peer (console arrêtée / port faux) ou REJECT pare-feu | démarrer la console peer ; `netstat -ano \| findstr :8112` → `LISTENING` |
| `unreachable (… timed out)` | pare-feu DROP ou machine éteinte | ouvrir TCP 8112 en entrée sur le peer |
| `unreachable (… No route to host)` | hôte absent du réseau (échec ARP) | vérifier l'IP réelle (`ipconfig`) et le VLAN |
| `unreachable (… Could not resolve host)` | nom d'hôte invalide dans la liste des peers | mettre l'adresse IP directe |
| `unreachable (… HTTP request failed!)` après TCP ok | le port parle probablement **HTTPS** | mettre `https://...` (auto-signé accepté) ou corriger la conf web |
| `bad_token` | secret différent entre nœuds **ou horloges désynchronisées (>5 min)** | aligner le secret ; activer NTP |
| `not_slave` / `not_master` | rôle mal enregistré côté cible | re-Save le rôle sur le nœud cible |
| `loopback_url` (refus au Save) | tentative d'enregistrer `127.0.0.1`/`localhost` comme own_url/peer | consulter la console par son **IP LAN** |
| URLs collées sans séparateur | re-découpage automatique v4.0.1 aux frontières `http(s)://` | vérifier la liste après Save |

### Matrice de lecture de la sonde « Probe URLs »

| TCP | TLS | HTTP | Conclusion |
|---|---|---|---|
| ok | FAILED | FAILED | le port parle HTTP mais pas TLS → utiliser `http://` |
| ok | ok | FAILED | le port parle TLS mais le HTTP échoue → `https://` (auto-signé OK en v3.5+) ; sinon endpoint |
| ok | FAILED | ok | console en HTTP — tout va bien |
| FAILED | — | — | réseau : hôte éteint / port fermé / pare-feu (voir `9_diagnostic_push.cmd`) |

---

## 9. Exploitation quotidienne

### Routine recommandée

| Fréquence | Vérification |
|---|---|
| **Quotidien** | statut HA : ligne d'état verte sur les deux nœuds (pas d'avertissement push en attente) |
| **Quotidien** | log nginx : pas de pluie de `499`/`5xx` (`5_WATCH_LOGS.ps1`) |
| **Hebdo** | `8_CHECK_FINAL.ps1` → verdict **CONFORME** (10 contrôles) |
| **Hebdo** | import LDAP : pas d'erreur dans le log après chaque tir planifié |
| **Mensuel** | test fonctionnel de bout en bout : créer un user test → visible sur le pair en ~1 s → supprimer → propagé |
| **Mensuel** | vérifier NTP des deux nœuds (dérive < 5 min sinon `bad_token`) |

### Commandes CLI utiles (sur un nœud)

```bat
cd C:\multiotp
php multiotp.server.php ha-status        :: état JSON complet du nœud (peers, révision, dernier pull/push)
php multiotp.server.php ha-sync-now      :: pull forcé
php multiotp.server.php ha-push-check    :: détecte les changements locaux et notifie les pairs
php multiotp.server.php ha-install-task  :: installe/affiche la tâche planifiée du rôle courant
multiotp.exe -ldap-users-sync            :: import AD manuel
multiotp.exe -userslist                  :: liste des utilisateurs
```

### Supervision minimale (3 points suffisants)

1. Service **multiOTPradius** démarré et port **1812 en écoute** sur chaque serveur.
2. **Statut HA** (`ha-status` ou panneau) montrant un last_pull/last_push **récent** sur chaque nœud.
3. **Absence d'erreur LDAP** dans le journal après chaque import planifié.

### Réagir à une panne de site

| Scénario | Effet automatique | Action |
|---|---|---|
| Un nœud tombe | les postes basculent sur le serveur suivant de `multiOTPServers` (~5 s) | rien ; réparer le nœud ; à son retour, la réplication rattrape automatiquement (retenté chaque minute) |
| Le WAN coupe entre sites | **chaque site continue d'authentifier ses utilisateurs** avec sa copie locale | rien ; au retour, fusion LWW converge |
| Perte définitive d'un nœud | l'autre continue seul (écriture acceptée) | re-construire le pair disparu (réinstaller, joindre le cluster, `Sync now`) |
| Les deux nœuds tombent | postes avec **cache CP activé** : authentification hors ligne possible | restaurer un nœud, puis laisser la cache resynchroniser |

### Bonnes pratiques

- **Secrets** : RADIUS et HA ≥ 16 caractères mélangés, jamais réutilisés entre environnements.
- **NTP** sur les deux nœuds (les jetons HA expirent à ±5 min).
- **Sauvegardes** : le bouton Backup de la console + copie hors site de `C:\multiotp\config\`
  (fichiers chiffrés) ; la réplication n'est pas un backup (elle réplique aussi les erreurs).
- **Changements** : tout peut être fait depuis **n'importe quel nœud** du cluster — c'est
  voulu (modèle AD). Évitez simplement de faire deux modifications contradictoires du même
  compte à la même seconde sur deux nœuds (LWW tranchera par mtime).
- **Mises à jour de la console** : déployez toujours la même version de `multiotp.server.php`
  sur tous les nœuds, un nœud après l'autre, sans interruption de service (Ctrl+F5 suffit).

