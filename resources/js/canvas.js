/**
 * Dijalankan di dalam iframe canvas builder (same-origin). Mengirim event seleksi & inline edit
 * ke parent via postMessage. Origin pesan selalu diverifikasi.
 */
const origin = window.location.origin;
const post = (payload) => window.parent.postMessage({ source: 'builder-canvas', ...payload }, origin);

document.documentElement.classList.add('builder-canvas');

// Nonaktifkan navigasi & submit di dalam canvas.
document.addEventListener('click', (e) => {
    const link = e.target.closest('a, button[type=submit]');
    if (link) e.preventDefault();
    const el = e.target.closest('[data-section-uuid]');
    if (el) post({ type: 'select', uuid: el.dataset.sectionUuid });
}, true);
document.addEventListener('submit', (e) => e.preventDefault(), true);

// Inline editing teks polos (heading/subtitle).
document.querySelectorAll('[data-inline-edit]').forEach((node) => {
    node.setAttribute('contenteditable', 'plaintext-only');
    node.setAttribute('spellcheck', 'true');
    node.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); node.blur(); } });
    node.addEventListener('blur', () => {
        const section = node.closest('[data-section-uuid]');
        if (!section) return;
        post({ type: 'inline-edit', uuid: section.dataset.sectionUuid, field: node.dataset.inlineEdit, value: node.innerText.trim() });
    });
});

window.addEventListener('message', (e) => {
    if (e.origin !== origin || e.data?.source !== 'builder-parent') return;
    if (e.data.type === 'highlight') {
        document.querySelectorAll('[data-section-uuid].is-selected').forEach((n) => n.classList.remove('is-selected'));
        const el = document.querySelector(`[data-section-uuid="${CSS.escape(e.data.uuid || '')}"]`);
        if (el) {
            el.classList.add('is-selected');
            if (e.data.scroll) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

// Pertahankan posisi scroll saat iframe dimuat ulang setelah perubahan.
const key = 'builder-canvas-scroll';
window.addEventListener('beforeunload', () => sessionStorage.setItem(key, String(window.scrollY)));
const saved = Number(sessionStorage.getItem(key) || 0);
if (saved) window.scrollTo(0, saved);
post({ type: 'ready' });
