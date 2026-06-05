<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PasswordInput from "@/Components/PasswordInput.vue";

const props = defineProps({
    portal: String,
    title: String,
    subtitle: String,
    action: String,
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});
</script>

<template>
    <AppLayout>
        <div class="auth-page">
            <div class="card auth-card">
                <h1 class="card__title">{{ title }}</h1>
                <p class="text-muted" style="margin: 0 0 1.25rem">{{ subtitle }}</p>
                <form
                    @submit.prevent="
                        form.post(action, {
                            onError: () => {
                                form.password = '';
                            },
                        })
                    "
                >
                    <div class="form-group">
                        <label>Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="Saisissez votre adresse e-mail…"
                        />
                    </div>
                    <PasswordInput
                        v-model="form.password"
                        label="Mot de passe"
                        :input-id="`portal-${portal}-password`"
                        autocomplete="current-password"
                        placeholder="Saisissez votre mot de passe…"
                        required
                    />
                    <label class="form-check">
                        <input v-model="form.remember" type="checkbox" />
                        Se souvenir de moi
                    </label>
                    <button
                        type="submit"
                        class="btn btn--primary btn--block"
                        :disabled="form.processing"
                    >
                        Se connecter
                    </button>
                </form>
                <p class="auth-card__footer">
                    <Link href="/login">Connexion candidat / RH</Link>
                </p>
            </div>
        </div>
    </AppLayout>
</template>
