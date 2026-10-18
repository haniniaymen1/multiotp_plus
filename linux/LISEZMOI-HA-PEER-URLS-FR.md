# Correctif HA v4.0.3 — Champ « Peer console URLs » dupliqué à chaque clic Save

**Fichier concerné** : `multiotp.server.php` (extension HA, double-maître cluster AD-like)
**Version affichée après correctif** : `HA extension: v4.0.3 (multi-master cluster, AD-like)`
**Base** : v4.0.2 (paquet `multiotp.ldap-admin-acces.v1.2`) — tout le reste du fichier est inchangé.

---

## 1. Symptôme

Dans la console d'administration, onglet **High availability**, champ
**« Peer console URLs (master slaves / cluster peers, comma-separated) »** :
chaque clic sur **Save** ajoute à nouveau la (les) URL(s) déjà enregistrée(s).
Après quelques sauvegardes, le champ contient plusieurs fois la même adresse
(souvent sous une forme légèrement différente, par exemple
`http://192.168.40.147:8112/multiotp.server.php` **et**
`https://192.168.40.147:8112/multiotp.server.php`), et un nœud peut même se
listé **lui-même** dans sa propre liste de pairs.

## 2. Cause racine

Toutes les dé-duplications et exclusions de la configuration HA comparaient
les URL de pairs par **chaîne exacte** (`in_array` / `strcasecmp`) :

1. **Auto-enregistrement** (`HaRegisterSlave`) : un pair qui s'annonce avec une
   *variante* d'une URL déjà connue (scheme `https` fantôme capté par PHP quand
   nginx envoie `fastcgi_param HTTPS on` sur une console en clair, forme courte
   sans `/multiotp.server.php`, casse du nom d'hôte) n'est pas reconnue comme
   doublon → **la variante est ajoutée**.
2. **Fusion cluster** (merge des `peers` du payload) : mêmes comparaisons en
   chaîne exacte → les variantes annoncées par les pairs sont ajoutées à leur
   tour.
3. **Exclusion de soi** (push, pull, merge) : faite par comparaison exacte avec
   l'URL propre du serveur → une *variante* de soi n'est pas reconnue → le
   serveur peut se lister lui-même.
4. À chaque clic **Save** (rôle master/cluster), la console se ré-annonce aux
   pairs ; le cycle « annonce → stockage variante → fusion retour » fait
   **grossir le champ à chaque sauvegarde** — c'est la duplication observée.

## 3. Correctif (v4.0.3)

Nouvelle règle : **une console de pair est identifiée par sa clé canonique
`hôte:port`** (hôte en minuscules, port effectif — 80/443 implicites selon le
scheme). Deux URL de même clé désignent la même console, quelle que soit la
forme écrite. La **première forme enregistrée gagne**, l'ordre est préservé.

| # | Fonction | Changement |
|---|----------|------------|
| 1 | `MultiotpHaUrlKey()` (nouvelle) | Clé canonique `hôte:port` d'une URL de pair |
| 2 | `MultiotpHaDedupeUrls()` (nouvelle) | 1 seule entrée par clé, première forme gagnée, ordre conservé |
| 3 | `MultiotpHaSlaveUrlList()` | Dé-duplication par clé en sortie (en plus de l'unique existant) |
| 4 | `MultiotpHaServeRegister()` | Variante d'un pair déjà connu → réponse `registered:false` (« already known ») au lieu d'un ajout |
| 5 | `MultiotpHaApplyPayload()` (merge peers) | Exclusion de soi **par clé** (aucune variante de soi ne peut être ajoutée) + fusion dé-dupliquée par clé |
| 6 | `MultiotpHaSetConfigFromOptions()` | Le bouton **Save remplace** la liste : la valeur saisie est normalisée et dé-dupliquée avant stockage |
| 7 | `MultiotpHaMasterNotify()` / `MultiotpHaDoPullCluster()` | Auto-exclusion par clé (pas de push/pull vers une variante de soi) |
| 8 | Bandeau de statut JS | Version affichée : `v4.0.3` |

Comportement après correctif :

- Sauvegarder le champ **remplace** la liste (jamais d'ajout caché).
- **Un seul clic Save suffit à nettoyer un champ déjà pollué** par l'ancienne
  version : les doublons et variantes disparaissent du champ au rechargement.
- Le maillage automatique des pairs (union AD-like) est inchangé : les pairs
  réellement nouveaux sont toujours appris par fusion, les variantes d'un pair
  connu sont ignorées.
- La limite de 16 pairs et les refus (loopback, self, trop de pairs) sont
  conservés à l'identique.

## 4. Déploiement

Sur **chacun des deux serveurs** (192.168.40.144 et 192.168.40.147) :

1. Sauvegarder le fichier actuel :
   `copy C:\multiotp\multiotp.server.php C:\multiotp\multiotp.server.php.v402bak`
2. Remplacer `C:\multiotp\multiotp.server.php` par la version fournie ici.
3. Dans la console : **Ctrl+F5** (rechargement sans cache). Aucun redémarrage
   de service n'est nécessaire.
4. Vérifier le bandeau : `HA extension: v4.0.3 (multi-master cluster, AD-like)`.
5. Dans le champ « Peer console URLs », cliquer une fois sur **Save** sans rien
   retoucher : les doublons/variantes éventuels sont purgés. Vérifier ensuite
   que le push/pull reste opérationnel (« Send test push », « Pull now »).

Le patch unifié `multiotp.server.perso-ha-v403.patch` (contre la v4.0.2 du
paquet ldap-admin-acces v1.2) est fourni pour application `patch -p0` si vous
préférez ne pas remplacer le fichier complet.

## 5. Validation

| Banc | Résultat |
|------|----------|
| Tests unitaires `MultiotpHaUrlKey` / `MultiotpHaDedupeUrls` (12 cas : variantes http/https, formes courtes/longues, casse, ports implicites) | 12/12 PASS |
| Scénario d'intégration dédié (2 nœuds cluster) : 3 clics Save mono-nœud, cluster + sync entre clics, navigation via 127.0.0.1, formes courtes, variante https fantôme, upgrade d'une config déjà polluée | 25/25 PASS |
| Régression cluster 3 nœuds (réplication, transitivité, LWW, tombstones, union mesh des pairs, anti-boucle, auth TOTP réelle) | 43/43 PASS |
| Régression push master→slave (auto-enregistrements, refus loopback/self, sonde, pull arrière-plan, HaSlaveStatus) | 94/94 PASS |
| `php -l` (PHP 8.4) | 0 erreur |

## 6. Rappel

- La configuration HA reste chiffrée (AES-256-CBC, format MOH1) ; le secret
  partagé n'est jamais affiché ni renvoyé par la console.
- Cette version n'apporte **aucun autre changement** : le correctif LDAP
  administrator access v1.2, la recherche utilisateurs v1.0 et le multi-admin
  sont inchangés.
