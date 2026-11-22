import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
// Bootstrap's own JS powers the dropdowns in the AdminLTE markup. AdminLTE's
// jQuery plugins are intentionally not loaded: sidebar treeview, sidebar
// toggle and tables are driven by Vue state instead.
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

createInertiaApp({
    title: (title) => (title ? `${title} | Laravel AdminLTE 4` : 'Laravel AdminLTE 4'),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue', { eager: true });
        const page = pages[`./pages/${name}.vue`];

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#0d6efd',
    },
});
