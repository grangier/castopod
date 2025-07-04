# TODO - Modifications Castopod

## Vue d'ensemble des objectifs

### 🎯 Objectifs principaux
1. **Redirection automatique** : `/@handle/` → `/@handle/episodes`
2. **Gestion des dates de publication** dans l'API REST
3. **Méthode PATCH** pour mise à jour épisodes
4. **Pagination standardisée** pour listes d'épisodes
5. **API plateformes de podcasting** avec informations de diffusion

---

## 1. 🔄 Redirection automatique vers /episodes

### 📍 Localisation du code
- **Contrôleur** : `/app/Controllers/PodcastController.php`
- **Routes** : `/app/Config/Routes.php` (lignes 55-190)
- **Thème** : `/themes/cp_app/podcast/_layout.php`
- **Navigation** : `/themes/cp_app/podcast/_partials/navigation.php`

### 🔍 Analyse actuelle
```php
// Dans Routes.php ligne 58
$routes->get('/', 'PodcastController::activity/$1', [
    'as' => 'podcast-activity',
    // ... alternate-content pour ActivityPub
]);
```

**Problème** : La route par défaut pointe vers `activity/$1` au lieu de `episodes/$1`

### ✅ Actions requises

#### 1.1 Modifier la route principale
```php
// Fichier: /app/Config/Routes.php
// Ligne 58 : Changer 'PodcastController::activity/$1' vers 'PodcastController::episodes/$1'
$routes->get('/', 'PodcastController::episodes/$1', [
    'as' => 'podcast-episodes', // Changer aussi l'alias
    'alternate-content' => [
        // Garder le support ActivityPub intact
    ]
]);
```

#### 1.2 Supprimer/commenter la vue activité
```php
// Dans /app/Controllers/PodcastController.php
// Commenter ou supprimer la méthode activity()
// public function activity(): string

// OU rediriger vers episodes
public function activity(): RedirectResponse
{
    return redirect()->to(route_to('podcast-episodes', $this->podcast->handle));
}
```

#### 1.3 Mettre à jour la navigation
```php
// Fichier: /themes/cp_app/podcast/_partials/navigation.php
// Supprimer l'onglet "Activité" ou le masquer
// Marquer "Épisodes" comme actif par défaut
```

#### 1.4 Vérifier les liens internes
- Rechercher tous les `route_to('podcast-activity')` et les remplacer
- Vérifier les redirections dans les contrôleurs admin
- Tester les liens de navigation dans l'interface

### 🔧 Fichiers à modifier
1. `/app/Config/Routes.php` - Route principale
2. `/app/Controllers/PodcastController.php` - Méthodes contrôleur
3. `/themes/cp_app/podcast/_partials/navigation.php` - Navigation
4. Recherche globale pour `podcast-activity` → `podcast-episodes`

---

## 2. 📅 Gestion des dates de publication dans l'API REST

### 📍 Localisation du code
- **Contrôleur API** : `/modules/Api/Rest/V1/Controllers/EpisodeController.php`
- **Modèle** : `/app/Models/EpisodeModel.php`
- **Import RSS** : `/modules/PodcastImport/Commands/PodcastImport.php`
- **Entité** : `/app/Entities/Episode.php`

### 🔍 Analyse actuelle
```php
// Dans EpisodeController API, méthode create()
// La date published_at n'est pas gérée dans les données POST
// Elle est automatiquement définie à NOW() lors de la publication
```

### 🔍 Référence import RSS
```php
// Fichier: /modules/PodcastImport/Commands/PodcastImport.php
// Rechercher comment les dates RSS sont traitées
// Probablement avec SimpleXML et conversion de dates RFC 2822
```

### ✅ Actions requises

#### 2.1 Modifier l'API REST pour accepter published_at
```php
// Fichier: /modules/Api/Rest/V1/Controllers/EpisodeController.php
// Dans la méthode create() et future update()

public function create(): ResponseInterface
{
    $data = $this->request->getJSON(true);
    
    // Ajouter validation pour published_at
    $rules = [
        // ... règles existantes
        'published_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        // OU permettre format ISO 8601
        'published_at' => 'permit_empty|regex_match[/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{3})?(?:Z|[+-]\d{2}:\d{2})$/]',
    ];
    
    // Traitement de la date
    if (isset($data['published_at'])) {
        // Convertir format ISO vers MySQL DATETIME
        $data['published_at'] = date('Y-m-d H:i:s', strtotime($data['published_at']));
    }
}
```

#### 2.2 Étudier l'import RSS existant
```bash
# Rechercher dans le code d'import RSS
grep -r "published_at\|pubDate\|published" /modules/PodcastImport/
grep -r "strtotime\|DateTime" /modules/PodcastImport/
```

#### 2.3 Validation et conversion des formats
```php
// Supporter plusieurs formats d'entrée :
// - "2024-01-15T14:30:00Z" (ISO 8601)
// - "2024-01-15 14:30:00" (MySQL)
// - "Mon, 15 Jan 2024 14:30:00 +0000" (RFC 2822)

private function parsePublishedDate(string $date): ?string
{
    $formats = [
        'Y-m-d\TH:i:s\Z',           // ISO 8601 UTC
        'Y-m-d\TH:i:sP',            // ISO 8601 avec timezone
        'Y-m-d H:i:s',              // MySQL datetime
        'D, d M Y H:i:s O',         // RFC 2822
    ];
    
    foreach ($formats as $format) {
        $parsed = DateTime::createFromFormat($format, $date);
        if ($parsed !== false) {
            return $parsed->format('Y-m-d H:i:s');
        }
    }
    
    // Fallback avec strtotime
    $timestamp = strtotime($date);
    return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
}
```

### 🔧 Fichiers à modifier
1. `/modules/Api/Rest/V1/Controllers/EpisodeController.php` - Validation et traitement
2. `/modules/Api/Rest/V1/Config/Validation.php` - Règles de validation
3. Étude de `/modules/PodcastImport/Commands/PodcastImport.php` - Référence

---

## 3. 🔄 Méthode PATCH pour mise à jour épisodes

### 📍 Localisation du code
- **Routes API** : `/modules/Api/Rest/V1/Config/Routes.php`
- **Contrôleur** : `/modules/Api/Rest/V1/Controllers/EpisodeController.php`
- **Validation** : `/modules/Api/Rest/V1/Config/Validation.php`

### 🔍 Analyse actuelle
```php
// Dans Routes.php - pas de route PATCH définie
// Seulement GET, POST, DELETE
```

### ✅ Actions requises

#### 3.1 Ajouter la route PATCH
```php
// Fichier: /modules/Api/Rest/V1/Config/Routes.php
$routes->patch('episodes/(:num)', 'EpisodeController::update/$1', [
    'as' => 'api-episode-update',
]);
```

#### 3.2 Implémenter la méthode update()
```php
// Fichier: /modules/Api/Rest/V1/Controllers/EpisodeController.php

public function update(int $episodeId): ResponseInterface
{
    // 1. Vérifier que l'épisode existe
    $episode = model('EpisodeModel')->find($episodeId);
    if (!$episode) {
        return $this->response->setStatusCode(404)
            ->setJSON(['error' => 'Episode not found']);
    }
    
    // 2. Vérifier les permissions (podcast ownership)
    // Logique similaire à delete()
    
    // 3. Récupérer données PATCH (support JSON et multipart)
    $data = $this->request->getJSON(true) ?? $this->request->getPost();
    
    // 4. Validation avec règles optionnelles (PATCH = partial update)
    $rules = [
        'title' => 'permit_empty|max_length[128]',
        'description' => 'permit_empty',
        'published_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        // ... autres champs
    ];
    
    // 5. Traitement upload fichiers (audio, cover)
    if ($this->request->getFile('audio_file')) {
        // Logique upload similar à create()
    }
    
    // 6. Mise à jour partielle
    $updateData = array_filter($data, function($value) {
        return $value !== null && $value !== '';
    });
    
    $updateData['updated_by'] = user_id();
    $updateData['updated_at'] = date('Y-m-d H:i:s');
    
    // 7. Exécuter update
    model('EpisodeModel')->update($episodeId, $updateData);
    
    // 8. Retourner épisode mis à jour
    $updatedEpisode = model('EpisodeModel')->getEpisodeById($episodeId);
    return $this->response->setJSON($updatedEpisode);
}
```

#### 3.3 Gestion des permissions
```php
// Réutiliser la logique de delete() pour vérifier ownership
private function checkEpisodePermission(Episode $episode): bool
{
    // Vérifier que l'utilisateur peut modifier cet épisode
    // Via podcast ownership ou permissions admin
}
```

#### 3.4 Support upload de fichiers en PATCH
```php
// Gérer les cas où on veut changer l'audio ou la cover
// Sans supprimer l'ancien fichier si pas de nouveau fichier fourni
```

### 🔧 Fichiers à modifier
1. `/modules/Api/Rest/V1/Config/Routes.php` - Nouvelle route PATCH
2. `/modules/Api/Rest/V1/Controllers/EpisodeController.php` - Méthode update()
3. Tests pour valider le comportement PATCH

---

## 4. 📄 Pagination standardisée pour listes d'épisodes

### 📍 Localisation du code
- **Contrôleur API** : `/modules/Api/Rest/V1/Controllers/EpisodeController.php`
- **Configuration** : `/modules/Api/Rest/V1/Config/Api.php`
- **Modèle** : `/app/Models/EpisodeModel.php`

### 🔍 Analyse actuelle
```php
// Dans EpisodeController::list()
// Pas de pagination actuellement implémentée
// Retourne tous les épisodes sans structure de pagination
```

### ✅ Actions requises

#### 4.1 Ajouter configuration pagination
```php
// Fichier: /modules/Api/Rest/V1/Config/Api.php
class Api extends BaseConfig
{
    public int $defaultLimit = 20;
    public int $maxLimit = 100;
    public string $paginationFormat = 'standard'; // ou 'simple'
}
```

#### 4.2 Modifier la méthode list()
```php
// Fichier: /modules/Api/Rest/V1/Controllers/EpisodeController.php

public function list(): ResponseInterface
{
    // 1. Récupérer paramètres pagination
    $limit = (int) ($this->request->getGet('limit') ?? config('Api')->defaultLimit);
    $offset = (int) ($this->request->getGet('offset') ?? 0);
    
    // 2. Validation des paramètres
    $limit = min($limit, config('Api')->maxLimit);
    $limit = max($limit, 1);
    $offset = max($offset, 0);
    
    // 3. Récupérer filtres existants
    $podcastId = $this->request->getGet('podcast_id');
    // ... autres filtres
    
    // 4. Compter total d'épisodes
    $totalCount = model('EpisodeModel')->countAllEpisodes($podcastId);
    
    // 5. Récupérer épisodes paginés
    $episodes = model('EpisodeModel')->getEpisodesPaginated($limit, $offset, $podcastId);
    
    // 6. Construire URLs next/previous
    $baseUrl = base_url('api/rest/v1/episodes');
    $queryParams = $this->request->getGet();
    
    $nextUrl = null;
    $prevUrl = null;
    
    if ($offset + $limit < $totalCount) {
        $queryParams['limit'] = $limit;
        $queryParams['offset'] = $offset + $limit;
        $nextUrl = $baseUrl . '?' . http_build_query($queryParams);
    }
    
    if ($offset > 0) {
        $queryParams['limit'] = $limit;
        $queryParams['offset'] = max(0, $offset - $limit);
        $prevUrl = $baseUrl . '?' . http_build_query($queryParams);
    }
    
    // 7. Retourner structure paginée
    return $this->response->setJSON([
        'count' => $totalCount,
        'next' => $nextUrl,
        'previous' => $prevUrl,
        'results' => $episodes
    ]);
}
```

#### 4.3 Étendre EpisodeModel
```php
// Fichier: /app/Models/EpisodeModel.php

public function countAllEpisodes(?int $podcastId = null): int
{
    $builder = $this->builder();
    
    if ($podcastId) {
        $builder->where('podcast_id', $podcastId);
    }
    
    // Appliquer filtres communs (published, not blocked, etc.)
    $builder->where('published_at IS NOT NULL')
            ->where('is_blocked', 0);
    
    return $builder->countAllResults();
}

public function getEpisodesPaginated(int $limit, int $offset, ?int $podcastId = null): array
{
    $builder = $this->builder();
    
    if ($podcastId) {
        $builder->where('podcast_id', $podcastId);
    }
    
    // Appliquer filtres et tri
    $builder->where('published_at IS NOT NULL')
            ->where('is_blocked', 0)
            ->orderBy('published_at', 'DESC')
            ->limit($limit, $offset);
    
    return $builder->get()->getResultArray();
}
```

#### 4.4 Paramètres de pagination supportés
```php
// URL: /api/rest/v1/episodes?limit=20&offset=40&podcast_id=5

// Paramètres à supporter :
// - limit : nombre d'éléments par page (défaut: 20, max: 100)
// - offset : décalage (défaut: 0)
// - page : alternative à offset (page * limit = offset)
// - Filtres existants : podcast_id, etc.
```

### 🔧 Fichiers à modifier
1. `/modules/Api/Rest/V1/Controllers/EpisodeController.php` - Logique pagination
2. `/app/Models/EpisodeModel.php` - Méthodes count et paginated
3. `/modules/Api/Rest/V1/Config/Api.php` - Configuration
4. Tests unitaires pour validation pagination

---

## 5. 🎵 API plateformes de podcasting

### 📍 Localisation du code
- **Modèle** : `/modules/Platforms/Models/PlatformModel.php`
- **Entité** : `/modules/Platforms/Entities/Platform.php`
- **Table liaison** : `podcasts_platforms` (migration 2021-06-05-200000)
- **Contrôleur Admin** : `/modules/Admin/Controllers/PodcastController.php` (méthodes platforms)

### 🔍 Analyse actuelle
```sql
-- Table platforms
slug VARCHAR(32) PRIMARY KEY
type ENUM('podcasting', 'social', 'funding')
label VARCHAR(32)
home_url VARCHAR(255)
submit_url VARCHAR(512)

-- Table podcasts_platforms (liaison)
podcast_id INT
platform_slug VARCHAR(32)
link_url VARCHAR(512)
account_id VARCHAR(128)
is_visible TINYINT(1)
is_on_embed TINYINT(1)
```

### ✅ Actions requises

#### 5.1 Créer nouveau contrôleur API
```php
// Fichier: /modules/Api/Rest/V1/Controllers/PlatformController.php

<?php

namespace Modules\Api\Rest\V1\Controllers;

class PlatformController extends RestController
{
    public function list(): ResponseInterface
    {
        // Lister toutes les plateformes de type 'podcasting'
        $platforms = model('PlatformModel')
            ->where('type', 'podcasting')
            ->findAll();
            
        return $this->response->setJSON($platforms);
    }
    
    public function podcastPlatforms(int $podcastId): ResponseInterface
    {
        // Récupérer les plateformes configurées pour un podcast
        $platforms = $this->getPodcastPlatforms($podcastId);
        
        return $this->response->setJSON($platforms);
    }
    
    public function updatePodcastPlatform(int $podcastId, string $platformSlug): ResponseInterface
    {
        // Mettre à jour la config d'une plateforme pour un podcast
        $data = $this->request->getJSON(true);
        
        // Validation
        $rules = [
            'link_url' => 'required|valid_url',
            'account_id' => 'permit_empty|max_length[128]',
            'is_visible' => 'permit_empty|in_list[0,1]',
            'is_on_embed' => 'permit_empty|in_list[0,1]',
        ];
        
        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['errors' => $this->validator->getErrors()]);
        }
        
        // Upsert dans podcasts_platforms
        $result = $this->upsertPodcastPlatform($podcastId, $platformSlug, $data);
        
        return $this->response->setJSON($result);
    }
    
    private function getPodcastPlatforms(int $podcastId): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->table('podcasts_platforms pp')
            ->select('p.*, pp.link_url, pp.account_id, pp.is_visible, pp.is_on_embed')
            ->join('platforms p', 'p.slug = pp.platform_slug')
            ->where('pp.podcast_id', $podcastId)
            ->where('p.type', 'podcasting')
            ->get();
            
        return $query->getResultArray();
    }
}
```

#### 5.2 Ajouter routes API plateformes
```php
// Fichier: /modules/Api/Rest/V1/Config/Routes.php

// Plateformes générales
$routes->get('platforms', 'PlatformController::list', [
    'as' => 'api-platforms-list'
]);

// Plateformes par podcast
$routes->get('podcasts/(:num)/platforms', 'PlatformController::podcastPlatforms/$1', [
    'as' => 'api-podcast-platforms'
]);

$routes->put('podcasts/(:num)/platforms/(:alphanum)', 'PlatformController::updatePodcastPlatform/$1/$2', [
    'as' => 'api-podcast-platform-update'
]);

$routes->delete('podcasts/(:num)/platforms/(:alphanum)', 'PlatformController::removePodcastPlatform/$1/$2', [
    'as' => 'api-podcast-platform-remove'
]);
```

#### 5.3 Étendre les endpoints épisodes/podcasts existants
```php
// Dans EpisodeController et PodcastController
// Inclure les informations de plateformes dans les réponses

public function show(int $id): ResponseInterface
{
    $episode = model('EpisodeModel')->getEpisodeById($id);
    
    if (!$episode) {
        return $this->response->setStatusCode(404)
            ->setJSON(['error' => 'Episode not found']);
    }
    
    // Ajouter les plateformes du podcast
    $episode['podcast']['platforms'] = $this->getPodcastPlatforms($episode['podcast_id']);
    
    return $this->response->setJSON($episode);
}
```

#### 5.4 Structure de réponse pour plateformes
```json
// GET /api/rest/v1/platforms
[
    {
        "slug": "apple-podcasts",
        "type": "podcasting",
        "label": "Apple Podcasts",
        "home_url": "https://podcasts.apple.com/",
        "submit_url": "https://podcastsconnect.apple.com/"
    }
]

// GET /api/rest/v1/podcasts/5/platforms
[
    {
        "slug": "apple-podcasts",
        "label": "Apple Podcasts",
        "link_url": "https://podcasts.apple.com/podcast/id123456789",
        "account_id": "123456789",
        "is_visible": true,
        "is_on_embed": true
    }
]
```

### 🔧 Fichiers à modifier
1. `/modules/Api/Rest/V1/Controllers/PlatformController.php` - Nouveau contrôleur
2. `/modules/Api/Rest/V1/Config/Routes.php` - Nouvelles routes
3. Étendre `/modules/Api/Rest/V1/Controllers/EpisodeController.php` et `PodcastController.php`
4. Tests d'intégration API

---

## 🔧 Plan d'implémentation suggéré

### Phase 1 : Frontend (Redirection)
1. Modifier route principale dans `Routes.php`
2. Adapter contrôleur `PodcastController`
3. Mettre à jour navigation et liens
4. Tester toutes les URLs

### Phase 2 : API - Dates et PATCH
1. Étudier code import RSS pour dates
2. Implémenter gestion `published_at` dans API
3. Ajouter méthode PATCH avec validation
4. Tests unitaires complets

### Phase 3 : API - Pagination
1. Configurer paramètres pagination
2. Étendre `EpisodeModel` avec méthodes count/paginated
3. Modifier `EpisodeController::list()`
4. Valider structure de réponse

### Phase 4 : API - Plateformes
1. Créer `PlatformController`
2. Définir routes plateformes
3. Étendre endpoints existants
4. Documentation API complète

### Phase 5 : Tests et validation
1. Tests d'intégration pour chaque endpoint
2. Validation des performances avec pagination
3. Tests de régression sur fonctionnalités existantes
4. Documentation utilisateur

---

## 📚 Ressources et références

### Fichiers clés à étudier
- `/modules/PodcastImport/Commands/PodcastImport.php` - Logique dates RSS
- `/modules/Admin/Controllers/PodcastController.php` - Gestion plateformes admin
- `/app/Models/EpisodeModel.php` - Modèle de données épisodes
- `/modules/Api/Rest/V1/Controllers/EpisodeController.php` - API actuelle

### Commandes utiles pour développement
```bash
# Activer l'API REST
# Modifier /modules/Api/Rest/V1/Config/Api.php : enabled = true

# Tests
composer test

# Linting
composer run style

# Base de données
php spark migrate
```

### Points d'attention
- **Permissions** : Vérifier ownership podcast pour toutes modifications API
- **Cache** : Invalider cache après modifications (épisodes, plateformes)
- **ActivityPub** : Maintenir compatibilité fediverse lors modifications routes
- **Sécurité** : Validation stricte toutes données API (XSS, injection, etc.)
- **Performance** : Optimiser requêtes pagination pour gros volumes