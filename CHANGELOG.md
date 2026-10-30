# Changelog

All notable changes to `creatix/app-notification-manager` will be documented in this file.

## [1.0.0] - 2026-10-30

### Added
- Initial release of **App Notification Manager** for Laravel.
- Support for **Android FCM HTTP v1 API** using Google OAuth2 Service Account authentication.
- Support for **iOS APNs HTTP/2** using modern `.p8` ES256 JWT authentication.
- High-priority heads-up notification channel presets:
  - `ChatMessagePreset` (WhatsApp style with sender avatars, grouped conversation threads, and quick reply action buttons).
  - `SocialPostPreset` (Instagram style with media thumbnails, deep-links, and activity actions).
  - `SnapStoryPreset` (Snapchat style with ephemeral TTL and background payload).
  - `CallAlertPreset` (VoIP/incoming call style with ringtones and high priority).
- Unified `AppNotification` facade and `AppNotificationChannel` for Laravel's `Notification::send()`.
- Intelligent automatic platform detection from device token signatures.
- Invalid token detection event (`InvalidTokenDetected`) and auto-pruning migration.
- Artisan CLI command `php artisan notification:test` for testing mobile push setups directly from the terminal.

