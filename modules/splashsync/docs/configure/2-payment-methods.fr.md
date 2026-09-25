---
lang: fr
permalink: configure/payment-methods
title: Détection des moyens de paiement
description: Comment le module convertit les modules de paiement PrestaShop en moyens de paiement génériques Splash.
updated: 2026-09-25
translation:
    from:        en
    source_hash: daf7a808
    mode:        llm
---

### Pourquoi détecter les moyens de paiement ?

Lorsque vos factures sont synchronisées via Splash, toutes les informations sont normalisées pour être
comprises par les autres applications. Chaque paiement doit donc être associé à un **moyen de paiement
générique Splash**, inspiré de [Schema.org](https://schema.org/PaymentMethod) :

| Code | Moyen de paiement |
|---|---|
| `ByBankTransferInAdvance` | virement bancaire |
| `CheckInAdvance` | chèque |
| `Cash` | espèces |
| `COD` | paiement à la livraison |
| `ByInvoice` | paiement sur facture |
| `DirectDebit` | prélèvement |
| `CreditCard` | carte bancaire |
| `VISA` / `AmericanExpress` | réseaux de cartes |
| `PayPal`, `GoogleCheckout`, `AmazonPay`, `ApplePay` | portefeuilles en ligne |

La détection repose sur deux informations :

- le **nom du moyen de paiement**, stocké sur chaque ligne de paiement ;
- le **code du module de paiement**, stocké sur la commande.

### Obligatoire : associer vos modules de paiement

Dans le bloc **Payments Methods Settings** de la configuration du module, sélectionnez le moyen de paiement
générique de chaque module de paiement détecté.

> [!IMPORTANT]
> Les modules de paiement sont collectés à partir de vos commandes existantes : un module de paiement
> n'apparaît qu'une fois qu'une première commande a été passée avec lui. Revenez sur ce bloc lorsque vous
> activez un nouveau module de paiement.

### Détection par les traductions

Les paiements des commandes PrestaShop ne stockent pas le code du module de paiement qui les a créés. Pour
y remédier, l'option **Detect by Translations** utilise votre base de données pour relier les noms de
paiement vus par les clients aux codes des modules de paiement.

> [!WARNING]
> Cette méthode peut être risquée sur certaines configurations : elle est facultative et désactivée par
> défaut. Activez-la manuellement et vérifiez le résultat sur quelques factures.

### Processus de conversion

Pour chaque paiement de facture, le module essaie les méthodes suivantes, dans cet ordre, et s'arrête à la
première qui aboutit.

1. **Par le nom du moyen de paiement**, uniquement si la détection par les traductions est activée.
   Le nom du paiement (ex. « Payer plus tard ») est recherché dans le dictionnaire construit à partir de vos
   commandes. Si une commande payée avec « Payer plus tard » est associée au module `ps_module`, alors
   `ps_module` est utilisé comme code de module et converti selon vos associations.
2. **Par le code du module de paiement de la commande**.
   Le code du module de paiement de la commande (ex. `ps_module`) est converti selon vos associations.
3. **Repli sur la carte bancaire**.
   Si un numéro de carte est stocké sur le paiement, `CreditCard` est utilisé.
