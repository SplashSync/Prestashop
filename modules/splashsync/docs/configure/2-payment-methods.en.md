---
lang: en
permalink: configure/payment-methods
title: Payment methods detection
description: How the module converts PrestaShop payment modules into Splash generic payment methods.
updated: 2026-09-25
---

### Why detect payment methods?

When your invoices are synchronized through Splash, all information is normalized to be understood by
other applications. Every payment must therefore be associated with a **Splash generic payment method**,
inspired from [Schema.org](https://schema.org/PaymentMethod):

| Code | Payment method |
|---|---|
| `ByBankTransferInAdvance` | bank transfer |
| `CheckInAdvance` | check |
| `Cash` | cash |
| `COD` | cash on delivery |
| `ByInvoice` | payment by invoice |
| `DirectDebit` | direct debit |
| `CreditCard` | credit card |
| `VISA` / `AmericanExpress` | card networks |
| `PayPal`, `GoogleCheckout`, `AmazonPay`, `ApplePay` | online wallets |

The detection relies on two pieces of information:

- the **payment method name**, stored on each payment line;
- the **payment module code**, stored on the order.

### Required: associate your payment modules

In the **Payments Methods Settings** block of the module configuration, select the generic payment method
of each detected payment module.

> [!IMPORTANT]
> Payment modules are collected from your existing orders: a payment module only appears once a first
> order has been placed with it. Come back to this block when you enable a new payment module.

### Detect by translations

PrestaShop order payments do not store the code of the payment module that created them. To solve this,
the **Detect by Translations** option uses your database to link the payment names seen by customers with
payment module codes.

> [!WARNING]
> This method may be risky on some configurations: it is optional and disabled by default. Enable it
> manually and check the results on a few invoices.

### Conversion process

For each invoice payment, the module tries the following methods, in this order, and stops at the first
match.

1. **By payment method name**, only if detection by translations is enabled.
   The payment name (e.g. "Pay Later") is searched in the dictionary built from your orders. If an order
   paid with "Pay Later" is associated with module `ps_module`, then `ps_module` is used as module code and
   converted with your associations.
2. **By order payment module code**.
   The payment module code of the order (e.g. `ps_module`) is converted with your associations.
3. **Credit card fallback**.
   If a card number is stored on the payment, `CreditCard` is used.
