# multiOTP Windows — HA Cluster & Console enrichie

**Langue / Language :** **Français** | [English](README.md)

> **Surcouche communautaire pour multiOTP 5.10.2.2 open source (Windows)** : console d'administration
> enrichie (multi-administrateurs, cluster haute disponibilité multi-maître « à la Active Directory »,
> recherche utilisateurs live), guides de déploiement complet 3 sites / 600 postes avec RADIUS,
> synchronisation AD/LDAP et Credential Provider.

![Version](https://img.shields.io/badge/console-v4.0.2-blue) ![multiOTP](https://img.shields.io/badge/multiOTP-5.10.2.2-green) ![Plateforme](https://img.shields.io/badge/plateforme-Windows%20Server-lightgrey) ![Statut](https://img.shields.io/badge/tests-281%20assertions%20vertes-brightgreen) ![Langue](https://img.shields.io/badge/docs-franche%CC%A7ais-orange)

---

## 📋 Table des matières

1. [Le projet en deux mots](#-le-projet-en-deux-mots)
2. [Infrastructure cible](#-infrastructure-cible)
3. [Ce qui change par rapport au multiOTP d'origine](#-ce-qui-change-par-rapport-au-multiotp-dorigine)
4. [Fonctionnalités](#-fonctionnalités)
5. [Architecture](#-architecture)
6. [Démarrage rapide](#-démarrage-rapide)
7. [Structure du dépôt](#-structure-du-dépôt)
8. [Documentation](#-documentation)
9. [Livrables téléchargeables](#-livrables-téléchargeables)
10. [Compatibilité](#-compatibilité)
11. [Avertissements importants](#-avertissements-importants)
12. [Crédits et licences](#-crédits-et-licences)

---

## 🎯 Le projet en deux mots

Le **multiOTP open source** (SysCo) est un serveur d'authentification forte (2FA) excellent, mais sa
console web n'accepte qu'un **seul compte administrateur** et n'offre **aucune haute disponibilité**.
Ce projet répond aux besoins d'une PME de **600 postes répartis sur 3 sites** qui devait :

| Besoin exprimé | Réponse apportée |
|---|---|
| Plusieurs administrateurs IT avec des droits différenciés | **Extension multi-administrateurs** (rôles super-admin / admin, verrouillage, chiffrement des comptes) |
| Plusieurs serveurs qui se répliquent « comme des contrôleurs de domaine AD » : écriture partout, mise à jour de tous les nœuds | **Extension HA v4.0.2 — Cluster multi-maître (AD-like)** : fusion LWW, tombstones, notification instantanée |
| Trouver un utilisateur dans une liste de 600 | **Extension recherche utilisateurs v1.0** (filtre live côté navigateur) |
| Diagnostiquer les incidents de réplication et de réseau | **Kit de diagnostic/réparation** : 8 scripts PowerShell auto-localisants + sonde intégrée « Probe URLs » |
| Déployer le Credential Provider sur 600 postes avec bassecour par site | **Paquet d'administration client v3.0** (MSI + exe + guides + scripts) |

Tout est implémenté comme une **surcouche d'un seul fichier** (`multiotp.server.php`) : le cœur
multiOTP 5.10.2.2 reste strictement intact (aucun octet de la classe officielle n'est modifié),
ce qui garantit la compatibilité avec les montées de version et le support officiel.

---

## 🏢 Infrastructure cible

> ⚠️ Les adresses ci-dessous sont celles de l'environnement de référence. **Adaptez-les à votre réseau.**

| Élément | Adresse / valeur | Rôle |
|---|---|---|
| multiOTP nœud A (Windows) | `192.168.40.144` | Serveur multiOTP + nginx/PHP-CGI sur **port 8112** + RADIUS UDP 1812/1813 |
| multiOTP nœud B (Windows) | `192.168.40.147` | Miroir du nœud A — **cluster multi-maître** (écriture acceptée sur les deux) |
| Contrôleur de domaine AD | `192.168.40.145` | AD/LDAP **port 389** — source de la synchronisation des comptes |
| Poste client de test | `192.168.40.140` | Windows avec **Credential Provider** multiOTP déployé |
| Console web | `http://<IP>:8112/multiotp.server.php` | Interface d'administration (HTTP simple sur le LAN) |
| RADIUS | UDP **1812** (auth) / **1813** (accounting) | Service `multiOTPradius` intégré au paquet Windows |
| Synchronisation LDAP | AD → multiOTP, port 389 | Import planifié `ldap-users-sync` (comptes AD + OTP) |

**Modèle d'authentification :**

| Type de compte | Identifiant | Facteur 1 | Facteur 2 |
|---|---|---|---|
| Compte **local** multiOTP | nom d'utilisateur | **PIN** (ex. `4821`) | Code **OTP** (TOTP 6 chiffres) |
| Compte **synchronisé AD** | login Windows | **Mot de passe AD** | Code **OTP** (TOTP 6 chiffres) |

---

## 🆚 Ce qui change par rapport au multiOTP d'origine

La console web officielle du multiOTP 5.10.2.2 open source est livrée avec **un seul compte
`admin` intégré, aucune gestion d'administrateurs, aucun panneau haute disponibilité et aucune
recherche d'utilisateurs** (vérifié sur la console officielle, md5 `92e1f9676031be9c9a7c6ae5fc39018f`).
Tout ce qui suit est **ajouté par-dessus** : la console officielle reste intégralement disponible,
et le cœur multiOTP (`multiotp.class.php`) n'est **pas modifié d'un seul octet** — chaque extension
est un ajout localisé de `multiotp.server.php` uniquement.

### A. Modifications de l'interface (console d'origine → console enrichie v4.0.2 + recherche v1.0)

| Zone de l'interface | multiOTP 5.10.2.2 officiel | Cette édition |
|---|---|---|
| Accès administrateur | Compte `admin` unique intégré | Section **« Administrator accounts »** : ajout / modification / désactivation / réactivation / suppression / déblocage de comptes admin **depuis la console** |
| Rôles | Aucun (un seul compte = tous les droits) | **Super administrator** (tout, y compris la gestion des comptes) et **Administrator** (utilisateurs, tokens, diagnostic, sauvegarde) — contrôle appliqué **côté serveur** sur chaque AJAX sensible |
| Stockage des comptes | — | `multiotp-admin-accounts.json` chiffré **AES-256-CBC** (aucun nom en clair), mots de passe **bcrypt**, verrouillage après 5 échecs (600 s), dernier super-admin actif protégé, compte natif `admin` conservé en secours permanent |
| Haute disponibilité | Absente de la console OSS (configuration en CLI uniquement) | Panneau **« High availability »** : rôle *Off / Slave / Master / **Cluster (multi-master, AD-like)***, secret partagé, champ **« Peer console URLs (master slaves / cluster peers, comma-separated) »**, `Save` auto-enregistre les pairs, boutons **Create/update scheduled task** et **Send test push**, ligne d'état « HA extension: v4.0.2 » |
| Réplication | — | Push événementiel **~1 s**, retenté chaque minute, fusion **LWW** (Last-Writer-Wins), **tombstones**, réplication de la liste des pairs, anti-boucle |
| Diagnostic réseau | — | Sonde intégrée **Probe URLs** (TCP → TLS → HTTP) + correction **Apply suggested URLs** en un clic, causes transport explicites (`Connection refused`, `timed out`…) |
| Liste des utilisateurs | Simple liste « List of users (N users) » | Champ live **« Search a user... »** : filtre à la frappe insensible à la casse, compteur « (3 of 23 users) », croix d'effacement, touche Échap, message no-match, filtre persistant après rafraîchissement |
| Moteur multiOTP | `multiotp.server.php` (GUI) + `multiotp.class.php` | **Cœur identique à l'octet près** : GUI enrichie + couche HA + couche multi-admin dans `multiotp.server.php` seulement ; API, RADIUS, chiffrement des configs officiels intacts |

### B. Fonctions ajoutées (résumé)

1. **Gestion multi-administrateurs à rôles** (super-admin / admin) — détail au § Fonctionnalités 1
2. **Cluster haute disponibilité multi-maître AD-like v4.0.2** (LWW, tombstones, push ~1 s) — détail au § Fonctionnalités 2
3. **Recherche utilisateurs live v1.0** (100 % navigateur, zéro requête serveur) — détail au § Fonctionnalités 3
4. **Kit de diagnostic/réparation HA** (8 scripts PowerShell auto-localisants) — détail au § Fonctionnalités 4
5. **Paquet d'administration client v3.0** (Credential Provider, MSI + MST + GPO 600 postes) — détail au § Fonctionnalités 5

---

## ✨ Fonctionnalités

### 1. Gestion multi-administrateurs

- Ajout / modification / désactivation / réactivation / suppression / déblocage de comptes
  administrateurs **depuis la console web** (section *Administrator accounts*).
- Deux rôles : **Super administrator** (tout, y compris la gestion des comptes) et
  **Administrator** (utilisateurs, tokens, diagnostic, sauvegarde — sans accès aux comptes).
- Stockage **chiffré** sur disque (`multiotp-admin-accounts.json`, AES-256-CBC, aucun nom en clair),
  mots de passe en **bcrypt** côté serveur.
- **Verrouillage automatique après 5 échecs** (600 s), protection du dernier super-admin actif,
  compte natif `admin` conservé comme porte de secours permanente, interdiction d'auto-suppression.
- Journalisation de chaque action dans `multiotp.log` (avec adresse IP).

### 2. Haute disponibilité — Cluster multi-maître (AD-like) v4.0.2

Le rôle **Cluster (multi-master, AD-like)** transforme chaque console en équivalent d'un
contrôleur de domaine :

| Active Directory (multi-DC) | multiOTP v4.0 (rôle Cluster) |
|---|---|
| Écriture acceptée sur n'importe quel DC | Création / modification / suppression d'utilisateurs sur **toute** console |
| Réplication incrémentale entre DC | **Fusion LWW** (Last-Writer-Wins) : le fichier au mtime le plus récent gagne |
| Suppression propagée (tombstone) | **Tombstones** : la suppression se réplique ; une recréation plus récente l'emporte |
| Convergence garantie | Décision LWW **déterministe et symétrique** (mtime, puis contenu) |
| Topologie complète (KCC) | **La liste des pairs se réplique aussi** (union validée) |
| Anti-boucle de réplication | Notification uniquement si changement local + fusion idempotente + auto-exclusion de soi |

Historique des capacités HA (de v2 à v4.0.2) :

- **Push événementiel** : tout changement sur un nœud est répliqué **instantanément (~1 s)**,
  sans attendre le cron ; un push échoué est **retenté chaque minute** (rien n'est perdu).
- **Auto-enregistrement des pairs** au `Save` (zéro saisie d'URL manuelle).
- **Diagnostic explicite** : cause transport lisible (`Connection refused`, `timed out`,
  `No route to host`…), sonde intégrée **Probe URLs** (TCP → TLS → HTTP) et correction
  d'URL en un clic (*Apply suggested URLs*).
- **Deadlock mono-worker éliminé** (v3.9) : l'esclave répond instantanément (ACK) puis
  réplique dans un processus détaché.
- **Sécurité durcie** (v3.10) : jetons en **HMAC-SHA256**, secret partagé jamais affiché,
  jamais stocké ni transmis en clair ; payload **AES-256-CBC**.
- **Correctifs terrain** (v4.0.1 / v4.0.2) : URLs collées sans séparateur re-découpées,
  cible loopback refusée, correction du « phantom https » (auto-guérison du schéma).

### 3. Recherche utilisateurs v1.0

Champ **`Search a user...`** dans la barre « List of users » : filtre **à la frappe**, insensible
à la casse, compteur `(3 of 23 users)`, croix d'effacement, touche `Échap`, message
« No user matches the search… ». Fonctionnement **100 % navigateur** : aucune requête AJAX
supplémentaire, aucun impact sur php-cgi. Le filtre survit aux rafraîchissements de la liste.

### 4. Kit de diagnostic / réparation HA (`multiotp-ha-fix-complet-v2.zip`)

8 scripts PowerShell **auto-localisants** (ASCII pur, dry-run par défaut, backup + rollback) :

| Script | Usage |
|---|---|
| `1_DIAG.ps1` | 9 sections read-only : ports, pool php-cgi, HTTPS/body size, tâches HA, nginx -t, HaPing… |
| `2_FIX_NGINX.ps1` | Commente `HTTPS on`, ajoute `client_max_body_size 10m`, **rollback auto** si `nginx -t` échoue |
| `3_STOP_HA_STORM.ps1` | Met en quarantaine les tâches HA (`Rename-Item` + `Disable-ScheduledTask`) |
| `4_HA_API_TEST.ps1` | Teste `ping`/`revision` avec les **tokens exacts v4.0** (md5 + HMAC-SHA256) |
| `5_WATCH_LOGS.ps1` | Surveillance live du log nginx avec chemin auto |
| `6_FIND_SERVICES.ps1` | Localise nginx / php-cgi / multiOTPradius |
| `7_POOL_PHP.ps1` | Applique `PHP_FCGI_CHILDREN=6` (+ `-Apply` / `-InstallTask`) |
| `8_CHECK_FINAL.ps1` | 10 contrôles verdictels `[OK]/[ALERTE]/[ÉCHEC]` + verdict final CONFORME / NON CONFORME |

### 5. Paquet d'administration client v3.0

Client RADIUS multiOTP pour Windows + **Credential Provider** (écran de connexion Windows avec OTP) :
installateur MSI, exécutable d'administration, guides, `clients.conf` RADIUS corrigé
(le piège du client fantôme `222.222.222.222` de l'installeur officiel est documenté et corrigé),
scripts de configuration LDAP et de réparation, déploiement MSI + GPO avec transformation `.mst`.

---

## 🗺️ Architecture

![Architecture 3 sites](docs/img/architecture.png)

**Principes vérifiés par 130 assertions rejouées (voir [docs/architecture.md](docs/architecture.md)) :**

1. **Ordre local d'abord** : chaque poste interroge son serveur multiOTP de site en premier
   (liste `multiOTPServers` du Credential Provider). En cas de timeout (~5 s), bassecour
   automatique vers les serveurs suivants de la liste — chaque site continue d'authentifier
   ses utilisateurs même si le WAN est coupé (failover validé 11/11).
2. **Réplication bidirectionnelle** : en cluster multi-maître, chaque nœud notifie ses pairs
   dès qu'un utilisateur/token change ; les pairs fusionnent en LWW. Trois mécanismes se
   complètent : veille des requêtes console (instantané), veille planifiée 1/min
   (`ha-push-check`), cron pair (filet de sécurité).
3. **Synchronisation AD/LDAP** : import planifié depuis l'AD vers les serveurs multiOTP
   (`ldap-users-sync`) ; les comptes AD s'authentifient alors avec mot de passe Windows + OTP.
4. **RADIUS** : les postes (Credential Provider) s'authentifient en UDP 1812/1813 auprès du
   service `multiOTPradius` local.

Flux détaillés, diagrammes de séquence (connexion utilisateur, flux admin LDAP) et tableau
des flux : **[docs/architecture.md](docs/architecture.md)**.

---

## 🚀 Démarrage rapide

```text
1. Installer le serveur multiOTP officiel 5.10.2.2 (Windows) sur chaque nœud
   → https://download.multiotp.net/  (paquet « Windows », nginx + PHP-CGI inclus)

2. Déployer la console enrichie v4.0.2 (release/multiotp.perso.ha.cluster.multimaster.v402.zip)
   → Sauvegarder, remplacer C:\multiotp\multiotp.server.php, Ctrl+F5 dans le navigateur

3. Configurer le cluster multi-maître
   → Panneau « High availability » : Role = Cluster (multi-master, AD-like),
     même secret partagé partout, Peer Console URLs = les AUTRES nœuds, Save
   → Create/update scheduled task, puis Send test push

Le guide complet (prérequis, nginx, LDAP, RADIUS, Credential Provider, 600 postes) :
👉 [INSTALLATION.md](INSTALLATION.md)
```

---

## 📁 Structure du dépôt

```text
multiotp-windows-ha-cluster/
├── README.md                    ← ce fichier (English)
├── README.fr.md                 ← version française de ce fichier
├── INSTALLATION.md              ← guide d'installation complet (8 étapes)
├── UTILISATION.md               ← guide d'utilisation (console, HA, rôles, diagnostic)
├── CHANGELOG.md                 ← historique des versions v1.0 → v4.0.2
├── LICENSE.md                   ← notes de licence
├── PUBLIER-SUR-GITHUB.md        ← procédure pas à pas pour publier ce dépôt sur GitHub
├── docs/
│   ├── architecture.md          ← architecture détaillée + diagrammes de séquence (Mermaid)
│   ├── securite.md              ← modèle de sécurité, matrice des rôles, bonnes pratiques
│   ├── depannage.md             ← dépannage : push, LDAP erreur 99, RADIUS, nginx, storm
│   └── img/
│       └── architecture.png     ← diagramme de topologie
└── release/                     ← paquets prêts à déployer (SHA-256 dans release/README.md)
    ├── README.md
    ├── multiotp.perso.ha.cluster.multimaster.v402.zip      ← console enrichie complète
    ├── multiotp.recherche-utilisateurs.v1.0.zip            ← extension recherche (base v4.0.2)
    ├── multiotp_5.10.2.2_multiadmin.zip                    ← extension multi-admin seule
    ├── multiotp-ha-fix-complet-v2.zip                      ← kit diagnostic/réparation (8 scripts PS1)
    └── MultiOTP_Administration_Package_Complet_3.0.zip     ← administration client + Credential Provider
```

---

## 📚 Documentation

| Document | Contenu |
|---|---|
| [INSTALLATION.md](INSTALLATION.md) | Prérequis, installation multiOTP, console enrichie, nginx/PHP-CGI, AD/LDAP, RADIUS, cluster HA, Credential Provider, vérifications, rollback |
| [UTILISATION.md](UTILISATION.md) | Connexion, gestion des administrateurs, **matrice des rôles**, utilisateurs et enrolment OTP, panneau HA, recherche, codes d'erreur, exploitation quotidienne |
| [docs/architecture.md](docs/architecture.md) | Topologie 3 sites, flux d'authentification et de réplication, diagrammes de séquence (Mermaid), modèle LWW/tombstones |
| [docs/securite.md](docs/securite.md) | Jetons HMAC-SHA256, chiffrement AES-256-CBC, matrice des rôles complète, secrets à remplacer, pare-feu |
| [docs/depannage.md](docs/depannage.md) | Tables de diagnostic « unreachable », matrice TCP/TLS/HTTP de la sonde, erreur LDAP 99, clients.conf RADIUS, storm HA |
| [CHANGELOG.md](CHANGELOG.md) | Toutes les versions et correctifs, de la v1.0 multi-admin à la v4.0.2 |
| [release/README.md](release/README.md) | Contenu, empreintes SHA-256, ordre de déploiement, vérification d'intégrité |

---

## 📦 Livrables téléchargeables

Les paquets sont **dans ce dépôt** (dossier [`release/`](release/)) — vérifiez l'intégrité après
téléchargement :

```powershell
# Windows (PowerShell)
Get-FileHash .\multiotp.perso.ha.cluster.multimaster.v402.zip -Algorithm SHA256
```

```bash
# Linux
sha256sum multiotp.perso.ha.cluster.multimaster.v402.zip
```

Empreintes de référence : [`release/README.md`](release/README.md).

> 💡 **Conseil** : pour un dépôt public, poussez le code tel quel et **attachez les paquets à une
> GitHub Release** (menu *Releases* → *Draft a new release* → glisser les ZIP) — procédure détaillée
> dans [PUBLIER-SUR-GITHUB.md](PUBLIER-SUR-GITHUB.md).

---

## 🧩 Compatibilité

| Composant | Version |
|---|---|
| multiOTP open source (cœur officiel, **non modifié**) | 5.10.2.2 (2026-04-03) |
| Console enrichie `multiotp.server.php` | HA extension **v4.0.2** |
| PHP | 8.1 – 8.4 (testé 8.4.8 ; php-cgi FastCGI) |
| nginx | toute version Windows (config du paquet officiel) |
| Windows Server | 2012 R2 → 2025 (serveurs) ; Windows 7 → 11 (postes, Credential Provider) |
| Navigateurs | Chrome / Edge / Firefox modernes |
| Déploiements console | Windows (`multiotp.server.php`), Raspberry Pi / Docker (`index.php`) |

---

## ⚠️ Avertissements importants

1. **Remplacez les secrets par défaut** : le secret RADIUS `multiotpsecret` et le secret HA de
   démonstration doivent être changés (≥ 16 caractères mélangés) sur le serveur **et** sur chaque
   client RADIUS déclaré.
2. **NTP obligatoire** : les jetons HA ne sont valides que ±5 minutes — gardez les horloges des
   nœuds à l'heure.
3. **Consultez chaque console par son IP LAN** (`http://<IP-LAN>:8112/`), **jamais** via
   `localhost`/`127.0.0.1` : l'URL propre du nœud serait enregistrée en loopback et rejetée par
   les pairs (garde-fou v4.0.1, mais autant éviter).
4. **Ordre `multiOTPServers` = priorité** : le serveur de site doit être **premier** dans la liste
   de chaque poste ; c'est cet ordre qui décide du bassecour.
5. Le cœur multiOTP reste le produit officiel de **SysCo systemes de communication sa** — ce
   projet ne remplace ni le support officiel ni la documentation du produit : voir
   <https://download.multiotp.net/> et <http://forum.multiotp.net/>.

---

## 🙏 Crédits et licences

| Composant | Auteur / licence |
|---|---|
| **multiOTP** (cœur 5.10.2.2, RADIUS, Credential Provider) | © 2010-2026 SysCo systemes de communication sa — licences d'origine conservées, voir <https://www.multiotp.net/> |
| **multiOTP Credential Provider** | © SysCo / NetKnights / ArcadeJust / Last Squirrel IT — **Apache License 2.0** |
| **Extensions de ce dépôt** (multi-admin, HA cluster, recherche, kit diagnostic, guides) | Projet communautaire interne — voir [LICENSE.md](LICENSE.md) |

Voir aussi [CHANGELOG.md](CHANGELOG.md) pour l'historique complet et
[docs/securite.md](docs/securite.md) pour le modèle de menace couvert.
