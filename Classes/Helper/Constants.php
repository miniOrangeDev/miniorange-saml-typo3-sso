<?php

namespace Miniorange\Sp\Helper;

class Constants
{
    //SAML Constants
    const SAML = 'SAML';
    const AUTHN_REQUEST = 'AuthnRequest';
    const SAML_RESPONSE = 'SamlResponse';
    const HTTP_REDIRECT = 'HttpRedirect';
    const FE_USER_EMAIL = "fe_email";
    const FEUSER_TYPO3_SES_INDEX = "fe_ses_id";
    const FEUSER_IDP_SESSION_INDEX = "session_index";

    //Table Names
    const TABLE_SAML = 'saml';
    const TABLE_CUSTOMER = 'customer';
    const TABLE_FE_USERS = 'fe_users';
    const TABLE_SPSETTINGS = 'spsettings';

    //COLUMNS IN SPSETTINGS TABLE
    const SAML_SPOBJECT = 'spobject';

    //IDP
    const COLUMN_IDP_NAME = 'idp_name';
    const COLUMN_IDP_ENTITY_ID = 'idp_entity_id';
    const COLUMN_IDP_LOGIN_BINDING_TYPE = 'login_binding_type';
    const COLUMN_IDP_LOGIN_URL = 'saml_login_url';
    const COLUMN_SAML_LOGOUT_URL = 'saml_logout_url';
    const COLUMN_IDP_CERTIFICATE = 'x509_certificate';

    //Column SAML
    const COLUMN_SP_ACS_URL = 'acs_url';
    const COLUMN_SP_ENTITY_ID = 'sp_entity_id';
    const COLUMN_PLUGIN_RESPONSE_URL = 'response';
    const COLUMN_PLUGIN_FESAML_URL = 'fesaml';
    const COLUMN_SITE_BASE_URL = 'site_base_url';
    const COLUMN_COUNTUSER = 'countuser';

    // COLUMNS IN CUSTOMER TABLE
    const CUSTOMER_EMAIL = "cust_email";
    const CUSTOMER_KEY = "cust_key";
    const CUSTOMER_API_KEY = "cust_api_key";
    const CUSTOMER_TOKEN = "cust_token";
    const CUSTOMER_REGSTATUS = "cust_reg_status";
    const CUSTOMER_CODE = "cust_code";
    const IDP_LIMIT = 'idplimit';
    //SAML Table Columns
    const IDP_X509_CERTIFICATE = 'x509_certificate';
    const IDP_LOGIN_URL = 'saml_login_url';
    const SAML_IDPOBJECT = 'object';

    const REDIRECT_URL = 'login_redirect_url';
    const SAML_LOGOUT_URL = 'slo_url';

    const SAML_GMOBJECT = 'gmobject';
    const SAML_ATTROBJECT = 'attrobject';
    const SAML_CUSTOM_ATTROBJECT = 'cattrobject';
    const TIMESTAMP = 'timestamp';

    //ATTRIBUTE TABLE COLUMNS
    const ATTRIBUTE_USERNAME = 'saml_am_username';
    const ATTRIBUTE_EMAIL = 'saml_am_email';
    const ATTRIBUTE_FNAME = 'saml_am_fname';
    const ATTRIBUTE_LNAME = 'saml_am_lname';
    const ATTRIBUTE_ADDRESS = 'saml_am_address';
    const ATTRIBUTE_PHONE = 'saml_am_phone';
    const ATTRIBUTE_CITY = 'saml_am_city';
    const ATTRIBUTE_COUNTRY = 'saml_am_country';
    const ATTRIBUTE_GROUPS = 'saml_am_groups';
    const ATTRIBUTE_ZIP = 'saml_am_zip';
    const ATTRIBUTE_TITLE = 'saml_am_title';
    const ATTRIBUTE_UPDATE = 'saml_am_update_attr';

    //Groups & Objects
    const COLUMN_OBJECT_SP = 'spobject';
    const COLUMN_OBJECT_IDP = 'object';
    const COLUMN_OBJECT_ATTR = 'attrobject';
    const COLUMN_GROUP_DEFAULT = 'defaultGroup';

    //Names
    const SP_CERT = 'sp-cert.crt';
    const SP_KEY = 'sp-cert.key';
    const RESOURCE_FOLDER = 'resources';
    const TEST_RELAYSTATE = 'testconfig';
    const SP_ALTERNATE_CERT = 'miniorange_sp_cert.crt';
    const SP_ALTERNATE_KEY = 'miniorange_sp_priv_key.key';

    //Images
    const IMAGE_RIGHT = 'right.png';
    const IMAGE_WRONG = 'wrong.png';

    const HOSTNAME = 'https://login.xecurify.com';
    const HASH = 'aec500ad83a2aaaa7d676c56d8015509d439d56e0e1726b847197f7f089dd8ed';
    const APPLICATION_NAME = 'typo3_saml_premium_plan';

    const AREA_OF_INTEREST = "TYPO3 miniOrange SAML";

    const ENCRYPT_TOKEN = "BEOESIA9Q3G7FZ3";
    const DEFAULT_CUSTOMER_KEY     = "16555";
    const DEFAULT_API_KEY         = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
    const EMAIL_SENT = 'isEmailSent';
    const TEST_CONFIG_EMAIL_SENT = 'test_config_email_sent';
    const AUTOCREATE_EXCEED_EMAIL_SENT = 'autocreate_exceed_email_sent';

    const EXTENSION_KEY = 'sp';
    const PLUGIN_METRICS_API = 'https://magento.shanekatear.in/plugin-portal/api/tracking';

}
