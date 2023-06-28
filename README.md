emerchantpay Gateway Module for Shopware 6
=============================

This is a Payment Plugin for Shopware 6, that gives you the ability to process payments through emerchantpay's Payment Gateway - Genesis.

Requirements
------------

* Shopware 6.4.20, 6.5.x (Tested up to __6.4.20__, __6.5.2.1__)
* [GenesisPHP v1.21.10](https://github.com/GenesisGateway/genesis_php/releases/tag/1.21.10)

GenesisPHP Requirements
------------

* PHP version 5.5.9 or newer
* PHP Extensions:
    * [BCMath](https://php.net/bcmath)
    * [CURL](https://php.net/curl) (required, only if you use the curl network interface)
    * [Filter](https://php.net/filter)
    * [Hash](https://php.net/hash)
    * [XMLReader](https://php.net/xmlreader)
    * [XMLWriter](https://php.net/xmlwriter)
    * [JSON](https://www.php.net/manual/en/book.json)
    * [OpenSSL](https://www.php.net/manual/en/book.openssl.php)

Installation (manual) via console
---------------------
* Download the archive with the desired release
* Extract the containing files into `<Shopware 6 Root>\custom\plugins\EmerchantpayGenesis`
* Navigate via the console to the Shopware 6 Root
* Check if Shopware 6 recognize the plugin

  ```$ php bin/console plugin:refresh```

  The output should contain the following row:

  | Plugin | Label | Version | Upgrade version | Author | Installed | Active | Upgradeable |
  | --- | --- | --- | --- | --- | --- | --- | --- |
  | EmerchantpayGenesis | emerchantpay Genesis Plugin | x.x.x |  | emerchantpay Ltd | No | No | No |

* Install and activate the plugin

```$ php bin/console plugin:install --activate EmerchantpayGenesis```

* Clear the cache

```$ php bin/console cache:clear```

Configuration
---------------------
### emerchantpay Genesis Plugin
* Log-in into the Admin
* Go to
  * `Extenstions->My Extensions` via Shopware 6.4.x, 6.5.x
* `Activate`, `Install`, `Uninstall`, etc.
* Choose `Config` and fill up the Credentials, choose Transaction Types, etc

### Checkout Payment Methods
* Log-in into the Admin
* Go to
  * `Extenstions->My Extensions` via Shopware 6.4.x, 6.5.x
* Choose `Edit`
* Set `Active`, `Availability Rule`, etc

### Shop
* Log-in into the Admin
* Go to <Your Shop>
* Navigate to section `Payment and shipping`
* Add Payment Methods `emerchantpay Checkout`

Uninstall \*CAUTION\*
---------------------
When uninstalling, a message will appear asking if the plug-in data needs to be removed:
* **Yes** - Removes all saved Plugin data \***THIS CAN NOT BE UNDONE**\*
* **No** - The Plugin data remain untouched

Supported Transactions
---------------------
* ```emerchantpay Checkout``` Payment Method
  * __Alternative Payment Methods__
    * __PPRO__
      * __iDEAL__
    * __SOFORT__
  * __Cash Payments__
    * __Pix__
  * __Credit Cards__
    * __Authorize__
    * __Authorize (3D-Secure)__
    * __Sale__
    * __Sale (3D-Secure)__
    * __EPS__
  * __Sepa Direct Debit__
    * __SDD Sale__
  * __Online Banking Payments__
    * __Bancontact__
    * __GiroPay__
    * __iDEAL__
    * __iDebit Payin__
    * __Interac Combined Pay-in__
  * __Mobile__
    * __Apple Pay__
    * __Google Pay__
  * __Wallets__
    * __PayPal__ 

_Note_: If you have trouble with your credentials or terminal configuration, get in touch with our [support] team

You're now ready to process payments through our gateway.

[support]: mailto:tech-support@emerchantpay.com

