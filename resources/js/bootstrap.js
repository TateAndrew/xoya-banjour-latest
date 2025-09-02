import axios from 'axios';
import toastr from 'toastr';
import 'toastr/build/toastr.css';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configure toastr
window.toastr = toastr;
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 5000,
    extendedTimeOut: 1000,
    preventDuplicates: true,
    newestOnTop: true
};
