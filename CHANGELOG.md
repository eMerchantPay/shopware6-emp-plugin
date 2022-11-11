Change Log
---------------------
__1.2.0__
-----
* Added support for 3DSv2 params thru the emerchantpay Checkout method
* Added support for SCA Exemption settings thru the emerchantpay Checkout method
* Added 3DSv2 parameters handling via Web Payment Form
* Added SCA Exemption parameters handling via Web Payment Form
* Updated Genesis PHP SDK version to 1.21.3
* Tested up to Shopware 6.4.17.0 version

__1.1.2__
-----
* Added Pix Transaction Type via Web Payment Form
* Updated Genesis PHP lib to version 1.21.2
* Tested up to Shopware 6.4.13

__1.1.1__
-----
* Added Interac Bank code to Online banking transaction type
* Tested up to Shopware v. 6.4.11

__1.1.0__
-----
* Added config option for enabling Web Payment Form Tokenization service. Please, contact your account manager before enabling tokenization
* Updated to work with Shopware v. 6.4.10
* Fixed payment_id database migration causing issues with the older Doctrine versions

__1.0.4__
-----
* Updated Genesis PHP SDK library to version 1.20.1
* Added new transaction type Apple Pay via Web Payment Form with support of its methods:
  * Authorize
  * Sale

__1.0.3__
-----
* Updated Genesis PHP SDK library to version 1.20.0
* Added new transaction type PayPal via Web Payment Form with support of its methods:
    * Authorize
    * Sale
    * Express
* Updated Google Pay transaction type via Web Payment Form with the latest requirements from the payment gateway

__1.0.2__
-----
* Updated Genesis PHP SDK library to version 1.19.2
* Updated Card.js JavaScript plugin used by the Direct Method
* Added support for Google Pay transaction type via Checkout Method (Web Payment Form)

__1.0.1__
-----
* Added Shopware 6.4.x support
* Fixed issue during re-installation
* Fixed the Checkout Language Setting for the Web Payment Form

__1.0.0__
-----
* Added emerchantpay Genesis Plugin skeleton
* Added configurations
* Added Web Payment Form service
* Added Genesis Transaction data to the Database
* Added Instant Payment Notification handling
* Added support for Reference Transactions (Void, Capture, Refund)
