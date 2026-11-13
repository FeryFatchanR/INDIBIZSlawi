import Sortable from 'sortablejs';

/**
 * Komponen Alpine untuk visual page builder (dimuat di halaman admin Filament).
 * Seluruh perubahan dikirim ke server Livewire dengan UUID; DOM yang dipindah Sortable
 * dikembalikan lalu Livewire merender ulang sesuai urutan server (mencegah state ganda).
 */
window.pageBuilder = (config) => ({
    selected: config.selected ?? null,
    dragging: false,
    autoApply: true,
    sortables: [],
    scale: 1,
    deviceWidth: 1280,

    init() {
        this.$nextTick(() => { this.bindSortables(); this.fit(); });
        // Livewire merender ulang daftar → pasang ulang Sortable.
        Livewire.hook('morphed', ({ el }) => {
            if (el.contains(this.$refs.outline)) this.$nextTick(() => { this.bindSortables(); this.fit(); });
        });
        this.$watch('$wire.selectedUuid', (uuid) => { this.selected = uuid; this.highlight(uuid, true); });

        window.addEventListener('message', (e) => {
            if (e.origin !== window.location.origin || e.data?.source !== 'builder-canvas') return;
            if (e.data.type === 'select' && typeof e.data.uuid === 'string') this.$wire.selectSection(e.data.uuid);
            if (e.data.type === 'inline-edit') this.$wire.inlineEdit(e.data.uuid, e.data.field, String(e.data.value ?? '').slice(0, 1000));
            if (e.data.type === 'ready') this.highlight(this.selected, false);
        });
    },

    bindSortables() {
        this.sortables.forEach((s) => s.destroy());
        this.sortables = [];

        this.$root.querySelectorAll('[data-library]').forEach((list) => {
            this.sortables.push(Sortable.create(list, {
                group: { name: 'builder', pull: 'clone', put: false },
                sort: false,
                animation: 150,
                onStart: () => { this.dragging = true; },
                onEnd: () => { this.dragging = false; },
            }));
        });

        const outline = this.$refs.outline;
        if (outline) {
            this.sortables.push(Sortable.create(outline, {
                group: { name: 'builder', pull: false, put: true },
                handle: '.pb-handle',
                draggable: '.pb-row',
                animation: 150,
                onAdd: (evt) => {
                    const type = evt.item.dataset.type;
                    const index = Array.from(outline.querySelectorAll(':scope > .pb-row, :scope > .pb-chip')).indexOf(evt.item);
                    evt.item.remove();
                    if (type) this.$wire.addSection(type, index);
                },
                onEnd: (evt) => {
                    if (evt.from !== outline || evt.oldIndex === evt.newIndex) return;
                    const order = Array.from(outline.querySelectorAll(':scope > .pb-row')).map((li) => li.dataset.uuid);
                    // Kembalikan DOM; server menentukan urutan final.
                    const ref = outline.children[evt.oldIndex + (evt.oldIndex > evt.newIndex ? 1 : 0)] ?? null;
                    outline.insertBefore(evt.item, ref);
                    this.$wire.reorder(order);
                },
            }));
        }

        const dropzone = this.$refs.dropzone;
        if (dropzone) {
            this.sortables.push(Sortable.create(dropzone, {
                group: { name: 'builder', pull: false, put: true },
                onAdd: (evt) => {
                    const type = evt.item.dataset.type;
                    evt.item.remove();
                    this.dragging = false;
                    if (type) this.$wire.addSection(type, null);
                },
            }));
        }
    },

    /** Skala iframe agar lebar device asli (1280/768/390) muat di panel tanpa mengubah breakpoint. */
    fit() {
        const stage = this.$refs.stage, scaler = this.$refs.scaler, box = this.$refs.box;
        if (!stage || !scaler) return;
        this.deviceWidth = Number(scaler.dataset.deviceWidth || 1280);
        const available = stage.clientWidth - 32;
        this.scale = Math.min(1, available / this.deviceWidth);
        scaler.style.width = this.deviceWidth + 'px';
        scaler.style.transform = `scale(${this.scale})`;
        box.style.width = (this.deviceWidth * this.scale) + 'px';
        box.style.height = (scaler.offsetHeight * this.scale) + 'px';
    },

    highlight(uuid, scroll) {
        const frame = this.$refs.frame;
        if (!frame?.contentWindow) return;
        frame.contentWindow.postMessage({ source: 'builder-parent', type: 'highlight', uuid, scroll }, window.location.origin);
    },
});
