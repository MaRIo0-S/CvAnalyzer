<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PasswordInput from "@/Components/PasswordInput.vue";
import { useStaffPaths } from "@/composables/useStaffPaths";

const paths = useStaffPaths();

const props = defineProps({
    entreprise: Object,
    rh: Object,
});

const form = useForm({
    name: props.rh.name,
    email: props.rh.email,
    telephone: props.rh.telephone || "",
    password: "",
    password_confirmation: "",
});

function submit() {
    form.put(`${paths.value.gerant}/rh/${props.rh.id}`);
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Gérant — {{ entreprise?.nom }}</p>
            <h1>Modifier le RH</h1>
            <p>{{ rh.name }}</p>
        </div>

        <p class="offres-back-link">
            <Link :href="paths.gerantRh">← Retour à l'équipe RH</Link>
        </p>

        <div class="card">
            <form @submit.prevent="submit">
                <div class="form-group">
                    <label>Nom</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="Saisissez le nom du RH…"
                    />
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="Saisissez l'adresse e-mail…"
                    />
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input
                        v-model="form.telephone"
                        type="tel"
                        required
                        placeholder="Saisissez le numéro de téléphone…"
                    />
                </div>
                <PasswordInput
                    v-model="form.password"
                    label="Nouveau mot de passe (optionnel)"
                    input-id="edit-rh-pwd"
                    placeholder="Laissez vide pour conserver l'actuel…"
                />
                <PasswordInput
                    v-model="form.password_confirmation"
                    label="Confirmer"
                    input-id="edit-rh-pwd2"
                    placeholder="Confirmez le nouveau mot de passe…"
                />
                <div class="table-actions">
                    <button
                        type="submit"
                        class="btn btn--primary"
                        :disabled="form.processing"
                    >
                        Enregistrer
                    </button>
                    <Link :href="paths.gerantRh" class="btn btn--secondary"
                        >Annuler</Link
                    >
                </div>
            </form>
        </div>
    </AppLayout>
</template>
