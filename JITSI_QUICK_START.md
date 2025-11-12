# Jitsi Video Calling - Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Install Dependencies
```bash
npm install
```

### Step 2: Run Database Migration
```bash
# Make sure your database server is running (MySQL/MariaDB)
# Check your .env file for database credentials
php artisan migrate
```

### Step 3: Build Frontend Assets
```bash
# For development (with hot reload)
npm run dev

# OR for production
npm run build
```

### Step 4: Start Application
```bash
# In a new terminal
php artisan serve
```

### Step 5: Access Video Calling
Navigate to: **http://localhost:8000/video-calls**

## ✨ What's Been Implemented

### Features
- ✅ **Quick Calls** - Instant one-to-one video calls
- ✅ **Scheduled Calls** - Plan calls for later
- ✅ **Conferences** - Multi-participant video meetings
- ✅ **Call History** - Track all your video calls
- ✅ **Participant Management** - See who's in the call
- ✅ **Duration Tracking** - Automatic call length recording

### Integration
- ✅ Works with your existing users
- ✅ Works with your contacts system
- ✅ Consistent UI with your app design
- ✅ Secure authentication required

## 🎯 How to Use

### Starting Your First Call

1. **Go to Video Calls**
   - Click "Video Calls" in the navigation menu
   
2. **Start a Quick Call**
   - Click "Quick Call" button
   - Select a user or contact
   - Click "Start Call"
   - You're instantly in the video call!

3. **In the Call**
   - Camera and microphone controls at the bottom
   - Screen sharing available
   - Chat with participants
   - Host can "End Call for All"
   - Anyone can "Leave Call" individually

### Creating a Conference

1. Click "Create Conference"
2. Name your conference (e.g., "Team Meeting")
3. Optionally invite users
4. Click "Create Conference"
5. Share the room link with participants

## 🔧 Configuration

### Using Public Jitsi (Default)
No configuration needed! Uses `meet.jit.si` (free, public)

### Using 8x8 JaaS (Recommended for Production)
**Hosted video conferencing with JWT authentication**

1. Sign up at [https://jaas.8x8.vc/](https://jaas.8x8.vc/)
2. Get your App ID and API Key
3. Add to `.env` file:

```env
JITSI_DOMAIN=8x8.vc
JITSI_APP_ID=vpaas-magic-cookie-xxxxxxxxxxxxx/yyyyyyyyy
JITSI_JWT_SECRET=your_api_key_here
```

**See `JITSI_JAAS_SETUP_GUIDE.md` for complete setup instructions**

### Using Your Own Self-Hosted Jitsi Server
Edit `.env` file:
```env
JITSI_DOMAIN=meet.yourcompany.com
JITSI_APP_ID=your_app_id
JITSI_JWT_SECRET=your_secret_key
```

### Customization Options
All in `config/services.php` under `'jitsi'`:
- Video quality settings
- UI customization
- Feature toggles (recording, streaming)
- Branding options

## 📊 Database Schema

The `video_calls` table tracks:
- Room names (unique identifiers)
- Host and participants
- Call type (one_to_one, group, conference)
- Status (scheduled, active, ended, cancelled)
- Duration and timestamps
- Participant metadata

## 🛠️ Files Created

### Backend
- ✅ `database/migrations/2025_11_03_120039_create_video_calls_table.php`
- ✅ `app/Models/VideoCall.php`
- ✅ `app/Http/Controllers/VideoCallController.php`
- ✅ `config/services.php` (updated)
- ✅ `routes/web.php` (updated)

### Frontend
- ✅ `resources/js/Pages/VideoCall/Index.vue` - Dashboard
- ✅ `resources/js/Pages/VideoCall/Room.vue` - Video room
- ✅ `resources/js/Layouts/AuthenticatedLayout.vue` (updated navigation)
- ✅ `package.json` (added date-fns dependency)

## 🔐 Security

- ✅ All routes require authentication
- ✅ Authorization checks for host-only actions
- ✅ Unique, secure room name generation
- ✅ Participant verification before joining

## 🐛 Troubleshooting

### "No connection could be made" Error
**Issue**: Database not running
**Solution**: Start your database server (MySQL/MariaDB)

### Video/Audio Not Working
**Issue**: Browser permissions or HTTPS required
**Solution**: 
1. Check browser camera/mic permissions
2. For production, use HTTPS (required by browsers)
3. Try refreshing the page

### Can't See "Video Calls" Menu
**Issue**: Assets not built
**Solution**: Run `npm run dev` or `npm run build`

### Jitsi Not Loading
**Issue**: Script loading issue
**Solution**:
1. Check internet connection
2. Clear browser cache
3. Check browser console for errors

## 📱 Browser Support

Works on:
- ✅ Chrome/Chromium
- ✅ Firefox
- ✅ Safari (macOS/iOS)
- ✅ Edge
- ✅ Mobile browsers (iOS Safari, Chrome Android)

## 🎨 UI Features

### Dashboard
- Recent calls list with status badges
- Quick action buttons
- Participant information display
- Duration tracking
- Join active calls with one click

### Video Room
- Full-screen video interface
- Jitsi Meet embedded with all features:
  - Camera/microphone controls
  - Screen sharing
  - Chat
  - Participant list
  - Raise hand
  - Reactions
  - Backgrounds
  - And much more!

## 🚀 Next Steps

### Basic Usage
1. ✅ Setup complete - Start making calls!
2. Try a quick call with a team member
3. Create a conference for a meeting
4. Review call history

### Advanced Setup (Optional)
1. Self-host Jitsi for better performance
2. Enable call recording
3. Add branding (logo, colors)
4. Integrate with calendar
5. Add email invitations

## 📚 Documentation

- **Full Guide**: See `VIDEO_CALLING_IMPLEMENTATION.md`
- **Jitsi Docs**: https://jitsi.github.io/handbook/
- **Self-Host Guide**: https://jitsi.github.io/handbook/docs/devops-guide/

## ✅ Testing Checklist

Test these features:
- [ ] Navigate to /video-calls
- [ ] Create a quick call
- [ ] Join the call (opens video interface)
- [ ] Test camera/microphone
- [ ] Try screen sharing
- [ ] Leave the call
- [ ] Check call appears in history
- [ ] Create a scheduled call
- [ ] Create a conference with multiple users

## 💡 Tips

1. **For Testing**: You can join the same room from multiple browser windows/devices
2. **Room Links**: Share `/video-calls/join/{roomName}` with participants
3. **Mobile**: Fully works on mobile browsers!
4. **Quality**: Adjust video quality in config if needed
5. **Performance**: For large meetings (10+ people), consider self-hosting Jitsi

## 🎉 You're All Set!

Your video calling system is ready to use! Navigate to `/video-calls` and start your first call.

**Need help?** Check `VIDEO_CALLING_IMPLEMENTATION.md` for detailed documentation.

