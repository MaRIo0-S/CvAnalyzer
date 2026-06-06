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
                <h1 class="card__title">Confirmer le nouvel e-mail</h1>
                <p class="card__lead">
                    Saisissez le code à 6 chiffres envoyé à
                    <strong>{{ email }}</strong>.
                </p>

                <form @submit.prevent="form.post('/compte/verification-email')">
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
                            placeholder="000000"
                            class="code-input"
                        />
                    </div>
                    <button
                        type="submit"
                        class="btn btn--primary btn--block"
                        :disabled="form.processing"
                    >
                        Valider le changement
                    </button>
                </form>
                <p class="auth-card__footer">
                    <Link href="/compte">Retour au profil</Link>
                </p>
            </div>
        </div>
    </AppLayout>
</template>
