--
-- Forms Module SQLite Database for Phire CMS 2.0
--

--  --------------------------------------------------------

--
-- Set database encoding
--

PRAGMA encoding = "UTF-8";
PRAGMA foreign_keys = ON;

-- --------------------------------------------------------

--
-- Table structure for table "forms"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]forms" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "method" varchar,
  "name" varchar,
  "to" varchar,
  "from" varchar,
  "reply_to" varchar,
  "action" varchar,
  "redirect" varchar,
  "attributes" varchar,
  "submit_value" varchar,
  "submit_attributes" varchar,
  "use_captcha" integer,
  "use_csrf" integer,
  "force_ssl" integer,
  UNIQUE ("id")
) ;

INSERT INTO sqlite_sequence ("name", "seq") VALUES ('[{prefix}]forms', 14000);

-- --------------------------------------------------------

--
-- Table structure for table "form_submissions"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]form_submissions" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "form_id" integer NOT NULL,
  "timestamp" datetime,
  "ip_address" varchar,
  UNIQUE ("id"),
  CONSTRAINT "fk_form_id" FOREIGN KEY ("form_id") REFERENCES "[{prefix}]forms" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

INSERT INTO sqlite_sequence ("name", "seq") VALUES ('[{prefix}]form_submissions', 15000);
