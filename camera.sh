#!/usr/bin/env bash
# ChipieCam — single-camera controller, called by camera.php.
#
# No persistent watchdog and no camera.service:
#   * The PHP request runs one short command and returns.
#   * The camera runs in the background only while being used.
#   * One delayed cleanup task checks inactivity, then exits.
#
# Only the rpicam commands and their signals use sudo.

set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "$0")" && pwd)"
SCRIPT_PATH="$SCRIPT_DIR/camera.sh"
STATE_DIR="/tmp/rpicam-web-$(id -u)"
PHOTO_PATH="$SCRIPT_DIR/photo.jpeg"

# Adjust these two settings for your Raspberry Pi.
IDLE_SECONDS=120
CHECK_AFTER_MINUTES=1

MODE_FILE="$STATE_DIR/mode"
ACTIVITY_FILE="$STATE_DIR/activity"
VIDEO_DEADLINE_FILE="$STATE_DIR/video.deadline"
VIDEO_PID_FILE="$STATE_DIR/video.pid"
PHOTO_PID_FILE="$STATE_DIR/photo.pid"
LAST_CAPTURE_FILE="$STATE_DIR/last-capture-ms"
SCHEDULED_FILE="$STATE_DIR/cleanup.scheduled"

mkdir -p "$STATE_DIR"

# PHP requests and the delayed cleanup must never modify camera state together.
exec 9>"$STATE_DIR/lock"
if ! flock -w 2 -x 9; then
    echo "Camera controller is busy" >&2
    exit 1
fi
is_photo_running() {
    pgrep -x rpicam-still >/dev/null 2>&1
}

is_video_running() {
    pgrep -x rpicam-vid >/dev/null 2>&1
}

# A camera may take a few seconds to open. Don't mistake startup for failure.
current_mode() {
    local saved_mode age now started
    saved_mode="$(cat "$MODE_FILE" 2>/dev/null || echo idle)"

    case "$saved_mode" in
        photo) if is_photo_running; then echo photo; return; fi ;;
        video) if is_video_running; then echo video; return; fi ;;
        *) echo idle; return ;;
    esac

    now="$(date +%s)"
    started="$(stat -c %Y "$MODE_FILE" 2>/dev/null || echo 0)"
    age=$((now - started))
    if ((age >= 0 && age < 5)); then
        echo "$saved_mode"
    else
        echo idle
    fi
}

set_mode() {
    printf '%s\n' "$1" > "$MODE_FILE"
}

stop_photo() {
    if is_photo_running; then
        # This Pi is assumed to have no unrelated rpicam-still processes.
        sudo -n /usr/bin/pkill -TERM -x rpicam-still || true
    fi
    rm -f "$PHOTO_PID_FILE"
}

stop_video() {
    local launcher="" pgid=""
    launcher="$(cat "$VIDEO_PID_FILE" 2>/dev/null || true)"

    # Send TERM to the root-owned camera. ffmpeg will see its input close.
    if is_video_running; then
        sudo -n /usr/bin/pkill -TERM -x rpicam-vid || true
    fi

    # Video was started inside its own process group. Only terminate that
    # group if its leader still matches the PID we created.
    if [[ "$launcher" =~ ^[0-9]+$ ]] && ((launcher > 1)); then
        pgid="$(ps -o pgid= -p "$launcher" 2>/dev/null | tr -d ' ' || true)"
        if [[ "$pgid" == "$launcher" ]]; then
            kill -TERM -- "-$pgid" 2>/dev/null || true
        fi
    fi

    rm -f "$VIDEO_PID_FILE" "$VIDEO_DEADLINE_FILE"
}

start_photo() {
    # A page opening during another page's stream must JOIN the stream,
    # not silently replace it with a new still camera.
    if [[ "${1:-}" != force ]] && is_video_running; then
        set_mode video
        return
    fi
    if is_photo_running; then
        set_mode photo
        return
    fi
    stop_video
    # All stdio AND the flock descriptor must be closed for background work.
    sudo -n /usr/bin/rpicam-still -t 0 -n --signal -o "$PHOTO_PATH" \
        </dev/null >"$STATE_DIR/photo.log" 2>&1 9>&- &
    PID=$!
    result=$?
    echo "$PID" > "$PHOTO_PID_FILE"
    set_mode photo
}

start_video() {
    local duration="$1" resolution width height fps
    [[ "$duration" =~ ^[0-9]+$ ]] && ((duration >= 10 && duration <= 3600)) || {
        echo 'Video duration must be between 10 and 3600 seconds.' >&2
        return 2
    }

    # Repeated clicks only extend the shared deadline; no camera restart.
    if is_video_running; then
        echo "$(($(date +%s) + duration))" > "$VIDEO_DEADLINE_FILE"
        set_mode video
        return
    fi

    stop_photo
    stop_video

    # Allow the camera device to be released before opening it for video.
    sleep 0.4

    resolution="$(cat /tmp/resolution 2>/dev/null || echo 640x480x10)"
    IFS=x read -r width height fps <<< "$resolution"
    [[ "$width" =~ ^[0-9]+$ ]] && ((width >= 160 && width <= 1920)) || width=640
    [[ "$height" =~ ^[0-9]+$ ]] && ((height >= 120 && height <= 1080)) || height=480
    [[ "$fps" =~ ^[0-9]+$ ]] && ((fps >= 1 && fps <= 30)) || fps=10

    # Keep the original working rpicam-vid | ffmpeg -> RTMP pipeline.
    # setsid creates a process group so the whole pipeline can be stopped.
    setsid bash -c '
        sudo -n /usr/bin/rpicam-vid -t 0 --inline \
            --width "$1" --height "$2" --framerate "$3" -o - |
        /usr/bin/ffmpeg -loglevel warning -i - -c:v copy -f flv \
            rtmp://localhost/live/stream
    ' bash "$width" "$height" "$fps" \
        </dev/null >"$STATE_DIR/video.log" 2>&1 9>&- &

    echo "$!" > "$VIDEO_PID_FILE"
    echo "$(($(date +%s) + duration))" > "$VIDEO_DEADLINE_FILE"
    set_mode video
}

capture_photo() {
    if ! is_photo_running; then
        echo 'The still camera is not ready yet.' >&2
        return 3
    fi

    # Avoid several browser tabs producing multiple signals at once.
    local now previous
    now="$(date +%s%3N)"
    previous="$(cat "$LAST_CAPTURE_FILE" 2>/dev/null || echo 0)"
    [[ "$previous" =~ ^[0-9]+$ ]] || previous=0

    if ((now - previous >= 700)); then
        sudo -n /usr/bin/pkill -USR1 -x rpicam-still
        echo "$now" > "$LAST_CAPTURE_FILE"
    fi

    # Do not wait for a JPEG in PHP: waiting was the expensive blocking part.
    # JavaScript delays the image reload a little instead.
}

capture_photo_dev() {

    magick -background white -size 800x600  -fill black  -gravity center label:"`date`"  photo.jpeg

    # Do not wait for a JPEG in PHP: waiting was the expensive blocking part.
    # JavaScript delays the image reload a little instead.
}


print_status() {
    local mode deadline=0 remaining=0
    mode="$(current_mode)"

    if [[ "$mode" == video ]]; then
        deadline="$(cat "$VIDEO_DEADLINE_FILE" 2>/dev/null || echo 0)"
        [[ "$deadline" =~ ^[0-9]+$ ]] || deadline=0
        remaining=$((deadline - $(date +%s)))
        ((remaining > 0)) || remaining=0
    fi

    printf '{"mode":"%s","remaining":%d}\n' "$mode" "$remaining"
}

date '+%s' > $ACTIVITY_FILE

# Each request performs exactly one action, releases the lock and exits.
case "${1:-}" in
    status) print_status ;;
    photo)     ./check.sh >/dev/null 2>/dev/null; start_photo; print_status ;;
    capture) capture_photo; print_status ;;
    video) start_video "${2:-180}"; print_status ;;
    stop-video)
        if [[ "$(current_mode)" == video ]]; then
            stop_video
            sleep 0.4
        fi
        start_photo force
        print_status
        ;;
    activity) schedule_cleanup; print_status ;;
    *) echo 'Unknown camera action.' >&2; exit 2 ;;
esac
