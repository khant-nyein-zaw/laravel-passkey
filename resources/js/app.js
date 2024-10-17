import axios from "axios";
import {
    startRegistration,
    browserSupportsWebAuthn,
} from "@simplewebauthn/browser";
import "./bootstrap";

import Alpine from "alpinejs";

window.Alpine = Alpine;

document.addEventListener("alpine:init", () => {
    Alpine.data("registerPasskey", () => ({
        name: "",
        errors: null,
        browserSupportsWebAuthn,
        async register(form) {
            console.log("name", this.name);

            this.errors = null;

            if (!this.browserSupportsWebAuthn()) {
                return;
            }

            const options = await axios.get("api/passkeys/register", {
                params: { name: this.name },
                validateStatus: (status) => [200, 422].includes(status),
            });

            if (options.status == 422) {
                this.errors = options.data.errors;
                return;
            }

            let attResp;
            try {
                attResp = await startRegistration(options.data);
                // console.log(attResp);
            } catch (e) {
                this.errors = {
                    name: ["Passkey creation failed. Please try again"],
                };
                return;
            }

            form.addEventListener("formdata", ({ formData }) => {
                formData.set("passkey", JSON.stringify(attResp));
            });

            form.submit();
        },
    }));
});

Alpine.start();
