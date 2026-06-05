<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    email: String,
});

const form = useForm({
    code: "",
});
</script>

<template>
    <AppLayout>
        <div class="auth-page">
            <div class="card auth-card">
                <h1 class="card__title">Confirmer l'inscription</h1>
                <p class="card__lead">
                    Saisissez le code à 6 chiffres envoyé à
                    <strong>{{ email }}</strong>.
                </p>

                <form @submit.prevent="form.post('/inscription/verification')">
                    <div class="form-group">
                        <label>Code de confirmation</label>
                        <input
                            v-model="form.code"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            required
                            autocomplete="one-time-code"
                            placeholder="Saisissez le code à 6 chiffres…"
                            class="code-input"
                        />
                    </div>
                    <button
                        type="submit"
                        class="btn btn--primary btn--block"
                        :disabled="form.processing"
                    >
                        Valider et créer mon compte
                    </button>
                </form>
                <p class="auth-card__footer">
                    <Link href="/inscription">Recommencer l'inscription</Link>
                </p>
            </div>
        </div>
    </AppLayout>
</template>
