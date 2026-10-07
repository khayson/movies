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

// Global Toast notification helper across page redirects and Livewire navigations
function checkFlashToast() {
    try {
        const storedToast = sessionStorage.getItem('flux_toast');
        if (storedToast) {
            sessionStorage.removeItem('flux_toast');
            const detail = JSON.parse(storedToast);
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('toast-show', { detail }));
            }, 120);
            return;
        }

        const metaToast = document.querySelector('meta[name="flash-toast"]');
        if (metaToast) {
            const content = metaToast.getAttribute('content');
            metaToast.remove();
            if (content) {
                const detail = JSON.parse(content);
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('toast-show', { detail }));
                }, 120);
            }
        }
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', checkFlashToast);
document.addEventListener('livewire:navigated', checkFlashToast);

// Interactive Auth Form Handler (Login & Register)
window.authForm = function (config = {}) {
    return {
        loading: false,
        errorMessage: '',
        errors: {},

        async submit() {
            if (this.loading) {
                return;
            }

            const form = this.$el;

            // Trigger browser validation if native constraints fail
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                form.reportValidity();
                return;
            }

            this.loading = true;
            this.errorMessage = '';
            this.errors = {};

            const formData = new FormData(form);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || formData.get('_token')
                || '';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                if (response.ok) {
                    const data = await response.json().catch(() => ({}));

                    const successDetail = {
                        slots: {
                            heading: config.successHeading || 'Success',
                            text: config.successText || 'Action completed successfully.',
                        },
                        dataset: { variant: 'success' },
                        duration: 5000,
                    };

                    // Persist for next screen in case of full-page redirect
                    try {
                        sessionStorage.setItem('flux_toast', JSON.stringify(successDetail));
                    } catch (e) {}

                    // Trigger immediate feedback toast
                    window.dispatchEvent(new CustomEvent('toast-show', { detail: successDetail }));

                    // Determine target redirect
                    const targetUrl = data.two_factor
                        ? (config.twoFactorRoute || '/two-factor-challenge')
                        : (data.redirect || config.defaultRedirect || '/dashboard');

                    if (window.Livewire?.navigate) {
                        window.Livewire.navigate(targetUrl);
                    } else {
                        window.location.href = targetUrl;
                    }
                    return;
                }

                // Handle error responses (422, 429, 419, 500)
                this.loading = false;
                let errorHeading = config.errorHeading || 'Authentication failed';
                let errorText = config.defaultErrorMessage || 'Invalid details provided.';

                try {
                    const errData = await response.json();
                    if (errData.errors) {
                        this.errors = errData.errors;
                        const firstKey = Object.keys(errData.errors)[0];
                        if (firstKey && Array.isArray(errData.errors[firstKey]) && errData.errors[firstKey][0]) {
                            errorText = errData.errors[firstKey][0];
                        }
                    } else if (errData.message) {
                        errorText = errData.message;
                    }
                } catch (e) {
                    if (response.status === 429) {
                        errorText = 'Too many attempts. Please try again in a few moments.';
                    } else if (response.status === 419) {
                        errorText = 'Session expired. Please refresh the page and try again.';
                    } else {
                        errorText = 'An unexpected error occurred. Please try again.';
                    }
                }

                this.errorMessage = errorText;

                window.dispatchEvent(new CustomEvent('toast-show', {
                    detail: {
                        slots: { heading: errorHeading, text: errorText },
                        dataset: { variant: 'danger' },
                        duration: 6000,
                    },
                }));
            } catch (err) {
                this.loading = false;
                const errorText = 'Network connection error. Please try again.';
                this.errorMessage = errorText;

                window.dispatchEvent(new CustomEvent('toast-show', {
                    detail: {
                        slots: { heading: 'Connection Error', text: errorText },
                        dataset: { variant: 'danger' },
                        duration: 6000,
                    },
                }));
            }
        },
    };
};
