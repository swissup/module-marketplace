# Marketplace

Marketplace — is an extensible one-click modules and themes installer.
It allows to add unlimited number of custom software update channels and install
modules and themes from any third-party vendor including official Magento
Marketplace.

## Contents

<!-- MarkdownTOC autolink="true" -->

- [Installation](#installation)
- [Usage](#usage)
    - [Command line interface](#command-line-interface)
    - [Magento backend interface](#magento-backend-interface)
- [FAQ](#faq)
    - [Where do I get my identity keys?](#where-do-i-get-my-identity-keys)
        - [Magento Marketplace customers](#magento-marketplace-customers)
        - [Swissuplabs, Firecheckout, and Argentotheme customers](#swissuplabs-firecheckout-and-argentotheme-customers)

<!-- /MarkdownTOC -->

## Installation

```bash
composer require swissup/module-marketplace
bin/magento setup:upgrade
```

## Usage

First, you need to [get your access keys](#where-do-i-get-my-identity-keys)
from the channel you'd like to use. When you get the keys, proceed to
[CLI-based](#command-line-interface) or [GUI-based](#magento-backend-interface)
section.

### Command line interface

 1. Activate update channel and enter access keys:

    ```bash
    bin/magento marketplace:channel:enable
    ```

 2. Install the package(s) you need:

    ```bash
    bin/magento marketplace:package:install <package/name>
    ```

 3. That's all. Check your store frontend now!

    > Some modules requires additional configuration after installation.
    > Please refer to the module documentation.

### Magento backend interface

 1. Navigate to _System > Tools > Marketplace_ page.
 2. Open **Configuration** panel, activate and add access keys to the
    channel you'd like to use.
 2. Search for the module you'd like to install, and press **Install** button.
 3. That's all. Check your store frontend now!

    > Some modules requires additional configuration after installation.
    > Please refer to the module documentation.

## FAQ

### Where do I get my identity keys?

#### Magento Marketplace customers

Get your public and private keys from
[marketplace.magento.com](https://marketplace.magento.com/customer/accessKeys/)
page.

#### Swissuplabs, Firecheckout, and Argentotheme customers

Get identity key(s) and activate your store domain on the site(s) where you’ve
purchased the product:

 -  [argentotheme.com](https://argentotheme.com/license/customer/activation/)
 -  [firecheckout.net](https://firecheckout.net/license/customer/activation/)
 -  [swissuplabs.com](https://swissuplabs.com/license/customer/activation/)
