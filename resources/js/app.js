window.ytApiReady = false;
window.onYouTubeIframeAPIReady = function () {
    window.ytApiReady = true;
    window.dispatchEvent(new CustomEvent('yt-api-ready'));
};

const tag = document.createElement('script');
tag.src = 'https://www.youtube.com/iframe_api';
document.head.appendChild(tag);

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Ignore registration failures (e.g. private browsing).
        });
    });
}

window.streamVaultInstall = (() => {
    let deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        window.dispatchEvent(new CustomEvent('pwa-installable'));
    });

    return {
        async prompt() {
            if (! deferredPrompt) {
                return false;
            }

            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;

            return true;
        },
        get available() {
            return deferredPrompt !== null;
        },
    };
})();
