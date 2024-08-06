import axios from 'axios';
import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.data('registerPasskey', () => ({
        token: "2|kYGPxERFVdek52F2rU9z28fR2iAeguvZtSd1LApu62204efa",
        async register() {
            const options = await axios.get('api/passkeys/register', {
                headers: {
                   Authorization: 'Bearer ' + this.token
                }
             });
            console.log(options);
        }
    }));
});

Alpine.start();
