# Professional Audio Dialer

A modern, Zoom-like audio dialer built with Laravel, Vue.js, and Telnyx WebRTC for professional calling with transcript features.

## Features

### 🎯 Core Functionality
- **Audio Calling**: High-quality audio calls using WebRTC technology
- **SIP Integration**: Connect to Telnyx SIP trunks for reliable calling
- **Call Controls**: Mute, hangup, and disconnect functionality
- **Real-time Status**: Live call status updates and notifications

### 🎨 Modern UI/UX
- **Zoom-like Design**: Clean, professional interface similar to popular video conferencing apps
- **Responsive Layout**: Works seamlessly on desktop and mobile devices
- **Visual Feedback**: Color-coded status indicators and animated elements
- **Intuitive Controls**: Easy-to-use call management interface

### 📝 Transcript & History
- **Live Transcripts**: Real-time call activity logging
- **Call History**: Comprehensive call logs with filtering and search
- **Export Functionality**: Download call history and transcripts
- **Detailed Analytics**: Call duration, status, and metadata tracking

### 🔔 Notifications
- **Browser Notifications**: Desktop notifications for call events
- **Audio Alerts**: Sound notifications for different call states
- **Status Updates**: Real-time call state changes
- **Error Handling**: Comprehensive error reporting and logging

## Technical Architecture

### Frontend (Vue.js 3)
- **Component-based**: Modular, reusable components
- **Reactive State**: Vue 3 Composition API for state management
- **Tailwind CSS**: Utility-first CSS framework for styling
- **WebRTC Integration**: Direct browser media API integration

### Backend (Laravel)
- **RESTful API**: Clean API endpoints for call management
- **Telnyx Integration**: Official Telnyx PHP SDK
- **Database Models**: Eloquent ORM for data persistence
- **Webhook Handling**: Real-time event processing

### WebRTC Features
- **Audio Streaming**: Local and remote audio stream management
- **Call Control**: Mute, unmute, and call termination
- **Connection Management**: SIP trunk connection handling
- **Error Recovery**: Robust error handling and reconnection

## Installation & Setup

### Prerequisites
- PHP 8.1+
- Laravel 10+
- Node.js 16+
- Telnyx account with WebRTC credentials

### 1. Install Dependencies
```bash
composer install
npm install
```

### 2. Configure Environment
```env
TELNYX_API_KEY=your_telnyx_api_key
TELNYX_WEBRTC_LOGIN=your_webrtc_login
TELNYX_WEBRTC_PASSWORD=your_webrtc_password
```

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Build Assets
```bash
npm run build
```

## Usage

### Making Calls
1. **Initialize WebRTC**: Click "Initialize WebRTC" to establish connection
2. **Select Connection**: Choose your SIP trunk from the dropdown
3. **Enter Numbers**: Input from and to phone numbers
4. **Start Call**: Click "Start Call" to initiate the connection

### Call Controls
- **🎤 Mute/Unmute**: Toggle microphone on/off
- **📴 End Call**: Terminate the current call
- **🔌 Disconnect**: Disconnect from WebRTC session

### Call History
- **View Logs**: Access comprehensive call history
- **Filter Results**: Filter by date, status, or phone number
- **Export Data**: Download call logs and transcripts
- **Search**: Quick search through call records

## API Endpoints

### Call Management
- `GET /api/telnyx/connections` - List available SIP connections
- `POST /api/telnyx/call` - Initiate a new call
- `POST /api/telnyx/end-call` - Terminate an active call

### Call History
- `GET /api/calls/history` - Retrieve user's call history
- `GET /dialer/history` - Call history web interface

## File Structure

```
resources/js/Pages/Dialer/
├── Index.vue          # Main dialer interface
└── History.vue        # Call history page

app/
├── Http/Controllers/
│   └── TelnyxController.php    # Telnyx API integration
├── Models/
│   └── Call.php               # Call data model
└── Services/
    └── TelynxService.php      # Telnyx service layer
```

## Customization

### Styling
- Modify `tailwind.config.js` for theme changes
- Update component styles in individual Vue files
- Customize color schemes and layouts

### Functionality
- Extend call controls in `Index.vue`
- Add new transcript types in the transcript system
- Implement additional notification methods

### Integration
- Add new Telnyx features in `TelnyxController`
- Extend call metadata in the `Call` model
- Implement additional WebRTC capabilities

## Troubleshooting

### Common Issues
1. **WebRTC Not Initializing**: Check Telnyx credentials and network connectivity
2. **Audio Issues**: Verify microphone permissions and device selection
3. **Call Failures**: Check SIP trunk configuration and phone number format
4. **Performance**: Ensure adequate bandwidth and system resources

### Debug Mode
- Enable debug logging in the WebRTC client
- Check browser console for detailed error messages
- Review Laravel logs for backend issues

## Security Considerations

- **Authentication**: All routes require user authentication
- **CSRF Protection**: Laravel CSRF tokens for form submissions
- **Input Validation**: Server-side validation for all inputs
- **API Rate Limiting**: Implement rate limiting for production use

## Performance Optimization

- **Asset Bundling**: Vite for fast development and optimized builds
- **Lazy Loading**: Components loaded on demand
- **Caching**: Implement Redis caching for call data
- **Database Indexing**: Optimize database queries for call history

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For technical support or questions:
- Check the troubleshooting section
- Review Telnyx documentation
- Open an issue on GitHub

## Implementation Details

### SIP Connection Management
- **Database-Driven**: SIP connections are loaded from the local database instead of Telnyx API
- **User-Specific**: Each user sees only their own SIP trunk connections
- **Active Status**: Only active SIP trunks are displayed in the connection dropdown
- **Real-time Updates**: Connection status and phone number assignments are refreshed dynamically

### Phone Number Assignment
- **Connection-Based**: Phone numbers are populated from the selected SIP connection
- **Assignment Types**: Supports primary, secondary, and backup phone number assignments
- **Active Filtering**: Only active phone number assignments are displayed
- **Auto-Selection**: First available phone number is automatically selected when connection changes

### WebRTC Integration
- **Credential Management**: WebRTC client uses credentials stored in the selected SIP connection
- **Fallback Support**: Graceful fallback to default credentials if connection credentials are unavailable
- **Connection Validation**: WebRTC initialization requires a valid SIP connection selection
- **Dynamic Switching**: WebRTC client can be reinitialized when switching between connections

---

**Built with ❤️ using Laravel, Vue.js, and Telnyx WebRTC**