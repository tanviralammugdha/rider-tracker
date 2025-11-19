<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Rider Partner App</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 h-screen flex flex-col">

    <!-- Top Bar -->
    <div class="bg-blue-600 text-white p-4 shadow-md flex justify-between items-center z-10">
        <div class="flex items-center gap-2">
            <i class="fas fa-motorcycle text-xl"></i>
            <h1 class="font-bold text-lg">GoRide Partner</h1>
        </div>
        <div class="bg-blue-700 px-3 py-1 rounded-full text-xs flex items-center gap-1">
            <div id="connStatus" class="w-2 h-2 bg-red-400 rounded-full"></div>
            <span id="connText">Offline</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col items-center justify-center p-6 relative">
        
        <!-- Login Section -->
        <div id="loginSection" class="w-full max-w-xs bg-white p-6 rounded-xl shadow-lg transition-all">
            <h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Welcome Back</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="text-xs text-gray-500 font-bold ml-1">EMAIL</label>
                    <input type="email" id="email" value="rider@gmail.com" class="w-full p-3 bg-gray-100 rounded-lg border-none focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="name@example.com">
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-bold ml-1">PASSWORD</label>
                    <input type="password" id="password" value="password" class="w-full p-3 bg-gray-100 rounded-lg border-none focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="••••••••">
                </div>
                <button onclick="login()" id="loginBtn" class="w-full bg-blue-600 active:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-lg transform active:scale-95 transition">
                    LOG IN
                </button>
            </div>
            <p id="loginError" class="text-red-500 text-xs text-center mt-3 hidden"></p>
        </div>

        <!-- Dashboard Section (Initially Hidden) -->
        <div id="dashboardSection" class="w-full max-w-xs hidden flex-col items-center text-center">
            
            <!-- Status Circle -->
            <div class="relative mb-8">
                <div id="pulseRing" class="absolute inset-0 bg-green-400 rounded-full opacity-0"></div>
                <div class="w-32 h-32 bg-white rounded-full shadow-xl flex items-center justify-center border-4 border-gray-200 z-10 relative">
                    <i class="fas fa-map-marker-alt text-4xl text-gray-400" id="statusIcon"></i>
                </div>
            </div>

            <h3 class="text-2xl font-bold text-gray-800 mb-1" id="riderName">Rider</h3>
            <p class="text-gray-500 text-sm mb-8">You are currently <span class="font-bold" id="textState">offline</span></p>

            <!-- Toggle Button -->
            <button onclick="toggleTracking()" id="toggleBtn" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition flex items-center justify-center gap-2">
                <i class="fas fa-power-off"></i> START SHARING
            </button>

            <!-- Stats -->
            <div class="mt-8 w-full grid grid-cols-2 gap-4">
                <div class="bg-white p-3 rounded-lg shadow-sm">
                    <p class="text-xs text-gray-400">LATITUDE</p>
                    <p class="font-mono text-sm font-bold text-gray-700" id="dispLat">--</p>
                </div>
                <div class="bg-white p-3 rounded-lg shadow-sm">
                    <p class="text-xs text-gray-400">LONGITUDE</p>
                    <p class="font-mono text-sm font-bold text-gray-700" id="dispLng">--</p>
                </div>
            </div>
            
            <button onclick="logout()" class="mt-6 text-gray-400 text-sm underline">Log Out</button>
        </div>
    </div>

    <script>
        let token = null;
        let watchId = null;
        let isTracking = false;

        // --- Authentication ---
        async function login() {
            const btn = document.getElementById('loginBtn');
            const err = document.getElementById('loginError');
            const email = document.getElementById('email').value;
            const pass = document.getElementById('password').value;

            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Logging in...';
            btn.disabled = true;
            err.classList.add('hidden');

            try {
                const res = await axios.post('/api/login', { email: email, password: pass });
                token = res.data.token;
                
                // Switch UI
                document.getElementById('riderName').innerText = res.data.user.name;
                document.getElementById('loginSection').classList.add('hidden');
                document.getElementById('dashboardSection').classList.remove('hidden');
                document.getElementById('dashboardSection').classList.add('flex');

                // Update Connection Status
                document.getElementById('connStatus').classList.replace('bg-red-400', 'bg-green-400');
                document.getElementById('connText').innerText = "Connected";

            } catch (error) {
                btn.innerText = 'LOG IN';
                btn.disabled = false;
                err.innerText = "Invalid Credentials";
                err.classList.remove('hidden');
            }
        }

        function logout() {
            stopTracking();
            token = null;
            document.getElementById('dashboardSection').classList.add('hidden');
            document.getElementById('dashboardSection').classList.remove('flex');
            document.getElementById('loginSection').classList.remove('hidden');
            document.getElementById('loginBtn').innerText = 'LOG IN';
            document.getElementById('loginBtn').disabled = false;
        }

        // --- Tracking Logic ---
        function toggleTracking() {
            if (isTracking) {
                stopTracking();
            } else {
                startTracking();
            }
        }

        function startTracking() {
            if (!navigator.geolocation) {
                alert("GPS not supported");
                return;
            }

            isTracking = true;
            
            // UI Updates
            const btn = document.getElementById('toggleBtn');
            btn.classList.replace('bg-green-500', 'bg-red-500');
            btn.classList.replace('hover:bg-green-600', 'hover:bg-red-600');
            btn.innerHTML = '<i class="fas fa-stop"></i> STOP SHARING';
            
            document.getElementById('textState').innerText = "sharing location";
            document.getElementById('statusIcon').classList.replace('text-gray-400', 'text-green-500');
            document.getElementById('pulseRing').classList.add('animate-ping');
            document.getElementById('pulseRing').classList.replace('opacity-0', 'opacity-75');

            // Start GPS Watch
            watchId = navigator.geolocation.watchPosition(sendLocation, (err) => {
                console.error(err);
                alert("GPS Error: " + err.message);
                stopTracking();
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        }

        function stopTracking() {
            isTracking = false;
            if (watchId) navigator.geolocation.clearWatch(watchId);

            // UI Updates
            const btn = document.getElementById('toggleBtn');
            btn.classList.replace('bg-red-500', 'bg-green-500');
            btn.classList.replace('hover:bg-red-600', 'hover:bg-green-600');
            btn.innerHTML = '<i class="fas fa-power-off"></i> START SHARING';

            document.getElementById('textState').innerText = "offline";
            document.getElementById('statusIcon').classList.replace('text-green-500', 'text-gray-400');
            document.getElementById('pulseRing').classList.remove('animate-ping');
            document.getElementById('pulseRing').classList.replace('opacity-75', 'opacity-0');
        }

        async function sendLocation(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            document.getElementById('dispLat').innerText = lat.toFixed(5);
            document.getElementById('dispLng').innerText = lng.toFixed(5);

            try {
                await axios.post('/api/update-location', {
                    lat: lat,
                    lng: lng
                }, {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
            } catch (err) {
                console.error("Network Error");
            }
        }
    </script>
</body>
</html>