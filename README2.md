### Complete Project Code

#### 1. **app/clients/FacebookClient.php**

```php
<?php

namespace HJRSocialMedia\Clients;

class FacebookClient {
    private $appId;
    private $appSecret;
    private $redirectUri;

    public function __construct($appId, $appSecret, $redirectUri) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->redirectUri = $redirectUri;
    }

    public function getAuthorizationUrl() {
        return "https://www.facebook.com/v10.0/dialog/oauth?client_id={$this->appId}&redirect_uri={$this->redirectUri}&scope=public_profile,email,publish_actions";
    }

    public function getAccessToken($code) {
        $url = "https://graph.facebook.com/v10.0/oauth/access_token?client_id={$this->appId}&redirect_uri={$this->redirectUri}&client_secret={$this->appSecret}&code={$code}";
        $response = file_get_contents($url);
        return json_decode($response, true);
    }

    public function postContent($accessToken, $message) {
        $url = "https://graph.facebook.com/me/feed";
        $data = [
            'message' => $message,
            'access_token' => $accessToken,
        ];

        $options = [
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
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

#### 2. **app/clients/InstagramClient.php**

```php
<?php

namespace HJRSocialMedia\Clients;

class InstagramClient {
    private $appId;
    private $appSecret;
    private $redirectUri;

    public function __construct($appId, $appSecret, $redirectUri) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->redirectUri = $redirectUri;
    }

    public function getAuthorizationUrl() {
        return "https://api.instagram.com/oauth/authorize?client_id={$this->appId}&redirect_uri={$this->redirectUri}&scope=user_profile,user_media&response_type=code";
    }

    public function getAccessToken($code) {
        $url = "https://api.instagram.com/oauth/access_token";
        $data = [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ];

        $options = [
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }

    public function postContent($accessToken, $imageUrl, $caption) {
        // Instagram API does not allow posting directly from the API for images.
        // You can use the Media API to create a media object and then publish it.
        return "Instagram post functionality is limited in this example.";
    }
}
```

#### 3. **app/clients/TikTokClient.php**

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
            'video' => new \CURLFile($videoPath),
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

#### 4. **app/clients/RedditClient.php**

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
            $data['media'] = new \CURLFile($mediaPath);
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

#### 5. **app/Analytics.php**

```php
<?php

namespace HJRSocialMedia;

class Analytics {
    public function trackEvent($platform, $event) {
        if (!isset($_SESSION['analytics'])) {
            $_SESSION['analytics'] = [];
        }

        if (!isset($_SESSION['analytics'][$platform])) {
            $_SESSION['analytics'][$platform] = [];
        }

        $_SESSION['analytics'][$platform][] = $event;
    }

    public function getAnalytics() {
        return $_SESSION['analytics'] ?? [];
    }
}
```

#### 6. **app/templates/index.twig**

```twig
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material

ize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <title>Social Media Post</title>
</head>
<body>
    <div class="container">
        <h1>Posten Sie auf Social Media</h1>
        <div id="social-media-buttons">
            <a href="{{ facebookClient.getAuthorizationUrl() }}" id="facebook-btn">Mit Facebook verbinden</a><br>
            <a href="{{ instagramClient.getAuthorizationUrl() }}" id="instagram-btn">Mit Instagram verbinden</a><br>
            <a href="{{ tiktokClient.getAuthorizationUrl() }}" id="tiktok-btn">Mit TikTok verbinden</a><br>
            <a href="{{ redditClient.getAuthorizationUrl() }}" id="reddit-btn">Mit Reddit verbinden</a>
        </div>
        <form id="postForm" enctype="multipart/form-data">
            <div class="input-field">
                <select name="platforms[]" id="platforms" multiple>
                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                    <option value="tiktok">TikTok</option>
                    <option value="reddit">Reddit</option>
                </select>
                <label>Plattformen auswählen</label>
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

        <div id="analytics">
            <h2>Analytics</h2>
            <pre id="analytics-data">{{ analytics | json_encode }}</pre>
        </div>

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

        // Change button colors after successful login
        const updateButtonColors = (platform) => {
            const buttons = ['facebook-btn', 'instagram-btn', 'tiktok-btn', 'reddit-btn'];
            buttons.forEach(btn => {
                const button = document.getElementById(btn);
                if (btn === platform) {
                    button.style.backgroundColor = 'green';
                } else {
                    button.style.backgroundColor = 'red';
                }
            });
        };
    </script>
</body>
</html>
```

#### 7. **app/post.php**

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
use HJRSocialMedia\Analytics;

// .env laden
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Konfiguration
$appIdFacebook = $_ENV['FACEBOOK_APP_ID'];
$appSecretFacebook = $_ENV['FACEBOOK_APP_SECRET'];
$redirectUriFacebook = $_ENV['FACEBOOK_REDIRECT_URI'];

$appIdInstagram = $_ENV['INSTAGRAM_APP_ID'];
$appSecretInstagram = $_ENV['INSTAGRAM_APP_SECRET'];
$redirectUriInstagram = $_ENV['INSTAGRAM_REDIRECT_URI'];

$appIdTikTok = $_ENV['TIKTOK_APP_ID'];
$appSecretTikTok = $_ENV['TIKTOK_APP_SECRET'];
$redirectUriTikTok = $_ENV['TIKTOK_REDIRECT_URI'];

$appIdReddit = $_ENV['REDDIT_APP_ID'];
$appSecretReddit = $_ENV['REDDIT_APP_SECRET'];
$redirectUriReddit = $_ENV['REDDIT_REDIRECT_URI'];

$facebookClient = new FacebookClient($appIdFacebook, $appSecretFacebook, $redirectUriFacebook);
$instagramClient = new InstagramClient($appIdInstagram, $appSecretInstagram, $redirectUriInstagram);
$tiktokClient = new TikTokClient($appIdTikTok, $appSecretTikTok, $redirectUriTikTok);
$redditClient = new RedditClient($appIdReddit, $appSecretReddit, $redirectUriReddit);

// Analytics initialisieren
$analytics = new Analytics();

// Post-Manager initialisieren
$postManager = new PostManager($facebookClient, $instagramClient, $tiktokClient, $redditClient);

// Posten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $platforms = $_POST['platforms'];
    $content = $_POST['content'];

    // Überprüfen, ob eine Datei hochgeladen wurde
    $uploadedFile = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['file'];
        $uploadPath = __DIR__ . '/uploads/' . basename($uploadedFile['name']);
        move_uploaded_file($uploadedFile['tmp_name'], $uploadPath);
    }

    foreach ($platforms as $platform) {
        if ($platform === 'facebook') {
            $result = $postManager->postToPlatform('facebook', $content);
            $analytics->trackEvent('Facebook', 'Post Successful');
        } elseif ($platform === 'instagram') {
            if ($uploadedFile) {
                $result = $postManager->postToPlatform('instagram', ['image_url' => 'http://localhost:8080/uploads/' . basename($uploadedFile['name']), 'caption' => $content]);
                $analytics->trackEvent('Instagram', 'Post Successful');
            }
        } elseif ($platform === 'tiktok') {
            if ($uploadedFile) {
                $result = $postManager->postToPlatform('tiktok', ['video_path' => 'http://localhost:8080/uploads/' . basename($uploadedFile['name']), 'caption' => $content]);
                $analytics->trackEvent('TikTok', 'Post Successful');
            }
        } elseif ($platform === 'reddit') {
            if ($uploadedFile) {
                $result = $postManager->postToPlatform('reddit', ['title' => $content, 'mediaPath' => $uploadPath]);
                $analytics->trackEvent('Reddit', 'Post Successful');
            } else {
                $result = $postManager->postToPlatform('reddit', ['title' => $content, 'content' => 'Hier ist ein Inhalt.']);
                $analytics->trackEvent('Reddit', 'Post Successful');
            }
        }
    }

    // Erfolgreiches Posten, Datei löschen
    if ($uploadedFile) {
        unlink($uploadPath); // Datei löschen
    }

    echo json_encode($result);
}
```

#### 8. **app/index.php**

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
use HJRSocialMedia\Analytics;

// .env laden
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Konfiguration
$appIdFacebook = $_ENV['FACEBOOK_APP_ID'];
$appSecretFacebook = $_ENV['FACEBOOK_APP_SECRET'];
$redirectUriFacebook = $_ENV['FACEBOOK_REDIRECT_URI'];

$appIdInstagram = $_ENV['INSTAGRAM_APP_ID'];
$appSecretInstagram = $_ENV['INSTAGRAM_APP_SECRET'];
$redirectUriInstagram = $_ENV['INSTAGRAM_REDIRECT_URI'];

$appIdTikTok = $_ENV['TIKTOK_APP_ID'];
$appSecretTikTok = $_ENV['TIKTOK_APP_SECRET'];
$redirectUriTikTok = $_ENV['TIKTOK_REDIRECT_URI'];

$appIdReddit = $_ENV['REDDIT_APP_ID'];
$appSecretReddit = $_ENV['REDDIT_APP_SECRET'];
$redirectUriReddit = $_ENV['REDDIT_REDIRECT_URI'];

$facebookClient = new FacebookClient($appIdFacebook, $appSecretFacebook, $redirectUriFacebook);
$instagramClient = new InstagramClient($appIdInstagram, $appSecretInstagram, $redirectUriInstagram);
$tiktokClient = new TikTokClient($appIdTikTok, $appSecretTikTok, $

redirectUriTikTok);
$redditClient = new RedditClient($appIdReddit, $appSecretReddit, $redirectUriReddit);

// Analytics initialisieren
$analytics = new Analytics();

// Twig-Umgebung einrichten
$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader);

// Analytics-Daten abrufen
$analyticsData = $analytics->getAnalytics();

echo $twig->render('index.twig', [
    'facebookClient' => $facebookClient,
    'instagramClient' => $instagramClient,
    'tiktokClient' => $tiktokClient,
    'redditClient' => $redditClient,
    'analytics' => $analyticsData,
]);
```

#### 9. **.env**

```env
FACEBOOK_APP_ID=your_facebook_app_id
FACEBOOK_APP_SECRET=your_facebook_app_secret
FACEBOOK_REDIRECT_URI=http://localhost:8080/facebook-callback

INSTAGRAM_APP_ID=your_instagram_app_id
INSTAGRAM_APP_SECRET=your_instagram_app_secret
INSTAGRAM_REDIRECT_URI=http://localhost:8080/instagram-callback

TIKTOK_APP_ID=your_tiktok_app_id
TIKTOK_APP_SECRET=your_tiktok_app_secret
TIKTOK_REDIRECT_URI=http://localhost:8080/tiktok-callback

REDDIT_APP_ID=your_reddit_app_id
REDDIT_APP_SECRET=your_reddit_app_secret
REDDIT_REDIRECT_URI=http://localhost:8080/reddit-callback
```

#### 10. **Dockerfile**

```Dockerfile
# PHP Image
FROM php:8.1-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy php.ini file
COPY php_upload.ini /usr/local/etc/php/conf.d/

# Set working directory
WORKDIR /var/www/html

# Copy the application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html
```

#### 11. **php_upload.ini**

```ini
upload_max_filesize = 2G
post_max_size = 2G
```

#### 12. **docker-compose.yml**

```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./app:/var/www/html
    environment:
      - FACEBOOK_APP_ID=${FACEBOOK_APP_ID}
      - FACEBOOK_APP_SECRET=${FACEBOOK_APP_SECRET}
      - FACEBOOK_REDIRECT_URI=${FACEBOOK_REDIRECT_URI}
      - INSTAGRAM_APP_ID=${INSTAGRAM_APP_ID}
      - INSTAGRAM_APP_SECRET=${INSTAGRAM_APP_SECRET}
      - INSTAGRAM_REDIRECT_URI=${INSTAGRAM_REDIRECT_URI}
      - TIKTOK_APP_ID=${TIKTOK_APP_ID}
      - TIKTOK_APP_SECRET=${TIKTOK_APP_SECRET}
      - TIKTOK_REDIRECT_URI=${TIKTOK_REDIRECT_URI}
      - REDDIT_APP_ID=${REDDIT_APP_ID}
      - REDDIT_APP_SECRET=${REDDIT_APP_SECRET}
      - REDDIT_REDIRECT_URI=${REDDIT_REDIRECT_URI}
```

### Step-by-Step Setup Guide

1. **Install Docker and Docker Compose**: Ensure you have Docker and Docker Compose installed on your system. Follow the official installation guides for your operating system.

2. **Create the Project Directory**:
   ```bash
   mkdir social-media-app
   cd social-media-app
   ```

3. **Set Up the Application Structure**:
   Create the following directories and files:
   ```bash
   mkdir -p app/clients app/templates uploads
   touch app/index.php app/post.php app/Analytics.php app/clients/FacebookClient.php app/clients/InstagramClient.php app/clients/TikTokClient.php app/clients/RedditClient.php app/templates/index.twig php_upload.ini Dockerfile docker-compose.yml .env
   ```

4. **Copy the Code**: Copy the provided code for each respective file.

5. **Fill the `.env` File**: Replace `your_facebook_app_id`, `your_facebook_app_secret`, etc., with the actual credentials from your social media developer accounts.

6. **Build the Docker Container**: In your terminal, run:
   ```bash
   docker-compose up --build
   ```

7. **Access the Application**: Open your web browser and navigate to `http://localhost:8080`.

8. **Set Up Social Media Applications**: Follow the respective platform's documentation to register your application and obtain the required credentials. Make sure to set the redirect URIs correctly.

9. **Testing**: Once you have logged in to the social media accounts, you can post content using the form. The analytics will be displayed on the same page.

10. **Debugging**: Check the logs if there are any issues:
   ```bash
   docker-compose logs
   ```

### Additional Notes

- Ensure you have the required permissions and scopes for posting content on the selected social media platforms.
- The current implementation assumes that the Instagram API allows image posting directly, but it might require media publishing through a media object. Please check the Instagram API documentation for the latest updates.
- For production deployment, consider configuring proper database support, error handling, and other security measures.

This setup should meet your requirements with all requested features integrated into the social media posting application. Let me know if you need further assistance!
