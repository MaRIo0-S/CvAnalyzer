<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PasswordInput from "@/Components/PasswordInput.vue";

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
                <h1 class="card__title">Connexion</h1>
                <form
                    @submit.prevent="
                        form.post('/login', {
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
                        input-id="login-password"
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
                    Pas encore de compte ?
                    <Link href="/inscription">S'inscrire</Link>
                </p>
            </div>
        </div>
    </AppLayout>
</template>
