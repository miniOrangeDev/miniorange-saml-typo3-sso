This is a Typo3 extension from miniOrange Inc. for SAML Single Sign On SSO (Backend + Frontend).
Feel free to point out any bugs or issues.
For any query or to enable premium features, contact us through the support form in the extension itself.
Also, you can email magentosupport@xecurify.com or visit https://www.miniorange.com/contact.

Installation Instructions:

1. Composer Installation:
    Run the below command to install the extension:
    - composer require miniorange/miniorange-saml

            OR

2. Manual Installation:
    - Unzip the plugin zip into the typo3conf/ext folder, rename the plugin folder to sp and activate the extension from the Extensions section in Typo3.


Pre-requirements Before Configuring SP-Configurations
*****************************************************

You must have atleast two pages before configuring the extension.

.. tip:: Pages can be created by the following steps:-

3. Navigate to the Pages section and create new standard page with name fesaml and add fesaml plugin to it.
4. Similarly create a page named response and and response plugin to it.
5. Create SSO Login button in your Typo3 Frontend Site and embed the fesaml page URL to it in order to initiate the SSO.


Service Provider Metadata Configurations
****************************************

6. Once the extension is installed successfully, navigate to the SP settings tab and configure the fields as below:
	- Fesaml plugin page URL: {URL of the Fesaml standard page created in earlier steps}
	- Response plugin page URL: {URL of the Response standard page created in earlier steps}
	- Base URL: {Base URL of your Typo3 site}
	- ACS URL: {URL of the Response standard page created in earlier steps}
	- Issuer/Entity ID: {Base URL of your Typo3 site}

7. Once you save the above details, you can download the SP XML Metadata or you can use XML Metadata URL to upload it in your Identity Provider or you can upload it manually.

8. You can choose the setup guide specific to your Identity Provider from below link:
https://plugins.miniorange.com/typo3-saml-sso-setup-guides


Identity Provider Metadata Configurations (Using XML Metadata)
--------------------------------------------------------------

9. Either download the IDP Metadata file or copy the IDP Metadata URL from your Identity Provider
10. Navigate to the IDP Settings tab of the plugin and click on Upload IDP Metadata button
11. Add the name of your Identity Provider.
12. add the downloaded IDP Metadata file or paste the IDP Metadata URL copied from your Identity Provider and save it.
13. Once you save the settings all the Metadata details will be fetched automatically.

OR

Identity Provider Metadata Configurations (Manually)
----------------------------------------------------

14. Identity Provider Name: {Add your Identity Provider Name}
15. IDP Entity ID: {Add your Identity Provider Entity ID}
16. SAML Login URL: {Add your Identity Provider Single Sign On Service URL}
17. SAML x509 Certificate: {Add your Identity Provider x509 certificate}
18. Once you configure both Identity Provider and Service Provider Metadata, click the Test Configuration button to check if the configurations are made correctly.

Default Group Mapping
---------------------

19. Navigate to the Group Mapping tab of the plugin, select the default group to be assigned to the new users in Typo3 and save the settings.

20. Once you have done all the above steps, you are ready to test the SSO. You can use your Fesaml Page URL in order to initiate the SSO.

* If you are looking for anything which you cannot find, please drop us an email on info@xecurify.com