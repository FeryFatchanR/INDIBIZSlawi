import Alpine from 'alpinejs';

// Website publik tidak memakai Livewire; Alpine hanya untuk interaksi ringan
// (menu mobile, accordion FAQ, dropdown). Halaman admin memakai Alpine bawaan Livewire.
if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
}
