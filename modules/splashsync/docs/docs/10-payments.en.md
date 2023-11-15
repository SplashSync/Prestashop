---
lang: en
permalink: docs/payment-methods
title: Payment methods detection
---

#### Why should we detect payment methods ?

When you synchronize your customer's invoices via Splash, all information are normalized to be compatible with others applications. 
That's why Splash needs to normalize payment methods used by customers, so that it could be used to others systems.

Thus, every payment must be associated with the suitable Splash Payment Method code. 
As usual with SplashSync, this list is inspired from [Schema.org](https://schema.org/PaymentMethod) !
You can see the full listing [HERE](https://github.com/SplashSync/Php-Core/blob/master/Models/Objects/Invoice/PaymentMethods.php)

#### What's new ?

Previously, payment method detection on Prestashop's invoices was done using a static list of known codes.
It works but remain limited for websites using multiple payment modules.

Since version 2.8.2, Prestashop module for Splash integrate a new payments method detection system.
This system is based on two information

 - Order payment method name, stored in payment line.
 - Order payment module code, stored on order object.

Now, Splash will perform intelligent association between payment module code and Splash generic Payment Method codes.

#### REQUIRED - Configure All Payment Modules Association

A new configuration block is available on module's configuration page. 
Here, you will be able to map each unknown payment method with corresponding Splash method code.

**Be careful!!** In order to make payment modules exhaustive, codes are collected from all existing Orders. 
Thus, payment module will be visible only once first order is placed!  

For each detected module, just select the generic type of payment method you want to use.

#### Payment Methods Translations

If you are familiar with Prestashop, you may know that Order's payments does not include source payment module code. 
To solve this issue, we have introduced a translation system. 

This translation is done using your database to identify connexions between payment methods labels, visible by customers, and payment modules codes.

As this method may be risky on some configurations, it is optional, you need to activate it manually and test it.

#### Conversion Process

Here is how, for each invoice payment, Splash will convert payments methods to generic codes.

##### Method 1 - Identify by Payment Method Label
1. Is translation is not activated, Splash will skip and go to next method 2.
2. Splash will look for method name on its dictionary. I.e. Imagine your payment is marked as "Pay Later".
3. If this label is not found, Splash will skip and go to next identification, method 2.
4. If you already have an order paid marked as "Pay Later" and associated with module "ps_module", Splash with use "ps_module" as module code.
5. Splash look at configured codes and convert it to generic method if found

##### Method 2 - Identify by Order Payment Module Code
1. Splash will look for Order Payment Module code. I.e "ps_module"
2. Splash look at configured codes and convert it to generic method if found

##### Method 3 - Credit Card Fallback
If none of previous methods worked, Splash will check if a card number is stored on Payment Item. 
If so, Splash will use CreditCard as default method.
