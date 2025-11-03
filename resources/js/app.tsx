import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { GoogleReCaptchaProvider } from 'react-google-recaptcha-v3';

const appName = import.meta.env.VITE_APP_NAME || 'Photographer';
const siteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY;

if (!siteKey) {
    console.error('reCAPTCHA site key not provided!');
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const rootElement = createRoot || hydrateRoot;
        const root = rootElement(el);

        root.render(
            <GoogleReCaptchaProvider
                reCaptchaKey={siteKey}
                scriptProps={{
                    async: true,
                    defer: true,
                    appendTo: 'head',
                    nonce: undefined,
                }}
            >
                <App {...props} />
            </GoogleReCaptchaProvider>
        );
        delete el.dataset.page;
    },
    progress: {
        color: '#4B5563',
    },
});