import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const codespaceName = process.env.CODESPACE_NAME;
const forwardingDomain = process.env.GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN;
const codespaceOrigin = codespaceName && forwardingDomain
    ? `${codespaceName}-5173.${forwardingDomain}`
    : null;
const appOrigin = codespaceName && forwardingDomain
    ? `https://${codespaceName}-8000.${forwardingDomain}`
    : null;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        ...(codespaceOrigin ? {
            origin: `https://${codespaceOrigin}`,
            cors: { origin: appOrigin },
            hmr: {
                host: codespaceOrigin,
                protocol: 'wss',
                clientPort: 443,
            },
        } : {}),
    },
});
