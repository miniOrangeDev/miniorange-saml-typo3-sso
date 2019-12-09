Installation
============

Extension Installation
**********************

* Download the zip file of the SAML extension.
* Go to your TYPO3 backend, go to Extensions.
* Upload the zip file.
* Now You will be able to activate extension in the list of extensions.


Prerequirements Before Configuring
**********************************

You must have atleast two pages before configuring the extension.

.. tip:: Pages can be created by the following steps:-

*

Follow the given steps to set up for extension.


* Add a Standard page and select "Website Users" in "Behavior Tab" while editing the page.Add "fesaml" plugin by adding the content.
* Add another standard page and select "Website Users" in "Behavior Tab", just like before. Add "response" plugin on this by adding content.
* Add a folder where website users will be stored.
* Add a "SSO" button and set its onclick

If you already have all the pages set up. Follow these steps given below.

* Add fesaml plugin to a newly constructed standard page.
* Put response plugin to a newly constructed standard page.
* Put an SSO button where your login form is present.
* It is adviced to put a login form on the page where you have put the response plugin.