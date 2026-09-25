---
lang: fr
permalink: doc/objects
title: Objets disponibles
description: Objets exposés par le module PrestaShop, leurs principaux champs et ce que Splash peut lire ou écrire.
updated: 2026-09-25
translation:
    from:        en
    source_hash: af4963d6
    mode:        llm
---

Le module expose cinq types d'objets à Splash. La liste complète des champs, avec leurs types et
contraintes, est disponible sur la page des objets du module et dans la définition OpenAPI (`swagger.json`).

### Objets & capacités

| Objet | Type Splash | Lecture | Création | Modification | Suppression |
|---|---|:---:|:---:|:---:|:---:|
| :bust_in_silhouette: Client | `ThirdParty` | ✅ | ✅ | ✅ | ✅ |
| :envelope: Adresse | `Address` | ✅ | ✅ | ✅ | ✅ |
| :package: Produit | `Product` | ✅ | ✅ | ✅ | ✅ |
| :shopping_cart: Commande client | `Order` | ✅ | 🚫 | 🚫 | 🚫 |
| :moneybag: Facture client | `Invoice` | ✅ | 🚫 | 🚫 | 🚫 |

> [!NOTE]
> Sur une nouvelle connexion, Splash ne fait que **mettre à jour** les clients, adresses et produits
> existants dans PrestaShop : la création et la suppression depuis Splash sont autorisées mais désactivées
> par défaut. Activez-les sur votre compte Splash si PrestaShop n'est pas maître de ces objets.

### Clients

Identité (prénom, nom, société, email, civilité), groupes de clients, inscriptions newsletter et offres
partenaires, numéros SIRET, APE et TVA, ainsi que l'adresse principale et les téléphones du client.

### Adresses

Adresses postales liées à un client : alias, nom du contact, société, rue, code postal, ville, état, pays,
téléphones, numéro d'identification (DNI), numéro de TVA et code du point relais.

### Produits

Les produits et leurs **déclinaisons** sont exposés comme des produits individuels, partageant un parent :

- références (SKU, EAN13, UPC, ISBN, référence fournisseur) et dimensions ;
- noms, descriptions et métadonnées SEO multilingues ;
- prix (HT / TTC, prix d'achat, prix réduit) ;
- stock, comportement en rupture, quantité minimale et emplacement de stock ;
- catégories, images, image de couverture et attributs des déclinaisons.

> [!TIP]
> Les noms de produits envoyés par PrestaShop incluent les options de la déclinaison, afin que chaque
> variante ait son propre nom. Pour permettre à Splash de créer des produits dans PrestaShop, écrivez le nom
> du produit Splash dans le champ **Product Name without Options** de vos schémas de synchronisation.

Les produits virtuels et les packs de produits ne sont synchronisés que s'ils sont activés dans les
**Local Settings** du module.

### Commandes clients

Les commandes sont en **lecture seule** : elles sont exportées de PrestaShop vers vos autres applications,
avec le client, les adresses de facturation et de livraison, les lignes, remises, frais de port, totaux,
paiements, statut, transporteur et suivi, ainsi que les liens vers la facture PDF et le bon de livraison.

### Factures clients

Les factures sont en **lecture seule** : numéro, commande liée, client, lignes, totaux, statut et paiements.
Les paiements utilisent les moyens de paiement génériques Splash, voir **Détection des moyens de paiement**.
