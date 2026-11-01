/**
 * ChipieCam client-side camera controller.
 *
 * The Raspberry Pi owns ONE shared camera. Browser tabs do not own processes:
 * they only request snapshots, request a mode change, and show the shared mode.
 *
 * Video duration is enforced here in the browser. If all browsers disappear,
 * a single delayed server-side inactivity check eventually stops the camera.
 */

'use strict';


const cameraState = {

    mode: 'idle',
    photoTimer: null,
    hls: null,
    videoDeadline: 0,

/*
    statusTimer: null,
    videoTimer: null,
    playerTimer: null,
    hls: null,
    photoBusy: false,
    stoppingVideo: false,
    lastPhotoStart: 0,
    photoFailures: 0
*/
    toto: 0
};

function cancelPhotoTimer() {
    clearTimeout(cameraState.photoTimer);
    cameraState.photoTimer = null;
}



const PHOTO_SETTLE_MS = 600; // Allow rpicam-still time to write the JPEG.

function delay(milliseconds) {
    return new Promise(resolve => setTimeout(resolve, milliseconds));
}


/** Send a small request to PHP. Throw useful errors instead of failing silently. */
async function cameraRequest(action, parameters = {}) {
    const response = await fetch('camera.php', {
        method: 'POST',
        body: new URLSearchParams({ action, ...parameters }),
        cache: 'no-store'
    });

    const result = await response.json();
    if (!response.ok) {
console.log(result.error)
        throw new Error(result.error || 'Camera command failed');
    }
    return result;
}


/** Load an image off-screen first to avoid displaying a partly-written JPEG. */
function preloadPhoto(url) {

    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(url);
        image.onerror = () => reject(new Error('JPEG not ready yet'));
        image.src = url;
    });
}



/**
 * One lightweight snapshot request:
 *  1. PHP sends SIGUSR1 and returns IMMEDIATELY.
 *  2. Wait in the browser (not in PHP).
 *  3. Download the JPEG with a cache-busting URL.
 */
async function capturePhoto() {

    if (cameraState.mode !== 'photo' ) {
        cancelPhotoTimer()
        return;
    }

    try {
        await cameraRequest('capture');

        await delay(PHOTO_SETTLE_MS);

        photo = document.getElementById("photo");

        photo.src = "photo.jpeg?" + new Date().getTime();


    } catch (error) {
        console.warn('Snapshot:', error.message);
    } finally {
    }
    cameraState.photoTimer = setTimeout(capturePhoto, document.getElementById(`snapshot-interval`).value*1000);

}




/** Browser-controlled video timeout, synchronized to the server deadline. */
function updateVideoCountdown(remainingSeconds) {
    const countdown = document.getElementById('video-countdown');

    if (cameraState.mode !== 'video') {
        countdown.textContent = '';
        return;
    }

    cameraState.videoDeadline = Date.now() + remainingSeconds * 1000;
    const tick = () => {
        if (cameraState.mode !== 'video') return;
        const remaining = Math.max(0, Math.ceil(
            (cameraState.videoDeadline - Date.now()) / 1000
        ));

        countdown.textContent = `${remaining} seconds remaining`;

        // The WEB CLIENT handles the video timeout, not a watchdog.
        if (remaining === 0) {
            if (!cameraState.stoppingVideo) stopVideo();
        }
    };

    tick();
    if (cameraState.videoDeadline > Date.now() && !cameraState.stoppingVideo) {
        cameraState.videoTimer = setInterval(tick, 1000);
    }
}

function switchVideo(){
    cameraState.mode = "video"

    document.getElementById("photo").style.display = "none";
    document.getElementById("video").style.display = "block";

    document.getElementById("start-video").style.display = "none";
    document.getElementById("stop-video").style.display = "inline"

    setTimeout(stopVideo, document.getElementById(`video-duration`).value*60*1000);

    var video = document.getElementById('video');
    cameraState.hls=  new Hls();

    cameraState.hls.loadSource('hls/stream.m3u8');
    cameraState.hls.attachMedia(video);


}


function switchPhoto(){
    cameraState.mode = "photo"

    document.getElementById("photo").style.display = "block";
    document.getElementById("video").style.display = "none";

    document.getElementById("start-video").style.display = "inline";
    document.getElementById("stop-video").style.display = "none"
    capturePhoto();
    cameraState.hls.detachMedia();

    const countdown = document.getElementById('video-countdown');
    countdown.textContent = ``;

}

async function stopVideo() {
    await cameraRequest('stop-video');

    switchPhoto()

}

/** User requests a temporary stream. Other tabs will see the shared mode. */
async function startVideo() {
    try {
        const minutes = Number(document.getElementById('video-duration').value);
        const seconds = Math.round(minutes * 60);

        if (!Number.isFinite(seconds) || seconds < 10 || seconds > 3600) {
            throw new Error('Video duration must be between 10 seconds and 60 minutes.');
        }

        await cameraRequest('video', { seconds });
        switchVideo()
        cancelPhotoTimer()
        updateVideoCountdown(Number(document.getElementById(`video-duration`).value *60));

    } catch (error) {
        console.error(error);
        alert(error.message);
    }
}

async function initializeCamera() {
    const now = Date.now();
    cameraState.lastPhotoStart = now;

    let state = await cameraRequest('status');

    if (state.mode === 'idle'){
        try {
            await cameraRequest('photo');

            while (state.mode === 'idle'){
                await delay(PHOTO_SETTLE_MS);
                state = await cameraRequest('status');

console.log("waiting:"+state.mode)

            }
            cameraState.mode = "photo"
            capturePhoto()
        }catch (error) {
            console.error(error);
            alert(error.message);
        }
    }else if (state.mode === 'video'){
        cameraState.mode = "video"
        switchVideo()
    }else if (state.mode === 'photo'){
        cameraState.mode = "photo"

        capturePhoto()
    }


}

document.addEventListener('DOMContentLoaded', initializeCamera);
