// resources/js/bootstrap.js
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Enable cookies for cross-origin requests (needed for auth cookies)
window.axios.defaults.withCredentials = true;

// Get CSRF token from meta tag if available
const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
}
