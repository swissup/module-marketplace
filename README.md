# Marketplace

Marketplace — is an extendable one-click modules and themes installer.
It allows to add unlimited number of custom software update channels and install
modules and themes from any third-party vendor including official Magento
Marketplace.

## Installation

```bash
composer require swissup/module-marketplace
bin/magento setup:upgrade
```

## Usage

### Swissuplabs, Firecheckout, and Argentotheme customers

Get identity key(s) and activate your store domain on the site(s) where you’ve
purchased the product:

 -  [argentotheme.com](https://argentotheme.com/license/customer/activation/)
 -  [firecheckout.net](https://firecheckout.net/license/customer/activation/)
 -  [swissuplabs.com](https://swissuplabs.com/license/customer/activation/)

Now, proceed to [CLI-based](#command-line-interface) or
[GUI-based](#magento-backend-interface) section.

#### Command line interface

 1. Connect to your server via ssh and run the following commands in magento
    root directory:

    ```bash
    bin/magento marketplace:auth:key:add swissuplabs
    ```

    Repeat this command for each of your keys.

 2. Install the package(s) you need:

    ```bash
    bin/magento marketplace:package:install swissup/firecheckout swissup/argento-m2
    ```

 3. That's all. Check your store frontend now!

    > Some modules requires additional configuration after installation.
    > Please refer to the module documentation.

#### Magento Backend Interface

 1. Navigate to _System > Tools > Marketplace_ page.
 2. Open **Configuration** panel and add your identity key(s) to the
    **Swissup, Argento, and Firecheckout** update channel section.
 2. Search for the module you'd like to install, and press **Install** button.
 3. That's all. Check your store frontend now!

    > Some modules requires additional configuration after installation.
    > Please refer to the module documentation.
