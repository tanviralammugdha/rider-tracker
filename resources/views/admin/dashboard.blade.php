<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Tracking Dashboard</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Custom Scrollbar */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        #map { height: 100vh; width: 100%; z-index: 1; }
        .sidebar { z-index: 1000; }
    </style>
</head>
<body class="bg-gray-100 overflow-hidden flex">

    <!-- Sidebar: Active Riders List -->
    <div class="sidebar w-80 bg-white shadow-xl h-screen flex flex-col absolute left-0 top-0 md:relative transition-transform transform -translate-x-full md:translate-x-0" id="sidebar">
        <div class="p-5 border-b bg-blue-600 text-white flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold">Phone Tracker</h1>
                <p class="text-xs opacity-80">Admin Panel</p>
            </div>
            <span class="bg-blue-800 text-xs px-2 py-1 rounded-full" id="activeCount">0 Active</span>
        </div>

        <div class="p-4 flex-1 overflow-y-auto" id="riderList">
            <!-- Riders will be injected here via JS -->
            <p class="text-center text-gray-400 text-sm mt-10">Loading riders...</p>
        </div>

        <div class="p-4 border-t bg-gray-50">
            <button class="w-full bg-red-500 hover:bg-red-600 text-white py-2 rounded shadow text-sm font-medium">Logout</button>
        </div>
    </div>

    <!-- Map Area -->
    <div class="flex-1 relative">
        <!-- Mobile Toggle Button -->
        <button onclick="toggleSidebar()" class="md:hidden absolute top-4 left-4 z-[999] bg-white p-2 rounded shadow text-gray-700">
            ☰
        </button>

        <div id="map"></div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        // --- Map Setup ---
        var map = L.map('map').setView([23.8103, 90.4125], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Store markers and rider data
        var markers = {}; 
        var ridersData = {};

        // --- Icons ---
        var riderIcon = L.icon({
            iconUrl: 'https://cdn-icons-png.flaticon.com/512/3063/3063823.png', // Bike Icon
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40]
        });

        // --- Functions ---

        // 1. Fetch Initial Riders
        function fetchInitialRiders() {
            axios.get('/api/riders')
                .then(response => {
                    const riders = response.data;
                    const listContainer = document.getElementById('riderList');
                    listContainer.innerHTML = ''; // Clear loading text

                    riders.forEach(rider => {
                        updateRiderOnMap(rider);
                        updateRiderList(rider);
                    });
                    updateCount();
                })
                .catch(err => console.error(err));
        }

        // 2. Update Map Marker
        function updateRiderOnMap(rider) {
            var lat = parseFloat(rider.latitude || rider.lat);
            var lng = parseFloat(rider.longitude || rider.lng);

            if (isNaN(lat) || isNaN(lng)) return;

            var lastSeen = new Date().toLocaleTimeString();

            if (markers[rider.id]) {
                // Update existing
                var newLatLng = new L.LatLng(lat, lng);
                markers[rider.id].setLatLng(newLatLng);
                markers[rider.id].setPopupContent(`
                    <div class='text-center'>
                        <h3 class='font-bold'>${rider.name}</h3>
                        <p class='text-xs text-gray-500'>Updated: ${lastSeen}</p>
                    </div>
                `);
            } else {
                // Create new
                var marker = L.marker([lat, lng], {icon: riderIcon}).addTo(map);
                marker.bindPopup(`
                    <div class='text-center'>
                        <h3 class='font-bold'>${rider.name}</h3>
                        <p class='text-xs text-gray-500'>Joined: ${lastSeen}</p>
                    </div>
                `);
                markers[rider.id] = marker;
            }
            
            // Keep data synced for list
            ridersData[rider.id] = rider;
        }

        // 3. Update Sidebar List
        function updateRiderList(rider) {
            // We will re-render the list for simplicity or update specific item
            // Here we just re-render efficiently using stored data
            renderList();
        }

        function renderList() {
            const container = document.getElementById('riderList');
            container.innerHTML = '';

            Object.values(ridersData).forEach(r => {
                const el = document.createElement('div');
                el.className = 'bg-white p-3 mb-2 rounded shadow-sm border-l-4 border-green-500 cursor-pointer hover:bg-gray-50';
                el.onclick = () => focusOnRider(r.id);
                el.innerHTML = `
                    <div class="flex justify-between items-center">
                        <h4 class="font-bold text-gray-800">${r.name}</h4>
                        <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">Online</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 truncate">${r.email}</p>
                `;
                container.appendChild(el);
            });
            updateCount();
        }

        function focusOnRider(id) {
            if (markers[id]) {
                map.setView(markers[id].getLatLng(), 16);
                markers[id].openPopup();
                // On mobile, close sidebar after click
                if(window.innerWidth < 768) toggleSidebar();
            }
        }

        function updateCount() {
            document.getElementById('activeCount').innerText = Object.keys(markers).length + ' Active';
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        }

        // --- Initialization ---
        document.addEventListener("DOMContentLoaded", function() {
            
            // Load initial data
            fetchInitialRiders();

            // Listen for live updates
            setTimeout(() => {
                if (typeof window.Echo === 'undefined') {
                    console.warn("Echo not loaded yet.");
                    return;
                }

                window.Echo.channel('live-tracking')
                    .listen('RiderLocationUpdated', (e) => {
                        if (!e.rider) return;
                        console.log("Live update:", e.rider);
                        
                        // Map data keys (lat/lng) vs DB keys (latitude/longitude) normalization
                        e.rider.latitude = e.rider.lat;
                        e.rider.longitude = e.rider.lng;

                        updateRiderOnMap(e.rider);
                        ridersData[e.rider.id] = e.rider; // update data store
                        renderList(); // refresh list
                    });
            }, 1000);
        });
    </script>
</body>
</html>