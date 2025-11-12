# Jitsi as a Service (JaaS) Setup Guide

## Overview
This guide shows you how to set up secure video calling using **8x8's Jitsi as a Service (JaaS)** with JWT authentication. The implementation follows the [official 8x8 JaaS demo](https://github.com/8x8/jaas_demo/blob/main/jaas-jwt-samples/php/jaas-jwt-firebase.php).

## What is JaaS?

**Jitsi as a Service (JaaS)** by 8x8 is a cloud-hosted, enterprise-grade video conferencing platform that eliminates the need to self-host Jitsi servers. It provides:

- ✅ **No Server Maintenance** - 8x8 handles all infrastructure
- ✅ **Global CDN** - Fast video delivery worldwide
- ✅ **JWT Authentication** - Secure, token-based access control
- ✅ **99.9% Uptime SLA** - Enterprise reliability
- ✅ **Scalability** - Automatically handles any number of participants
- ✅ **Pay-as-you-go** - Only pay for what you use

## Quick Start (5 Minutes)

### Step 1: Sign Up for JaaS

1. Go to [https://jaas.8x8.vc/](https://jaas.8x8.vc/)
2. Click **"Start Free Trial"** or **"Sign Up"**
3. Complete registration
4. You'll get a **free trial** with included minutes

### Step 2: Get Your Credentials

After signing up, you'll receive:

1. **App ID** - Format: `vpaas-magic-cookie-xxxxxxxxxxxxx/yyyyyyyyy`
2. **API Key** - Your JWT secret (long string)

Find these in your JaaS dashboard under **"API Keys"** or **"Credentials"**.

### Step 3: Configure Your Laravel App

Add to your `.env` file:

```env
# 8x8 JaaS Configuration
JITSI_DOMAIN=8x8.vc
JITSI_APP_ID=vpaas-magic-cookie-xxxxxxxxxxxxx/yyyyyyyyy
JITSI_JWT_SECRET=your_api_key_here
```

**Example:**
```env
JITSI_DOMAIN=8x8.vc
JITSI_APP_ID=vpaas-magic-cookie-1234567890ab/MyAppName
JITSI_JWT_SECRET=AbCdEf123456789...
```

### Step 4: Clear Config Cache

```bash
php artisan config:clear
```

### Step 5: Test It!

1. Navigate to `/video-calls`
2. Start a quick call
3. Check browser console - you should see:
   ```
   Jitsi JWT generated successfully
   ```
4. Video call opens with JWT authentication! 🎉

## How It Works

### JWT Token Generation

Based on the [official 8x8 implementation](https://github.com/8x8/jaas_demo/blob/main/jaas-jwt-samples/php/jaas-jwt-firebase.php), our service generates tokens with:

```php
$payload = [
    'aud' => 'jitsi',
    'iss' => 'chat',                    // For JaaS
    'sub' => $appId,                     // Your vpaas-magic-cookie-xxx/yyy
    'room' => '*',                       // Wildcard for all rooms
    'iat' => time(),                     // Issued at
    'exp' => time() + 7200,             // Expires in 2 hours
    'context' => [
        'user' => [
            'id' => 'user_123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'moderator' => true,         // Host privileges
        ],
        'features' => [
            'recording' => true,         // Can record
            'livestreaming' => false,
        ],
    ],
];

$token = JWT::encode($payload, $apiKey, 'HS256');
```

### Automatic JaaS Detection

The service automatically detects if you're using JaaS:

```php
$isJaaS = str_contains($appId, 'vpaas-magic-cookie') || 
          str_contains($domain, '8x8.vc');
```

When JaaS is detected:
- Uses `'iss' => 'chat'` (required by JaaS)
- Uses `'sub' => $appId` (your vpaas credentials)
- Logs JaaS-specific information

## Configuration Options

### Environment Variables

```env
# Required for JaaS
JITSI_DOMAIN=8x8.vc
JITSI_APP_ID=vpaas-magic-cookie-xxxxx/yyyyy
JITSI_JWT_SECRET=your_api_key

# Optional: Custom domain if you have a branded JaaS
# JITSI_DOMAIN=meet.yourcompany.8x8.vc
```

### PHP Configuration

In `config/services.php`:

```php
'jitsi' => [
    'domain' => env('JITSI_DOMAIN', 'meet.jit.si'),
    'app_id' => env('JITSI_APP_ID', null),
    'jwt_secret' => env('JITSI_JWT_SECRET', null),
    
    'config' => [
        // Your Jitsi configuration options
        'startWithAudioMuted' => false,
        'startWithVideoMuted' => false,
        // ... more options
    ],
],
```

## Features with JaaS

### ✅ What You Get

1. **Secure Authentication**
   - Only users with valid JWT tokens can join
   - Tokens expire automatically (default: 2 hours)
   - No public access to rooms

2. **Role-Based Access**
   - **Hosts** (moderators): Can end call, kick users, enable recording
   - **Participants**: Can join, use video/audio, share screen

3. **Enterprise Features**
   - Recording (if enabled)
   - Live streaming (if enabled)
   - Custom branding (enterprise plans)
   - Analytics and usage reports

4. **Global Infrastructure**
   - Worldwide CDN
   - Automatic server selection
   - Low latency
   - High availability

## Testing JWT Authentication

### 1. Check Token Generation

When a user joins a call, check your Laravel logs:

```bash
tail -f storage/logs/laravel.log
```

You should see:
```
[2025-11-03 12:00:00] local.INFO: Jitsi JWT generated successfully
{
    "app_id": "vpaas-magic-cookie-xxxxx/yyyyy",
    "user": "John Doe",
    "room": "room-abc123",
    "is_jaas": true
}
```

### 2. Verify in Browser

Open browser console during a video call:

```javascript
// You should NOT see errors like:
// "Conference destroyed" or "Authentication failed"

// You SHOULD see:
// "Conference joined successfully"
```

### 3. Test Token Decode

Visit [https://jwt.io/](https://jwt.io/) and paste your token to inspect:

```json
{
  "aud": "jitsi",
  "iss": "chat",
  "sub": "vpaas-magic-cookie-xxxxx/yyyyy",
  "room": "*",
  "context": {
    "user": {
      "id": "123",
      "name": "John Doe",
      "email": "john@example.com",
      "moderator": true
    }
  }
}
```

## Pricing

### Free Trial
- Includes **25 hours** of video per month
- Unlimited participants
- All features enabled
- No credit card required

### Pay-as-you-go
After trial:
- **$0.0035 per participant-minute** (~$0.21/hour/participant)
- Example: 10-person, 1-hour meeting = $2.10
- Monthly billing
- No minimums or commitments

### Enterprise Plans
- Custom pricing
- Dedicated support
- Custom branding
- Advanced analytics
- SLA guarantees

Visit [jaas.8x8.vc/pricing](https://jaas.8x8.vc/pricing) for current rates.

## Comparison: Public Jitsi vs JaaS

| Feature | Public Jitsi (meet.jit.si) | JaaS (8x8.vc) |
|---------|---------------------------|---------------|
| **Cost** | Free | Free trial, then pay-per-use |
| **Authentication** | ❌ None | ✅ JWT required |
| **Privacy** | ⚠️ Public servers | ✅ Private, secure |
| **Reliability** | ⚠️ Best effort | ✅ 99.9% SLA |
| **Support** | ❌ Community only | ✅ Enterprise support |
| **Recording** | ❌ Limited | ✅ Built-in |
| **Branding** | ❌ Jitsi branded | ✅ Custom branding |
| **Best For** | Development, testing | Production, business |

## Troubleshooting

### "Conference destroyed" Error

**Problem**: Token is invalid or expired

**Solution**:
1. Verify your App ID format: `vpaas-magic-cookie-xxxxx/yyyyy`
2. Check API Key is correct (no extra spaces)
3. Clear config cache: `php artisan config:clear`
4. Check Laravel logs for JWT generation errors

### "Authentication failed"

**Problem**: Token structure is incorrect

**Solution**:
1. Our implementation follows the [official 8x8 spec](https://github.com/8x8/jaas_demo)
2. Verify payload has `'iss' => 'chat'` for JaaS
3. Check `'sub'` field matches your App ID exactly

### Token Expired During Call

**Problem**: Long meetings exceed 2-hour token expiration

**Solution**:
Edit `app/Services/JitsiService.php`:
```php
'exp' => $now + ($options['exp_seconds'] ?? 28800), // 8 hours
```

### Can't Find App ID

**Problem**: Don't know where to get credentials

**Solution**:
1. Log in to [jaas.8x8.vc](https://jaas.8x8.vc/)
2. Go to **"Developers"** → **"API Keys"**
3. Copy App ID and API Key
4. Or click **"Create New Key"** if none exist

## Self-Hosted Jitsi vs JaaS

You can also use this implementation with **self-hosted Jitsi**:

```env
# Self-hosted Jitsi
JITSI_DOMAIN=meet.yourcompany.com
JITSI_APP_ID=your_app_id
JITSI_JWT_SECRET=your_secret_key
```

The service automatically detects non-JaaS domains and adjusts:
- Uses `'iss' => $appId` (instead of 'chat')
- Compatible with prosody JWT module
- Full control over infrastructure

## Security Best Practices

1. **Keep API Keys Secret**
   - Never commit `.env` file to git
   - Use environment variables in production
   - Rotate keys periodically

2. **Token Expiration**
   - Default 2 hours is recommended
   - Longer tokens = more security risk
   - Shorter tokens = better for sensitive calls

3. **HTTPS Required**
   - Always use HTTPS in production
   - Browsers require HTTPS for camera/mic access
   - JaaS enforces HTTPS

4. **Monitoring**
   - Check Laravel logs regularly
   - Monitor JaaS usage dashboard
   - Set up alerts for failures

## Advanced Features

### Custom Token Lifetime

```php
// In your controller or service
$token = $jitsiService->generateJWT($roomName, $userName, $userEmail, [
    'exp_seconds' => 3600, // 1 hour
]);
```

### Enable Recording for Moderators

```php
$token = $jitsiService->generateModeratorJWT($roomName, $userName, $userEmail, $userId);
// Recording is automatically enabled for moderators
```

### Custom User Avatar

```php
$token = $jitsiService->generateJWT($roomName, $userName, $userEmail, [
    'avatar' => $user->avatar_url,
]);
```

## Resources

- **8x8 JaaS Official**: [https://jaas.8x8.vc/](https://jaas.8x8.vc/)
- **JaaS Documentation**: [https://developer.8x8.com/jaas](https://developer.8x8.com/jaas)
- **Official Demo Code**: [https://github.com/8x8/jaas_demo](https://github.com/8x8/jaas_demo)
- **JWT Debugger**: [https://jwt.io/](https://jwt.io/)
- **Support**: support@8x8.com

## Summary

✅ **Your system now supports:**
- 8x8 JaaS with JWT authentication
- Self-hosted Jitsi with JWT
- Public Jitsi (no auth) for development
- Automatic detection of which mode to use
- Role-based access control
- Enterprise-grade security

**To enable JaaS:** Just add your credentials to `.env` and you're done! The system automatically handles everything else.

🎉 **You're ready for production video calling with 8x8 JaaS!**











