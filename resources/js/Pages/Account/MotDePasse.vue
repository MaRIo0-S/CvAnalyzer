<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";

const user = computed(() => usePage().props.auth?.user);
const aProfil = computed(
    () =>
        user.value?.role === "candidat" ||
        user.value?.role === "sous_admin"
);
import PasswordInput from "@/Components/PasswordInput.vue";

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
                    required
                />
                <PasswordInput
                    v-model="form.password"
                    label="Nouveau mot de passe"
                    input-id="pwd-new"
                    autocomplete="new-password"
                    required
                />
                <PasswordInput
                    v-model="form.password_confirmation"
                    label="Confirmer le mot de passe"
                    input-id="pwd-new-2"
                    autocomplete="new-password"
                    required
                />
                <button
                    type="submit"
                    class="btn btn--primary"
                    style="margin-top: 0.5rem"
                    :disabled="form.processing"
                >
                    Enregistrer
                </button>
            </form>
            <p v-if="aProfil" class="auth-card__footer" style="margin-top: 1.25rem">
                <Link href="/compte">Retour au profil</Link>
            </p>
        </div>
    </AppLayout>
</template>
