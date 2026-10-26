import './bootstrap';
import axios from 'axios';
import datePicker from './date-picker';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('datePicker', datePicker);
});
