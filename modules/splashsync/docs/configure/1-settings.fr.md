---
lang: fr
permalink: configure/settings
title: Réglages du module
description: Tous les réglages de la page de configuration du module, bloc par bloc.
updated: 2026-09-25
translation:
    from:        en
    source_hash: 36be9a5d
    mode:        llm
---

La page de configuration du module est découpée en blocs. Seuls les **Authentication Settings** et
l'**utilisateur par défaut** sont obligatoires : tous les autres réglages ont des valeurs par défaut sûres.

### Réglages d'authentification

| Réglage | Rôle |
|---|---|
| Server Id | identifiant de ce serveur sur votre compte Splash |
| Server Private Key | clé de chiffrement de ce serveur |
| Webservice | librairie utilisée pour communiquer avec Splash : **Generic PHP SOAP** (par défaut) ou **NuSOAP Library** |
| Smart Notifications | lors des modifications, n'affiche que les avertissements et erreurs dans le back-office |
| Enable Expert Mode | déverrouille le réglage **Server Host Url** |

> [!CAUTION]
> N'activez le mode expert et ne modifiez l'url du serveur **que sur demande de l'équipe Splash** : une url
> erronée déconnecte votre boutique de Splash.

### Réglages locaux

Utilisateur par défaut (Default user)
: Employé utilisé pour toutes les actions effectuées par Splash. Utilisez un employé dédié, disposant des
  droits appropriés.

Produits virtuels (Virtual Products)
: Autorise la synchronisation des produits virtuels (téléchargeables ou services). Désactivé par défaut.

Packs de produits (Products Packs)
: Autorise la synchronisation des packs de produits. Désactivé par défaut.

### Réglages experts : configurations personnalisées

Le réglage **Custom Configuration** bascule le module dans un mode avancé prédéfini :

| Mode | Comportement |
|---|---|
| None, use generic configuration | par défaut : tous les objets et champs sont disponibles |
| Stock Only | sur les produits, seuls les références (SKU) et les stocks sont modifiables |
| Marketplace Client | les produits peuvent seulement être mis à jour (SKU et stocks), les autres objets ne sont pas synchronisés |

> [!TIP]
> Utilisez **Stock Only** lorsqu'une autre application (ERP, PIM...) est maître de vos stocks mais ne doit
> jamais modifier votre catalogue.

### Réglages d'écriture des commandes

Les commandes sont en lecture seule dans la configuration standard : ce bloc ne sert qu'aux
configurations avancées où Splash est autorisé à modifier les commandes, sur demande de l'équipe Splash.
Splash envoie alors un **statut générique** (paiement en attente, en cours, en transit, livrée,
annulée...). Par défaut, le module le convertit vers l'état de commande PrestaShop natif correspondant.

Si vous utilisez des états de commande personnalisés, associez chaque statut générique à l'état PrestaShop
de votre choix, ou conservez **Use Generic Status**.

### Réglages des moyens de paiement

Les moyens de paiement trouvés sur vos commandes doivent être associés à un moyen de paiement générique
Splash. Ce réglage est détaillé sur sa propre page : **Détection des moyens de paiement**.
