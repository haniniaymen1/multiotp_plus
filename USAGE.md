# User Guide — Enhanced multiOTP Console

**Language / Langue :** [English](USAGE.md) | [Français](UTILISATION.md)

> This guide covers the day-to-day use of the enhanced administration console
> (**multiOTP 5.10.2.2 + HA extension v4.0.2**): administrator, user and token management,
> the high-availability panel, search, diagnostics and operations.
> For installation, see [INSTALLATION.md](INSTALLATION.md) (French); for the architecture,
> see [docs/architecture.md](docs/architecture.md) (French).

## 📋 Table of contents

1. [Logging in to the console](#1-logging-in-to-the-console)
2. [Interface tour](#2-interface-tour)
3. [Administrator management](#3-administrator-management)
4. [Role matrix](#4-role-matrix)
5. [User management and OTP enrolment](#5-user-management-and-otp-enrolment)
6. [The High Availability panel](#6-the-high-availability-panel)
7. [User search](#7-user-search)
8. [Error codes and common messages](#8-error-codes-and-common-messages)
9. [Day-to-day operations](#9-day-to-day-operations)

---

## 1. Logging in to the console

- **URL**: `http://<node-LAN-IP>:8112/multiotp.server.php` — ⚠️ always through the node's
  **LAN IP**, never via `localhost`/`127.0.0.1` (the node's own URL would be enrolled as
  loopback and rejected by the peers).
- **Accepted accounts**:
  - the native **`admin`** account (permanent fallback door, password set via
    *Change admin password*);
  - any **additional administrator account** created in the *Administrator accounts* section
    (see §3) — it uses the same logon screen.
- **Lockout**: 5 password failures → account locked for 600 seconds. A super-administrator
  can unlock it immediately (**Unlock** button).
- If the page looks stale or odd after a file change: **Ctrl+F5** (the console HTML is
  cached by the browser).

---

## 2. Interface tour

The console keeps the multiOTP ergonomics (side menus, panels) enhanced with three visible
additions:

| Area | Content | Project addition |
|---|---|---|
| Side menus | Users, Users' tokens, Configuration, Backup, Log… | + **Administrator accounts** section |
| "List of users" panel | account list (AD/LDAP pills, locked, delayed…) | + **live search field** in the title bar |
| "High availability (master / slave / cluster)" panel | replication configuration and diagnostics | fully enhanced (v2 → v4.0.2) |
| HA status line | extension version, role, last pull/push | `HA extension: v4.0.2` |

---

## 3. Administrator management

Section **Administrator accounts** (between "Change admin password" and "Import new hardware
tokens"). Visible to **super-administrators only**.

### Available actions

| Action | Description |
|---|---|
| **Add a new administrator account** | creation: name, role (`Super administrator` / `Administrator`), password + confirmation |
| **Modify** | change an account's password and/or role |
| **Disable / Enable** | disable (logon refused) / re-enable |
| **Unlock** | immediately unlock an account locked after failures |
| **Delete** | permanently delete the account |
| **Change my password** | each administrator changes their own password (current password verification) |

### Protection rules

- The native `admin` account is marked **(built-in)**: it can be neither disabled nor deleted
  (its password is changed via *Change admin password*).
- You can **neither disable nor delete your own account** (marked **(you)**).
- The console refuses any operation that would leave **zero active super-administrator**
  (`lastSuperAdmin` protection).
- Every action is **logged** to `multiotp.log` with the source IP address.
- Storage: `config\multiotp-admin-accounts.json`, **encrypted AES-256-CBC** — no user name
  or hash readable in clear text on disk.

---

## 4. Role matrix

Four identities coexist in the system. The matrix below summarizes **who can do what**:

### 4.1 Administration console

| Capability | Super administrator (super_admin) | Administrator (admin) | native admin (built-in) |
|---|:-:|:-:|:-:|
| View the user list | ✅ | ✅ | ✅ |
| Create / modify / delete a user | ✅ | ✅ | ✅ |
| Enrol a token (TOTP QR, hardware) | ✅ | ✅ | ✅ |
| Run an authentication test | ✅ | ✅ | ✅ |
| Modify the general configuration | ✅ | ✅ | ✅ |
| Backup / restore | ✅ | ✅ | ✅ |
| View logs | ✅ | ✅ | ✅ |
| Configure the HA panel (Save, test push, probe…) | ✅ | ✅ | ✅ |
| **See the Administrator accounts section** | ✅ | ❌ (list refused) | ✅ |
| **Create / modify / disable / delete an admin** | ✅ | ❌ | ✅ |
| **Unlock a locked admin** | ✅ | ❌ | ✅ |
| Change one's own password | ✅ | ✅ | ✅ |
| Can be locked after 5 failures | yes (Unlock by another super-admin) | yes | **cannot be locked** |
| Disabling / deletion of one's own account | forbidden for yourself | forbidden for yourself | impossible |

> ⚠️ **Invariant**: there must **always** be at least one active super-administrator —
> any demotion/disable/delete that would violate this rule is refused by the console.

### 4.2 Authentication accounts (end users)

| Capability | multiOTP local account | AD/LDAP synchronized account |
|---|---|---|
| Created by | console / CLI / CSV import | **LDAP import** (`ldap-users-sync`) |
| Factor 1 | **PIN** (e.g. `4821`) | **Windows password** |
| Factor 2 | OTP code | OTP code |
| Available if AD is down | ✅ | factor 1 remains the last AD password known to multiOTP |
| Pills visible in the console | — | `AD` / `LDAP` |

---

## 5. User management and OTP enrolment

### Create a user and enrol their TOTP

1. Menu **Users** → **Add a new user** → enter the username.
2. Open the user's record → **Create a new token** (software TOTP).
3. Scan the **QR code** with the app (Google Authenticator, Microsoft Authenticator,
   FreeOTP…) → the 6-digit code rotates every 30 seconds.
4. Test immediately: **Check a user's OTP** button → enter `PIN` + code
   (local account) or `AD password` + code (AD account).
5. In cluster mode: the account is **replicated to peers in ~1 second** (instant
   notification).

### Synchronize AD accounts

- Manual: CLI `multiotp.exe -ldap-users-sync` on one node → new AD accounts are imported
  then **replicated automatically** to the peers.
- Scheduled: the "multiOTP LDAP sync" task (see INSTALLATION.md §4) performs the periodic
  import; every detected change goes out as a push.
- In the console, AD accounts carry an **AD/LDAP** pill; they authenticate with
  **Windows password + OTP** (never the PIN).

### Deletion

Delete a user in the console → replicated tombstone: it disappears from the peers in
~1 s. A **newer** re-creation "resurrects" the account everywhere (LWW).

---

## 6. The High Availability panel

Central replication panel. All buttons act **on the node you are browsing** — it is normal
to open two tabs (one per node) to check both sides.

### Configuration fields

| Field | Roles | Description |
|---|---|---|
| **Role of this server** | both | `Disabled` / `Master` / `Slave` / **`Cluster (multi-master, AD-like)`** |
| **Master console URL** | Slave | full URL of the master console (classic master/slave mode) |
| **HA shared secret** | both | shared secret — **identical everywhere**; leave empty = keep current (never displayed again) |
| **Synchronization interval in minutes (slave cron)** | Slave | 1–1440 min between two periodic pulls (safety net) |
| **Instant push on data change (master)** | Master | checked = notify slaves on every change |
| **Peer Console URLs (Master/Slaves, Cluster Peers, comma-separated)** | Master / Cluster | URLs of the **other** nodes, **comma-separated**. Filled automatically by peer auto-enrollment (one `Save` on the peer side is enough) — manual entry possible. ⚠️ In Cluster mode this list **replicates too** (validated union): add a node anywhere, it propagates everywhere |

> 💡 **Expected behavior**: `Save` must **replace** the value typed in the *Peer Console
> URLs* field — never duplicate it. **If your build duplicates the value on every click**
> (the field grows with each Save), **immediate workaround**: retype the full correct list
> then `Save`, and check the result in `ha-status`
> (`slave_urls_effective`, deduplicated list). Peer auto-enrollment itself adds a URL only
> if it is missing (deduplicated, capped at 16 targets).

### Buttons

| Button | What it does | When to use it |
|---|---|---|
| **Save** | saves the role configuration; captures and **verifies** the `own_url` (real HaPing, http/https schema self-healing, loopback refused) | on every setting change |
| **Test peer** | tests the reachability of the master/peer URL | quick diagnostics |
| **Sync now** | **forced** pull (without waiting for the interval) | first replica, immediate catch-up |
| **Send test push** | notifies each peer and **displays its answer** (`up_to_date`, `applied:{...}`, `unreachable (cause)`, `bad_token`, `not_slave`) | push verification; in cluster mode, shows the full verdict "ACK + replication confirmed in X s" |
| **Probe URLs (diagnostic)** | for each peer URL: **TCP** → **TLS** → **HTTP** executed from the node's PHP; shows where the chain breaks + `hint` lines | when the push fails and you want to know *why* |
| **Apply suggested URLs** | applies the URL fixes proposed by the probe (e.g. switch from `https` to proven `http`) | after a probe that suggests a schema change |
| **Create/update scheduled task** | installs the **role-appropriate** scheduled task (1/min watch: `ha-push-check` in master/cluster, `ha-cron-pull` in slave) | at installation and after every role change |

### Status line

- `HA extension: v4.0.2` — deployed version (if you see anything else, the file was not
  replaced or the cache was not flushed: **Ctrl+F5**).
- Master/Cluster: last push + **orange warning** "Data changed but push NOT confirmed by
  every peer – automatic retry every minute" as long as a peer is unreachable.
- Slave: last pull + applied revision.
- Explicit red warnings: push not ready, loopback target detected, missing secret…

### Machine-to-machine endpoints (token-protected, session-less)

| Endpoint | Served by | Usage |
|---|---|---|
| `HaPing` | every node | liveness test (`pong` + revision) |
| `HaGetRevision` | every node | `rev|<fingerprint>` — economical check |
| `HaGetBackup` | master/peer | AES-256-CBC encrypted backup |
| `HaNotifyPull` | every node | "change" notification → checks revision → pulls if needed |
| `HaCronPull` | slave | pull for the HTTP scheduler |
| `HaRegisterSlave` | master | slave auto-enrollment (`register` token) |

Security: **HMAC-SHA256** tokens (v3.10) + legacy md5 token accepted for backward
compatibility; validity **±5 min**; encrypted payload; validated file names (no traversal);
secret never returned in clear.

---

## 7. User search

In the **"List of users"** bar, the `Search a user...` field (right side):

| Element | Behavior |
|---|---|
| Typing | **as-you-type** filter, case-insensitive, substring of the **user name** |
| Counter | `(23 users)` without filter → `(3 of 23 users)` with filter |
| Cross ✕ | appears as soon as there is text; one click clears |
| `Esc` key | clears and redisplays everything |
| "No user matches the search…" | if no result (and only if users exist) |
| Persistence | the filter **survives** list refreshes (e.g. after a deletion) |

100 % browser-side: no AJAX request, no server impact, works on 600-account lists.

---

## 8. Error codes and common messages

### Authentication (RADIUS / multiOTP logs)

| Code | Meaning | Action |
|---|---|---|
| `99` | authentication failure — **often a configuration LDAP error** | see [docs/depannage.md](docs/depannage.md) § LDAP erreur 99 (French) |
| `90` | **LDAP** authentication failure (AD bind) | check `ldap-bind-user/password`, port 389 open |
| `26` | token **already used** (anti-replay) | normal if the same code was resubmitted |
| `33` | **PIN missing** | local account: enter `PIN` then the OTP code |
| `-` /failure | account nonexistent on this server | replication not done yet? *Sync now* / check the fallback |

### HA panel / journals

| Message | Meaning | Fix |
|---|---|---|
| `up_to_date` | the peer is already at the right revision | nothing to do (that is success) |
| `applied:{...}` | the peer applied the pull | success |
| `unreachable (… Connection refused)` | nothing listens on the peer port (console stopped / wrong port) or firewall REJECT | start the peer console; `netstat -ano \| findstr :8112` → `LISTENING` |
| `unreachable (… timed out)` | firewall DROP or machine off | open inbound TCP 8112 on the peer |
| `unreachable (… No route to host)` | host off the network (ARP failure) | check the real IP (`ipconfig`) and the VLAN |
| `unreachable (… Could not resolve host)` | invalid host name in the peer list | use the direct IP address |
| `unreachable (… HTTP request failed!)` after TCP ok | the port probably speaks **HTTPS** | use `https://...` (self-signed accepted) or fix the web config |
| `bad_token` | secret differs between nodes **or clocks out of sync (>5 min)** | align the secret; enable NTP |
| `not_slave` / `not_master` | role badly enrolled on the target | re-Save the role on the target node |
| `loopback_url` (refused at Save) | attempt to enroll `127.0.0.1`/`localhost` as own_url/peer | browse the console via its **LAN IP** |
| Pasted URLs without separator | automatic re-split v4.0.1 at `http(s)://` boundaries | check the list after Save |

### "Probe URLs" reading matrix

| TCP | TLS | HTTP | Conclusion |
|---|---|---|---|
| ok | FAILED | FAILED | the port speaks HTTP but not TLS → use `http://` |
| ok | ok | FAILED | the port speaks TLS but HTTP fails → `https://` (self-signed OK in v3.5+); otherwise endpoint |
| ok | FAILED | ok | console in HTTP — all good |
| FAILED | — | — | network: host down / port closed / firewall (see `9_diagnostic_push.cmd`) |

---

## 9. Day-to-day operations

### Recommended routine

| Frequency | Check |
|---|---|
| **Daily** | HA status: green status line on both nodes (no pending-push warning) |
| **Daily** | nginx log: no shower of `499`/`5xx` (`5_WATCH_LOGS.ps1`) |
| **Weekly** | `8_CHECK_FINAL.ps1` → **CONFORME** verdict (10 checks) |
| **Weekly** | LDAP import: no error in the log after each scheduled run |
| **Monthly** | end-to-end functional test: create a test user → visible on the peer in ~1 s → delete → propagated |
| **Monthly** | check NTP on both nodes (drift < 5 min otherwise `bad_token`) |

### Useful CLI commands (on one node)

```bat
cd C:\multiotp
php multiotp.server.php ha-status        :: full JSON state of the node (peers, revision, last pull/push)
php multiotp.server.php ha-sync-now      :: forced pull
php multiotp.server.php ha-push-check    :: detects local changes and notifies the peers
php multiotp.server.php ha-install-task  :: installs/shows the scheduled task of the current role
multiotp.exe -ldap-users-sync            :: manual AD import
multiotp.exe -userslist                  :: user list
```

### Minimal monitoring (3 points are enough)

1. **multiOTPradius** service running and port **1812 listening** on each server.
2. **HA status** (`ha-status` or panel) showing a **recent** last_pull/last_push on each node.
3. **No LDAP error** in the journal after each scheduled import.

### Reacting to a site outage

| Scenario | Automatic effect | Action |
|---|---|---|
| One node goes down | workstations fall over to the next server in `multiOTPServers` (~5 s) | nothing; repair the node; on its return, replication catches up automatically (retried every minute) |
| WAN outage between sites | **each site keeps authenticating its users** with its local copy | nothing; on return, LWW merge converges |
| Permanent loss of a node | the other one continues alone (writes accepted) | rebuild the lost peer (reinstall, join the cluster, `Sync now`) |
| Both nodes go down | workstations with **CP cache enabled**: offline authentication possible | restore one node, then let the cache resynchronize |

### Best practices

- **Secrets**: RADIUS and HA ≥ 16 mixed characters, never reused between environments.
- **NTP** on both nodes (HA tokens expire at ±5 min).
- **Backups**: the console Backup button + off-site copy of `C:\multiotp\config\`
  (encrypted files); replication is not a backup (it also replicates mistakes).
- **Changes**: everything can be done from **any node** of the cluster — that is by design
  (AD model). Just avoid making two contradictory changes to the same account in the same
  second on two nodes (LWW will arbitrate by mtime).
- **Console updates**: always deploy the same `multiotp.server.php` version on all nodes,
  one node after the other, without service interruption (Ctrl+F5 is enough).
