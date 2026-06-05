<script setup>
import { ref } from "vue";

defineProps({
    modelValue: { type: String, default: "" },
    label: { type: String, required: true },
    autocomplete: { type: String, default: "current-password" },
    required: { type: Boolean, default: false },
    inputId: { type: String, required: true },
    placeholder: { type: String, default: "" },
});

defineEmits(["update:modelValue"]);

const visible = ref(false);
</script>

<template>
    <div class="form-group password-input">
        <label :for="inputId">{{ label }}</label>
        <div class="password-input__row">
            <input
                :id="inputId"
                :type="visible ? 'text' : 'password'"
                :value="modelValue"
                :autocomplete="autocomplete"
                :required="required"
                :placeholder="placeholder"
                class="password-input__field"
                @input="
                    $emit('update:modelValue', $event.target.value)
                "
            />
            <button
                type="button"
                class="btn btn--ghost password-input__eye"
                :aria-label="visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                :aria-pressed="visible"
                @click="visible = !visible"
            >
                <!-- œil ouvert : mot de passe masqué -->
                <svg
                    v-show="!visible"
                    class="password-input__icon"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                <!-- œil barré : mot de passe visible -->
                <svg
                    v-show="visible"
                    class="password-input__icon"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path
                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a21.52 21.52 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"
                    />
                    <line x1="1" y1="1" x2="23" y2="23" />
                </svg>
            </button>
        </div>
    </div>
</template>
