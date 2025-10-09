<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public transcription channels (no authentication required)
Broadcast::channel('call-transcription', function () {
    return true; // Public channel - anyone can listen
});

Broadcast::channel('call-transcription.{callControlId}', function ($user, $callControlId) {
    return true; // Public channel for specific call transcription
});

// Public call status channels (no authentication required)
Broadcast::channel('call-status', function () {
    return true; // Public channel - anyone can listen
});

Broadcast::channel('call-status.{callSessionId}', function ($user, $callSessionId) {
    return true; // Public channel for specific call status
});