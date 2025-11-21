CREATE TABLE tx_queue_job (
  uid int unsigned NOT NULL auto_increment,
  pid int unsigned DEFAULT '0' NOT NULL,
  tstamp int unsigned DEFAULT '0' NOT NULL,
  crdate int unsigned DEFAULT '0' NOT NULL,
  cruser_id int unsigned DEFAULT '0' NOT NULL,
  queue varchar(64) NOT NULL DEFAULT '',
  payload mediumtext NOT NULL,
  available_at int unsigned NOT NULL DEFAULT '0',
  reserved_at int unsigned NOT NULL DEFAULT '0',
  attempts int unsigned NOT NULL DEFAULT '0',
  max_attempts int unsigned NOT NULL DEFAULT '1',
  last_error text,
  priority smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (uid),
  KEY queue_available_reserved (queue,available_at,reserved_at)
);
