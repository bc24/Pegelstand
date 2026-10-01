# Schema-Entwurf (Phase 2) – zur Freigabe

Status: Entwurf, noch keine Migration geschrieben. Alle Tabellennamen tragen einen konfigurierbaren Präfix (hier `ps_`). Die Migrationen entstehen schrittweise: `0001` in Phase 2, die übrigen mit der Phase, die sie braucht.

## Konventionen

- InnoDB, `utf8mb4`, Collation `utf8mb4_unicode_ci`. Sie ist in MySQL 8.0 und MariaDB 10.6 gleich verfügbar, die MySQL-Standardcollation `utf8mb4_0900_ai_ci` gibt es in MariaDB nicht.
- Zeitstempel der Rohdaten in UTC. Tagesgrenzen, Anzeige und Aggregate richten sich nach der Zeitzone der Site.
- Fremdschlüssel nur bei den Verwaltungstabellen. Die großen Datentabellen (`sessions`, `events`, Aggregate) haben keine, weil sie Schreibvorgänge bremsen. Die Konsistenz sichern die Anwendung und der Aufräumjob.
- Zeichenketten stehen in Wörterbüchern, die Datentabellen enthalten nur Zahlen-IDs. Der Wörterbuch-Schlüssel ist `UNHEX(MD5(wert))` (16 Byte) mit Unique-Index. Das hält den Index klein, auch bei langen Pfaden. Bei einer Kollision vergleicht die Anwendung den Wert.
- Größen sind Planwerte und werden in Phase 4 mit dem Demo-Daten-Generator (10 Mio. Ereignisse) gemessen.

## 1. Verwaltung (Migration 0001, Phase 2)

```sql
CREATE TABLE ps_migrations (
  version     VARCHAR(32)  NOT NULL PRIMARY KEY,   -- z. B. 0001
  name        VARCHAR(190) NOT NULL,
  checksum    CHAR(64)     NOT NULL,                -- SHA-256 der Migrationsdatei
  applied_at  DATETIME     NOT NULL
);

CREATE TABLE ps_settings (                          -- laufzeitänderbare Einstellungen
  name        VARCHAR(100) NOT NULL PRIMARY KEY,
  value       MEDIUMTEXT   NOT NULL,
  updated_at  DATETIME     NOT NULL
);

CREATE TABLE ps_users (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email            VARCHAR(190) NOT NULL,
  name             VARCHAR(120) NOT NULL,
  password_hash    VARCHAR(255) NOT NULL,           -- Argon2id, falls verfügbar, sonst bcrypt
  role             ENUM('admin','viewer') NOT NULL DEFAULT 'viewer',
  totp_secret      VARBINARY(255) NULL,             -- verschlüsselt mit app_key
  totp_enabled_at  DATETIME NULL,
  created_at       DATETIME NOT NULL,
  last_login_at    DATETIME NULL,
  disabled_at      DATETIME NULL,
  UNIQUE KEY uq_users_email (email)
);

CREATE TABLE ps_sites (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  public_id         CHAR(16)     NOT NULL,          -- Wert für data-site im Tracking-Code
  name              VARCHAR(120) NOT NULL,
  domain            VARCHAR(190) NOT NULL,          -- Hostname ohne Schema
  allowed_hosts     TEXT NULL,                      -- zusätzliche erlaubte Hostnamen und Subdomains, ein Eintrag pro Zeile
  timezone          VARCHAR(64)  NOT NULL DEFAULT 'Europe/Berlin',
  retention_days    SMALLINT UNSIGNED NOT NULL,     -- Aufbewahrung der Rohdaten (Standardwert offen, Frage 5)
  respect_dnt       TINYINT(1) NOT NULL DEFAULT 0,
  respect_gpc       TINYINT(1) NOT NULL DEFAULT 0,
  public_token      CHAR(32) NULL,                  -- öffentlicher, lesender Dashboard-Link (Phase 7)
  created_at        DATETIME NOT NULL,
  UNIQUE KEY uq_sites_public_id (public_id),
  UNIQUE KEY uq_sites_public_token (public_token)
);

CREATE TABLE ps_site_users (                        -- Zuordnung Benutzer zu Sites
  site_id  INT UNSIGNED NOT NULL,
  user_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (site_id, user_id),
  FOREIGN KEY (site_id) REFERENCES ps_sites(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES ps_users(id) ON DELETE CASCADE
);
```

## 2. Betrieb und Sicherheit (spätere Migrationen)

```sql
CREATE TABLE ps_daily_salts (                       -- Phase 3: nur das aktuelle Salt bleibt erhalten
  salt_date  DATE NOT NULL PRIMARY KEY,
  salt       BINARY(32) NOT NULL,
  created_at DATETIME NOT NULL
);

CREATE TABLE ps_job_runs (                          -- Phase 4: Hintergrundjobs, Sperre gegen Doppelstart
  job          VARCHAR(60) NOT NULL PRIMARY KEY,
  locked_until DATETIME NULL,
  started_at   DATETIME NULL,
  finished_at  DATETIME NULL,
  status       VARCHAR(20) NOT NULL DEFAULT 'idle',
  message      VARCHAR(255) NULL
);

CREATE TABLE ps_site_ip_exclusions (                -- Phase 6: eigene Besuche ausschließen (Adressen des Betreibers)
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  site_id   INT UNSIGNED NOT NULL,
  ip_range  VARCHAR(64)  NOT NULL,                  -- Adresse oder CIDR
  label     VARCHAR(120) NULL,
  FOREIGN KEY (site_id) REFERENCES ps_sites(id) ON DELETE CASCADE
);

CREATE TABLE ps_password_resets (                   -- Phase 6
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME NULL,
  UNIQUE KEY uq_resets_token (token_hash),
  FOREIGN KEY (user_id) REFERENCES ps_users(id) ON DELETE CASCADE
);

CREATE TABLE ps_rate_limits (                       -- Phase 3 und 6, nur wenn Frage 7 so entschieden wird
  bucket       VARCHAR(30) NOT NULL,                -- 'ingest' oder 'login'
  key_hash     BINARY(16)  NOT NULL,                -- gesalzener Hash, keine Klartext-Adresse
  window_start DATETIME    NOT NULL,
  hits         INT UNSIGNED NOT NULL,
  PRIMARY KEY (bucket, key_hash)
);

CREATE TABLE ps_api_keys (                          -- Phase 7
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  name         VARCHAR(120) NOT NULL,
  key_prefix   CHAR(8)  NOT NULL,
  key_hash     CHAR(64) NOT NULL,
  created_at   DATETIME NOT NULL,
  last_used_at DATETIME NULL,
  UNIQUE KEY uq_api_key_hash (key_hash),
  FOREIGN KEY (user_id) REFERENCES ps_users(id) ON DELETE CASCADE
);

CREATE TABLE ps_report_subscriptions (              -- Phase 7
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  site_id      INT UNSIGNED NOT NULL,
  frequency    ENUM('weekly','monthly') NOT NULL,
  last_sent_at DATETIME NULL,
  UNIQUE KEY uq_report (user_id, site_id, frequency),
  FOREIGN KEY (user_id) REFERENCES ps_users(id) ON DELETE CASCADE,
  FOREIGN KEY (site_id) REFERENCES ps_sites(id) ON DELETE CASCADE
);

CREATE TABLE ps_goals (                             -- Phase 7
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  site_id   INT UNSIGNED NOT NULL,
  name      VARCHAR(120) NOT NULL,
  kind      ENUM('page','event') NOT NULL,
  path_id   INT UNSIGNED NULL,                      -- bei kind = page
  event_id  INT UNSIGNED NULL,                      -- bei kind = event (dict_event_name)
  FOREIGN KEY (site_id) REFERENCES ps_sites(id) ON DELETE CASCADE
);
```

## 3. Wörterbücher (Phase 3)

Alle haben denselben Aufbau, nur Name und Länge von `value` unterscheiden sich.

```sql
CREATE TABLE ps_dict_path (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  value_hash BINARY(16)   NOT NULL,                 -- UNHEX(MD5(value))
  value      VARCHAR(1024) NOT NULL,
  UNIQUE KEY uq_dict_path (value_hash)
);
```

| Tabelle | `value` | Inhalt |
|---|---|---|
| `dict_path` | 1024 | Pfad ohne Query-String (Frage 3) |
| `dict_referrer` | 255 | Referrer nur als Domain |
| `dict_utm` | 255 | Werte aller fünf UTM-Felder |
| `dict_browser` | 64 | Browsername ohne Version (Frage 4) |
| `dict_os` | 64 | Betriebssystem ohne Version |
| `dict_event_name` | 120 | Ereignisname |
| `dict_prop_key` | 64 | Eigenschaftsname |
| `dict_prop_value` | 255 | Eigenschaftswert |

Land: `CHAR(2)` (ISO 3166-1 alpha-2) direkt in der Sitzung statt im Wörterbuch (Frage 9). Gerätetyp: `TINYINT` (1 Desktop, 2 Smartphone, 3 Tablet, 0 unbekannt).

## 4. Rohdaten (Phase 3 und 4)

Die Sitzung wird beim Eintreffen eines Aufrufs zugeordnet (Frage 2): Eine indizierte Suche findet die offene Sitzung desselben Besuchers (Abstand unter 30 Minuten), sie wird aktualisiert oder neu angelegt. Danach folgt ein schmaler Insert in `events`.

```sql
CREATE TABLE ps_sessions (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  site_id        INT UNSIGNED NOT NULL,
  visitor_hash   BINARY(16)   NOT NULL,             -- HMAC über Tages-Salt, Site-ID, IP, User-Agent; wechselt täglich
  started_at     DATETIME     NOT NULL,             -- UTC
  last_seen_at   DATETIME     NOT NULL,
  entry_path_id  INT UNSIGNED NOT NULL,
  exit_path_id   INT UNSIGNED NOT NULL,
  pageviews      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  custom_events  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  referrer_id    INT UNSIGNED NULL,
  utm_source_id  INT UNSIGNED NULL,
  utm_medium_id  INT UNSIGNED NULL,
  utm_campaign_id INT UNSIGNED NULL,
  utm_term_id    INT UNSIGNED NULL,
  utm_content_id INT UNSIGNED NULL,
  country        CHAR(2)      NULL,
  device         TINYINT UNSIGNED NOT NULL DEFAULT 0,
  browser_id     SMALLINT UNSIGNED NULL,
  os_id          SMALLINT UNSIGNED NULL,
  KEY ix_sessions_lookup (site_id, visitor_hash, last_seen_at),  -- Zuordnung beim Eintreffen
  KEY ix_sessions_time   (site_id, started_at),                  -- Zeiträume, gefilterte Abfragen
  KEY ix_sessions_live   (site_id, last_seen_at)                 -- aktive Besucher
);
-- Absprung: pageviews = 1 UND custom_events = 0.  Dauer: last_seen_at - started_at (Einzelseiten-Besuche zählen mit 0 s).
-- Weitere Filter-Indizes (z. B. site_id, started_at, country) kommen erst nach Messung mit 10 Mio. Ereignissen.

CREATE TABLE ps_events (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  site_id     INT UNSIGNED NOT NULL,
  session_id  BIGINT UNSIGNED NOT NULL,
  occurred_at DATETIME NOT NULL,                    -- UTC
  kind        TINYINT UNSIGNED NOT NULL,            -- 1 Seitenaufruf, 2 Ereignis
  path_id     INT UNSIGNED NOT NULL,
  name_id     INT UNSIGNED NULL,                    -- Ereignisname (bei kind = 2)
  KEY ix_events_time    (site_id, occurred_at),
  KEY ix_events_path    (site_id, path_id, occurred_at),
  KEY ix_events_name    (site_id, name_id, occurred_at),
  KEY ix_events_session (session_id)
);

CREATE TABLE ps_event_props (
  event_id BIGINT UNSIGNED NOT NULL,
  key_id   INT UNSIGNED NOT NULL,
  value_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (event_id, key_id)
);
```

## 5. Aggregate (Phase 4)

Sie bleiben dauerhaft. Besucher über mehrere Tage sind die Summe der Tageswerte, weil der Hash täglich wechselt (wird so in der Doku erklärt).

```sql
CREATE TABLE ps_agg_hourly (                        -- Diagramme für Heute, Gestern, kurze Zeiträume
  site_id INT UNSIGNED NOT NULL,
  hour    DATETIME     NOT NULL,                    -- Beginn der Stunde in der Zeitzone der Site
  visitors INT UNSIGNED NOT NULL, sessions INT UNSIGNED NOT NULL, pageviews INT UNSIGNED NOT NULL,
  bounces INT UNSIGNED NOT NULL, duration_sum BIGINT UNSIGNED NOT NULL, events INT UNSIGNED NOT NULL,
  PRIMARY KEY (site_id, hour)
);

CREATE TABLE ps_agg_daily (                         -- Kennzahlen und Diagramme ab zwei Tagen
  site_id INT UNSIGNED NOT NULL,
  day     DATE         NOT NULL,                    -- Tag in der Zeitzone der Site
  visitors INT UNSIGNED NOT NULL, sessions INT UNSIGNED NOT NULL, pageviews INT UNSIGNED NOT NULL,
  bounces INT UNSIGNED NOT NULL, duration_sum BIGINT UNSIGNED NOT NULL, events INT UNSIGNED NOT NULL,
  PRIMARY KEY (site_id, day)
);

CREATE TABLE ps_agg_daily_dim (                     -- alle Aufschlüsselungen in einer Tabelle
  site_id INT UNSIGNED NOT NULL,
  day     DATE         NOT NULL,
  dim     TINYINT UNSIGNED NOT NULL,                -- siehe Tabelle unten
  value_id INT UNSIGNED NOT NULL,
  visitors INT UNSIGNED NOT NULL, sessions INT UNSIGNED NOT NULL, hits INT UNSIGNED NOT NULL,
  bounces INT UNSIGNED NOT NULL, duration_sum BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (site_id, day, dim, value_id)
);

CREATE TABLE ps_agg_daily_prop (                    -- Ereignis-Eigenschaften
  site_id INT UNSIGNED NOT NULL, day DATE NOT NULL,
  name_id INT UNSIGNED NOT NULL, key_id INT UNSIGNED NOT NULL, value_id INT UNSIGNED NOT NULL,
  visitors INT UNSIGNED NOT NULL, events INT UNSIGNED NOT NULL,
  PRIMARY KEY (site_id, day, name_id, key_id, value_id)
);

CREATE TABLE ps_agg_daily_goal (                    -- Phase 7
  site_id INT UNSIGNED NOT NULL, day DATE NOT NULL, goal_id INT UNSIGNED NOT NULL,
  visitors INT UNSIGNED NOT NULL, conversions INT UNSIGNED NOT NULL,
  PRIMARY KEY (site_id, day, goal_id)
);
```

| `dim` | Bedeutung | `value_id` verweist auf |
|---|---|---|
| 1 | Seite | `dict_path` |
| 2 / 3 | Einstiegs- / Ausstiegsseite | `dict_path` |
| 4 | Referrer | `dict_referrer` |
| 5–9 | UTM source, medium, campaign, term, content | `dict_utm` |
| 10 | Land | Zahl aus den beiden Buchstaben des Codes |
| 11 | Gerätetyp | 1 bis 3 |
| 12 / 13 | Browser / Betriebssystem | `dict_browser` / `dict_os` |
| 14 | Ereignisname | `dict_event_name` |

## Aufbewahrung

Ein Aufräumjob löscht je Site Rohdaten (`sessions`, `events`, `event_props`) in kleinen Blöcken, sobald sie älter als `retention_days` sind. Aggregate bleiben. Wörterbuch-Einträge ohne Verweis werden gelegentlich entfernt.

## Zeitzonen

Rohdaten liegen in UTC. Tages- und Stundengrenzen der Aggregate folgen der Zeitzone der Site zum Zeitpunkt der Aggregation. Ändert jemand die Zeitzone später, gilt das für neue Aggregate. Vorhandene Rohdaten lassen sich bei Bedarf neu aggregieren.

## Offene Entscheidungen

Siehe die nummerierten Fragen im Bericht. Sie betreffen: Präfix (1), Sitzungszuordnung (2), Query-Strings (3), Versionen (4), Aufbewahrung (5), Salt-Wechsel (6), Ratenbegrenzung (7), Zeitzonen (8), Land (9), Installer-Umfang (10), Einstellungen (11), Hash-Verfahren (12).
