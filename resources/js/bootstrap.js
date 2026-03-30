import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

try {
    const session = JSON.parse(localStorage.getItem('wf_session') || 'null')
    const token = session?.token
    if (token) {
        window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    }
} catch {
    // ignore
}
