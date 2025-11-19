<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Simulator App</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; text-align: center; }
        .box { background: white; padding: 20px; border-radius: 8px; max-width: 400px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; background: #28a745; color: white; border: none; border-radius: 5px; margin-top: 10px; }
        button:disabled { background: #ccc; }
        #status { margin-top: 15px; font-weight: bold; color: #333; }
        .log { font-size: 12px; color: #666; margin-top: 10px; text-align: left; max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body>

    <div class="box">
        <h2>🛵 Rider App Simulator</h2>
        <p>This page acts like the Rider's Phone.</p>
        
        <button id="startBtn" onclick="startRide()">Start Riding</button>
        
        <div id="status">Status: Waiting...</div>
        <div class="log" id="logArea"></div>
    </div>

    <script>
        let token = null;
        let intervalId = null;
        
        // শুরুর লোকেশন (ঢাকা)
        let lat = 23.8103;
        let lng = 90.4125;

        // ১. রাইডার লগইন ফাংশন
        async function login() {
            updateStatus("Logging in...");
            try {
                // আমাদের তৈরি করা টেস্ট ক্রেডেনশিয়াল
                const response = await axios.post('/api/login', {
                    email: 'rider@gmail.com',
                    password: 'password'
                });

                token = response.data.token;
                updateStatus("Login Success! Token Received.");
                return true;
            } catch (error) {
                updateStatus("Login Failed! Check console.");
                console.error(error);
                return false;
            }
        }

        // ২. লোকেশন আপডেট ফাংশন
        async function sendLocation() {
            if (!token) return;

            // লোকেশন একটু একটু করে পরিবর্তন করা হচ্ছে (যাতে ম্যাপে নড়াচড়া বোঝা যায়)
            lat += 0.0005; // উত্তর দিকে যাচ্ছে
            lng += 0.0002; // পূর্ব দিকে যাচ্ছে

            try {
                await axios.post('/api/update-location', {
                    lat: lat,
                    lng: lng
                }, {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                
                log(`Location sent: ${lat.toFixed(4)}, ${lng.toFixed(4)}`);
            } catch (error) {
                console.error("Update failed", error);
            }
        }

        // ৩. রাইড শুরু করা
        async function startRide() {
            document.getElementById('startBtn').disabled = true;
            
            // প্রথমে লগইন করবো
            const isLoggedIn = await login();
            
            if (isLoggedIn) {
                updateStatus("Rider is Moving... Check Admin Map!");
                // প্রতি ৩ সেকেন্ড পর পর লোকেশন পাঠাবে
                intervalId = setInterval(sendLocation, 3000);
            }
        }

        function updateStatus(msg) {
            document.getElementById('status').innerText = "Status: " + msg;
        }

        function log(msg) {
            const logArea = document.getElementById('logArea');
            logArea.innerHTML = `<div>${msg}</div>` + logArea.innerHTML;
        }
    </script>
</body>
</html>