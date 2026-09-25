---
lang: fr
permalink: start/configure
title: Connexion à Splash
description: Créez les clés de votre serveur sur Splash, saisissez-les dans le module et vérifiez les autotests.
updated: 2026-09-25
translation:
    from:        en
    source_hash: 55c1da8f
    mode:        llm
---

### Ouvrir la configuration du module

Dans votre back-office, allez dans **Modules > Gestionnaire de modules**, recherchez **Splash Sync Connector**
et cliquez sur **Configurer**.

![Module dans le gestionnaire de modules PrestaShop](../assets/img/screenshot_1.png "Gestionnaire de modules")

### Créer les clés du serveur sur Splash

Chaque boutique PrestaShop est déclarée comme un **serveur** dans votre compte Splash. Dans l'espace de
travail Splash, allez dans **My Servers**, cliquez sur **New Server** et notez l'**identifiant** et la
**clé de chiffrement** du nouveau serveur.

![Nouveau serveur sur Splash](../assets/img/splash-new-server.png "Nouveau serveur")

### Saisir les clés dans le module

Dans le bloc **Authentication Settings** de la configuration du module, renseignez :

| Réglage | Valeur |
|---|---|
| Server Id | l'identifiant du serveur fourni par Splash |
| Server Private Key | la clé de chiffrement fournie par Splash |

![Réglages d'authentification](../assets/img/ps-auth-settings.png "Réglages d'authentification")

> [!WARNING]
> Copiez les clés avec soin : un seul caractère manquant et la connexion échoue. Ne partagez jamais la clé privée.

### Choisir l'utilisateur par défaut

Dans le bloc **Local Settings**, sélectionnez l'**utilisateur par défaut** (Default user) : l'employé utilisé
pour toutes les actions effectuées par Splash. Nous recommandons fortement un **employé dédié** à Splash : la
politique de droits de PrestaShop s'applique, cet utilisateur doit donc disposer des permissions appropriées.

![Réglages locaux](../assets/img/screenshot_4.png "Réglages locaux")

> [!NOTE]
> La langue par défaut de PrestaShop sert de langue de référence. Toutes les langues installées sont
> synchronisées via les champs multilingues.

### Vérifier les autotests

À chaque enregistrement de la configuration, le module vérifie vos paramètres et la communication avec Splash.

![Résultats des autotests](../assets/img/screenshot_5.png "Autotests")

> [!IMPORTANT]
> Tous les tests doivent passer : c'est essentiel ! Une fois au vert, votre boutique apparaît comme connectée
> sur votre compte Splash.
