---
lang: fr
permalink: start/tricks
title: Astuces et FAQ
---

### Code de langue dans un format incorrect

Si vous avez installé un package préconfiguré de Prestashop, le code de langue peut être incomplet.
Mais Splash a besoin de codes de langues ISO complets pour identifier les langues utilisées dans les descriptions de vos produits.

Vous verrez cette erreur sur la page de configuration du module.

![]({{ "/assets/img/screenshot_6.png" | relative_url }})

Pour résoudre ce problème, sélectionnez Configuration des langues et remplacez **Code de langue** par un format standard.

![]({{ "/assets/img/screenshot_9.png" | relative_url }})

Par exemple, si votre code de langue est:

- **en**, changez pour **en-us**.  
- **es**, changez pour **es-es**. 
- **fr**, changez pour **fr-fr**. 
- **it**, changez pour **it-it**. 

### Configuration requise pour le mode multilingue

Pour utiliser et synchroniser le catalogue de produits Prestashop avec d'autres applications, assurez-vous qu'elles sont compatibles avec les champs multilingues.

Demandez à notre support si vous avez des problèmes pour configurer votre synchronisation.

### Import de Produits depuis Splash (Push Create)

Par défaut, notre module Prestashop n’est utilisé que pour exporter de nouveaux produits de Prestashop vers votre Splash Eco-System.
Pour améliorer les choses, nous utilisons des noms de produits avec des options comme nom de produit, ce qui nous permet d’obtenir des noms différents pour chaque variante de produit.

Pour autoriser l'importation de nouveaux produits de Splash vers Prestashop, vous devrez mettre à jour vos schémas de synchronisation afin d'ajouter un schéma d'exportation dans le champ "Nom du produit sans options".
Cela expliquera Splash que le nom du produit doit être écrit ici.
