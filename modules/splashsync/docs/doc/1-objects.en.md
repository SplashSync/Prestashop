---
lang: en
permalink: doc/objects
title: Available objects
description: Objects exposed by the PrestaShop module, their main fields and what Splash can read or write.
updated: 2026-09-25
---

The module exposes five object types to Splash. The complete list of fields, with their types and
constraints, is available on the module objects page and in the OpenAPI definition (`swagger.json`).

### Objects & capabilities

| Object | Splash type | Read | Create | Update | Delete |
|---|---|:---:|:---:|:---:|:---:|
| :bust_in_silhouette: Customer | `ThirdParty` | ✅ | ✅ | ✅ | ✅ |
| :envelope: Address | `Address` | ✅ | ✅ | ✅ | ✅ |
| :package: Product | `Product` | ✅ | ✅ | ✅ | ✅ |
| :shopping_cart: Customer Order | `Order` | ✅ | 🚫 | 🚫 | 🚫 |
| :moneybag: Customer Invoice | `Invoice` | ✅ | 🚫 | 🚫 | 🚫 |

> [!NOTE]
> On a new connection, Splash only **updates** existing customers, addresses and products in PrestaShop:
> creation and deletion from Splash are allowed but disabled by default. Enable them on your Splash account
> if PrestaShop is not the master of these objects.

### Customers

Identity (first name, last name, company, email, gender), customer groups, newsletter & opt-in flags,
SIRET, APE and VAT numbers, plus the main address and phones of the customer.

### Addresses

Postal addresses linked to a customer: alias, contact name, company, street, postcode, city, state,
country, phones, identification number (DNI), VAT number and relay point code.

### Products

Products and **combinations** (variants) are exposed as individual products, sharing a parent:

- references (SKU, EAN13, UPC, ISBN, supplier reference) and dimensions;
- multilingual names, descriptions and SEO metadata;
- prices (tax excluded / included, wholesale price, reduced price);
- stock, out-of-stock behaviour, minimal quantity and stock location;
- categories, images, cover image and variant attributes.

> [!TIP]
> Product names sent by PrestaShop include the combination options, so each variant has its own name. To
> let Splash create products in PrestaShop, write the Splash product name to the **Product Name without
> Options** field in your synchronization schemas.

Virtual products and products packs are only synchronized when enabled in the **Local Settings** of the
module.

### Customer orders

Orders are **read only**: they are exported from PrestaShop to your other applications, with customer,
invoice & delivery addresses, order lines, discounts, shipping costs, totals, payments, status, carrier
and tracking information, and links to the PDF invoice and delivery slip.

### Customer invoices

Invoices are **read only**: number, related order, customer, lines, totals, status and payments. Payments
use Splash generic payment methods, see **Payment methods detection**.
