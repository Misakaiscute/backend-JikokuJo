<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reverb Tester</title>

    <!-- 🔥 THIS IS THE IMPORTANT LINE -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://js.pusher.com/8.4.0-rc2/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1/dist/echo.iife.js"></script>

    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        #log { background: #f8f9fa; padding: 15px; height: 400px; overflow-y: auto; border: 1px solid #ddd; }
    </style>
</head>
<body>

    <h1>Laravel Reverb WebSocket Tester</h1>

    <div>
        <label><strong>Trip ID:</strong></label><br>
        <input type="text" id="tripId" value="D095812028" style="width: 300px; padding: 8px;">
    </div>

    <br>
    <button onclick="connect()" style="padding:10px 20px; background:blue; color:white; border:none; cursor:pointer;">
        Connect & Subscribe
    </button>

    <button onclick="disconnect()" style="padding:10px 20px; background:red; color:white; border:none; cursor:pointer;">
        Disconnect
    </button>

    <hr>
    <h3>Log</h3>
    <pre id="log"></pre>

    <script>
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: '{{ env("VITE_REVERB_APP_KEY") }}',
        wsHost: window.location.hostname,   // localhost
        wsPort: 8080,                       // ← THIS WAS THE PROBLEM (change from 8000 to 8080)
        forceTLS: false,
        enabledTransports: ['ws'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        }
    });

    let channel = null;

    function log(message) {
        const logEl = document.getElementById('log');
        logEl.textContent += new Date().toLocaleTimeString() + ' | ' + message + '\n';
        logEl.scrollTop = logEl.scrollHeight;
    }

    function connect() {
        const tripId = document.getElementById('tripId').value.trim();
        if (!tripId) return alert('Please enter Trip ID');

        log('Trying to join presence:trip.' + tripId);

        channel = Echo.join('trip.' + tripId)
            .here(users => log('✅ Here: ' + users.length + ' users online'))
            .joining(user => log('👤 Joining: ' + (user.name || user.id)))
            .leaving(user => log('👋 Leaving: ' + (user.name || user.id)))
            .listen('.vehicle.position-updated', data => {
                log('📍 Position updated: ' + JSON.stringify(data));
            })
            .error(err => {
                log('❌ WebSocket Error: ' + JSON.stringify(err));
                console.error(err);
            });
    }

    function disconnect() {
        if (channel) {
            channel.unsubscribe();
            channel = null;
            log('Disconnected');
        }
    }
</script>

</body>
</html>