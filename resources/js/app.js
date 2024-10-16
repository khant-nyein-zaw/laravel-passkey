import axios from "axios";
import { startRegistration } from "@simplewebauthn/browser";
import "./bootstrap";

import Alpine from "alpinejs";

window.Alpine = Alpine;

document.addEventListener("alpine:init", () => {
    Alpine.data("registerPasskey", () => ({
        async register() {
            try {
                const options = await axios.get("api/passkeys/register");
                const passkey = await startRegistration(options.data);
                console.log(passkey);
            } catch (e) {
                throw e;
            }
        },
    }));
});

Alpine.start();
