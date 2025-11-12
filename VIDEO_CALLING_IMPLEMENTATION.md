# Jitsi Video Calling Implementation Guide

## Overview
This guide provides a complete implementation of video calling and conferencing features using Jitsi Meet in your Laravel + Inertia.js application.

## Features Implemented

### 1. Video Call Types
- **One-to-One Calls**: Direct video calls between two users
- **Group Calls**: Video calls with multiple participants
- **Conference Calls**: Scheduled conferences with named rooms and participant management

### 2. Core Functionality
- ✅ Create and schedule video calls
- ✅ Quick call (instant start)
- ✅ Join existing calls via room name
- ✅ Conference management
- ✅ Participant tracking
- ✅ Call history and duration tracking
- ✅ Host controls (end call for all)
- ✅ Individual participant controls (leave call)

### 3. Integration with Existing System
- Integrated with your user management
- Works with your contacts system
- Uses your authentication layer
- Consistent UI with your existing design

## Files Created/Modified

### Backend Files

#### 1. Database Migration
```
database/migrations/2025_11_03_120039_create_video_calls_table.php
```
Creates the `video_calls` table with fields for:
- Room name, host, participants, contacts
- Call status (scheduled, active, ended, cancelled)
- Call type (one_to_one, group, conference)
- Timestamps and duration tracking
- Metadata for additional information

#### 2. Model
```
app/Models/VideoCall.php
```
Features:
- Status and type constants
- Relationships with User and Contact models
- Helper methods for call management
- Participant management
- Room name generation

#### 3. Controller
```
app/Http/Controllers/VideoCallController.php
```
Endpoints:
- `index()` - Video calls dashboard
- `createRoom()` - Create scheduled call
- `joinRoom()` - Join a call room
- `startQuickCall()` - Instant call start
- `createConference()` - Create conference
- `endCall()` - End call (host only)
- `updateStatus()` - Update call status
- `getCallDetails()` - Get call information
- `history()` - Call history
- `destroy()` - Delete call record

#### 4. Routes
```
routes/web.php
```
All routes are protected with authentication middleware.

#### 5. Configuration
```
config/services.php
```
Jitsi configuration including:
- Domain (default: meet.jit.si)
- JWT settings for private servers
- UI customization
- Feature toggles
- Interface configuration

### Frontend Files

#### 1. Video Calls Dashboard
```
resources/js/Pages/VideoCall/Index.vue
```
Features:
- Recent calls list
- Quick call modal
- Schedule call modal
- Conference creation modal
- Call history with status badges
- Participant information
- Duration display

#### 2. Video Call Room
```
resources/js/Pages/VideoCall/Room.vue
```
Features:
- Jitsi Meet integration
- Real-time participant tracking
- Call controls
- Loading states
- Call ended state
- Host-specific controls
- Clean, modern UI

#### 3. Navigation Update
```
resources/js/Layouts/AuthenticatedLayout.vue
```
Added "Video Calls" link to main navigation.

#### 4. Package Dependencies
```
package.json
```
Added `date-fns` for date formatting.

## Setup Instructions

### 1. Install Dependencies

```bash
# Install JavaScript dependencies
npm install

# Rebuild assets
npm run build
# OR for development
npm run dev
```

### 2. Run Database Migration

```bash
# Start your database server (MySQL/MariaDB)
# Then run:
php artisan migrate
```

### 3. Configure Jitsi (Optional)

The system uses the public Jitsi Meet server (meet.jit.si) by default. To use your own Jitsi server or customize settings:

Edit your `.env` file:
```env
JITSI_DOMAIN=meet.jit.si
# Optional: For private Jitsi servers with JWT
JITSI_APP_ID=your_app_id
JITSI_JWT_SECRET=your_jwt_secret

# Optional: Feature toggles
JITSI_FILE_RECORDING_ENABLED=false
JITSI_LIVE_STREAMING_ENABLED=false
```

### 4. Update User Model

Add the video calls relationship to `app/Models/User.php`:

```php
public function videoCallsAsHost(): HasMany
{
    return $this->hasMany(VideoCall::class, 'host_user_id');
}

public function videoCallsAsParticipant(): HasMany
{
    return $this->hasMany(VideoCall::class, 'participant_user_id');
}
```

### 5. Start Your Application

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Queue worker (if using)
php artisan queue:work

# Terminal 3: Vite dev server
npm run dev
```

## Usage Guide

### For Users

#### Starting a Quick Call
1. Navigate to "Video Calls" in the navigation menu
2. Click "Quick Call"
3. Select a user or contact
4. Click "Start Call"
5. You'll be instantly connected

#### Scheduling a Call
1. Click "Schedule Call"
2. Choose call type (One-to-One, Group, or Conference)
3. Select participant
4. Click "Create Room"
5. Share the room link with participants

#### Creating a Conference
1. Click "Create Conference"
2. Enter conference name
3. Optionally select participants to invite
4. Click "Create Conference"
5. Share the join link

#### Joining a Call
- From the dashboard: Click "Join" on an active/scheduled call
- From a link: Navigate to `/video-calls/join/{roomName}`

### For Developers

#### Creating a Call Programmatically

```php
use App\Models\VideoCall;

$call = VideoCall::create([
    'room_name' => VideoCall::generateRoomName(),
    'host_user_id' => auth()->id(),
    'participant_user_id' => $participantId,
    'type' => VideoCall::TYPE_ONE_TO_ONE,
    'status' => VideoCall::STATUS_SCHEDULED,
]);
```

#### Tracking Call Events

```javascript
// In your Vue component
const handleCallEvent = async (event) => {
    await axios.post(`/api/video-calls/${callId}/status`, {
        action: 'join', // or 'leave', 'start', 'end'
        participant_data: {
            id: userId,
            name: userName,
            // ... other data
        }
    });
};
```

## API Endpoints

### Public Endpoints (Authenticated)

#### GET `/video-calls`
Returns the video calls dashboard

#### GET `/video-calls/join/{roomName}`
Join a specific video call room

#### POST `/api/video-calls/create-room`
Create a new scheduled call
```json
{
    "type": "one_to_one|group|conference",
    "participant_user_id": 123, // optional
    "contact_id": 456, // optional
    "room_name": "custom-name" // optional
}
```

#### POST `/api/video-calls/quick-call`
Start an instant call
```json
{
    "participant_user_id": 123, // or
    "contact_id": 456
}
```

#### POST `/api/video-calls/create-conference`
Create a conference room
```json
{
    "name": "Team Meeting",
    "participants": [1, 2, 3] // user IDs, optional
}
```

#### POST `/api/video-calls/{id}/end`
End a call (host only)

#### POST `/api/video-calls/{id}/status`
Update call status
```json
{
    "action": "join|leave|start|end",
    "participant_data": {
        "id": "user@example.com",
        "name": "John Doe"
    }
}
```

#### GET `/api/video-calls/{id}`
Get call details

#### GET `/api/video-calls/history`
Get user's call history (paginated)

#### DELETE `/api/video-calls/{id}`
Delete a call record (host only)

## Customization

### Jitsi Configuration

Edit `config/services.php` under the `jitsi` key:

```php
'jitsi' => [
    'config' => [
        'startWithAudioMuted' => false,
        'startWithVideoMuted' => false,
        'resolution' => 720,
        // ... more options
    ],
    'interface_config' => [
        'SHOW_JITSI_WATERMARK' => false,
        'APP_NAME' => 'Your App Name',
        // ... more options
    ],
]
```

### UI Customization

The Vue components use Tailwind CSS and are fully customizable:
- Edit `resources/js/Pages/VideoCall/Index.vue` for the dashboard
- Edit `resources/js/Pages/VideoCall/Room.vue` for the video interface

### Adding Features

#### Recording Support
Jitsi supports call recording. To enable:
1. Set up a Jitsi recorder (Jibri)
2. Enable in config:
```env
JITSI_FILE_RECORDING_ENABLED=true
```

#### Live Streaming
For YouTube/Facebook live streaming:
```env
JITSI_LIVE_STREAMING_ENABLED=true
```

#### Custom Branding
Add your logo and colors in `config/services.php`:
```php
'interface_config' => [
    'BRAND_WATERMARK_LINK' => 'https://yoursite.com/logo.png',
    'DEFAULT_LOGO_URL' => 'https://yoursite.com/logo.png',
]
```

## Security Considerations

1. **Authentication**: All routes are protected with Laravel's auth middleware
2. **Authorization**: Host-only actions (end call, delete) are verified
3. **Private Servers**: For sensitive calls, use a private Jitsi server with JWT
4. **Room Names**: Generated with cryptographically secure random strings
5. **Participant Verification**: Users must be authenticated to join calls

## Troubleshooting

### Video/Audio Not Working
- Ensure HTTPS is enabled (required by browsers for media access)
- Check browser permissions for camera/microphone
- Verify Jitsi domain is accessible

### Database Connection Error
```bash
# Make sure database server is running
# Check .env file database credentials
php artisan config:clear
php artisan migrate
```

### Jitsi Not Loading
- Check browser console for errors
- Verify Jitsi domain is accessible
- Clear browser cache
- Check Content Security Policy headers

### Participant Can't Join
- Verify room name is correct
- Check user authentication
- Review call status (must be 'scheduled' or 'active')
- Check authorization logic in `canUserJoin()`

## Performance Optimization

### For Large Conferences
1. Use a dedicated Jitsi server (not meet.jit.si)
2. Configure video constraints:
```php
'constraints' => [
    'video' => [
        'height' => ['ideal' => 360, 'max' => 720]
    ]
]
```
3. Enable simulcast (default)
4. Consider video mute by default for large groups

### Database Indexing
The migration includes indexes on:
- `room_name` (for quick lookups)
- `host_user_id` (for user's calls)
- `status` (for active calls filtering)

## Future Enhancements

Potential additions:
- [ ] Screen sharing indicator
- [ ] Chat integration
- [ ] Call recording storage
- [ ] Calendar integration
- [ ] Email invitations
- [ ] Waiting room feature
- [ ] Virtual backgrounds
- [ ] Breakout rooms
- [ ] Call analytics and reporting
- [ ] Integration with your SMS/voice system

## Support

### Jitsi Meet Documentation
- Official Docs: https://jitsi.github.io/handbook/
- API Reference: https://jitsi.github.io/handbook/docs/dev-guide/dev-guide-iframe

### Self-Hosting Jitsi
For production, consider self-hosting:
- Installation Guide: https://jitsi.github.io/handbook/docs/devops-guide/devops-guide-quickstart
- Docker Setup: https://jitsi.github.io/handbook/docs/devops-guide/devops-guide-docker

## Conclusion

You now have a complete video calling and conferencing system integrated into your Laravel application! The system supports:
- ✅ One-to-one video calls
- ✅ Group video calls
- ✅ Conference rooms with multiple participants
- ✅ Call history and tracking
- ✅ Full Jitsi Meet feature set
- ✅ Modern, responsive UI
- ✅ Secure, authenticated access

Navigate to `/video-calls` in your application to start using the system!

