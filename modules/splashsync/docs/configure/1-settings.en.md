---
lang: en
permalink: configure/settings
title: Module settings
description: All the settings of the module configuration page, block by block.
updated: 2026-09-25
---

The module configuration page is split into blocks. Only the **Authentication Settings** and the
**Default user** are required: all other settings have safe default values.

### Authentication settings

| Setting | Role |
|---|---|
| Server Id | identifier of this server on your Splash account |
| Server Private Key | encryption key of this server |
| Webservice | library used to communicate with Splash: **Generic PHP SOAP** (default) or **NuSOAP Library** |
| Smart Notifications | on changes, display only warning & error notifications in the back office |
| Enable Expert Mode | unlocks the **Server Host Url** setting |

> [!CAUTION]
> Enable the expert mode and change the server host url **only if requested by the Splash team**: a wrong
> url disconnects your store from Splash.

### Local settings

Default user
: Employee used for all actions performed by Splash. Use a dedicated employee with the appropriate rights.

Virtual Products
: Allow the synchronization of virtual products (downloadable or services). Disabled by default.

Products Packs
: Allow the synchronization of products packs. Disabled by default.

### Expert settings: custom configurations

The **Custom Configuration** setting switches the module to a predefined advanced mode:

| Mode | Behaviour |
|---|---|
| None, use generic configuration | default: all objects and fields are available |
| Stock Only | on products, only SKUs and stocks are writable |
| Marketplace Client | products can only be updated (SKUs and stocks), all other objects are not synchronized |

> [!TIP]
> Use **Stock Only** when another application (ERP, PIM...) is the master of your stocks but must never
> change your catalog.

### Orders writing settings

Orders are read only in the standard setup: this block is only used by advanced setups where Splash is
allowed to update orders, on request of the Splash team. Splash then sends a **generic status** (payment
due, processing, in transit, delivered, canceled...). By default, the module converts it to the matching
native PrestaShop order state.

If you use custom order states, map each generic status to the PrestaShop state of your choice, or keep
**Use Generic Status**.

### Payment methods settings

Payment methods found on your orders must be associated with a Splash generic payment method. This setting
is detailed on its own page: **Payment methods detection**.
