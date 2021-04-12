<?php

namespace Miniorange\Helper;

class Constants
{
    //SAML Constants
    const SAML 	 			= 'SAML';
    const AUTHN_REQUEST 	= 'AuthnRequest';
    const SAML_RESPONSE 	= 'SamlResponse';
    const HTTP_REDIRECT 	= 'HttpRedirect';
    const LOGOUT_REQUEST 	= 'LogoutRequest';

    //Tables
    const TABLE_SAML = 'saml';
    const TABLE_CUSTOMER = 'customer';
    const TABLE_FE_USERS = 'fe_users';

    //IDP
    const COLUMN_IDP_NAME = 'idp_name';
    const COLUMN_IDP_ENTITY_ID = 'idp_entity_id';
    const COLUMN_IDP_BINDING_TYPE = 'login_binding_type';
    const COLUMN_IDP_LOGOUT_URL = 'saml_logout_url';
    const COLUMN_IDP_LOGIN_URL = 'saml_login_url';
    const COLUMN_IDP_CERTIFICATE = 'x509_certificate';

    //Column SAML
    const COLUMN_SP_LOGOUT_URL = 'slo_url';
    const COLUMN_SP_ACS_URL = 'acs_url';
    const COLUMN_SP_ENTITY_ID = 'sp_entity_id';
    const COLUMN_PLUGIN_RESPONSE_URL = 'response';
    const COLUMN_PLUGIN_FESAML_URL = 'fesaml';
    const COLUMN_SITE_BASE_URL = 'site_base_url';

    //Groups & Objects
    const COLUMN_OBJECT_SP = 'spobject';
    const COLUMN_OBJECT_IDP = 'object';
    const COLUMN_OBJECT_ATTR = 'attrobject';
    const COLUMN_GROUP_DEFAULT = 'defaultGroup';

    //file Names
    const RESOURCE_FOLDER   = 'resources';
    const TEST_RELAYSTATE   = 'testconfig';

    //images
    const IMAGE_RIGHT 		= 'right.png';
    const IMAGE_WRONG 		= 'wrong.png';
  	const HOSTNAME        = "https://login.xecurify.com";
	const HASH            = 'aec500ad83a2aaaa7d676c56d8015509d439d56e0e1726b847197f7f089dd8ed';

// DATABASE COLUMNS IN CUSTOMER TABLE

	 const CUSTOMER_EMAIL = "cust_email";
	 const CUSTOMER_KEY = "cust_key";
	 const CUSTOMER_API_KEY = "cust_api_key";
	 const CUSTOMER_TOKEN = "cust_token";
	 const CUSTOMER_REGSTATUS = "cust_reg_status";
	 const AREA_OF_INTEREST = "TYPO3 miniOrange SAML SP";

}
