# Marketplace

Marketplace — is an extensible one-click modules and themes installer with command
line and Magento backend interfaces.
It allows to add unlimited number of custom software update channels and install
modules and themes from any third-party vendor including official Magento
Marketplace.

<p>
    <img alt="CLI Screenshot"
        width="310px"
        height="237px"
        src="https://docs.swissuplabs.com/images/m2/marketplace/cli.png?v=2"
    />
    <img alt="GUI Screenshot"
        width="310px"
        height="237px"
        src="https://docs.swissuplabs.com/images/m2/marketplace/gui.png?v=2"
    />
</p>

## Contents

<!-- MarkdownTOC autolink="true" -->

- [Installation](#installation)
- [Usage](#usage)
    - [Command line interface](#command-line-interface)
    - [Magento backend interface](#magento-backend-interface)
- [Extending Marketplace](#extending-marketplace)
    - [Create one-click installer](#create-one-click-installer)
- [FAQ](#faq)
    - [Can I install modules from the packagist?](#can-i-install-modules-from-the-packagist)
    - [Can I install modules from private repository?](#can-i-install-modules-from-private-repository)

<!-- /MarkdownTOC -->

## Installation

```bash
composer require swissup/module-marketplace
bin/magento setup:upgrade
```

## Usage

### Command line interface

 1. Activate update channel and enter access keys:

    ```bash
    bin/magento marketplace:channel:enable
    bin/magento marketplace:auth:key:add <channel>
    ```

    Use `marketplace:channel:list` to see the registered channels, and
    `marketplace:auth:show` / `marketplace:auth:check` to inspect and verify
    the stored credentials.

 2. Install the package(s) you need:

    ```bash
    # Download and enable module:
    bin/magento marketplace:require <package/name>
    # Run one-click installer (if module provides it):
    bin/magento marketplace:install <package/name>
    ```

 3. That's all. Check your store frontend now!

    > Some modules requires additional configuration after installation.
    > Please refer to the module documentation.

### Magento backend interface

Composer is never executed from the backend - running it through Magento
is not reliable. Instead, the backend shows the command to copy and run
in your terminal. The one-click installer does not use composer and is still
available right in the backend.

Channels and access keys are managed from the command line only - see
[Command line interface](#command-line-interface) above.

 1. Navigate to _System > Tools > Marketplace_ page.
 2. Search for the module you'd like to install, and press **Install** button.
    Copy the suggested command and run it:

    ```bash
    bin/magento marketplace:require <package/name>
    ```

 3. Reload the page and press **Run Installer** button, if the module provides
    a one-click installer.
 4. That's all. Check your store frontend now!

    > Some modules requires additional configuration after installation.
    > Please refer to the module documentation.

## Extending Marketplace

### Create one-click installer

One-click installer is registered with `etc/marketplace/installer.xml` file.
See following examples:

 - [Absolute Theme](https://github.com/swissup/theme-frontend-absolute/blob/master/etc/marketplace/installer.xml)

## FAQ

### Can I install modules from the packagist?

Yes. But via CLI only.

### Can I install modules from private repository?

Yes. But via CLI only. Here is an example:

```bash
# 1. Add your private repository to the composer.json
composer config repositories.<id> vcs https://github.com/repo/url.git

# 2. Setup auth data. Get token at https://github.com/settings/tokens/new?scopes=repo
bin/magento marketplace:auth github-oauth.github.com <token>

# 3. Use marketplace to download the module and run installer (if any)
bin/magento marketplace:require <package/name>
bin/magento marketplace:install <package/name>
```
