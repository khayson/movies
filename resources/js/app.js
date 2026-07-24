window.ytApiReady = false;
window.onYouTubeIframeAPIReady = function () {
    window.ytApiReady = true;
    window.dispatchEvent(new CustomEvent('yt-api-ready'));
};

const tag = document.createElement('script');
tag.src = 'https://www.youtube.com/iframe_api';
document.head.appendChild(tag);
