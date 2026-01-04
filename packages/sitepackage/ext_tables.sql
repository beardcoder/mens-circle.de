#
# Table structure for table 'tx_sitepackage_domain_model_subscriber'
#
CREATE TABLE tx_sitepackage_domain_model_subscriber (
    email varchar(255) NOT NULL DEFAULT '',
    status tinyint(1) unsigned NOT NULL DEFAULT '0',
    token varchar(64) NOT NULL DEFAULT '',
    confirmed_at int(11) unsigned DEFAULT NULL,
    unsubscribed_at int(11) unsigned DEFAULT NULL,

    KEY email (email),
    KEY status (status),
    KEY token (token)
);

#
# Table structure for table 'tx_sitepackage_domain_model_newsletter'
#
CREATE TABLE tx_sitepackage_domain_model_newsletter (
    subject varchar(255) NOT NULL DEFAULT '',
    preheader varchar(255) NOT NULL DEFAULT '',
    content text,
    status tinyint(1) unsigned NOT NULL DEFAULT '0',
    scheduled_at int(11) unsigned DEFAULT NULL,
    sent_at int(11) unsigned DEFAULT NULL,
    recipients_count int(11) unsigned NOT NULL DEFAULT '0',
    sent_count int(11) unsigned NOT NULL DEFAULT '0',
    failed_count int(11) unsigned NOT NULL DEFAULT '0',

    KEY status (status)
);

#
# Table structure for table 'tx_sitepackage_domain_model_newsletter_log'
#
CREATE TABLE tx_sitepackage_domain_model_newsletter_log (
    newsletter int(11) unsigned NOT NULL DEFAULT '0',
    subscriber int(11) unsigned NOT NULL DEFAULT '0',
    status tinyint(1) unsigned NOT NULL DEFAULT '0',
    sent_at int(11) unsigned DEFAULT NULL,
    error_message text,

    KEY newsletter (newsletter),
    KEY subscriber (subscriber)
);
