--
-- Forms Module PostgreSQL Database for Phire CMS 2.0
--

-- --------------------------------------------------------

--
-- Table structure for table "forms"
--

CREATE SEQUENCE form_id_seq START 14001;

CREATE TABLE IF NOT EXISTS "[{prefix}]forms" (
  "id" integer NOT NULL DEFAULT nextval('form_id_seq'),
  "method" varchar(255),
  "name" varchar(255),
  "to" varchar(255),
  "from" varchar(255),
  "reply_to" varchar(255),
  "action" varchar(255),
  "redirect" varchar(255),
  "attributes" varchar(255),
  "submit_value" varchar(255),
  "submit_attributes" varchar(255),
  "captcha" integer,
  "csrf" integer,
  "force_ssl" integer,
  PRIMARY KEY ("id")
) ;

ALTER SEQUENCE form_id_seq OWNED BY "[{prefix}]forms"."id";

-- --------------------------------------------------------

--
-- Table structure for table "form_submissions"
--

CREATE SEQUENCE form_submission_id_seq START 15001;

CREATE TABLE IF NOT EXISTS "[{prefix}]form_submissions" (
  "id" integer NOT NULL DEFAULT nextval('form_submission_id_seq'),
  "form_id" integer NOT NULL,
  "timestamp" timestamp,
  "ip_address" varchar(255),
  PRIMARY KEY ("id"),
  CONSTRAINT "fk_form_id" FOREIGN KEY ("form_id") REFERENCES "[{prefix}]forms" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

ALTER SEQUENCE form_submission_id_seq OWNED BY "[{prefix}]form_submissions"."id";