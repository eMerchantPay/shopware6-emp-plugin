emerchantpay Gateway Module for Shopware 6
=============================

[![Software License](https://img.shields.io/badge/license-GPL-green.svg?style=flat)](LICENSE)

This is a Payment Plugin for Shopware 6, that gives you the ability to process payments through emerchantpay's Payment Gateway - Genesis.

Requirements
------------

* Shopware 6.6.x (Tested up to __6.6.10.2__)
* [GenesisPHP v2.1.2](https://github.com/GenesisGateway/genesis_php/releases/tag/2.1.2)
* [Composer v2.6.0](https://github.com/composer/composer/releases/tag/2.6.0)

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

**Note**: In order to use processing in an iframe, you have to modify the SameSite cookie settings. This ensures that cookies are properly handled during cross-site requests, which is crucial for iframe-based payment solutions.

### Step 1: Add/edit your framework.yml file
Ensure you have access to your Shopware 6 installation directory. This can be on your local machine or a server where your Shopware instance is hosted.

Navigate to the config/packages directory within your Shopware installation. This directory contains configuration files for various packages used by Shopware.

```shell
nano /path/to/your/shopware/installation/config/packages/framework.yml
```
**Note** instead of `nano` you can use your favorite editor.

Add/update the Cookie SameSite setting:

**framework.yml**:
```yaml
framework:
  session:
    cookie_samesite: none
```

Save your changes and clear the cache:
```sh
cd /path/to/your/shopware/installation
./bin/console cache:clear
```

Uninstall \*CAUTION\*
---------------------
When uninstalling, a message will appear asking if the plug-in data needs to be removed:
* **Yes** - Removes all saved Plugin data \***THIS CAN NOT BE UNDONE**\*
* **No** - The Plugin data remain untouched

Supported Transactions
---------------------
* ```emerchantpay Checkout``` Payment Method
  * __Alternative Payment Methods__
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
    * __Bancontact (BCT)__
    * __iDEAL__
    * __iDebit Payin__
    * __Interac Combined Pay-in (CPI)__
    * __BLIK (BLK)__
    * __P24__
    * __SPEI (SE)__
    * __PayID (PID)__
  * __Mobile__
    * __Apple Pay__
    * __Google Pay__
  * __Vouchers__
    * __Paysafecard__
  * __Wallets__
    * __PayPal__ 

_Note_: If you have trouble with your credentials or terminal configuration, get in touch with our [support] team

You're now ready to process payments through our gateway.

Development
------------
* Install with development packages
```shell
composer install
```
* Install without development packages
```shell
composer build
```
* Run PHP Code Sniffer
```shell
composer php-cs
```
* Run PHP Mess Detector
```shell
composer php-md
```
* Pack installation archive (Linux or macOS only)
```shell
composer pack
```

[support]: mailto:tech-support@emerchantpay.com
