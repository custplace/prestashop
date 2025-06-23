# Custplace Module For Prestashop

## Description
Custplace.com est une plateforme web de gestion d’avis produits/marchand et d’enquêtes de satisfaction.

## Features
Ce module vous permet d'intégrer la sollicitation des avis de vos clients directement dans votre boutique en ligne après chaque commande. Les avis seront accessibles depuis votre compte Custplace et peuvent aussi être affichés dans les fiches de vos produits à l'aide de nos widgets. Voici la liste des Widgets disponible:

- Badge de confiance : Widget affichant la note de votre entreprise
- ProductReviewBox : Widget affichant la note et les avis de vos produits

## Installation
- Télécharger la dernière version du module
- Extraire le contenu du module
- Transférer le dossier du module par FTP dans me dossier "/modules" de votre Prestashop
- Connectez-vous sur votre Back-office Prestashop et aller dans le menu "Modules > Gestionnaire de modules" puis chercher le module et cliquer sur "Installer". Ensuite cliquer sur le lien "Configurer" pour compléter l'installation.

### Configuration du module

Le module propose plusieurs sections de configuration :

#### 1. Sollicitation des avis
- **Activer la sollicitation** : Active/désactive l'envoi automatique d'invitations
- **ID client** : Votre identifiant client Custplace
- **Clé API** : Votre clé d'accès API Custplace (cryptée automatiquement)
- **Délai de sollicitation** : Nombre de jours après la commande avant l'envoi (max 30 jours)
- **ID template d'invitation** : (Optionnel) ID du template d'invitation personnalisé
- **Catégories exclues** : IDs des catégories séparés par des virgules (ex: 1,2,3). Les commandes contenant des produits de ces catégories ne recevront pas d'invitation
- **Statuts déclencheurs** : Sélectionnez les statuts de commande qui déclenchent automatiquement l'envoi d'invitations

#### 2. Badge de confiance
- **Widget Sceau de confiance** : Active/désactive l'affichage du badge de confiance sur la page d'accueil

#### 3. Widget Avis Produits
- **Widget Avis Produit** : Active/désactive l'affichage des avis produits
- **Clé Widget** : Clé d'accès pour les widgets (cryptée automatiquement)
- **Couleur primaire/secondaire** : Personnalisation des couleurs (codes HEX)
- **Notes détaillées** : Affiche les notes détaillées des enquêtes de satisfaction
- **Note produit global** : Affiche la note globale à côté du nom du produit

#### 4. Mode Test
- **Activer le mode test** : Utilise les environnements de test pour l'API et les widgets
  - API de test : apis.kustplace.com
  - Widgets de test : widgets.kustplace.com

## How to use
Pour utiliser ce module il est necessaire d'avoir un compte Custplace actif.
Connectez-vous sur votre espace Custplace Manager pour récupérer les accès de connexion (clé API).

## Guide pour développeurs

### Hook pour modifier les données d'invitation

Le module fournit un hook `actionCustplaceInvitationData` qui permet aux autres modules de modifier les données d'invitation avant l'envoi à l'API Custplace.

#### Utilisation du hook

```php
// Dans votre module, enregistrez le hook
public function hookActionCustplaceInvitationData($params)
{
    $invitationData = $params['invitation_data'];
    $orderId = $params['order_id'];
    
    // Exemple : Ajouter des champs personnalisés
    $invitationData['custom_field'] = 'valeur_personnalisée';
    
    // Exemple : Modifier des champs existants
    $invitationData['send_at'] = date('Y-m-d H:i', strtotime('+2 days'));
    
    // Exemple : Ajouter des données spécifiques à la commande
    $order = new Order($orderId);
    $invitationData['payment_method'] = $order->payment;
    
    return [
        'invitation_data' => $invitationData
    ];
}
```

#### Sécurité et validation

- Les champs requis sont validés après modification : `order_ref`, `firstname`, `lastname`, `email`, `type`, `send_at`, `lang`, `products`
- En cas d'erreur ou de validation échouée, les données originales sont utilisées
- Les erreurs sont loggées automatiquement

### Logging et surveillance

Le module utilise le système de logs intégré de PrestaShop pour tracer les erreurs et succès de l'API.

#### Consultation des logs

Rendez-vous dans **Paramètres avancés > Logs** de votre back-office PrestaShop et filtrez par :
- **Objet** : "custplace"
- **Type d'objet** : "Module"

#### Types de logs

- **Erreur** (Rouge) : Échecs de connexion API, erreurs HTTP, erreurs de parsing JSON
- **Avertissement** (Orange) : Réponses API avec statut d'erreur
- **Information** (Bleu) : Invitations envoyées avec succès

#### Exemples de messages de logs

```
Custplace API Success: Invitation sent for order CMD123 - ID: 67890
Custplace API Error: HTTP 401 - Response: {"error":"invalid_token"}
Custplace API Error: cURL failed - SSL connection timeout
```

## Support & issue tracking
Plus d'infos sur : https://cust.fr/prestashop

## Security Vulnerabilities
If you discover a security vulnerability within this module, please send an e-mail to support@custplace.com. All security vulnerabilities will be promptly addressed.

## License
Proprietary-license whose copyright belongs to the Licensor Third Voice.
