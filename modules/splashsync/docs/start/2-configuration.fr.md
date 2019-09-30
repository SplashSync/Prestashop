---
lang: fr
permalink: start/configure
title: Configuration
---

### Activer le module

La configuration de votre module est disponible dans l'interface d'administration Modules et services >> Splash

![]({{ "/assets/img/screenshot_1.png" | relative_url }})

### Connectez-vous à votre compte Splash

Tout d’abord, vous devez créer des clés d’accès pour votre module sur notre site Web. 
Pour ce faire, dans l'espace de travail Splash, accédez à **Serveurs** >> **Ajouter un serveur** et notez vos clés d'identification et de chiffrement. 

![]({{ "/assets/img/screenshot_2.png" | relative_url }})

Ensuite, entrez les clés dans la configuration du module (veillez à ne rien oublier).

![]({{ "/assets/img/screenshot_3.png" | relative_url }})

### Configuration par défaut

Pour fonctionner correctement, ce module nécessite la sélection de peu de paramètres. Ces valeurs par défaut seront utilisées lors de la création / modification d'objets.

![]({{ "/assets/img/screenshot_4.png" | relative_url }})

##### Langage par défaut

Sélectionnez la langue à utiliser pour la communication avec Splash Server.

##### Utilisateur par defaut

Sélectionnez quel utilisateur sera utilisé pour toutes les actions exécutées par le module Splash.
Nous recommandons fortement la création d’un utilisateur dédié pour Splash.
Attention, le module Splash se chargera de la politique des droits des utilisateurs, cet utilisateur doit avoir un droit approprié sur Prestashop.

### Vérifier les résultats des autotests

Chaque fois que vous mettez à jour votre configuration, le module vérifiera vos paramètres et s'assurera que la communication avec Splash fonctionne correctement.
Assurez-vous que tous les tests sont réussis… c'est essentiel!

![]({{ "/assets/img/screenshot_5.png" | relative_url }})
