<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    profil: Object,
    peutModifierNom: { type: Boolean, default: true },
    peutModifierEmail: { type: Boolean, default: true },
});

const form = useForm({
    name: props.profil.name ?? "",
    email: props.profil.email ?? "",
});

const deleteForm = useForm({
    confirmation: "",
});

function submit() {
    form.put("/compte", {
        preserveScroll: true,
        onSuccess: () => form.clearErrors(),
    });
}

function supprimerCompte() {
    if (
        !confirm(
            "Cette action est irréversible. Tous vos CV et notifications seront supprimés."
        )
    ) {
        return;
    }
    deleteForm.delete("/compte", {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Compte</p>
            <h1>Mon profil</h1>
            <p>Modifiez vos coordonnées de connexion.</p>
        </div>

        <div class="card auth-card" style="max-width: 520px">
            <h2 class="card__title card__title--sm">Informations personnelles</h2>

            <form @submit.prevent="submit">
                <div v-if="profil.entreprise" class="form-group">
                    <label>Entreprise (lecture seule)</label>
                    <input
                        type="text"
                        :value="profil.entreprise"
                        disabled
                        class="input--readonly"
                    />
                    <small class="text-muted"
                        >Rattachée par l'administrateur — non modifiable
                        ici.</small
                    >
                </div>

                <div class="form-group">
                    <label>Nom complet</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        :disabled="!peutModifierNom"
                        placeholder="Saisissez votre nom complet…"
                    />
                </div>

                <div class="form-group">
                    <label>E-mail</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        :disabled="!peutModifierEmail"
                        placeholder="Saisissez votre adresse e-mail…"
                    />
                </div>

                <button
                    type="submit"
                    class="btn btn--primary"
                    :disabled="form.processing"
                >
                    Enregistrer
                </button>
            </form>

            <p class="auth-card__footer" style="margin-top: 1.5rem">
                <Link href="/compte/mot-de-passe">Changer le mot de passe</Link>
            </p>
        </div>

        <div
            class="card auth-card"
            style="max-width: 520px; margin-top: 2rem"
        >
            <h2 class="card__title card__title--sm">Supprimer mon compte</h2>
            <p class="text-muted">
                Suppression définitive de votre compte, de vos candidatures et de
                vos notifications. Cette action est irréversible.
            </p>
            <form @submit.prevent="supprimerCompte">
                <div class="form-group">
                    <label
                        >Saisissez <strong>SUPPRIMER</strong> pour
                        confirmer</label
                    >
                    <input
                        v-model="deleteForm.confirmation"
                        type="text"
                        required
                        autocomplete="off"
                        placeholder="SUPPRIMER"
                    />
                </div>
                <button
                    type="submit"
                    class="btn btn--danger"
                    :disabled="deleteForm.processing"
                >
                    Supprimer définitivement mon compte
                </button>
            </form>
        </div>
    </AppLayout>
</template>
