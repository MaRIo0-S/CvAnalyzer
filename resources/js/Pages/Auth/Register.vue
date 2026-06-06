<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PasswordInput from "@/Components/PasswordInput.vue";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});
</script>

<template>
    <AppLayout>
        <div class="auth-page">
            <div class="card auth-card">
                <h1 class="card__title">Créer un compte candidat</h1>
                <p class="card__lead">
                    Un code à 6 chiffres vous sera envoyé par e-mail pour
                    confirmer la création du compte.
                </p>

                <div class="hint-box">
                    L'inscription sert uniquement au <strong>suivi</strong> et
                    aux <strong>notifications</strong>. Pour postuler, utilisez
                    <Link href="/offres">Voir les offres</Link> : vos coordonnées
                    y seront enregistrées pour que le recruteur puisse vous
                    contacter.
                </div>

                <form @submit.prevent="form.post('/inscription')">
                    <div class="form-group">
                        <label>Nom complet</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Saisissez votre nom complet…"
                        />
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="Saisissez votre adresse e-mail…"
                        />
                        <p v-if="form.errors.email" class="form-error">
                            {{ form.errors.email }}
                        </p>
                    </div>
                    <PasswordInput
                        v-model="form.password"
                        label="Mot de passe"
                        input-id="register-password"
                        autocomplete="new-password"
                        required
                    />
                    <small style="display: block; margin-top: 0.35rem"
                        >Au moins 8 caractères.</small
                    >
                    <PasswordInput
                        v-model="form.password_confirmation"
                        label="Confirmer le mot de passe"
                        input-id="register-password-2"
                        autocomplete="new-password"
                        placeholder="Confirmez votre mot de passe…"
                        required
                    />
                    <button
                        type="submit"
                        class="btn btn--primary btn--block"
                        :disabled="form.processing"
                    >
                        S'inscrire
                    </button>
                </form>
                <p class="auth-card__footer">
                    Déjà un compte ? <Link href="/login">Se connecter</Link>
                </p>
            </div>
        </div>
    </AppLayout>
</template>
