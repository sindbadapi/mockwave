import '../css/app.css';
import './bootstrap';


import {createInertiaApp, type ResolvedComponent} from '@inertiajs/react';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {createRoot} from 'react-dom/client';
import {route} from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ) as Promise<ResolvedComponent>,
    setup({el, App, props}) {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const ziggy = (props.initialPage.props as any).ziggy;
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        (globalThis as any).route = (name: any, params?: any, absolute?: boolean) =>
            route(name, params, absolute, ziggy);

        if (import.meta.env.SSR) {
            return <App {...props} />;
        }

        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
