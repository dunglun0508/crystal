#!/bin/bash

# Script để chạy nhiều queue workers song song
# Sử dụng: ./run_parallel_queues.sh [số_workers]

# Số workers mặc định
WORKERS=${1:-5}

echo "Starting $WORKERS queue workers in parallel..."

# Tạo thư mục logs nếu chưa có
mkdir -p storage/logs

# Chạy nhiều workers song song
for i in $(seq 1 $WORKERS); do
    echo "Starting worker $i..."
    php artisan queue:work database --queue=shopify-sync --timeout=3600 --sleep=3 --tries=3 > storage/logs/worker_$i.log 2>&1 &
    WORKER_PIDS[$i]=$!
done

echo "All $WORKERS workers started!"
echo "Worker PIDs: ${WORKER_PIDS[@]}"
echo "Logs saved to storage/logs/worker_*.log"
echo ""
echo "To stop all workers, run: php artisan queue:restart"
echo "To check worker status: ps aux | grep 'queue:work'"
echo "To view logs: tail -f storage/logs/worker_*.log" 