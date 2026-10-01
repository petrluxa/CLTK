-- I. ČLTK Praha – schéma databáze
--
-- Kompatibilní s MySQL 5.7+ / MariaDB (Forpsi) i se SQLite (lokální vývoj).
-- Syntaxi pro konkrétní ovladač upravuje inc/db.php (db_install):
--   * INTEGER PRIMARY KEY AUTO_INCREMENT → v SQLite AUTOINCREMENT,
--   * TEXT NOT NULL DEFAULT '' → v MySQL podle verze serveru
--     (MariaDB 10.2+ ponechá, MySQL 8.0.13+ DEFAULT (''), starší TEXT NULL),
--   * každá tabulka se zakládá jako CREATE TABLE IF NOT EXISTS,
--   * v MySQL se přidá ENGINE=InnoDB a utf8mb4_czech_ci.
--
-- POZOR: databáze na serveru je SDÍLENÁ s webem TK Olymp. Všechny tabulky
-- mají předponu cltk_ a jiné se nesmí zakládat ani měnit (hlídá q()).
-- Popis sloupců: sql/SCHEMA.md. Při změně upravte obojí.

-- ================================================================
-- SPRÁVA, NASTAVENÍ, NÁVŠTĚVNOST
-- ================================================================

CREATE TABLE cltk_users (
  id            INTEGER PRIMARY KEY AUTO_INCREMENT,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  jmeno         VARCHAR(120) NOT NULL DEFAULT '',
  role          VARCHAR(20)  NOT NULL DEFAULT 'admin',
  last_login    DATETIME     NULL,
  created_at    DATETIME     NULL
);

CREATE TABLE cltk_login_attempts (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  ip         VARCHAR(64)  NOT NULL,
  tried_at   DATETIME     NOT NULL
);

CREATE TABLE cltk_settings (
  skey       VARCHAR(80)  PRIMARY KEY,
  sval       TEXT         NOT NULL DEFAULT '',
  label      VARCHAR(160) NOT NULL DEFAULT '',
  grp        VARCHAR(40)  NOT NULL DEFAULT 'obecne',
  typ        VARCHAR(20)  NOT NULL DEFAULT 'text',
  napoveda   VARCHAR(255) NOT NULL DEFAULT '',
  poradi     INTEGER      NOT NULL DEFAULT 0
);

CREATE TABLE cltk_visits (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  day        DATE         NOT NULL,
  path       VARCHAR(190) NOT NULL,
  hits       INTEGER      NOT NULL DEFAULT 0,
  UNIQUE (day, path)
);

CREATE TABLE cltk_visit_log (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  day        DATE         NOT NULL,
  visitor    VARCHAR(64)  NOT NULL,
  UNIQUE (day, visitor)
);

-- ================================================================
-- ÚVODNÍ STRÁNKA
-- ================================================================

-- Informační lišta pod menu (zobrazí se jen aktivní zprávy)
CREATE TABLE cltk_oznameni (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  text       VARCHAR(255) NOT NULL DEFAULT '',
  odkaz      VARCHAR(255) NOT NULL DEFAULT '',
  plati_od   DATE         NULL,
  plati_do   DATE         NULL,
  visible    INTEGER      NOT NULL DEFAULT 1,
  poradi     INTEGER      NOT NULL DEFAULT 0,
  created_at DATETIME     NULL,
  updated_at DATETIME     NULL
);

-- Úvodní galerie (snímek typu fotka, nebo navy deska s výsledkem)
CREATE TABLE cltk_uvodni_galerie (
  id           INTEGER PRIMARY KEY AUTO_INCREMENT,
  typ          VARCHAR(10)  NOT NULL DEFAULT 'foto',
  rejstrik     VARCHAR(60)  NOT NULL DEFAULT '',
  foto         VARCHAR(255) NOT NULL DEFAULT '',
  foto_w       INTEGER      NOT NULL DEFAULT 0,
  foto_h       INTEGER      NOT NULL DEFAULT 0,
  fokus        VARCHAR(20)  NOT NULL DEFAULT '50% 50%',
  alt          VARCHAR(255) NOT NULL DEFAULT '',
  popisek      VARCHAR(255) NOT NULL DEFAULT '',
  kredit       VARCHAR(160) NOT NULL DEFAULT '',
  deska_stitek VARCHAR(120) NOT NULL DEFAULT '',
  deska_titul  VARCHAR(160) NOT NULL DEFAULT '',
  deska_tym_a  VARCHAR(60)  NOT NULL DEFAULT '',
  deska_skore  VARCHAR(20)  NOT NULL DEFAULT '',
  deska_tym_b  VARCHAR(60)  NOT NULL DEFAULT '',
  deska_hrac   VARCHAR(120) NOT NULL DEFAULT '',
  deska_souper VARCHAR(120) NOT NULL DEFAULT '',
  deska_sety   VARCHAR(60)  NOT NULL DEFAULT '',
  deska_misto  VARCHAR(160) NOT NULL DEFAULT '',
  visible      INTEGER      NOT NULL DEFAULT 1,
  poradi       INTEGER      NOT NULL DEFAULT 0,
  created_at   DATETIME     NULL,
  updated_at   DATETIME     NULL
);

-- Aktuality z klubu – NE články: fotka, nadpis, krátký popis, volitelně odkaz
CREATE TABLE cltk_aktuality (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  nadpis     VARCHAR(160) NOT NULL DEFAULT '',
  popis      TEXT         NOT NULL DEFAULT '',
  foto       VARCHAR(255) NOT NULL DEFAULT '',
  fokus      VARCHAR(20)  NOT NULL DEFAULT '50% 50%',
  odkaz      VARCHAR(255) NOT NULL DEFAULT '',
  odkaz_text VARCHAR(80)  NOT NULL DEFAULT '',
  visible    INTEGER      NOT NULL DEFAULT 1,
  poradi     INTEGER      NOT NULL DEFAULT 0,
  created_at DATETIME     NULL,
  updated_at DATETIME     NULL
);

-- Výsledky hráčů (sety = JSON pole řetězců, např. ["6:2","6:3"])
CREATE TABLE cltk_vysledky (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  datum      DATE         NULL,
  misto      VARCHAR(80)  NOT NULL DEFAULT '',
  stitek     VARCHAR(200) NOT NULL DEFAULT '',
  hraci      VARCHAR(200) NOT NULL DEFAULT '',
  souper     VARCHAR(200) NOT NULL DEFAULT '',
  text       TEXT         NOT NULL DEFAULT '',
  sety       VARCHAR(255) NOT NULL DEFAULT '[]',
  verdikt    VARCHAR(40)  NOT NULL DEFAULT '',
  odkaz      VARCHAR(255) NOT NULL DEFAULT '',
  foto       VARCHAR(255) NOT NULL DEFAULT '',
  visible    INTEGER      NOT NULL DEFAULT 1,
  poradi     INTEGER      NOT NULL DEFAULT 0,
  created_at DATETIME     NULL,
  updated_at DATETIME     NULL
);

-- ================================================================
-- KALENDÁŘ AKCÍ A PŘIHLÁŠKY
-- ================================================================

-- formular = JSON nastavení přihlášky (která pole, vlastní pole) – viz SCHEMA.md
CREATE TABLE cltk_akce (
  id                   INTEGER PRIMARY KEY AUTO_INCREMENT,
  nazev                VARCHAR(200) NOT NULL DEFAULT '',
  rok                  INTEGER      NOT NULL DEFAULT 0,
  mesic                INTEGER      NOT NULL DEFAULT 0,
  datum_od             DATE         NULL,
  datum_do             DATE         NULL,
  termin_text          VARCHAR(120) NOT NULL DEFAULT '',
  cas                  VARCHAR(60)  NOT NULL DEFAULT '',
  misto                VARCHAR(160) NOT NULL DEFAULT '',
  stitek               VARCHAR(80)  NOT NULL DEFAULT '',
  perex                TEXT         NOT NULL DEFAULT '',
  popis                TEXT         NOT NULL DEFAULT '',
  foto                 VARCHAR(255) NOT NULL DEFAULT '',
  odkaz                VARCHAR(255) NOT NULL DEFAULT '',
  odkaz_text           VARCHAR(80)  NOT NULL DEFAULT '',
  prihlaseni_povoleno  INTEGER      NOT NULL DEFAULT 0,
  formular             TEXT         NOT NULL DEFAULT '',
  prihlaseni_do        DATE         NULL,
  kapacita             INTEGER      NULL,
  poznamka_interni     TEXT         NOT NULL DEFAULT '',
  visible              INTEGER      NOT NULL DEFAULT 1,
  poradi               INTEGER      NOT NULL DEFAULT 0,
  created_at           DATETIME     NULL,
  updated_at           DATETIME     NULL
);

-- Přihlášky k akcím (tlačítko „Přihlásit se“). Standardní pole ve sloupcích,
-- vlastní pole akce (cltk_akce.formular) v JSON odpovedi.
CREATE TABLE cltk_signups (
  id             INTEGER PRIMARY KEY AUTO_INCREMENT,
  akce_id        INTEGER      NOT NULL,
  jmeno          VARCHAR(160) NOT NULL DEFAULT '',
  email          VARCHAR(160) NOT NULL DEFAULT '',
  telefon        VARCHAR(40)  NOT NULL DEFAULT '',
  pocet          INTEGER      NOT NULL DEFAULT 1,
  poznamka       TEXT         NOT NULL DEFAULT '',
  souhlas        INTEGER      NOT NULL DEFAULT 0,
  odpovedi       TEXT         NOT NULL DEFAULT '',
  vyrizeno       INTEGER      NOT NULL DEFAULT 0,
  poznamka_admin TEXT         NOT NULL DEFAULT '',
  created_at     DATETIME     NULL
);

-- Přihlášky do klubu z clenstvi.php (celý formulář je v JSON data)
CREATE TABLE cltk_prihlasky_clenstvi (
  id               INTEGER PRIMARY KEY AUTO_INCREMENT,
  jmeno            VARCHAR(80)  NOT NULL DEFAULT '',
  prijmeni         VARCHAR(80)  NOT NULL DEFAULT '',
  email            VARCHAR(160) NOT NULL DEFAULT '',
  telefon          VARCHAR(40)  NOT NULL DEFAULT '',
  typ_clenstvi     VARCHAR(160) NOT NULL DEFAULT '',
  cena             INTEGER      NULL,
  pocet_osob       INTEGER      NOT NULL DEFAULT 1,
  data             TEXT         NOT NULL DEFAULT '',
  souhlas_gdpr     INTEGER      NOT NULL DEFAULT 0,
  souhlas_podminky INTEGER      NOT NULL DEFAULT 0,
  stav             VARCHAR(20)  NOT NULL DEFAULT 'nova',
  poznamka_admin   TEXT         NOT NULL DEFAULT '',
  created_at       DATETIME     NULL,
  updated_at       DATETIME     NULL
);

-- ================================================================
-- TRENÉŘI, CENÍKY, TENISOVÁ ŠKOLA, AREÁL
-- ================================================================

-- zarazeni: zavodni | skola | privatni (kdo je ve dvou, má dva řádky)
CREATE TABLE cltk_treneri (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  jmeno      VARCHAR(120) NOT NULL DEFAULT '',
  role       VARCHAR(160) NOT NULL DEFAULT '',
  zarazeni   VARCHAR(20)  NOT NULL DEFAULT 'zavodni',
  skupina    VARCHAR(120) NOT NULL DEFAULT '',
  foto       VARCHAR(255) NOT NULL DEFAULT '',
  fokus      VARCHAR(20)  NOT NULL DEFAULT '50% 20%',
  fakta      TEXT         NOT NULL DEFAULT '',
  text       TEXT         NOT NULL DEFAULT '',
  telefon    VARCHAR(40)  NOT NULL DEFAULT '',
  email      VARCHAR(160) NOT NULL DEFAULT '',
  kontakt    VARCHAR(255) NOT NULL DEFAULT '',
  visible    INTEGER      NOT NULL DEFAULT 1,
  poradi     INTEGER      NOT NULL DEFAULT 0,
  created_at DATETIME     NULL,
  updated_at DATETIME     NULL
);

-- Ceníky: list (klic) → sekce → řádky.
-- klic: kurty-leto | kurty-zima | clenstvi | skola | kempy | doplnkove
CREATE TABLE cltk_price_lists (
  id              INTEGER PRIMARY KEY AUTO_INCREMENT,
  klic            VARCHAR(40)  NOT NULL UNIQUE,
  nazev           VARCHAR(160) NOT NULL DEFAULT '',
  podnazev        VARCHAR(200) NOT NULL DEFAULT '',
  obdobi          VARCHAR(120) NOT NULL DEFAULT '',
  poznamka_nahore TEXT         NOT NULL DEFAULT '',
  poznamka_dole   TEXT         NOT NULL DEFAULT '',
  pdf_url         VARCHAR(255) NOT NULL DEFAULT '',
  visible         INTEGER      NOT NULL DEFAULT 1,
  poradi          INTEGER      NOT NULL DEFAULT 0,
  updated_at      DATETIME     NULL
);

CREATE TABLE cltk_price_sections (
  id                  INTEGER PRIMARY KEY AUTO_INCREMENT,
  list_id             INTEGER      NOT NULL,
  nazev               VARCHAR(160) NOT NULL DEFAULT '',
  popis               VARCHAR(255) NOT NULL DEFAULT '',
  hl_nazev            VARCHAR(60)  NOT NULL DEFAULT '',
  hl_cena             VARCHAR(60)  NOT NULL DEFAULT '',
  hl_cena_clen        VARCHAR(60)  NOT NULL DEFAULT '',
  hl_cena_sezona      VARCHAR(60)  NOT NULL DEFAULT '',
  hl_cena_sezona_clen VARCHAR(60)  NOT NULL DEFAULT '',
  poradi              INTEGER      NOT NULL DEFAULT 0
);

CREATE TABLE cltk_price_rows (
  id               INTEGER PRIMARY KEY AUTO_INCREMENT,
  section_id       INTEGER      NOT NULL,
  nazev            VARCHAR(255) NOT NULL DEFAULT '',
  poznamka         VARCHAR(255) NOT NULL DEFAULT '',
  cena             VARCHAR(80)  NOT NULL DEFAULT '',
  cena_clen        VARCHAR(80)  NOT NULL DEFAULT '',
  cena_sezona      VARCHAR(80)  NOT NULL DEFAULT '',
  cena_sezona_clen VARCHAR(80)  NOT NULL DEFAULT '',
  poradi           INTEGER      NOT NULL DEFAULT 0
);

-- Tenisová škola: typ = info | harmonogram | rozvrh | kemp
CREATE TABLE cltk_skola (
  id          INTEGER PRIMARY KEY AUTO_INCREMENT,
  typ         VARCHAR(20)  NOT NULL DEFAULT 'info',
  nazev       VARCHAR(200) NOT NULL DEFAULT '',
  datum_od    DATE         NULL,
  datum_do    DATE         NULL,
  termin_text VARCHAR(120) NOT NULL DEFAULT '',
  den         VARCHAR(30)  NOT NULL DEFAULT '',
  cas         VARCHAR(60)  NOT NULL DEFAULT '',
  skupina     VARCHAR(120) NOT NULL DEFAULT '',
  misto       VARCHAR(120) NOT NULL DEFAULT '',
  trener      VARCHAR(160) NOT NULL DEFAULT '',
  cena        VARCHAR(120) NOT NULL DEFAULT '',
  text        TEXT         NOT NULL DEFAULT '',
  odkaz       VARCHAR(255) NOT NULL DEFAULT '',
  visible     INTEGER      NOT NULL DEFAULT 1,
  poradi      INTEGER      NOT NULL DEFAULT 0,
  created_at  DATETIME     NULL,
  updated_at  DATETIME     NULL
);

-- Služby v areálu (je_stitek = 1 → pilulka „Služby v areálu“ na úvodu)
CREATE TABLE cltk_sluzby (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  nazev      VARCHAR(120) NOT NULL DEFAULT '',
  kotva      VARCHAR(60)  NOT NULL DEFAULT '',
  perex      VARCHAR(255) NOT NULL DEFAULT '',
  text       TEXT         NOT NULL DEFAULT '',
  fakta      TEXT         NOT NULL DEFAULT '',
  foto       VARCHAR(255) NOT NULL DEFAULT '',
  fokus      VARCHAR(20)  NOT NULL DEFAULT '50% 50%',
  casy       VARCHAR(160) NOT NULL DEFAULT '',
  odkaz      VARCHAR(255) NOT NULL DEFAULT '',
  je_stitek  INTEGER      NOT NULL DEFAULT 0,
  visible    INTEGER      NOT NULL DEFAULT 1,
  poradi     INTEGER      NOT NULL DEFAULT 0,
  created_at DATETIME     NULL,
  updated_at DATETIME     NULL
);

-- ================================================================
-- HISTORIE
-- ================================================================

-- Kronika – milníky
CREATE TABLE cltk_milniky (
  id           INTEGER PRIMARY KEY AUTO_INCREMENT,
  rok          INTEGER      NOT NULL DEFAULT 0,
  rok_text     VARCHAR(60)  NOT NULL DEFAULT '',
  era          VARCHAR(120) NOT NULL DEFAULT '',
  titulek      VARCHAR(200) NOT NULL DEFAULT '',
  text         TEXT         NOT NULL DEFAULT '',
  foto         VARCHAR(255) NOT NULL DEFAULT '',
  foto_popisek VARCHAR(255) NOT NULL DEFAULT '',
  zdroj        TEXT         NOT NULL DEFAULT '',
  jistota      VARCHAR(255) NOT NULL DEFAULT '',
  visible      INTEGER      NOT NULL DEFAULT 1,
  poradi       INTEGER      NOT NULL DEFAULT 0
);

-- Triptych „Tři wimbledonské trávy“ (úvod + historie)
CREATE TABLE cltk_triptych (
  id           INTEGER PRIMARY KEY AUTO_INCREMENT,
  rok          VARCHAR(10)  NOT NULL DEFAULT '',
  jmeno        VARCHAR(120) NOT NULL DEFAULT '',
  disciplina   VARCHAR(160) NOT NULL DEFAULT '',
  foto         VARCHAR(255) NOT NULL DEFAULT '',
  fokus        VARCHAR(20)  NOT NULL DEFAULT '50% 30%',
  alt          VARCHAR(255) NOT NULL DEFAULT '',
  vitez        VARCHAR(80)  NOT NULL DEFAULT '',
  souper       VARCHAR(80)  NOT NULL DEFAULT '',
  sety         VARCHAR(255) NOT NULL DEFAULT '[]',
  hral_za      VARCHAR(80)  NOT NULL DEFAULT '',
  hral_za_text TEXT         NOT NULL DEFAULT '',
  visible      INTEGER      NOT NULL DEFAULT 1,
  poradi       INTEGER      NOT NULL DEFAULT 0
);

-- Osobnosti klubu – medailony „Od Žemly po Muchovou“
CREATE TABLE cltk_osobnosti (
  id           INTEGER PRIMARY KEY AUTO_INCREMENT,
  jmeno        VARCHAR(120) NOT NULL DEFAULT '',
  kategorie    VARCHAR(80)  NOT NULL DEFAULT '',
  roky         VARCHAR(80)  NOT NULL DEFAULT '',
  cin          TEXT         NOT NULL DEFAULT '',
  foto         VARCHAR(255) NOT NULL DEFAULT '',
  fokus        VARCHAR(20)  NOT NULL DEFAULT '50% 10%',
  alt          VARCHAR(255) NOT NULL DEFAULT '',
  pramen       VARCHAR(255) NOT NULL DEFAULT '',
  jistota      VARCHAR(20)  NOT NULL DEFAULT 'overeno',
  jistota_text VARCHAR(80)  NOT NULL DEFAULT '',
  visible      INTEGER      NOT NULL DEFAULT 1,
  poradi       INTEGER      NOT NULL DEFAULT 0
);

-- Zlatá deska. kategorie: cestni | zasluzili | mistri | grandslam | oh | prezidenti
-- skupina: hlavni (řádek s rokem a činem) | jmena (jen jméno do souvislého výčtu)
CREATE TABLE cltk_deska_zaznamy (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  kategorie  VARCHAR(20)  NOT NULL DEFAULT 'cestni',
  skupina    VARCHAR(20)  NOT NULL DEFAULT 'hlavni',
  rok        VARCHAR(30)  NOT NULL DEFAULT '',
  jmeno      VARCHAR(160) NOT NULL DEFAULT '',
  cin        VARCHAR(255) NOT NULL DEFAULT '',
  pramen     VARCHAR(120) NOT NULL DEFAULT '',
  metr       VARCHAR(40)  NOT NULL DEFAULT '',
  historie   INTEGER      NOT NULL DEFAULT 0,
  visible    INTEGER      NOT NULL DEFAULT 1,
  poradi     INTEGER      NOT NULL DEFAULT 0
);

-- ================================================================
-- REVUE, NEWSLETTERY, VEDENÍ, CTC
-- ================================================================

-- titulky = JSON pole řetězců, obsah = JSON pole dvojic ["strana","titulek"]
CREATE TABLE cltk_revue (
  id            INTEGER PRIMARY KEY AUTO_INCREMENT,
  rok           INTEGER      NOT NULL DEFAULT 0,
  cislo         INTEGER      NOT NULL DEFAULT 1,
  oznaceni      VARCHAR(20)  NOT NULL DEFAULT '',
  obalka        VARCHAR(255) NOT NULL DEFAULT '',
  obalka_popis  VARCHAR(255) NOT NULL DEFAULT '',
  titulky       TEXT         NOT NULL DEFAULT '',
  obsah         TEXT         NOT NULL DEFAULT '',
  stran         INTEGER      NULL,
  naklad        VARCHAR(60)  NOT NULL DEFAULT '',
  uzaverka      VARCHAR(60)  NOT NULL DEFAULT '',
  pdf_url       VARCHAR(255) NOT NULL DEFAULT '',
  pdf_soubor    VARCHAR(255) NOT NULL DEFAULT '',
  pdf_mb        VARCHAR(10)  NOT NULL DEFAULT '',
  visible       INTEGER      NOT NULL DEFAULT 1,
  poradi        INTEGER      NOT NULL DEFAULT 0
);

CREATE TABLE cltk_newslettery (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  rok        INTEGER      NOT NULL DEFAULT 0,
  cislo      VARCHAR(10)  NOT NULL DEFAULT '',
  oznaceni   VARCHAR(40)  NOT NULL DEFAULT '',
  nazev      VARCHAR(160) NOT NULL DEFAULT '',
  pdf_cs     VARCHAR(255) NOT NULL DEFAULT '',
  pdf_en     VARCHAR(255) NOT NULL DEFAULT '',
  visible    INTEGER      NOT NULL DEFAULT 1,
  poradi     INTEGER      NOT NULL DEFAULT 0
);

-- skupina: vybor | kancelar | kontakt
CREATE TABLE cltk_vedeni (
  id               INTEGER PRIMARY KEY AUTO_INCREMENT,
  skupina          VARCHAR(20)  NOT NULL DEFAULT 'vybor',
  jmeno            VARCHAR(120) NOT NULL DEFAULT '',
  funkce           VARCHAR(160) NOT NULL DEFAULT '',
  telefon          VARCHAR(40)  NOT NULL DEFAULT '',
  email            VARCHAR(160) NOT NULL DEFAULT '',
  zobrazit_kontakt INTEGER      NOT NULL DEFAULT 1,
  foto             VARCHAR(255) NOT NULL DEFAULT '',
  text             TEXT         NOT NULL DEFAULT '',
  visible          INTEGER      NOT NULL DEFAULT 1,
  poradi           INTEGER      NOT NULL DEFAULT 0
);

-- typ: klub (klub CTC, který hrál na Štvanici) | utkani | soutez | rodokmen | fakt
CREATE TABLE cltk_ctc (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  typ        VARCHAR(20)  NOT NULL DEFAULT 'utkani',
  nazev      VARCHAR(200) NOT NULL DEFAULT '',
  rok        VARCHAR(30)  NOT NULL DEFAULT '',
  misto      VARCHAR(120) NOT NULL DEFAULT '',
  text       TEXT         NOT NULL DEFAULT '',
  odkaz      VARCHAR(255) NOT NULL DEFAULT '',
  zdroj      VARCHAR(255) NOT NULL DEFAULT '',
  zvyraznit  INTEGER      NOT NULL DEFAULT 0,
  visible    INTEGER      NOT NULL DEFAULT 1,
  poradi     INTEGER      NOT NULL DEFAULT 0
);

-- ================================================================
-- STRÁNKY, PARTNEŘI, DOKUMENTY
-- ================================================================

-- Editovatelné textové bloky podstránek (stranka = soubor bez .php)
CREATE TABLE cltk_bloky (
  id           INTEGER PRIMARY KEY AUTO_INCREMENT,
  stranka      VARCHAR(60)  NOT NULL DEFAULT '',
  klic         VARCHAR(60)  NOT NULL DEFAULT '',
  stitek       VARCHAR(120) NOT NULL DEFAULT '',
  nadpis       VARCHAR(255) NOT NULL DEFAULT '',
  perex        TEXT         NOT NULL DEFAULT '',
  text         TEXT         NOT NULL DEFAULT '',
  foto         VARCHAR(255) NOT NULL DEFAULT '',
  foto_popisek VARCHAR(255) NOT NULL DEFAULT '',
  odkaz        VARCHAR(255) NOT NULL DEFAULT '',
  odkaz_text   VARCHAR(80)  NOT NULL DEFAULT '',
  odkaz2       VARCHAR(255) NOT NULL DEFAULT '',
  odkaz2_text  VARCHAR(80)  NOT NULL DEFAULT '',
  doplni_klub  INTEGER      NOT NULL DEFAULT 0,
  visible      INTEGER      NOT NULL DEFAULT 1,
  poradi       INTEGER      NOT NULL DEFAULT 0,
  updated_at   DATETIME     NULL,
  UNIQUE (stranka, klic)
);

CREATE TABLE cltk_partneri (
  id         INTEGER PRIMARY KEY AUTO_INCREMENT,
  nazev      VARCHAR(120) NOT NULL DEFAULT '',
  url        VARCHAR(255) NOT NULL DEFAULT '',
  logo       VARCHAR(255) NOT NULL DEFAULT '',
  logo_mono  VARCHAR(255) NOT NULL DEFAULT '',
  visible    INTEGER      NOT NULL DEFAULT 1,
  poradi     INTEGER      NOT NULL DEFAULT 0,
  created_at DATETIME     NULL,
  updated_at DATETIME     NULL
);

-- Dokumenty ke stažení (soubor = nahrané PDF v uploads/, nebo url = odkaz ven)
CREATE TABLE cltk_dokumenty (
  id            INTEGER PRIMARY KEY AUTO_INCREMENT,
  nazev         VARCHAR(200) NOT NULL DEFAULT '',
  kategorie     VARCHAR(40)  NOT NULL DEFAULT 'klub',
  popis         VARCHAR(255) NOT NULL DEFAULT '',
  soubor        VARCHAR(255) NOT NULL DEFAULT '',
  soubor_nazev  VARCHAR(160) NOT NULL DEFAULT '',
  url           VARCHAR(255) NOT NULL DEFAULT '',
  v_paticce     INTEGER      NOT NULL DEFAULT 0,
  paticka_text  VARCHAR(120) NOT NULL DEFAULT '',
  visible       INTEGER      NOT NULL DEFAULT 1,
  poradi        INTEGER      NOT NULL DEFAULT 0,
  created_at    DATETIME     NULL,
  updated_at    DATETIME     NULL
);
