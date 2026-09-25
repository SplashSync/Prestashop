---
lang: fr
permalink: start/install
title: Installation
description: Prérequis et installation du module Splash Sync sur une boutique PrestaShop.
updated: 2026-09-25
translation:
    from:        en
    source_hash: 9de8e5c7
    mode:        llm
---

### Prérequis

- PrestaShop **1.7**, **8.x** ou **9.x**
- PHP **7.4** ou **8.x**, avec les extensions `json` et `soap`
- Un compte utilisateur [Splash Sync](https://www.splashsync.com) actif :key:

### Installation depuis le back-office

PrestaShop permet d'installer des modules directement depuis son interface d'administration.

1. Téléchargez la dernière version stable du module depuis les [modules Splash](https://www.splashsync.com/fr/modules/).
2. Dans votre back-office, allez dans **Modules > Gestionnaire de modules** et cliquez sur **Installer un module**.
3. Déposez l'archive du module, puis cliquez sur **Configurer** une fois l'installation terminée.

### Installation manuelle

Si l'envoi échoue (taille de fichier, permissions...), installez le module manuellement :

1. Téléchargez la dernière version stable depuis les [modules Splash](https://www.splashsync.com/fr/modules/).
2. Décompressez l'archive dans le dossier `modules/splashsync` de votre installation PrestaShop.
3. Dans **Modules > Gestionnaire de modules**, recherchez **Splash Sync Connector**, puis installez-le et configurez-le.

> [!IMPORTANT]
> Le dossier du module doit s'appeler `splashsync` : tout autre nom empêche PrestaShop de le charger.

### Mettre à jour le module

Envoyez la nouvelle version par-dessus l'existante : votre configuration est conservée. Après une mise à jour,
ouvrez la page de configuration du module et vérifiez que tous les **autotests** passent toujours.
