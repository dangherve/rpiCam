#!/usr/bin/env bash

STATE_DIR="/tmp/rpicam-web-$(id -u)"
ACTIVITY_FILE="$STATE_DIR/activity"

last=`cat $ACTIVITY_FILE`

now=`date '+%s'`

difference=$((now - last))

if [ $difference -lt 300 ]; then
    at  now "+ 1 minute" -f "./check.sh"
else
    sudo pkill -TERM -x rpicam-still
    sudo pkill -TERM -x rpicam-vid


   for pin in 22 27 17; do
        pigs p "$pin" 255 >/dev/null 2>&1 || true
    done

fi
