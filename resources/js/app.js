import './bootstrap';
import axios from 'axios';
import datePicker from './date-picker';
import selectBox from './select';
import themeSwitcher from './theme';
import './tooltip';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('datePicker', datePicker);
    window.Alpine.data('selectBox', selectBox);
    window.Alpine.data('themeSwitcher', themeSwitcher);
});
