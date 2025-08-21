
npm i @telnyx/webrtc this package use for calling already installed 
calling is already working 
just need audio calling 
make design same like zoom dailer with transcript 
i want hangup mute and disconnect call using function for notificaiton  mention below 


// Attach event listeners
client
  .on('telnyx.ready', () => console.log('ready to call'))
  .on('telnyx.error', () => console.log('error'))
  // Events are fired on both session and call updates
  // ex: when the session has been established
  // ex: when there's an incoming call
  .on('telnyx.notification', (notification) => {
    if (notification.type === 'callUpdate') {
      activeCall = notification.call;
    }
  });


in Auto dailer i want 
Dailpad design 
with disconnect call button, mute button, hold button


Debug log preetyify please 


Noification state is below set according to this 

Value - Description
new -	New call has been created in the client.
trying	- It's attempting to call someone.
requesting - 	The outbound call is being sent to the server.
recovering -	The previous call is recovering after the page refreshes. If the user -  refreshes the page during a call, it will automatically join the latest call.
ringing - 	Someone is attempting to call you.
answering -	You are attempting to answer this inbound call.
early - It receives the media before the call has been answered.
active - Call has become active.
held - Call has been held.
hangup - Call has ended.
destroy - Call has been destroyed.
purge - Call has been purged.

https://developers.telnyx.com/docs/voice/webrtc/js-sdk/classes/call


i want to setup for incoming calls also 

i want add drop down assign user numbers in dailer 

i need toastr type notification for call 
