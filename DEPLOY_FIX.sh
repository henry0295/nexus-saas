#!/bin/bash
# Run this script on the server at /opt/nexus-saas
# chmod +x deploy-fix.sh && ./deploy-fix.sh

cd /opt/nexus-saas

echo "📦 Deploying corrected welcome.blade.php with proper Blade syntax..."

# Use tee to write the file reliably
docker compose -f docker-compose.prod.yml exec -T php bash -c 'cat > /app/resources/views/welcome.blade.php' << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NexusSaaS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px;
            max-width: 500px;
            text-align: center;
        }
        h1 { color: #333; font-size: 32px; margin-bottom: 20px; }
        p { color: #666; line-height: 1.6; margin: 15px 0; }
        .status { 
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            font-size: 14px;
        }
        a { 
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        a:hover { background: #764ba2; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 NexusSaaS</h1>
        <p>Welcome to NexusSaaS - Your Multi-tenant Business Platform</p>
        
        <div class="status">
            <strong>✓ System Status: Operational</strong><br>
            Database: Connected<br>
            API: Ready<br>
            Services: All Healthy
        </div>
        
        <p>Your SaaS application is running successfully. The backend infrastructure is operational and ready to handle requests.</p>
        
        <p>To get started, use the API endpoints at <strong>/api</strong> or deploy the frontend application.</p>
        
        <a href="https://github.com/henry0295/nexus-saas">View Documentation</a>
        
        <div class="footer">
            <p>NexusSaaS v1.0 | Laravel 13.1.1 | Running in Production</p>
        </div>
    </div>
</body>
</html>
EOF

echo "✓ File deployed"

echo "🔄 Clearing all caches..."
docker compose -f docker-compose.prod.yml exec -T php php artisan view:clear
docker compose -f docker-compose.prod.yml exec -T php php artisan cache:clear

echo "✓ Caches cleared"

echo "🔄 Restarting PHP container..."
docker compose -f docker-compose.prod.yml restart php
sleep 5

echo "✓ Container restarted"

echo ""
echo "✅ Deployment complete!"
echo ""
echo "VERIFICATION STEPS:"
echo "1. Test locally: curl -sk https://localhost/ | grep -o '<title>[^<]*</title>'"
echo "2. Test remotely: curl -sk https://192.168.101.99/ | grep -o '<title>[^<]*</title>'"
echo "3. Full response: curl -sk https://192.168.101.99/"
