<?php

/**
 * Telnyx Setup Script
 * 
 * This script helps you configure your Telnyx credentials
 * Run this script to set up your Telnyx calling system
 */

echo "🚀 Telnyx WebRTC Calling Setup\n";
echo "================================\n\n";

echo "📋 Required Credentials:\n";
echo "1. Telnyx API Key\n";
echo "2. Connection ID\n";
echo "3. SIP Username\n";
echo "4. SIP Password\n\n";

echo "🔗 Get your credentials from:\n";
echo "- API Key: https://portal.telnyx.com/#/app/api-keys\n";
echo "- Connection ID: https://portal.telnyx.com/#/app/call-control/connections\n";
echo "- SIP Credentials: From your SIP Connection settings\n\n";

echo "📝 Add these to your .env file:\n";
echo "================================\n";
echo "TELNYX_API_KEY=your_api_key_here\n";
echo "TELNYX_PUBLIC_KEY=your_public_key_here\n";
echo "TELNYX_WEBHOOK_SECRET=your_webhook_secret_here\n";
echo "TELNYX_CONNECTION_ID=your_connection_id_here\n";
echo "TELNYX_SIP_USERNAME=your_sip_username\n";
echo "TELNYX_SIP_PASSWORD=your_sip_password\n";
echo "TELNYX_SIP_DOMAIN=sip.telnyx.com\n";
echo "TELNYX_SIP_PORT=5060\n";
echo "TELNYX_WEBRTC_USERNAME=your_webrtc_username\n";
echo "TELNYX_WEBRTC_PASSWORD=your_webrtc_password\n\n";

echo "🎯 Next Steps:\n";
echo "1. Update your .env file with real credentials\n";
echo "2. Run: php artisan key:generate\n";
echo "3. Run: php artisan migrate\n";
echo "4. Visit: /dialer\n";
echo "5. Click: '🧪 Test Telnyx WebRTC Setup'\n";
echo "6. Make your first call! 📞\n\n";

echo "📞 Example Call Flow:\n";
echo "1. Select your phone number from dropdown\n";
echo "2. Enter destination number (e.g., +1234567890)\n";
echo "3. Click '📞 Make Call'\n";
echo "4. Allow microphone access when prompted\n";
echo "5. Start talking! 🎤\n\n";

echo "🔧 Troubleshooting:\n";
echo "- Check browser console for detailed error messages\n";
echo "- Verify your credentials are correct\n";
echo "- Ensure your Telnyx account is active\n";
echo "- Use a modern browser (Chrome, Firefox, Safari)\n\n";

echo "✅ Ready to make calls with Telnyx WebRTC!\n"; 