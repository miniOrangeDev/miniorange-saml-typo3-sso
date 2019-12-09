Configuration
=============

Identity Provider Settings
**************************

In the Plugin Settings tab, under the Identity Provider Settings column, fill the necessary configuration options provided by your Identity Provider (IdP). ( Identity Provider Name, IdP Entity Id, SAML Login URL, SAML x509 Certificate ) and click on “Save”. You will get all these inputs by your Identity Provider.

For Example:

* IdP Entity Id https://login.xecurify.com/moas
* Single Sign On URL  https://login.xecurify.com/moas/idp/samlsso
* Single Logout URL https://login.xecurify.com/moas/idp/samllogout
* Identity Provider Certificate Upload the certificate downloaded from miniOrange Admin Console

.. image:: ../Images/IDPSettings.png

Service Provider Settings
*************************

In this tab you have to give the URLs of the pages where you have put fesaml(frontend saml) and resonse plugins.
As you have to configure it and save it.

For Example:

	*URL with FESAML - http://--------/typo/index.php?id=4

	*URL with response - http://--------/typo/index.php?id=4

	*ACS URL - URL with response plugin

	*Site Base URL - http://--------/typo3

	*SP Entity ID - http://--------/typo3

.. image:: ..Images/ServiceProvider.png


The Service Provider (SP) Settings column has the data that you will need to provide to your Identity Provider (IdP).


Attribute mapping
*****************

Attribute mapping maps the incoming attributes from SAML Response to user profile of TYPO3 website.

.. image:: ..Images/AttributeMapping


Support
*******

miniOrange is best known for its 24/7 support. No matter where you are stuck we are always there for you.You can always mail us at `info@miniOrange.com <https://www.miniorange.com/>`__

.. image:: ..Images/AttributeMapping
