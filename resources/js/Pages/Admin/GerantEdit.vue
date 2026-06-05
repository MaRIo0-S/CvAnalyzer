<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useStaffPaths } from "@/composables/useStaffPaths";

const paths = useStaffPaths();
import PasswordInput from "@/Components/PasswordInput.vue";
import SearchSelect from "@/Components/SearchSelect.vue";
import { computed, ref, watch } from "vue";

const props = defineProps({
    gerant: Object,
    entreprises: Array,
});

const entrepriseId = ref(props.gerant.entreprise || "");

const entrepriseItems = computed(() =>
    (props.entreprises || []).map((e) => ({ id: e.nom, label: e.nom }))
);

const form = useForm({
    name: props.gerant.name,
    email: props.gerant.email,
    telephone: props.gerant.telephone || "",
    entreprise_nom: props.gerant.entreprise || "",
    password: "",
    password_confirmation: "",
});

watch(entrepriseId, (id) => {
    if (id) form.entreprise_nom = String(id);
});

function submit() {
    form.put(`${paths.value.adminGerants}/${props.gerant.id}`);
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Administration</p>
            <h1>Modifier le gérant</h1>
            <p>{{ gerant.name }} — {{ gerant.entreprise }}</p>
        </div>

        <p class="offres-back-link">
            <Link :href="paths.adminGerants">← Retour aux gérants</Link>
        </p>

        <div class="card">
            <form @submit.prevent="submit">
                <div class="form-group">
                    <label>Nom complet</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="Saisissez le nom du gérant…"
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
                <SearchSelect
                    v-model="entrepriseId"
                    :items="entrepriseItems"
                    label="Entreprise gérée"
                    placeholder="Saisissez ou sélectionnez une entreprise…"
                />
                <div v-if="!entrepriseId" class="form-group">
                    <label>Nouvelle entreprise</label>
                    <input
                        v-model="form.entreprise_nom"
                        type="text"
                        required
                        placeholder="Saisissez le nom de la nouvelle entreprise…"
                    />
                </div>
                <PasswordInput
                    v-model="form.password"
                    label="Nouveau mot de passe (optionnel)"
                    input-id="edit-gerant-pwd"
                    placeholder="Laissez vide pour conserver l'actuel…"
                />
                <PasswordInput
                    v-model="form.password_confirmation"
                    label="Confirmer le mot de passe"
                    input-id="edit-gerant-pwd2"
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
                    <Link
                        :href="paths.adminGerants"
                        class="btn btn--secondary"
                        >Annuler</Link
                    >
                </div>
            </form>
        </div>
    </AppLayout>
</template>
