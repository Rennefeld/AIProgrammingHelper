# HJRSocialMedia

Hier ist die aktualisierte Version der Anwendung mit einer `.env`-Datei zur Speicherung der App-ID und des App-Secrets sowie der Möglichkeit, Bilder oder Videos für Reddit hochzuladen. Ich habe die Anwendung so angepasst, dass sie die Umgebungsvariablen aus der `.env`-Datei lädt und auch lokale Pfade für den Upload verwendet. 

### Verzeichnisstruktur

```plaintext
.
├── app
│   ├── clients
│   │   ├── FacebookClient.php
│   │   ├── InstagramClient.php
│   │   ├── TikTokClient.php
│   │   └── RedditClient.php
│   ├── templates
│   │   └── index.twig
│   ├── index.php
│   ├── post.php
│   ├── uploads
│   ├── .env
│   ├── composer.json
├── docker-compose.yml
└── Dockerfile
```

### 1. Aktualisierte Dateien

#### **app/.env**

```plaintext
APP_ID=your_app_id
APP_SECRET=your_app_secret
REDIRECT_URI=http://localhost:8080/index.php
```

#### **app/clients/TikTokClient.php**

```php
<?php

namespace HJRSocialMedia\Clients;

class TikTokClient {
    private $appId;
    private $appSecret;
    private $redirectUri;

    public function __construct($appId, $appSecret, $redirectUri) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->redirectUri = $redirectUri;
    }

    public function getAuthorizationUrl() {
        return "https://www.tiktok.com/auth/authorize?client_key={$this->appId}&redirect_uri={$this->redirectUri}&response_type=code&scope=user.info.basic,video.list";
    }

    public function getAccessToken($code) {
        $url = "https://open-api.tiktok.com/oauth/access_token/";
        $data = [
            'client_key' => $this->appId,
            'client_secret' => $this->appSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
        
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
            ],
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }

    public function postContent($accessToken, $videoPath, $caption) {
        $url = "https://open-api.tiktok.com/v1/video/publish/";
        $data = [
            'access_token' => $accessToken,
            'video' => new \CURLFile($videoPath), // Benutzt CURLFile für Dateiupload
            'caption' => $caption,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
```

#### **app/clients/RedditClient.php**

```php
<?php

namespace HJRSocialMedia\Clients;

class RedditClient {
    private $appId;
    private $appSecret;
    private $redirectUri;

    public function __construct($appId, $appSecret, $redirectUri) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->redirectUri = $redirectUri;
    }

    public function getAuthorizationUrl() {
        return "https://www.reddit.com/api/v1/authorize?client_id={$this->appId}&response_type=code&state=random_string&redirect_uri={$this->redirectUri}&scope=read,submit";
    }

    public function getAccessToken($code) {
        $url = "https://www.reddit.com/api/v1/access_token";
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ];

        $options = [
            'http' => [
                'header' => "Authorization: Basic " . base64_encode($this->appId . ':' . $this->appSecret) . "\r\n" .
                            "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }

    public function postContent($accessToken, $title, $content, $mediaPath = null) {
        $url = "https://oauth.reddit.com/api/submit";
        $data = [
            'title' => $title,
            'kind' => 'self',
            'sr' => 'YOUR_SUBREDDIT', // Hier den Subreddit einfügen
        ];

        if ($mediaPath) {
            $data['media'] = new \CURLFile($mediaPath); // Lokaler Pfad für Bild oder Video
            $data['kind'] = 'image'; // Oder 'video' für Videos
        }

        $options = [
            'http' => [
                'header' => "Authorization: bearer {$accessToken}\r\n" .
                            "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }
}
```

#### **app/post.php**

```php
<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/vlucas/phpdotenv/src/Dotenv.php';
require_once __DIR__ . '/vendor/vlucas/phpdotenv/src/Loader.php';
require_once __DIR__ . '/vendor/vlucas/phpdotenv/src/DotenvBuilder.php';

use HJRSocialMedia\PostManager;
use HJRSocialMedia\Clients\FacebookClient;
use HJRSocialMedia\Clients\InstagramClient;
use HJRSocialMedia\Clients\TikTokClient;
use HJRSocialMedia\Clients\RedditClient;

// .env laden
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Konfiguration
$appId = $_ENV['APP_ID'];
$appSecret = $_ENV['APP_SECRET'];
$redirectUri = $_ENV['REDIRECT_URI'];

$facebookClient = new FacebookClient($appId, $appSecret, $redirectUri);
$instagramClient = new InstagramClient($appId, $appSecret, $redirectUri);
$tiktokClient = new TikTokClient($appId, $appSecret, $redirectUri);
$redditClient = new RedditClient($appId, $appSecret, $redirectUri);

// Post-Manager initialisieren
$postManager = new PostManager($facebookClient, $instagramClient, $tiktokClient, $redditClient);

// Posten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $platform = $_POST['platform'];
    $content = $_POST['content'];

    // Überprüfen, ob eine Datei hochgeladen wurde
    $uploadedFile = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['file'];
        $uploadPath = __DIR__ . '/uploads/' . basename($uploadedFile['name']);
        move_uploaded_file($uploadedFile['tmp_name'], $uploadPath);
    }

    // Hier können spezifische Inhalte für jede Plattform definiert werden
    if ($platform === 'facebook') {
        $result = $postManager->postToPlatform('facebook', $content);
    } elseif ($platform === 'instagram') {
        if ($uploadedFile) {
            $result = $postManager->postToPlatform('instagram', ['image_url' => 'http://localhost:8080/uploads/' . basename($uploadedFile['name']), 'caption' => $content]);
        }
    } elseif ($platform === 'tiktok') {
        if ($uploadedFile) {
            $result = $postManager->postToPlatform('tiktok', ['video_path' => 'http://localhost:8080/uploads/' . basename($uploadedFile['name']), 'caption' => $content]);
        }
    } elseif ($platform === 'reddit') {
        if ($uploadedFile) {
            $result = $postManager->postToPlatform('reddit', ['title' => $content, 'mediaPath' => $uploadPath]);
        } else {
            $result = $postManager->postToPlatform('reddit', ['title' => $content, 'content' => 'Hier ist ein Inhalt.']);
        }
    }

    // Erfolgreiches Posten, Datei löschen
    if ($uploadedFile) {
        unlink($uploadPath); // Datei löschen
    }

    echo json_encode($result);
}
```

#### **app/templates/index.twig**

```twig
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset

="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <title>Social Media Post</title>
</head>
<body>
    <div class="container">
        <h1>Posten Sie auf Social Media</h1>
        <a href="{{ facebookClient.getAuthorizationUrl() }}">Mit Facebook verbinden</a><br>
        <a href="{{ instagramClient.getAuthorizationUrl() }}">Mit Instagram verbinden</a><br>
        <a href="{{ tiktokClient.getAuthorizationUrl() }}">Mit TikTok verbinden</a><br>
        <a href="{{ redditClient.getAuthorizationUrl() }}">Mit Reddit verbinden</a>

        <form id="postForm" enctype="multipart/form-data">
            <div class="input-field">
                <select name="platform" id="platform">
                    <option value="" disabled selected>Wählen Sie eine Plattform</option>
                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                    <option value="tiktok">TikTok</option>
                    <option value="reddit">Reddit</option>
                </select>
                <label>Plattform auswählen</label>
            </div>
            <div class="input-field">
                <input type="text" name="content" id="content" required>
                <label for="content">Inhalt</label>
            </div>
            <div class="input-field">
                <input type="file" name="file" id="file" required>
                <label for="file">Datei hochladen</label>
            </div>
            <button class="waves-effect waves-light btn" type="submit">Posten</button>
        </form>

        <div id="result"></div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.getElementById('postForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            fetch('post.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('result').innerHTML = JSON.stringify(data);
            })
            .catch(error => console.error('Fehler:', error));
        });
    </script>
</body>
</html>
```

#### **app/index.php**

```php
<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/vlucas/phpdotenv/src/Dotenv.php';
require_once __DIR__ . '/vendor/vlucas/phpdotenv/src/Loader.php';
require_once __DIR__ . '/vendor/vlucas/phpdotenv/src/DotenvBuilder.php';

use HJRSocialMedia\Clients\FacebookClient;
use HJRSocialMedia\Clients\InstagramClient;
use HJRSocialMedia\Clients\TikTokClient;
use HJRSocialMedia\Clients\RedditClient;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

// .env laden
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Konfiguration
$appId = $_ENV['APP_ID'];
$appSecret = $_ENV['APP_SECRET'];
$redirectUri = $_ENV['REDIRECT_URI'];

$facebookClient = new FacebookClient($appId, $appSecret, $redirectUri);
$instagramClient = new InstagramClient($appId, $appSecret, $redirectUri);
$tiktokClient = new TikTokClient($appId, $appSecret, $redirectUri);
$redditClient = new RedditClient($appId, $appSecret, $redirectUri);

// Twig initialisieren
$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader);

// Rendern der Hauptseite
echo $twig->render('index.twig', [
    'facebookClient' => $facebookClient,
    'instagramClient' => $instagramClient,
    'tiktokClient' => $tiktokClient,
    'redditClient' => $redditClient,
]);
```

#### **app/composer.json**

```json
{
    "require": {
        "twig/twig": "^3.0",
        "vlucas/phpdotenv": "^5.4"
    }
}
```

### 2. Docker-Setup

#### **docker-compose.yml**

```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./app:/var/www/html
    networks:
      - social-media-network

networks:
  social-media-network:
    driver: bridge
```

#### **Dockerfile**

```Dockerfile
FROM php:7.4-apache

RUN docker-php-ext-install pdo pdo_mysql

COPY ./app /var/www/html
```

### 3. Hinweise zu den Datei-Uploads

- **Lokale Pfade**: Der Upload von Bildern oder Videos erfordert die Verwendung von lokalen Pfaden, die auf dem Server zugänglich sind. Wenn ein Benutzer ein Bild oder Video hochlädt, wird es auf dem Server gespeichert, bevor es an die API der sozialen Plattform gesendet wird.
- **URL-Anpassung**: In der `post.php`-Datei wird der lokale Pfad verwendet, um die hochgeladenen Dateien an die APIs der sozialen Medien zu übergeben.

### Fazit

Mit dieser vollständigen Implementierung sollten Sie in der Lage sein, Ihre Anwendung erfolgreich auszuführen, einschließlich der Upload-Funktionalität für Reddit, und die API-Integrationen für alle anderen Plattformen. Stellen Sie sicher, dass Sie die Umgebungsvariablen in der `.env`-Datei korrekt einrichten und die erforderlichen Berechtigungen in den jeweiligen Entwicklerportalen der sozialen Plattformen konfigurieren.
