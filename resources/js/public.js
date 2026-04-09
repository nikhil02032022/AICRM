import './bootstrap';

// Alpine.js is NOT started by Livewire on public (unauthenticated) pages.
// This entry point starts Alpine independently for public web enquiry forms.
// Do NOT use this bundle on any page that also loads Livewire.
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
