<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PasswordInput from "@/Components/PasswordInput.vue";

const isCandidat = computed(() => usePage().props.auth?.user?.role === "candidat");

const form = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

function submit() {
    form.put("/compte/mot-de-passe", {
        preserveScroll: true,
        onSuccess: () =>
            form.reset("current_password", "password", "password_confirmation"),
        onError: () => {
            form.password = "";
            form.password_confirmation = "";
        },
    });
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Compte</p>
            <h1>Changer le mot de passe</h1>
            <p>Au moins 8 caractères pour le nouveau mot de passe.</p>
        </div>

        <div class="card auth-card" style="max-width: 480px">
            <form @submit.prevent="submit">
                <PasswordInput
                    v-model="form.current_password"
                    label="Mot de passe actuel"
                    input-id="pwd-current"
                    autocomplete="current-password"
                    placeholder="Saisissez votre mot de passe actuel…"
                    required
                />
                <PasswordInput
                    v-model="form.password"
                    label="Nouveau mot de passe"
                    input-id="pwd-new"
                    autocomplete="new-password"
                    placeholder="Choisissez un nouveau mot de passe…"
                    required
                />
                <PasswordInput
                    v-model="form.password_confirmation"
                    label="Confirmer le mot de passe"
                    input-id="pwd-new-2"
                    autocomplete="new-password"
                    placeholder="Confirmez le nouveau mot de passe…"
                    required
                />
                <div class="form-actions" style="margin-top: 0.5rem">
                    <button
                        type="submit"
                        class="btn btn--primary"
                        :disabled="form.processing"
                    >
                        Enregistrer
                    </button>
                    <Link
                        v-if="isCandidat"
                        href="/compte"
                        class="btn btn--ghost"
                        >← Retour au profil</Link
                    >
                </div>
            </form>
        </div>
    </AppLayout>
</template>
