# Correctif HA v4.0.6 — « ui-clean » : masquer la ligne « Instant push on data change (master) »

**Fichier concerné** : `multiotp.server.php` (extension HA, double-maître cluster AD-like)
**Version affichée après correctif** : `HA extension: v4.0.6 (multi-master cluster, AD-like, install-push + recreate-sync + ui-clean fixes)`
**Base** : v4.0.5 (`multiotp.ha.recreate-sync.v4.0.5.zip`) — tout le reste du fichier est inchangé.

---

## 1. Demande

Masquer dans la console la ligne de réglage **« Instant push on data change (master) »**
— le libellé, la case à cocher et le texte explicatif
« (when the users/tokens data change on the master, the slaves are notified at once and
pull immediately) ».

## 2. Ce qui a été fait

- Le `<div class="form-row">` qui porte cette ligne reçoit `style="display:none;"`.
- **La case reste dans le DOM** : le JavaScript de chargement
  (`document.getElementById('ha_push').checked = …`) et de sauvegarde
  (`HaSaveConfig()` lit la même case) fonctionne à l'identique — le réglage
  enregistré (coché dans votre déploiement) continue d'être chargé et soumis.
- **Le comportement Instant Push est inchangé** : à chaque changement de données sur un
  peer, les autres peers sont notifiés et tir immédiatement (c'est ce correctif qui
  rend possible la réplication instantanée des recréations, cf. v4.0.5).
- Bandeau HA : `v4.0.6 (…, install-push + recreate-sync + ui-clean fixes)`.

Note : avec la ligne masquée, l'état du réglage n'est plus modifiable depuis l'interface
(il reste celui qui est enregistré dans la configuration). Pour le changer
exceptionnellement, éditer `config/multiotp-ha-config.json` (`push_enabled`) ou
réafficher la ligne (retirer le `display:none`).

## 3. Installation

Remplacer `multiotp.server.php` par la version v4.0.6 sur chaque nœud (les deux
maîtres), redémarrer nginx/php-cgi. Aucune reconfiguration, aucune migration : la
v4.0.6 ne touche à rien d'autre que l'affichage de cette ligne et les bandeaux.

## 4. Tests (sandbox, PHP 8.4.8) — 89 PASS / 0 FAIL

- **Suite dédiée v4.0.6 (12 PASS)** : la page console servie contient bien la ligne
  avec `display:none`, la case `ha_push` reste dans le DOM avec sa valeur, les
  JavaScripts de chargement et de sauvegarde sont intacts, bandeau v4.0.6 présent ;
  le réglage `push_enabled` se sauvegarde et se recharge correctement (true ↔ false) ;
  le cluster 2 nœuds se configure normalement.
- **Régressions** : repro console 2 nœuds (20 PASS) et suite end-to-end recréation
  (57 PASS, y compris la preuve déterministe v4.0.4 bloque / v4.0.5+ accepte).
