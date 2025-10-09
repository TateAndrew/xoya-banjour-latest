import Echo from 'laravel-echo';

// Dynamic import for Pusher to handle Vite build properly
import('pusher-js')
    .then((module) => {
        // Handle different export formats (ESM default export vs CommonJS)
        const Pusher = module.default || module;
        
        // Verify we have the constructor
        if (typeof Pusher !== 'function') {
            console.error('Pusher module structure:', module);
            throw new Error('Pusher is not a constructor function');
        }
        
        // Make Pusher available globally
        window.Pusher = Pusher;
        
        // Initialize Echo with explicit Pusher client
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            forceTLS: true,
            client: new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
                forceTLS: true
            })
        });
        
        console.log('✅ Pusher and Echo initialized successfully');
    })
    .catch((error) => {
        console.error('❌ Failed to initialize Pusher/Echo:', error);
    });
