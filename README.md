# multiOTP Windows — HA Cluster & Enhanced Console

**Language / Langue :** [Français](README.fr.md) | **English**

> **Community layer for multiOTP 5.10.2.2 open source (Windows)**: an enriched administration
> console (multi-administrator management, high-availability multi-master cluster "Active
> Directory style", live user search), complete deployment guides for 3 sites / 600 workstations
> with RADIUS, AD/LDAP synchronization and Credential Provider.

![Version](https://img.shields.io/badge/console-v4.0.2-blue) ![multiOTP](https://img.shields.io/badge/multiOTP-5.10.2.2-green) ![Platform](https://img.shields.io/badge/platform-Windows%20Server-lightgrey) ![Status](https://img.shields.io/badge/tests-281%20green%20assertions-brightgreen) ![Docs](https://img.shields.io/badge/docs-FR%20%2B%20EN%20README-orange)

---

## 📋 Table of contents

1. [The project in a nutshell](#-the-project-in-a-nutshell)
2. [Target infrastructure](#-target-infrastructure)
3. [What changes compared to the original multiOTP](#-what-changes-compared-to-the-original-multiotp)
4. [Features](#-features)
5. [Architecture](#-architecture)
6. [Quick start](#-quick-start)
7. [Repository structure](#-repository-structure)
8. [Documentation](#-documentation)
9. [Downloadable packages](#-downloadable-packages)
10. [Compatibility](#-compatibility)
11. [Important warnings](#-important-warnings)
12. [Credits and licenses](#-credits-and-licenses)

---

## 🎯 The project in a nutshell

The **multiOTP open source** server (SysCo) is an excellent strong-authentication (2FA) product,
but its web console accepts **a single `admin` account** and offers **no high availability**.
This project answers the needs of an SME with **600 workstations across 3 sites** that had to:

| Requirement | Answer provided |
|---|---|
| Several IT administrators with differentiated rights | **Multi-administrator extension** (super-admin / admin roles, lockout, encrypted account storage) |
| Several servers replicating "like Active Directory domain controllers": writes everywhere, every node up to date | **HA extension v4.0.2 — Multi-master cluster (AD-like)**: LWW merge, tombstones, instant notification |
| Find a user in a list of 600 | **User search extension v1.0** (live browser-side filter) |
| Diagnose replication and network incidents | **Diagnostic/repair kit**: 8 self-locating PowerShell scripts + built-in "Probe URLs" probe |
| Deploy the Credential Provider on 600 workstations with per-site fallback | **Client administration package v3.0** (MSI + exe + guides + scripts) |

Everything is implemented as a **single-file layer** (`multiotp.server.php`): the multiOTP 5.10.2.2
core remains strictly intact (not a single byte of the official class is modified), which preserves
compatibility with upgrades and official support.

---

## 🏢 Target infrastructure

> ⚠️ The addresses below are those of the reference environment. **Adapt them to your network.**

| Element | Address / value | Role |
|---|---|---|
| multiOTP node A (Windows) | `192.168.40.144` | multiOTP server + nginx/PHP-CGI on **port 8112** + RADIUS UDP 1812/1813 |
| multiOTP node B (Windows) | `192.168.40.147` | Mirror of node A — **multi-master cluster** (writes accepted on both) |
| Active Directory DC | `192.168.40.145` | AD/LDAP **port 389** — source of account synchronization |
| Test client workstation | `192.168.40.140` | Windows with the multiOTP **Credential Provider** deployed |
| Web console | `http://<IP>:8112/multiotp.server.php` | Administration interface (plain HTTP on the LAN) |
| RADIUS | UDP **1812** (auth) / **1813** (accounting) | `multiOTPradius` service bundled with the Windows package |
| LDAP synchronization | AD → multiOTP, port 389 | Scheduled `ldap-users-sync` import (AD accounts + OTP) |

**Authentication model:**

| Account type | Identifier | Factor 1 | Factor 2 |
|---|---|---|---|
| multiOTP **local** account | username | **PIN** (e.g. `4821`) | **OTP** code (6-digit TOTP) |
| **AD-synchronized** account | Windows login | **AD password** | **OTP** code (6-digit TOTP) |

---

## 🆚 What changes compared to the original multiOTP

The official multiOTP 5.10.2.2 open-source web console ships with **a single built-in `admin`
account, no administrator management, no high-availability panel and no user search** (verified
against the official console, md5 `92e1f9676031be9c9a7c6ae5fc39018f`). Everything below is
**added on top**: the official console remains fully available, and the multiOTP core
(`multiotp.class.php`) is **not modified by a single byte** — each extension is a localized
addition to `multiotp.server.php` only.

### A. Interface changes (original console → enhanced console v4.0.2 + search v1.0)

| Interface area | Official multiOTP 5.10.2.2 | This edition |
|---|---|---|
| Administrator access | Single built-in `admin` account | **"Administrator accounts"** section: add / edit / disable / reactivate / delete / unlock admin accounts **from the web console** |
| Roles | None (one account = all rights) | **Super administrator** (everything, including account management) and **Administrator** (users, tokens, diagnostics, backup) — enforced **server-side** on every sensitive AJAX call |
| Account storage | — | `multiotp-admin-accounts.json` encrypted with **AES-256-CBC** (no plain-text names), **bcrypt** passwords, automatic lockout after 5 failures (600 s), last active super-admin protected, native `admin` account kept as a permanent fallback |
| High availability | Absent from the OSS console (CLI-only configuration) | **"High availability"** panel: role *Off / Slave / Master / **Cluster (multi-master, AD-like)***, shared secret, **"Peer console URLs (master slaves / cluster peers, comma-separated)"** field, `Save` auto-enrolls peers, **Create/update scheduled task** and **Send test push** buttons, "HA extension: v4.0.2" status line |
| Replication | — | Event-driven push **~1 s**, retried every minute, **LWW** merge (Last-Writer-Wins), **tombstones**, peer-list replication, anti-loop guard |
| Network diagnostics | — | Built-in **Probe URLs** probe (TCP → TLS → HTTP) + one-click **Apply suggested URLs** fix, explicit transport causes (`Connection refused`, `timed out`…) |
| User list | Plain "List of users (N users)" list | Live **"Search a user..."** field: case-insensitive as-you-type filter, "(3 of 23 users)" counter, clear cross, `Esc` key, no-match message, filter persists across list refreshes |
| multiOTP engine | `multiotp.server.php` (GUI) + `multiotp.class.php` | **Byte-identical core**: enhanced GUI + HA layer + multi-admin layer in `multiotp.server.php` only; official API, RADIUS and config encryption untouched |

### B. Added functions (summary)

1. **Role-based multi-administrator management** (super-admin / admin) — details in § Features 1
2. **AD-like multi-master HA cluster v4.0.2** (LWW, tombstones, ~1 s push) — details in § Features 2
3. **Live user search v1.0** (100 % browser-side, zero server requests) — details in § Features 3
4. **HA diagnostic/repair kit** (8 self-locating PowerShell scripts) — details in § Features 4
5. **Client administration package v3.0** (Credential Provider, MSI + MST + GPO for 600 workstations) — details in § Features 5

---

## ✨ Features

### 1. Multi-administrator management

- Add / edit / disable / reactivate / delete / unlock administrator accounts **from the web
  console** (*Administrator accounts* section).
- Two roles: **Super administrator** (everything, including account management) and
  **Administrator** (users, tokens, diagnostics, backup — no access to accounts).
- **Encrypted** on-disk storage (`multiotp-admin-accounts.json`, AES-256-CBC, no plain-text
  names), passwords hashed with **bcrypt** server-side.
- **Automatic lockout after 5 failures** (600 s), protection of the last active super-admin,
  native `admin` account kept as a permanent fallback door, self-deletion forbidden.
- Every action logged to `multiotp.log` (with client IP address).

### 2. High availability — Multi-master cluster (AD-like) v4.0.2

The **Cluster (multi-master, AD-like)** role turns every console into the equivalent of a
domain controller:

| Active Directory (multi-DC) | multiOTP v4.0 (Cluster role) |
|---|---|
| Writes accepted on any DC | User creation / modification / deletion on **any** console |
| Incremental replication between DCs | **LWW merge** (Last-Writer-Wins): the file with the newest mtime wins |
| Tombstone-propagated deletions | **Tombstones**: deletions replicate; a newer re-creation wins |
| Guaranteed convergence | **Deterministic and symmetric** LWW decision (mtime, then content) |
| Full topology (KCC) | **The peer list itself replicates too** (validated union) |
| Replication anti-loop | Notification only on local change + idempotent merge + self-exclusion |

HA capability history (from v2 to v4.0.2):

- **Event-driven push**: any change on a node is replicated **instantly (~1 s)**, without
  waiting for the cron job; a failed push is **retried every minute** (nothing is lost).
- **Peer auto-enrollment** on `Save` (zero manual URL typing).
- **Explicit diagnostics**: readable transport cause (`Connection refused`, `timed out`,
  `No route to host`…), built-in **Probe URLs** probe (TCP → TLS → HTTP) and one-click URL
  fix (*Apply suggested URLs*).
- **Single-worker deadlock eliminated** (v3.9): the slave answers instantly (ACK) then
  replicates in a detached process.
- **Hardened security** (v3.10): tokens as **HMAC-SHA256**, shared secret never displayed,
  never stored nor transmitted in clear; **AES-256-CBC** payload.
- **Field fixes** (v4.0.1 / v4.0.2): pasted URLs without separators re-split, loopback target
  refused, "phantom https" fixed (schema self-healing).

### 3. User search v1.0

A **`Search a user...`** field in the "List of users" title bar: **as-you-type** filter,
case-insensitive, "(3 of 23 users)" counter, clear cross, `Esc` key, "No user matches the
search…" message. **100 % browser-side**: no extra AJAX request, no impact on php-cgi. The
filter survives list refreshes.

### 4. HA diagnostic / repair kit (`multiotp-ha-fix-complet-v2.zip`)

8 **self-locating** PowerShell scripts (pure ASCII, dry-run by default, backup + rollback):

| Script | Usage |
|---|---|
| `1_DIAG.ps1` | 9 read-only sections: ports, php-cgi pool, HTTPS/body size, HA tasks, nginx -t, HaPing… |
| `2_FIX_NGINX.ps1` | Comments out `HTTPS on`, adds `client_max_body_size 10m`, **automatic rollback** if `nginx -t` fails |
| `3_STOP_HA_STORM.ps1` | Quarantines the HA tasks (`Rename-Item` + `Disable-ScheduledTask`) |
| `4_HA_API_TEST.ps1` | Tests `ping`/`revision` with the **exact v4.0 tokens** (md5 + HMAC-SHA256) |
| `5_WATCH_LOGS.ps1` | Live nginx log watch with automatic path discovery |
| `6_FIND_SERVICES.ps1` | Locates nginx / php-cgi / multiOTPradius |
| `7_POOL_PHP.ps1` | Applies `PHP_FCGI_CHILDREN=6` (+ `-Apply` / `-InstallTask`) |
| `8_CHECK_FINAL.ps1` | 10 verdict checks `[OK]/[WARNING]/[FAIL]` + final CONFORME / NON CONFORME verdict |

### 5. Client administration package v3.0

multiOTP RADIUS client for Windows + **Credential Provider** (Windows logon screen with OTP):
MSI installer, administration executable, guides, corrected RADIUS `clients.conf` (the
`222.222.222.222` ghost-client pitfall of the official installer is documented and fixed),
LDAP configuration and repair scripts, MSI + GPO deployment with `.mst` transform.

---

## 🗺️ Architecture

![3-site architecture](docs/img/architecture.png)

**Principles verified by 130 replayed assertions (see [docs/architecture.md](docs/architecture.md)):**

1. **Local server first**: each workstation queries its site's multiOTP server first
   (`multiOTPServers` list of the Credential Provider). On timeout (~5 s), automatic fallback
   to the next servers in the list — each site keeps authenticating its users even if the WAN
   is down (failover validated 11/11).
2. **Bidirectional replication**: in multi-master cluster mode, each node notifies its peers
   as soon as a user/token changes; peers merge in LWW. Three mechanisms complement each
   other: console request watch (instant), scheduled watch 1/min (`ha-push-check`), peer cron
   (safety net).
3. **AD/LDAP synchronization**: scheduled import from AD to the multiOTP servers
   (`ldap-users-sync`); AD accounts then authenticate with Windows password + OTP.
4. **RADIUS**: workstations (Credential Provider) authenticate over UDP 1812/1813 against the
   local `multiOTPradius` service.

Detailed flows, sequence diagrams (user logon, LDAP admin flow) and the flow table:
**[docs/architecture.md](docs/architecture.md)**.

---

## 🚀 Quick start

```text
1. Install the official multiOTP server 5.10.2.2 (Windows) on each node
   → https://download.multiotp.net/  ("Windows" package, nginx + PHP-CGI included)

2. Deploy the enhanced console v4.0.2 (release/multiotp.perso.ha.cluster.multimaster.v402.zip)
   → Back up, replace C:\multiotp\multiotp.server.php, Ctrl+F5 in the browser

3. Configure the multi-master cluster
   → "High availability" panel: Role = Cluster (multi-master, AD-like),
     same shared secret everywhere, Peer Console URLs = the OTHER nodes, Save
   → Create/update scheduled task, then Send test push

The complete guide (prerequisites, nginx, LDAP, RADIUS, Credential Provider, 600 workstations):
👉 [INSTALLATION.md](INSTALLATION.md)
```

---

## 📁 Repository structure

```text
multiotp-windows-ha-cluster/
├── README.md                    ← this file (English)
├── README.fr.md                 ← French version of this file
├── INSTALLATION.md              ← full installation guide (8 steps)
├── UTILISATION.md               ← user guide (console, HA, roles, diagnostics)
├── CHANGELOG.md                 ← version history v1.0 → v4.0.2
├── LICENSE.md                   ← license notes
├── PUBLIER-SUR-GITHUB.md        ← step-by-step procedure to publish this repository on GitHub
├── docs/
│   ├── architecture.md          ← detailed architecture + sequence diagrams (Mermaid)
│   ├── securite.md              ← security model, role matrix, best practices
│   ├── depannage.md             ← troubleshooting: push, LDAP error 99, RADIUS, nginx, storm
│   └── img/
│       └── architecture.png     ← topology diagram
└── release/                     ← ready-to-deploy packages (SHA-256 in release/README.md)
    ├── README.md
    ├── multiotp.perso.ha.cluster.multimaster.v402.zip      ← full enhanced console
    ├── multiotp.recherche-utilisateurs.v1.0.zip            ← search extension (v4.0.2 base)
    ├── multiotp_5.10.2.2_multiadmin.zip                    ← multi-admin extension alone
    ├── multiotp-ha-fix-complet-v2.zip                      ← diagnostic/repair kit (8 PS1 scripts)
    └── MultiOTP_Administration_Package_Complet_3.0.zip     ← client administration + Credential Provider
```

---

## 📚 Documentation

> ℹ️ The detailed guides below are written in **French**; this bilingual README summarizes
> everything you need to get started.

| Document | Content |
|---|---|
| [INSTALLATION.md](INSTALLATION.md) | Prerequisites, multiOTP installation, enhanced console, nginx/PHP-CGI, AD/LDAP, RADIUS, HA cluster, Credential Provider, checks, rollback |
| [UTILISATION.md](UTILISATION.md) | Logon, administrator management, **role matrix**, users and OTP enrolment, HA panel, search, error codes, daily operations |
| [docs/architecture.md](docs/architecture.md) | 3-site topology, authentication and replication flows, sequence diagrams (Mermaid), LWW/tombstone model |
| [docs/securite.md](docs/securite.md) | HMAC-SHA256 tokens, AES-256-CBC encryption, full role matrix, secrets to replace, firewall |
| [docs/depannage.md](docs/depannage.md) | "unreachable" diagnostic tables, probe TCP/TLS/HTTP matrix, LDAP error 99, RADIUS clients.conf, HA storm |
| [CHANGELOG.md](CHANGELOG.md) | All versions and fixes, from v1.0 multi-admin to v4.0.2 |
| [release/README.md](release/README.md) | Contents, SHA-256 fingerprints, deployment order, integrity verification |

---

## 📦 Downloadable packages

The packages are **in this repository** ([`release/`](release/) folder) — verify integrity after
download:

```powershell
# Windows (PowerShell)
Get-FileHash .\multiotp.perso.ha.cluster.multimaster.v402.zip -Algorithm SHA256
```

```bash
# Linux
sha256sum multiotp.perso.ha.cluster.multimaster.v402.zip
```

Reference fingerprints: [`release/README.md`](release/README.md).

> 💡 **Tip**: for a public repository, push the code as-is and **attach the packages to a GitHub
> Release** (menu *Releases* → *Draft a new release* → drag the ZIP files) — detailed procedure
> in [PUBLIER-SUR-GITHUB.md](PUBLIER-SUR-GITHUB.md) (French).

---

## 🧩 Compatibility

| Component | Version |
|---|---|
| multiOTP open source (official core, **unmodified**) | 5.10.2.2 (2026-04-03) |
| Enhanced console `multiotp.server.php` | HA extension **v4.0.2** |
| PHP | 8.1 – 8.4 (tested 8.4.8; php-cgi FastCGI) |
| nginx | any Windows version (config from the official package) |
| Windows Server | 2012 R2 → 2025 (servers); Windows 7 → 11 (workstations, Credential Provider) |
| Browsers | modern Chrome / Edge / Firefox |
| Console deployments | Windows (`multiotp.server.php`), Raspberry Pi / Docker (`index.php`) |

---

## ⚠️ Important warnings

1. **Replace the default secrets**: the RADIUS secret `multiotpsecret` and the demo HA secret
   must be changed (≥ 16 mixed characters) on the server **and** on every declared RADIUS
   client.
2. **NTP is mandatory**: HA tokens are only valid ±5 minutes — keep node clocks accurate.
3. **Browse each console via its LAN IP** (`http://<LAN-IP>:8112/`), **never** via
   `localhost`/`127.0.0.1`: the node's own URL would be enrolled as loopback and rejected by
   the peers (v4.0.1 guard rail, but better avoid).
4. **`multiOTPServers` order = priority**: the site server must be **first** in the list of
   each workstation; that order decides the fallback.
5. The multiOTP core remains the official product of **SysCo systemes de communication sa** —
   this project is neither a replacement for official support nor for the product
   documentation: see <https://download.multiotp.net/> and <http://forum.multiotp.net/>.

---

## 🙏 Credits and licenses

| Component | Author / license |
|---|---|
| **multiOTP** (core 5.10.2.2, RADIUS, Credential Provider) | © 2010-2026 SysCo systemes de communication sa — original licenses kept, see <https://www.multiotp.net/> |
| **multiOTP Credential Provider** | © SysCo / NetKnights / ArcadeJust / Last Squirrel IT — **Apache License 2.0** |
| **Extensions of this repository** (multi-admin, HA cluster, search, diagnostic kit, guides) | Internal community project — see [LICENSE.md](LICENSE.md) |

See also [CHANGELOG.md](CHANGELOG.md) for the full history and
[docs/securite.md](docs/securite.md) for the covered threat model.
# multiotp-ha-windows-cluster
