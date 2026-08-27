<div
    wire:ignore
    x-data="{
        init() {
            let renderWidget = () => {
                if (window.turnstile && this.$refs.turnstileContainer) {
                    if (this.$refs.turnstileContainer.children.length > 0) {
                        return;
                    }
                    try {
                        window.turnstile.render(this.$refs.turnstileContainer, {
                            sitekey: '{{ config('services.turnstile.site_key', '1x00000000000000000000AA') }}',
                            callback: (token) => {
                                window.cfTurnstileToken = token;
                            },
                            'expired-callback': () => {
                                window.cfTurnstileToken = '';
                            }
                        });
                    } catch (e) {
                        console.error('Turnstile render error:', e);
                    }
                }
            };

            if (window.turnstile) {
                renderWidget();
            } else {
                if (!document.getElementById('turnstile-script')) {
                    let script = document.createElement('script');
                    script.id = 'turnstile-script';
                    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
                    script.async = true;
                    script.defer = true;
                    script.onload = () => renderWidget();
                    document.head.appendChild(script);
                } else {
                    let checkInterval = setInterval(() => {
                        if (window.turnstile) {
                            clearInterval(checkInterval);
                            renderWidget();
                        }
                    }, 100);
                }
            }

            if (!window.turnstileHookRegistered) {
                window.turnstileHookRegistered = true;
                document.addEventListener('livewire:init', () => {
                    Livewire.hook('request', ({ options }) => {
                        if (window.cfTurnstileToken) {
                            options.headers = options.headers || {};
                            options.headers['X-Turnstile-Token'] = window.cfTurnstileToken;
                        }
                    });
                });
            }
        }
    }"
    class="mt-4 mb-4 flex justify-center w-full"
>
    <div x-ref="turnstileContainer" class="flex justify-center w-full overflow-hidden rounded-lg"></div>
</div>
