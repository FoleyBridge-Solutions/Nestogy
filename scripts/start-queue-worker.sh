#!/bin/bash

# Queue Worker Startup Script for Nestogy
# This script starts the Laravel queue worker for processing emails and other background jobs

cd /opt/nestogy

echo "Starting queue worker for Nestogy..."
echo "Processing queues: default,emails"
echo "Press Ctrl+C to stop"

# Start the queue worker with email queue priority
php artisan queue:work --queue=emails,default --timeout=60 --memory=512 --tries=3 --verbose