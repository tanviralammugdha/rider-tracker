<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Rider Tracker</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        #map { height: 600px; width: 100%; border: 2px solid #333; }
    </style>
</head>
<body>

    <h2 style="text-align: center;">Rider Live Tracking Dashboard</h2>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            // ১. ম্যাপ ইনিশিয়াল করা (ডিফল্ট লোকেশন: ঢাকা)
            var map = L.map('map').setView([23.8103, 90.4125], 13);

            // টাইলস লোড করা
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // মার্কার স্টোর করার অবজেক্ট
            var markers = {};

            // ২. ইভেন্ট লিসেন করা
            setTimeout(() => {
                if (typeof window.Echo === 'undefined') {
                    console.error("Laravel Echo is not loaded! Check 'npm run dev'");
                    return;
                }

                console.log("Listening for location updates...");

                window.Echo.channel('live-tracking')
                    .listen('RiderLocationUpdated', (e) => {
                        console.log("New Event Received:", e);

                        // --- [FIX START] ডাটা ভ্যালিডেশন ---
                        // ডাটা যদি না থাকে বা lat/lng যদি null হয়, তাহলে থামো
                        if (!e.rider || e.rider.lat == null || e.rider.lng == null) {
                            console.warn("Incomplete data received (NULL values). Ignoring...");
                            return;
                        }

                        var lat = parseFloat(e.rider.lat);
                        var lng = parseFloat(e.rider.lng);

                        // যদি এগুলো নম্বর না হয় (NaN), তাহলেও থামো
                        if (isNaN(lat) || isNaN(lng)) {
                            console.warn("Invalid Coordinates (NaN). Ignoring...");
                            return;
                        }
                        // --- [FIX END] ---

                        var rider = e.rider;

                        // ৩. মার্কার আপডেট বা তৈরি করা
                        if (markers[rider.id]) {
                            // আগের মার্কার আপডেট
                            var newLatLng = new L.LatLng(lat, lng);
                            markers[rider.id].setLatLng(newLatLng);
                            markers[rider.id].bindPopup(`<b>${rider.name}</b><br>Moving...`).openPopup();
                        } else {
                            // নতুন মার্কার তৈরি
                            var marker = L.marker([lat, lng]).addTo(map);
                            marker.bindPopup(`<b>${rider.name}</b>`).openPopup();
                            markers[rider.id] = marker;
                        }
                    });
            }, 1000); 
        });
    </script>
</body>
</html>