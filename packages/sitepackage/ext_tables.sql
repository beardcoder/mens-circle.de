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

#
# Table structure for table 'tx_sitepackage_domain_model_event'
#
CREATE TABLE tx_sitepackage_domain_model_event (
    title varchar(255) NOT NULL DEFAULT '',
    slug varchar(255) NOT NULL DEFAULT '',
    description text,
    image int(11) unsigned NOT NULL DEFAULT '0',
    event_date int(11) unsigned DEFAULT NULL,
    start_time int(11) unsigned DEFAULT NULL,
    end_time int(11) unsigned DEFAULT NULL,
    location varchar(255) NOT NULL DEFAULT 'Straubing',
    street varchar(255) NOT NULL DEFAULT '',
    postal_code varchar(20) NOT NULL DEFAULT '',
    city varchar(255) NOT NULL DEFAULT '',
    location_details text,
    max_participants int(11) unsigned NOT NULL DEFAULT '8',
    cost_basis varchar(255) NOT NULL DEFAULT 'Auf Spendenbasis',
    is_published tinyint(1) unsigned NOT NULL DEFAULT '0',
    sorting int(11) NOT NULL DEFAULT '0',

    KEY event_date (event_date),
    KEY is_published (is_published),
    KEY slug (slug)
);

#
# Table structure for table 'tx_sitepackage_domain_model_eventregistration'
#
CREATE TABLE tx_sitepackage_domain_model_eventregistration (
    event int(11) unsigned NOT NULL DEFAULT '0',
    first_name varchar(255) NOT NULL DEFAULT '',
    last_name varchar(255) NOT NULL DEFAULT '',
    email varchar(255) NOT NULL DEFAULT '',
    phone_number varchar(50) NOT NULL DEFAULT '',
    privacy_accepted tinyint(1) unsigned NOT NULL DEFAULT '0',
    status varchar(20) NOT NULL DEFAULT 'confirmed',
    confirmed_at int(11) unsigned DEFAULT NULL,

    KEY event (event),
    KEY email (email),
    KEY status (status),
    UNIQUE event_email (event, email)
);
