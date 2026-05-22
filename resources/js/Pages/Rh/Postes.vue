<script setup>
import { router, useForm } from "@inertiajs/vue3";
import { reactive, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    postes: Array,
    entreprise: Object,
    rhColleguesCount: { type: Number, default: 1 },
});

const form = useForm({
    titre: "",
    description: "",
    est_ouvert: true,
});

const entrepriseForm = useForm({
    description: props.entreprise?.description ?? "",
});

const drafts = reactive({});

watch(
    () => props.postes,
    (postes) => {
        postes?.forEach((p) => {
            drafts[p.id] = {
                titre: p.titre,
                description: p.description ?? "",
            };
        });
    },
    { immediate: true }
);

function savePoste(poste) {
    router.put(`/rh/postes/${poste.id}`, drafts[poste.id], {
        preserveScroll: true,
    });
}

function toggleOuvert(poste) {
    router.patch(`/rh/postes/${poste.id}/ouvert`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Sub-admin</p>
            <h1>Postes & présentation</h1>
            <p v-if="entreprise">
                Entreprise : <strong>{{ entreprise.nom }}</strong> — visible
                sur la page de dépôt candidat.
            </p>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">
                Présentation de l'entreprise
            </h2>
            <p class="card__lead">
                Les candidats lisent ce texte avant de choisir un poste.
            </p>
            <div v-if="rhColleguesCount > 1" class="hint-box">
                <strong>Description partagée</strong>
                <p style="margin: 0.35rem 0 0">
                    {{ rhColleguesCount }} comptes RH sont rattachés à cette
                    entreprise. Toute modification remplace le texte visible
                    par les candidats pour <em>toute</em> la société.
                </p>
                <p
                    v-if="entreprise?.description_updated_by"
                    style="margin: 0.5rem 0 0; font-size: 0.85rem"
                >
                    Dernière mise à jour par
                    <strong>{{ entreprise.description_updated_by }}</strong>
                    <template v-if="entreprise.description_updated_at">
                        le {{ entreprise.description_updated_at }}
                    </template>
                </p>
            </div>
            <form @submit.prevent="entrepriseForm.put('/rh/entreprise')">
                <div
                    v-if="entrepriseForm.errors.description"
                    class="alert alert--error"
                >
                    {{ entrepriseForm.errors.description }}
                </div>
                <div class="form-group">
                    <label>Description de l'entreprise</label>
                    <textarea
                        v-model="entrepriseForm.description"
                        rows="5"
                        placeholder="Activité, valeurs, lieu de travail…"
                    />
                </div>
                <button
                    type="submit"
                    class="btn btn--primary"
                    :disabled="entrepriseForm.processing"
                >
                    Enregistrer
                </button>
            </form>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">Nouveau poste</h2>
            <form @submit.prevent="form.post('/rh/postes')">
                <div
                    v-if="Object.keys(form.errors).length"
                    class="alert alert--error"
                >
                    <ul style="margin: 0; padding-left: 1.2rem">
                        <li v-for="(msg, key) in form.errors" :key="key">
                            {{ msg }}
                        </li>
                    </ul>
                </div>
                <div class="form-group">
                    <label>Titre</label>
                    <input v-model="form.titre" type="text" required />
                </div>
                <div class="form-group">
                    <label>Description détaillée</label>
                    <textarea v-model="form.description" rows="4" />
                </div>
                <label class="form-check">
                    <input v-model="form.est_ouvert" type="checkbox" />
                    Poste ouvert aux candidatures
                </label>
                <button
                    type="submit"
                    class="btn btn--primary"
                    :disabled="form.processing"
                >
                    Créer le poste
                </button>
            </form>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">Postes existants</h2>
            <p v-if="!postes?.length" class="text-muted">Aucun poste.</p>
            <div
                v-for="p in postes"
                :key="p.id"
                class="poste-edit-block"
            >
                <form v-if="drafts[p.id]" @submit.prevent="savePoste(p)">
                    <div class="form-group">
                        <label>Titre</label>
                        <input
                            v-model="drafts[p.id].titre"
                            type="text"
                            required
                        />
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea
                            v-model="drafts[p.id].description"
                            rows="3"
                        />
                    </div>
                    <div class="poste-edit-block__actions">
                        <button type="submit" class="btn btn--accent btn--sm">
                            Enregistrer
                        </button>
                        <button
                            type="button"
                            class="toggle-switch"
                            :class="{ 'toggle-switch--on': p.est_ouvert }"
                            @click="toggleOuvert(p)"
                        >
                            <span class="toggle-switch__thumb" />
                        </button>
                        <span class="toggle-switch__label">
                            {{ p.est_ouvert ? "Ouvert" : "Fermé" }}
                        </span>
                        <button
                            type="button"
                            class="btn btn--danger btn--sm"
                            @click="$inertia.delete(`/rh/postes/${p.id}`)"
                        >
                            Supprimer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
