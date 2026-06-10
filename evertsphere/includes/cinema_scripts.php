<script>
// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('tab-active');
            b.classList.add('text-coffee-400');
        });
        btn.classList.add('tab-active');
        btn.classList.remove('text-coffee-400');

        const tab = btn.dataset.tab;
        document.getElementById('tab-movies').classList.toggle('hidden', tab !== 'movies');
        document.getElementById('tab-locations').classList.toggle('hidden', tab !== 'locations');
    });
});

// Book Now buttons
document.querySelectorAll('.movie-overlay button, .movie-overlay a').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (btn.tagName === 'A' && btn.href) {
            window.location = btn.href;
            return;
        }
        const card = btn.closest('.movie-card');
        const title = card.querySelector('h3')?.textContent || '';
        btn.textContent = '✓ Booked!';
        btn.classList.replace('bg-coffee-500', 'bg-green-600');
        setTimeout(() => {
            btn.textContent = 'Book Now';
            btn.classList.replace('bg-green-600', 'bg-coffee-500');
        }, 2000);
    });
});

// Element SDK wiring (optional)
const defaultConfig = {
    page_title: 'Cinema Management',
    location_heading: 'Our Locations',
    background_color: '#fdf8f3',
    surface_color: '#ffffff',
    text_color: '#33200F',
    primary_action: '#8B5E3C',
    secondary_action: '#6B4226',
    font_family: 'Playfair Display',
    font_size: 16
};

function applyConfig(config) {
    document.getElementById('page-title').textContent = config.page_title || defaultConfig.page_title;
    document.getElementById('location-heading').textContent = config.location_heading || defaultConfig.location_heading;

    const body = document.body;
    body.style.backgroundColor = config.background_color || defaultConfig.background_color;

    document.querySelectorAll('.bg-white').forEach(el => {
        el.style.backgroundColor = config.surface_color || defaultConfig.surface_color;
    });

    body.style.color = config.text_color || defaultConfig.text_color;

    const font = config.font_family || defaultConfig.font_family;
    const size = config.font_size || defaultConfig.font_size;
    document.querySelectorAll('.font-display').forEach(el => {
        el.style.fontFamily = `${font}, serif`;
    });
    document.querySelectorAll('h1').forEach(el => el.style.fontSize = `${size * 1.5}px`);
    document.querySelectorAll('h2').forEach(el => el.style.fontSize = `${size * 1.4}px`);
    document.querySelectorAll('h3').forEach(el => el.style.fontSize = `${size * 1.1}px`);
    document.querySelectorAll('p, span, a, button').forEach(el => {
        if (!el.closest('header') && !el.closest('footer')) {
            el.style.fontSize = `${size * 0.9}px`;
        }
    });

    document.querySelectorAll('.bg-coffee-500').forEach(el => {
        if (el.tagName === 'BUTTON' || el.tagName === 'A') {
            el.style.backgroundColor = config.primary_action || defaultConfig.primary_action;
        }
    });
}

window.elementSdk?.init?.({
    defaultConfig,
    onConfigChange: async (config) => { applyConfig(config); },
    mapToCapabilities: (config) => ({
        recolorables: [
            { get: () => config.background_color || defaultConfig.background_color, set: (v) => { config.background_color = v; window.elementSdk.setConfig({ background_color: v }); } },
            { get: () => config.surface_color || defaultConfig.surface_color, set: (v) => { config.surface_color = v; window.elementSdk.setConfig({ surface_color: v }); } },
            { get: () => config.text_color || defaultConfig.text_color, set: (v) => { config.text_color = v; window.elementSdk.setConfig({ text_color: v }); } },
            { get: () => config.primary_action || defaultConfig.primary_action, set: (v) => { config.primary_action = v; window.elementSdk.setConfig({ primary_action: v }); } },
            { get: () => config.secondary_action || defaultConfig.secondary_action, set: (v) => { config.secondary_action = v; window.elementSdk.setConfig({ secondary_action: v }); } }
        ],
        borderables: [],
        fontEditable: { get: () => config.font_family || defaultConfig.font_family, set: (v) => { config.font_family = v; window.elementSdk.setConfig({ font_family: v }); } },
        fontSizeable: { get: () => config.font_size || defaultConfig.font_size, set: (v) => { config.font_size = v; window.elementSdk.setConfig({ font_size: v }); } }
    }),
    mapToEditPanelValues: (config) => new Map([
        ['page_title', config.page_title || defaultConfig.page_title],
        ['location_heading', config.location_heading || defaultConfig.location_heading]
    ])
});
</script>
