# Castopod - Guide pour Claude

## Présentation du projet

**Castopod** est une plateforme open-source d'hébergement de podcasts conçue pour permettre aux podcasters d'interagir avec leur audience. Développé en PHP avec CodeIgniter 4, le projet intègre des fonctionnalités de fédération ActivityPub (Fediverse).

### Informations générales
- **Version actuelle**: 1.13.4
- **Licence**: AGPL-3.0-or-later
- **Framework**: CodeIgniter 4.5.7
- **PHP**: ^8.1
- **Repository principal**: https://code.castopod.org/adaures/castopod
- **Documentation**: https://castopod.org/

## Architecture du projet

### Structure des dossiers principaux

#### `/app/` - Application principale
- **Config/**: Configuration CodeIgniter (App.php, Routes.php, Database.php, etc.)
- **Controllers/**: Contrôleurs publics (PodcastController, EpisodeController, HomeController)
- **Models/**: Modèles de données (PodcastModel, EpisodeModel, etc.)
- **Entities/**: Entités métier (Podcast, Episode, Actor, etc.)
- **Language/**: Fichiers de traduction (support de 25+ langues)
- **Views/**: Vues et composants (structure modulaire)
- **Libraries/**: Bibliothèques spécifiques (PodcastActor, Router, etc.)

#### `/modules/` - Architecture modulaire
- **Admin/**: Interface d'administration
- **Analytics/**: Système d'analytics et statistiques
- **Auth/**: Authentification et autorisation
- **Fediverse/**: Intégration ActivityPub
- **Media/**: Gestion des médias (audio, images, transcripts)
- **Platforms/**: Intégration plateformes de podcast
- **PodcastImport/**: Import de podcasts existants
- **PremiumPodcasts/**: Système de podcasts premium

#### Frontend (`/app/Resources/`)
- **TypeScript/JavaScript**: Modules pour audio player, charts, maps
- **CSS**: Styles avec Tailwind CSS
- **Icons**: SVG optimisés pour plateformes sociales et funding

### Technologies clés

#### Backend
- **CodeIgniter 4**: Framework PHP MVC
- **Base de données**: MySQL/MariaDB avec migrations
- **Authentication**: CodeIgniter Shield
- **Queue**: Tâches asynchrones via CodeIgniter Tasks
- **Storage**: Support S3 et filesystem local

#### Frontend
- **Vite**: Build tool et dev server
- **TypeScript**: Language principal côté client
- **Tailwind CSS**: Framework CSS utility-first
- **Web Components**: Architecture composant native
- **PWA**: Support Progressive Web App

#### Intégrations spécialisées
- **ActivityPub**: Fédération avec le Fediverse
- **RSS**: Génération de flux podcast standards
- **Analytics**: Tracking détaillé d'écoute
- **Media Processing**: Traitement audio/vidéo

## Fonctionnalités principales

### Gestion de podcasts
- **Création et édition** de podcasts avec métadonnées complètes
- **Upload d'épisodes** avec support multi-format audio
- **Gestion des personnes** (hosts, guests, contributors)
- **Catégorisation** selon taxonomie podcast standard
- **Transcriptions** et chapitres

### Intégration Fediverse
- **ActivityPub**: Publication automatique sur le Fediverse
- **Interactions sociales**: Likes, commentaires, partages
- **Followers**: Système d'abonnement fédéré
- **Posts**: Publication de contenus liés aux épisodes

### Analytics et métriques
- **Statistiques d'écoute** par épisode/podcast
- **Géolocalisation** des auditeurs
- **Plateformes d'écoute** et user agents
- **Cartes interactives** avec leaflet

### Administration
- **Interface complète** de gestion
- **Gestion des utilisateurs** et permissions
- **Import/Export** de podcasts
- **Configuration** multi-instance

## Commandes utiles

### Développement
```bash
# Serveur de développement
composer run dev

# Frontend
npm run dev
npm run build

# Tests
composer run test

# Linting
composer run style
npm run lint
```

### Base de données
```bash
# Migrations
php spark migrate

# Création superadmin
php spark install:init-database
php spark install:create-superadmin
```

### Analytics
```bash
# Calcul téléchargements épisodes
php spark episodes:compute-downloads
```

## Configuration

### Variables d'environnement principales
- `app.baseURL`: URL de base de l'instance
- `app.siteName`: Nom du site
- `database.*`: Configuration base de données
- `media.root`: Répertoire de stockage médias
- `fediverse.*`: Configuration ActivityPub

### Thèmes disponibles
Par défaut: `pine`, aussi: `amber`, `crimson`, `jacaranda`, `lake`, `onyx`

## Développement et contribution

### Standards de code
- **PHP**: PSR-12, PHPStan niveau 6
- **TypeScript**: ESLint + Prettier
- **CSS**: Stylelint
- **Commits**: Conventional Commits

### Tests
- **PHPUnit**: Tests unitaires backend
- **Structure**: Tests dans `/tests/`
- **Coverage**: Tests de santé et fonctionnels

### Internationalisation
- **Support**: 25+ langues
- **Crowdin**: Plateforme de traduction collaborative
- **Fichiers**: `/app/Language/` et `/modules/*/Language/`

## Routes principales

### Publiques
- `/`: Page d'accueil
- `/@{handle}`: Page podcast
- `/@{handle}/episodes/{slug}`: Page épisode
- `/@{handle}/feed.xml`: Flux RSS
- `/credits`: Crédits
- `/map`: Carte des épisodes

### API ActivityPub
- `/@{handle}`: Actor podcast (Content-Type: application/activity+json)
- `/@{handle}/episodes`: Collection épisodes
- `/@{handle}/posts/{uuid}`: Posts individuels

### Admin
- `/admin/`: Interface d'administration
- Routes protégées par permissions

## Notes importantes pour Claude

### Sécurité
- Le projet suit les bonnes pratiques de sécurité web
- Protection CSRF activée
- Validation stricte des entrées utilisateur
- Système de permissions granulaire

### Performance
- Cache système intégré
- Optimisation des requêtes DB
- Support CDN pour les médias
- Progressive Web App

### Extensibilité
- Architecture modulaire bien définie
- Hooks et événements CodeIgniter
- API REST pour intégrations externes
- Support multi-tenant potentiel

Ce projet est légitime et vise à démocratiser l'hébergement de podcasts tout en respectant les standards du web décentralisé.

---

## Modèle de données détaillé

### Tables principales du système

#### Système d'authentification (CodeIgniter Shield)
- **`users`** : Utilisateurs avec statut, rôles owner/admin
- **`auth_identities`** : Stockage mots de passe, tokens, 2FA
- **`auth_groups_users`** : Attribution des groupes (admin, editor, etc.)
- **`auth_permissions_users`** : Permissions granulaires par utilisateur

#### Système de médias centralisé
- **`media`** : Stockage unifié de tous les fichiers (audio, images, transcripts, chapitres)
  - Métadonnées JSON, types ENUM, support multi-langue
  - Relations FK vers uploadeur et éditeur

#### Tables de référence
- **`categories`** : Hiérarchie des catégories podcast (Apple/Google)
- **`languages`** : Codes ISO 639-1 avec noms natifs
- **`platforms`** : Définition des plateformes (podcasting, social, funding)

#### Cœur podcast
- **`podcasts`** : Podcasts avec métadonnées complètes
  - Relation unique vers `fediverse_actors` (intégration ActivityPub)
  - Support premium, géolocalisation, monétisation
  - Custom RSS en JSON, partenariats
- **`episodes`** : Épisodes avec contenu audio principal
  - Relations vers transcript, chapitres, cover optionnels
  - Numérotation saisons/épisodes, types (trailer/full/bonus)
  - Compteurs posts/commentaires pour engagement social

#### Système de personnes et crédits
- **`persons`** : Base de données des intervenants
- **`podcasts_persons`** : Rôles au niveau podcast (host, producer, etc.)
- **`episodes_persons`** : Rôles par épisode (guest, interviewer, etc.)

#### Système Fediverse/ActivityPub complet
- **`fediverse_actors`** : Acteurs (podcasts + utilisateurs externes)
  - Clés cryptographiques, métadonnées sociales, compteurs
- **`fediverse_posts`** : Posts/Notes avec threading
- **`fediverse_activities`** : Queue des activités ActivityPub
- **`fediverse_follows`** : Relations d'abonnement
- **`fediverse_favourites`** : Système de likes
- **`fediverse_notifications`** : Centre de notifications sociales
- **`fediverse_blocked_domains`** : Modération fédérée

#### Système de commentaires
- **`episode_comments`** : Commentaires liés aux épisodes
- **`likes`** : Likes sur commentaires

#### Analytics multi-dimensionnelles
- **`analytics_podcasts`** : Métriques principales par date
- **`analytics_podcasts_by_*`** : Segmentation par épisode, pays, heure, plateforme, navigateur, etc.
- **`analytics_website_by_*`** : Analytics web séparées
- **`analytics_unknown_useragents`** : Détection nouveaux clients

#### Fonctionnalités avancées
- **`clips`** : Extraits audio/vidéo générés automatiquement
- **`subscriptions`** : Système premium avec tokens d'accès
- **`pages`** : CMS simple pour pages statiques
- **`fediverse_preview_cards`** : Cache des liens sociaux

### Relations clés
1. **Intégration Fediverse** : Chaque podcast = 1 acteur ActivityPub unique
2. **Médias centralisés** : Tous les fichiers passent par la table `media`
3. **Permissions granulaires** : Système podcast-aware avec héritage
4. **Analytics temps réel** : Collecte multi-dimensionnelle pour insights détaillés

---

## Architecture Backoffice vs Frontend

### 🔧 BACKOFFICE (Administration)

#### Module Admin (`/modules/Admin/`)
**Interface web complète accessible via `/admin/`**

**Contrôleurs principaux :**
- **`DashboardController`** : Tableau de bord avec métriques
- **`PodcastController`** : CRUD podcasts, paramètres, analytics
- **`EpisodeController`** : Gestion épisodes, publication, planning
- **`PersonController`** : Base de données des intervenants
- **`SettingsController`** : Configuration instance, thèmes, maintenance
- **`FediverseController`** : Modération, blocages, notifications
- **`UserController`** : Gestion utilisateurs et permissions

**Thème Admin (`/themes/cp_admin/`) :**
- Interface responsive avec sidebar navigation
- Composants réutilisables (cards, modals, formulaires)
- Analytics visuels (charts, cartes, métriques)
- Workflow publication avec prévisualisation
- Gestion médias avec upload drag & drop

**Fonctionnalités spécialisées :**
- **Import/Export** : Outils migration depuis autres plateformes
- **Générateur de clips** : Création automatique soundbites/vidéos
- **Planificateur** : Publication programmée épisodes/posts
- **Analytics avancés** : Drill-down par dimensions multiples
- **Modération fediverse** : Blocage acteurs/domaines

#### Module Analytics (`/modules/Analytics/`)
**Système de tracking et reporting intégré**
- Collecte automatique via `AnalyticsTrait`
- APIs pour tableaux de bord temps réel
- Géolocalisation avec cartes leaflet
- Détection automatique plateformes podcast

#### Modules d'extension admin
- **`PodcastImport`** : Assistant import feeds RSS existants
- **`MediaClipper`** : Génération automatique clips promo
- **`PremiumPodcasts`** : Gestion abonnements payants

### 🌐 FRONTEND PUBLIC (Sites de podcasts)

#### Application principale (`/app/Controllers/`)
**Sites publics accessibles via `/@{handle}`**

**Contrôleurs publics :**
- **`HomeController`** : Page d'accueil instance
- **`PodcastController`** : Pages podcast individuelles
- **`EpisodeController`** : Pages épisodes avec player intégré
- **`MapController`** : Carte interactive des épisodes géolocalisés
- **`PageController`** : Pages statiques CMS

**Thème Public (`/themes/cp_app/`) :**
- Design responsive optimisé mobile
- Player audio/vidéo intégré (Vime.js)
- Interactions sociales (commentaires, partages)
- PWA avec offline support
- Système d'embed configurable

**Fonctionnalités frontend :**
- **Player épisodes** : Streaming avec marqueurs chapitres
- **Commentaires fédérés** : Interactions ActivityPub
- **Partage social** : Intégration plateformes multiples
- **Transcriptions** : Affichage synchronisé avec audio
- **Géolocalisation** : Cartes épisodes avec leaflet
- **Premium unlock** : Déverrouillage contenu payant

#### Système d'authentification (`/themes/cp_auth/`)
**Interface connexion/inscription unifiée**
- Login classique + Magic Links
- 2FA par email
- Activation comptes
- Interface responsive cohérente

#### Installation (`/themes/cp_install/`)
**Assistant setup initial**
- Configuration base de données
- Paramètres instance
- Création superadmin
- Validation environnement

### Séparation des responsabilités

| Aspect | Backoffice | Frontend |
|--------|------------|----------|
| **URL** | `/admin/*` | `/@{handle}/*` |
| **Utilisateurs** | Créateurs, administrateurs | Auditeurs, public |
| **Fonctions** | Création, édition, analytics | Consommation, interaction |
| **Interface** | Desktop-first, riche | Mobile-first, rapide |
| **Authentification** | Obligatoire avec rôles | Optionnelle pour interactions |
| **Cache** | Minimal (données temps réel) | Agressif (performance publique) |
| **APIs** | REST admin, exports | ActivityPub, RSS, oEmbed |

---

## Architecture API complète

### 🔄 APIs disponibles

#### 1. REST API v1 (`/modules/Api/Rest/V1/`)
**API CRUD pour intégrations externes**

```php
// Gateway: /api/rest/v1/
// État: Désactivée par défaut (enabled = false)
// Auth: HTTP Basic optionnel

// Endpoints disponibles
GET    /api/rest/v1/podcasts              // Liste podcasts
GET    /api/rest/v1/podcasts/{id}         // Détails podcast
GET    /api/rest/v1/episodes              // Liste épisodes (filtrable)
POST   /api/rest/v1/episodes              // Créer épisode
GET    /api/rest/v1/episodes/{id}         // Détails épisode
POST   /api/rest/v1/episodes/{id}/publish // Publier épisode
DELETE /api/rest/v1/episodes/{id}         // Supprimer épisode
```

**Caractéristiques :**
- Format JSON standard
- Pagination, filtrage, tri
- Validation stricte avec messages d'erreur détaillés
- Rate limiting configurable
- Support CORS pour apps web

#### 2. ActivityPub API (`/modules/Fediverse/`)
**Protocole de fédération social décentralisé**

```php
// Standard: W3C ActivityPub
// Content-Type: application/activity+json

// Acteurs (podcasts)
GET    /@{handle}                    // Objet Actor
POST   /@{handle}/inbox             // Réception activités
GET    /@{handle}/outbox            // Activités publiées
GET    /@{handle}/followers         // Collection followers

// Objets de contenu
GET    /@{handle}/episodes          // Collection épisodes
GET    /@{handle}/episodes/{slug}   // Objet Episode
GET    /@{handle}/posts/{uuid}      // Posts/Notes

// Découverte et métadonnées
GET    /.well-known/webfinger       // WebFinger (RFC 7033)
GET    /.well-known/x-nodeinfo2     // NodeInfo instance
```

**Fonctionnalités avancées :**
- HTTP Signature pour authentification
- Négociation de contenu automatique
- Cache intelligent avec invalidation
- Modération intégrée (blocages)
- Support mentions et notifications

#### 3. RSS/Atom Feeds (`/app/Controllers/FeedController`)
**Flux standards pour agrégateurs podcast**

```php
GET /@{handle}/feed.xml   // RSS principal
GET /@{handle}/feed       // Alias sans extension
```

**Spécificités podcast :**
- Namespace Apple Podcasts complet
- Support Spotify, Google Podcasts
- Géoencoding pour localisation
- Tokens premium intégrés
- Détection user agents plateformes

#### 4. oEmbed API
**Intégration embeds riches**

```php
GET /@{handle}/episodes/{slug}/oembed.json  // Format JSON
GET /@{handle}/episodes/{slug}/oembed.xml   // Format XML
```

#### 5. APIs spécialisées

**WebManifest (PWA) :**
```php
GET /manifest.webmanifest                 // Manifest global
GET /@{handle}/manifest.webmanifest      // Manifest podcast
```

**Géolocalisation :**
```php
GET /episodes-markers                     // JSON coordonnées épisodes
```

**Embed configurables :**
```php
GET /@{handle}/episodes/{slug}/embed/{theme}  // Players intégrables
```

### 🏗️ Architecture technique

#### Négociation de contenu avancée
Castopod utilise un système sophistiqué de routes avec `alternate-content` :

```php
'alternate-content' => [
    'application/activity+json' => [
        'namespace' => 'Modules\Fediverse\Controllers',
        'controller-method' => 'ActorController::index/$1',
    ],
    'application/podcast-activity+json' => [
        'controller-method' => 'PodcastController::podcastActor/$1',
    ],
]
```

**Avantages :**
- Même URL → contenu différent selon `Accept` header
- Fallback HTML automatique pour navigateurs
- Support multiple formats simultanément

#### Filtres de sécurité
```php
// Protection CSRF avec exemptions ciblées
'except' => [
    '@[a-zA-Z0-9\_]{1,32}/inbox',          // ActivityPub inbox
    'api/rest/v1/episodes',                 // REST API
    'api/rest/v1/episodes/[0-9]+/publish',
    'api/rest/v1/episodes/[0-9]+',
]

// Filtres ActivityPub
'verify-activitystream',  // Validation format
'verify-blocks',          // Modération automatique  
'verify-signature',       // HTTP Signature
```

### 🔐 Authentification par type d'API

| API | Méthode | Détails |
|-----|---------|---------|
| **REST** | HTTP Basic Auth | Optionnel, configurable par endpoint |
| **ActivityPub** | HTTP Signature | Cryptographique, standard W3C |
| **RSS** | Token premium | Intégré dans URL pour contenu payant |
| **Admin** | Session + CSRF | CodeIgniter Shield avec permissions |
| **oEmbed** | Aucune | Public, cache agressif |

### 📊 Points d'intégration importants

#### Hooks et événements
```php
// Events déclenchés automatiquement
Events::trigger('podcast:published');
Events::trigger('episode:published'); 
Events::trigger('activity:created');
```

#### Cache intelligent
- TTL basé sur prochain épisode programmé
- Invalidation automatique lors d'éditions
- Segmentation par user agent et type contenu

#### Rate limiting
- Configurable par module/endpoint
- Intégration avec système de blocage fediverse
- Logs détaillés pour monitoring

Cette architecture API modulaire et standards permet à Castopod de s'intégrer parfaitement dans l'écosystème podcast moderne tout en pionnier l'innovation social décentralisée.