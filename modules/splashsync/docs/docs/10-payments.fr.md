---
lang: fr
permalink: docs/payment-methods
title: Détection des méthodes de paiement
---

#### Pourquoi détecter les moyens de paiement ?

Lorsque vous synchronisez les factures de vos clients via Splash, toutes les informations sont normalisées pour être compatibles avec d'autres applications.
C'est pourquoi Splash doit normaliser les méthodes de paiement utilisées par les clients, afin qu'elles puissent être utilisées sur d'autres systèmes.

Ainsi, chaque paiement doit être associé au code de méthode de paiement Splash approprié.
Comme d'habitude avec SplashSync, cette liste est inspirée de [Schema.org](https://schema.org/PaymentMethod) !
Vous pouvez voir la liste complète [ICI](https://github.com/SplashSync/Php-Core/blob/master/Models/Objects/Invoice/PaymentMethods.php)

#### Quoi de neuf ?

Auparavant, la détection des moyens de paiement sur les factures de Prestashop se faisait à l'aide d'une liste statique de codes connus.
Cela fonctionne mais reste limité pour les sites Web utilisant plusieurs modules de paiement.

Depuis la version 2.8.2, le module Prestashop pour Splash intègre un nouveau système de détection des moyens de paiement.
Ce système est basé sur deux informations

- Nom du mode de paiement de la commande, stocké dans la ligne de paiement.
- Code du module de paiement de la commande, stocké sur l'objet de commande.

Désormais, Splash effectuera une association intelligente entre le code du module de paiement et les codes de méthode de paiement génériques Splash.

#### OBLIGATOIRE - Configurer l'association de tous les modules de paiement

Un nouveau bloc de configuration est disponible sur la page de configuration du module.
Ici, vous pourrez câbler chaque mode de paiement inconnu avec le code de méthode Splash correspondant.

**Attention !!** Afin de rendre les modules de paiement exhaustifs, les codes sont collectés sur toutes les Commandes existantes.
Ainsi, le module de paiement ne sera visible qu'une fois la première commande passée !

Pour chaque module détecté, sélectionnez simplement le type générique de moyen de paiement que vous souhaitez utiliser.

#### Traductions de méthodes de paiement

Si vous connaissez Prestashop, vous savez peut-être que les paiements de la commande n'incluent pas le code du module de paiement source.
Pour résoudre ce problème, nous avons introduit un système de traduction.

Cette traduction est réalisée à partir de votre base de données pour identifier les connexions entre les noms des moyens de paiement, visibles par les clients, et les codes des modules de paiement.

Cette méthode pouvant être risquée sur certaines configurations, elle est facultative, vous devez l'activer manuellement et la tester.

##### Processus de conversion

Voici comment, pour chaque paiement de facture, Splash convertira les modes de paiement en codes génériques.

##### Méthode 1 – Identifier par l'étiquette du mode de paiement
1. Si la traduction n'est pas activée, Splash sautera et passera à la méthode suivante 2.
2. Splash recherchera le nom de la méthode dans son dictionnaire. C'est à dire. Imaginez que votre paiement soit marqué comme « Payer plus tard ».
3. Si cette étiquette n'est pas trouvée, Splash sautera et passera à l'identification suivante, méthode 2.
4. Si vous avez déjà une commande payée marquée comme "Payer plus tard" et associée au module "ps_module", Splash utilise "ps_module" comme code de module.
5. Splash examine les codes configurés et les convertit en méthode générique s'il est trouvé

##### Méthode 2 - Identifier par le code du module de paiement de la commande
1. Splash recherchera le code du module de paiement des commandes. C'est-à-dire "ps_module"
2. Splash examine les codes configurés et les convertit en méthode générique s'il est trouvé

##### Méthode 3 – Solution de secours par carte de crédit
Si aucune des méthodes précédentes n'a fonctionné, Splash vérifiera si un numéro de carte est stocké sur l'élément de paiement.
Si tel est le cas, Splash utilisera la carte de crédit comme méthode par défaut.
