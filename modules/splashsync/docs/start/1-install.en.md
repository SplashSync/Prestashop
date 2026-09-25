---
lang: en
permalink: start/install
title: Installation
description: Requirements and installation of the Splash Sync module on a PrestaShop store.
updated: 2026-09-25
---

### Requirements

- PrestaShop **1.7**, **8.x** or **9.x**
- PHP **7.4** or **8.x**, with the `json` and `soap` extensions
- An active [Splash Sync](https://www.splashsync.com) user account :key:

### Install from the back office

PrestaShop can install modules directly from its administration interface.

1. Download the latest stable version of the module from [Splash modules](https://www.splashsync.com/en/modules/).
2. In your back office, go to **Modules > Module Manager** and click **Upload a module**.
3. Drop the module archive, then click **Configure** once the installation is done.

### Manual installation

If the upload fails (file size limits, permissions...), install the module manually:

1. Download the latest stable version from [Splash modules](https://www.splashsync.com/en/modules/).
2. Extract the archive into the `modules/splashsync` folder of your PrestaShop installation.
3. In **Modules > Module Manager**, search for **Splash Sync Connector**, then install and configure it.

> [!IMPORTANT]
> The module folder must be named `splashsync`: any other name prevents PrestaShop from loading it.

### Update the module

Upload the new version over the existing one: your configuration is kept. After an update, open the module
configuration page and check that all **self-tests** are still passing.
