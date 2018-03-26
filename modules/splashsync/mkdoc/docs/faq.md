
## FAQ

### Language Code in Wrong Format 

If you installed a pre-configured package of Prestashop, language code may be incomplete. 
But Splash need complete ISO languages codes to identfy which languages are used in your products descriptions. 

You will see this error on module's configuration page.

![](https://splashsync.github.io/Prestashop/img/screenshot_8.png)

To solve this, goes to languages configuration and change **Language Code** to a standard format.

![](https://splashsync.github.io/Prestashop/img/screenshot_9.png)

I.e, if your language code is:

- **en**, change to **en-us**.  
- **es**, change to **es-es**. 
- **fr**, change to **fr-fr**. 
- **it**, change to **it-it**. 

### Multi-Language Mode Requirement 

To use and synchronize Prestashop Products catalog with other applications, ensure they are compatible with Multilanguage fields.

I.e on Dolibarr, you must enable Multilanguage mode to synchronize Prestashop Products. 

### Products Imports from Splash

By default, our Prestashop Module is only done to export new products from Prestashop to your Splash Eco-System.
To make it better, we use product names with options as products name, this allow us to get different names for each product variants. 

In order to allow import of new products from Splash to Prestashop, you will have to update your synchronization schemas in order to add an export schema on field "Product Name without Options". 
This will explain Splash that Product Name shall be written here. 