<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider App (Real GPS)</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!-- Tailwind CSS for minimal styling -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded shadow-md w-full max-w-md text-center">
        <h2 class="text-2xl font-bold mb-4">🛵 Rider App</h2>
        
        <!-- Login Form -->
        <div id="loginSection">
            <input type="email" id="email" placeholder="Rider Email" class="border p-2 w-full mb-2 rounded" value="rider@gmail.com">
            <input type="password" id="password" placeholder="Password" class="border p-2 w-full mb-2 rounded" value="password">
            <button onclick="login()" class="bg-blue-500 text-white px-4 py-2 rounded w-full">Login & Start</button>
        </div>

        <!-- Tracking Status -->
        <div id="trackingSection" class="hidden">
            <p class="text-green-600 font-bold text-lg animate-pulse">🟢 Live Tracking ON</p>
            <p class="text-gray-600 text-sm mt-2">Your location is being shared.</p>
            
            <div class="mt-4 text-left bg-gray-50 p-3 rounded text-xs">
                <p>Latitude: <span id="dispLat">...</span></p>
                <p>Longitude: <span id="dispLng">...</span></p>
                <p>Last Update: <span id="lastUpd">Never</span></p>
            </div>
        </div>

        <div id="errorMsg" class="text-red-500 mt-2 text-sm"></div>
    </div>

    <script>
        let token = null;
        let watchId = null;

        // 1. Login Function
        async function login() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorMsg = document.getElementById('errorMsg');

            try {
                const res = await axios.post('/api/login', { email, password });
                token = res.data.token;
                
                // UI Change
                document.getElementById('loginSection').classList.add('hidden');
                document.getElementById('trackingSection').classList.remove('hidden');
                
                // Start GPS
                startGPS();

            } catch (err) {
                errorMsg.innerText = "Login Failed! Check email/pass.";
                console.error(err);
            }
        }

        // 2. Real GPS Tracking
        function startGPS() {
            if (navigator.geolocation) {
                // watchPosition detects movement
                watchId = navigator.geolocation.watchPosition(sendLocation, handleError, {
                    enableHighAccuracy: true, // Use GPS for better result
                    timeout: 5000,
                    maximumAge: 0
                });
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        // 3. Send Data to Server
        async function sendLocation(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            // UI Update
            document.getElementById('dispLat').innerText = lat.toFixed(6);
            document.getElementById('dispLng').innerText = lng.toFixed(6);
            document.getElementById('lastUpd').innerText = new Date().toLocaleTimeString();

            try {
                await axios.post('/api/update-location', {
                    lat: lat,
                    lng: lng
                }, {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                console.log("Location sent!");
            } catch (err) {
                console.error("Failed to send location", err);
            }
        }

        function handleError(error) {
            let msg = "";
            switch(error.code) {
                case error.PERMISSION_DENIED: msg = "User denied Geolocation request."; break;
                case error.POSITION_UNAVAILABLE: msg = "Location info is unavailable."; break;
                case error.TIMEOUT: msg = "The request to get location timed out."; break;
                default: msg = "An unknown error occurred."; break;
            }
            document.getElementById('errorMsg').innerText = msg;
        }
    </script>
</body>
</html>