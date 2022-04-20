Change Log
---------------------

__1.1.0__
-----
* Added config option for enabling Web Payment Form Tokenization service. Please, contact your account manager before enabling tokenization (#839)
* Updated to work with Shopware v. 6.4.10 (#859)
* Fixed payment_id database migration causing issues with the older Doctrine versions (#862)

__1.0.4__
-----
* Updated Genesis PHP SDK library to version 1.20.1 (#809)
* Added new transaction type Apple Pay via Web Payment Form with support of its methods (#810):
  * Authorize
  * Sale

__1.0.3__
-----
* Updated Genesis PHP SDK library to version 1.20.0 (#760)
* Added new transaction type Pay Pal via Web Payment Form with support of its methods (#761):
    * Authorize
    * Sale
    * Express
* Updated Google Pay transaction type via Web Payment Form with the latest requirements from the payment gateway (#786)

__1.0.2__
-----
* Updated Genesis PHP SDK library to version 1.19.2 (#730)
* Updated Card.js JavaScript plugin used by the Direct Method (#730)
* Added support for Google Pay transaction type via Checkout Method (Web Payment Form) (#729)

__1.0.1__
-----
* Added Shopware 6.4.x support (#583)
* Fixed issue during re-installation (#584)
* Fixed the Checkout Language Setting for the Web Payment Form (#586)

__1.0.0__
-----
* Added emerchantpay Genesis Plugin skeleton (#555)
* Added configurations (#556)
* Added Web Payment Form service (#558)
* Added Genesis Transaction data to the Database (#561)
* Added Instant Payment Notification handling (#562)
* Added support for Reference Transactions (Void, Capture, Refund) (#569)
