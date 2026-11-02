# Laravel Firebase Notification

<p align="center">
  <img src="https://raw.githubusercontent.com/Creatix-Infotech/laravel-firebase-notification/main/art/banner.png" alt="Laravel Firebase Notification Banner" width="100%">
</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creatix/laravel-firebase-notification.svg?style=flat-square)](https://packagist.org/packages/creatix/laravel-firebase-notification)
[![Total Downloads](https://img.shields.io/packagist/dt/creatix/laravel-firebase-notification.svg?style=flat-square)](https://packagist.org/packages/creatix/laravel-firebase-notification)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

**Laravel Firebase Notification** is an enterprise-grade Laravel package designed to send rich, high-priority, real-time push notifications to **Android** and **iOS** mobile applications—mimicking the notification experience of **WhatsApp**, **Instagram**, and **Snapchat**.

Powered by the **Google FCM HTTP v1 API** (OAuth2 Service Account) and **Apple APNs HTTP/2** (`.p8` JWT tokens).

---

## 🌟 Key Features

- **📱 WhatsApp-Style Chat Presets**: Sender avatars, conversation thread grouping (`thread-id` / collapse tags), direct reply categories (`CHAT_REPLY`), and high-priority heads-up banners.
- **📸 Instagram-Style Media Presets**: Rich thumbnail previews (photos, reel thumbnails), deep-links (`app://posts/123`), activity actions, and branded themes.
- **👻 Snapchat-Style Ephemeral Presets**: Time-to-Live (TTL) expiration and silent background synchronization (`content-available: 1`).
- **📞 Incoming Call Alerts**: Highest-priority VoIP/Call alerts with custom ringtone sounds (`ringtone.caf`) and answer/decline action categories.
- **⚡ Modern Transport Protocols**:
  - **Android**: Direct integration with the modern **FCM HTTP v1** REST API using Google OAuth2 Service Accounts (deprecating legacy server keys).
  - **iOS**: Direct **APNs HTTP/2** client with `.p8` ES256 token authentication (no expiring `.p12` certificates!).
- **🔍 Smart Platform Detection**: Automatically detects whether a recipient token belongs to iOS or Android by analyzing its signature format.
- **🧹 Auto Token Pruning**: Automatically detects `Unregistered`, `BadDeviceToken`, or expired device tokens and deactivates them in your database.
- **🔔 Native Laravel Notifications**: Seamlessly integrates with Laravel's `Notification::send()` as an `AppNotificationChannel`.

---

## 📦 Installation

Install the package via Composer:

```bash
composer require creatix/laravel-firebase-notification
```

Publish the configuration file and optional device tokens migration:

```bash
php artisan vendor:publish --tag="notification-manager-config"
php artisan vendor:publish --tag="notification-manager-migrations"
php artisan migrate
```

---

## ⚙️ Configuration

Add your Firebase and Apple credentials to your `.env` file:

```env
# Default driver: 'auto', 'fcm', or 'apns'
PUSH_NOTIFICATION_DRIVER=auto

# --- Firebase Cloud Messaging (FCM HTTP v1) ---
FCM_PROJECT_ID=your-firebase-project-id
FCM_CREDENTIALS_PATH=/absolute/path/to/firebase-credentials.json
# Or provide the JSON directly:
# FCM_CREDENTIALS_JSON='{"type":"service_account", ...}'
FCM_DEFAULT_CHANNEL_ID=high_importance_channel
FCM_DEFAULT_SOUND=default

# --- Apple Push Notification Service (APNs HTTP/2) ---
APNS_KEY_ID=ABC123DEFG
APNS_TEAM_ID=TEAM123456
APNS_BUNDLE_ID=com.yourcompany.app
APNS_PRIVATE_KEY_PATH=/absolute/path/to/AuthKey_APNS.p8
APNS_PRODUCTION=false # Set to true for production App Store builds
APNS_DEFAULT_SOUND=default

# --- Auto Token Pruning & Logging ---
PUSH_AUTO_PRUNE_INVALID_TOKENS=true
PUSH_LOGGING_ENABLED=false
```

---

## 🚀 Usage

### 1. WhatsApp-Style Chat Notification in Laravel Notifications

Create a Laravel Notification using the `AppNotificationChannel` and `ChatMessagePreset`:

```php
namespace App\Notifications;

use Creatix\AppNotificationManager\Channels\AppNotificationChannel;
use Creatix\AppNotificationManager\Contracts\NotificationMessageInterface;
use Creatix\AppNotificationManager\Messages\Presets\ChatMessagePreset;
use Illuminate\Notifications\Notification;

class NewMessageReceived extends Notification
{
    public function __construct(
        protected $message
    ) {}

    public function via($notifiable): array
    {
        return [AppNotificationChannel::class];
    }

    public function toAppNotification($notifiable): NotificationMessageInterface
    {
        return ChatMessagePreset::create()
            ->sender(
                name: $this->message->sender->name,
                avatarUrl: $this->message->sender->avatar_url
            )
            ->message($this->message->text)
            ->conversation(
                conversationId: $this->message->room_id,
                groupName: $this->message->room->name
            )
            ->attachment($this->message->attachment_preview_url)
            ->badge(1)
            ->deepLink("app://chat/{$this->message->room_id}")
            ->withQuickReply();
    }
}
```

Then trigger it for any user:

```php
use App\Models\User;
use App\Notifications\NewMessageReceived;

$user = User::find(1);
$user->notify(new NewMessageReceived($message));
```

---

### 2. Standalone Direct Push Dispatch

You can send push notifications on demand using the `AppNotification` Facade without creating a Laravel Notification class:

```php
use Creatix\AppNotificationManager\Facades\AppNotification;
use Creatix\AppNotificationManager\Messages\Presets\ChatMessagePreset;

// Single device (Platform is auto-detected!)
AppNotification::to($deviceToken)
    ->send(
        ChatMessagePreset::create()
            ->sender('Sarah Connor')
            ->message('Check your terminal now.')
    );

// Explicit iOS dispatch
AppNotification::to($iosToken, platform: 'ios')
    ->send($message);

// Multicast / Multiple devices
AppNotification::to([$token1, $token2, $token3])
    ->send($message);
```

---

### 3. Instagram-Style Social Interaction (`SocialPostPreset`)

```php
use Creatix\AppNotificationManager\Facades\AppNotification;
use Creatix\AppNotificationManager\Messages\Presets\SocialPostPreset;

$notification = SocialPostPreset::create()
    ->actor('alex_ross', 'https://cdn.example.com/avatars/alex.jpg')
    ->activity('New Comment', 'alex_ross commented: "Amazing photo! 🔥"')
    ->mediaThumbnail('https://cdn.example.com/posts/photo_123.jpg')
    ->targetPost(postId: '123')
    ->badge(2);

AppNotification::to($deviceToken)->send($notification);
```

---

### 4. Snapchat-Style Ephemeral Notification (`SnapStoryPreset`)

```php
use Creatix\AppNotificationManager\Facades\AppNotification;
use Creatix\AppNotificationManager\Messages\Presets\SnapStoryPreset;

$notification = SnapStoryPreset::create()
    ->snapFrom('Jessica', avatarUrl: 'https://cdn.example.com/avatars/jessica.jpg')
    ->ttl(seconds: 300); // Expires in 5 minutes

AppNotification::to($deviceToken)->send($notification);
```

---

### 5. Incoming Audio / Video Call (`CallAlertPreset`)

```php
use Creatix\AppNotificationManager\Facades\AppNotification;
use Creatix\AppNotificationManager\Messages\Presets\CallAlertPreset;

$notification = CallAlertPreset::create()
    ->caller('Dr. David Smith', avatarUrl: 'https://cdn.example.com/doctors/david.jpg', type: 'Video')
    ->callSession(sessionId: 'call_live_7721', channelToken: 'agora_token_abc');

AppNotification::to($deviceToken)->send($notification);
```

---

### 6. Background Silent Sync Notification (Wakeup App)

```php
use Creatix\AppNotificationManager\Facades\AppNotification;
use Creatix\AppNotificationManager\Messages\AppNotificationMessage;

$silentMessage = AppNotificationMessage::create()
    ->silent(true)
    ->withData('action', 'SYNC_OFFLINE_MESSAGES')
    ->withData('last_sync_timestamp', now()->timestamp);

AppNotification::to($deviceToken)->send($silentMessage);
```

---

## 🛠️ Mobile Setup Guide for Rich Displays

### Android (FCM Notification Channels)
For heads-up banner notifications (popping up over current screen like WhatsApp):
1. In your Android app (Java/Kotlin/Flutter/React Native), create a notification channel with `IMPORTANCE_HIGH`:
```kotlin
val channel = NotificationChannel(
    "chat_messages",
    "Chat Messages",
    NotificationManager.IMPORTANCE_HIGH
).apply {
    description = "WhatsApp style high priority messages"
    enableLights(true)
    enableVibration(true)
}
val manager = getSystemService(NotificationManager::class.java)
manager.createNotificationChannel(channel)
```

### iOS (Notification Service Extension for Avatars & Media)
iOS requires a **Notification Service Extension** target in Xcode to display rich media previews and avatars:
1. In Xcode: File -> New -> Target -> **Notification Service Extension**.
2. When the push arrives, iOS sees `mutable-content: 1` and runs `didReceive(_:withContentHandler:)`.
3. Read `bestAttemptContent.userInfo["image_url"]` or `"avatar_url"`, download the image to local temporary storage, and attach it as an `UNNotificationAttachment`!

---

## 🧪 Terminal Testing Command

Test your push notification credentials right from the command line:

```bash
php artisan notification:test "your-device-token-here" \
    --platform=ios \
    --title="WhatsApp Test" \
    --body="Hello from Laravel!" \
    --image="https://cdn.example.com/test.jpg"
```

---

## 🧪 Running Package Tests

```bash
composer test
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

