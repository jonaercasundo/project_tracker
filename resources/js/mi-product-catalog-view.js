const storageKey = 'mi-product-catalog-view';
const supportedViews = ['photo', 'table'];

window.miCatalogView = () => ({
    view: 'table',
    encodedPeriod: 'all',

    initialize() {
        this.encodedPeriod = document.getElementById('encoded_period')?.value || 'all';

        try {
            const savedView = window.localStorage.getItem(storageKey);
            if (supportedViews.includes(savedView)) this.view = savedView;
        } catch {
            this.view = 'table';
        }
    },

    setView(view) {
        if (!supportedViews.includes(view)) return;

        this.view = view;

        try {
            window.localStorage.setItem(storageKey, view);
        } catch {
            // Keep the current selection for this page when storage is unavailable.
        }
    },
});

document.addEventListener('error', (event) => {
    const image = event.target;
    if (!(image instanceof HTMLImageElement) || !image.matches('[data-catalog-photo]')) return;

    image.hidden = true;
    const placeholder = image.parentElement?.querySelector('[data-catalog-photo-placeholder]');
    if (placeholder) placeholder.hidden = false;
}, true);
