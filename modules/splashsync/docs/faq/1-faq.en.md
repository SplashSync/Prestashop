---
lang: en
permalink: faq/faq
title: Tricks & FAQ
description: Solutions to the most common issues met with the PrestaShop module.
updated: 2026-09-25
---

## Frequently Asked Questions {.faq}

### Self-tests fail with "Language Code is not in expected format"

Pre-configured PrestaShop packages may use incomplete language codes, but Splash needs full ISO codes to
identify the languages of your multilingual fields. The error is displayed on the module configuration page.

![Language code error](../assets/img/screenshot_8.png "Language code error")

Go to **International > Localization > Languages**, edit each language and change its **Language code** to
a full format:

| Current code | Expected code |
|---|---|
| `en` | `en-us` |
| `es` | `es-es` |
| `fr` | `fr-fr` |
| `it` | `it-it` |

![Language code setting](../assets/img/screenshot_9.png "Language code")

### My other applications do not receive all the languages of my products

PrestaShop products use multilingual fields. Check that the applications you synchronize with support
multilingual fields, or map the language you need in your synchronization schemas. Ask our support if
you have trouble setting up your synchronization.

### New products created on Splash are not created in PrestaShop

By default, the module only exports new products from PrestaShop to Splash. Product names sent by
PrestaShop include the combination options, so each variant gets its own name.

To allow the creation of products from Splash, update your synchronization schemas and add an export to
the **Product Name without Options** field: it tells Splash where the product name must be written.

### Orders or invoices changed in my ERP are not updated in PrestaShop

This is expected: customer orders and invoices are **read only**. They are exported from PrestaShop to your
other applications, never written back.

### Some payments are exported with a wrong payment method

Associate each payment module with a Splash generic payment method in the **Payments Methods Settings**
block. A payment module only appears there once a first order has been placed with it. See **Payment
methods detection** for the full process.
