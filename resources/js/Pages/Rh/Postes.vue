<script setup>
import { router, useForm } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useStaffPaths } from "@/composables/useStaffPaths";

const props = defineProps({
    postes: Array,
    entreprise: Object,
    rhColleguesCount: { type: Number, default: 1 },
    peutModifierEntreprise: { type: Boolean, default: false },
});

const paths = useStaffPaths();

const recherche = ref("");
const filtreStatut = ref("");
const tri = ref("date_desc");
const filtresOuverts = ref(false);

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

function normaliser(texte) {
    return (texte ?? "").toString().toLowerCase().trim();
}

function timestamp(poste) {
    if (poste.created_at_ts) return poste.created_at_ts;
    if (!poste.created_at) return 0;
    const t = Date.parse(poste.created_at);
    return Number.isNaN(t) ? 0 : t;
}

function trierPostes(a, b) {
    switch (tri.value) {
        case "titre_asc":
            return normaliser(a.titre).localeCompare(normaliser(b.titre), "fr");
        case "titre_desc":
            return normaliser(b.titre).localeCompare(normaliser(a.titre), "fr");
        case "date_asc":
            return timestamp(a) - timestamp(b);
        case "statut_ouvert":
            return Number(b.est_ouvert) - Number(a.est_ouvert);
        case "date_desc":
        default:
            return timestamp(b) - timestamp(a);
    }
}

const postesFiltres = computed(() => {
    const q = normaliser(recherche.value);
    let list = (props.postes || []).filter((p) => {
        if (filtreStatut.value === "ouvert" && !p.est_ouvert) return false;
        if (filtreStatut.value === "ferme" && p.est_ouvert) return false;
        if (!q) return true;
        return (
            normaliser(p.titre).includes(q) ||
            normaliser(p.description).includes(q)
        );
    });
    return [...list].sort(trierPostes);
});

function savePoste(poste) {
    router.put(`/rh/postes/${poste.id}`, drafts[poste.id], {
        preserveScroll: true,
    });
}

function toggleOuvert(poste) {
    router.patch(`/rh/postes/${poste.id}/ouvert`, {}, { preserveScroll: true });
}

function supprimerPoste(poste) {
    router.delete(`/rh/postes/${poste.id}`, { preserveScroll: true });
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

        <div v-if="entreprise" class="card">
            <h2 class="card__title card__title--sm">
                Présentation de l'entreprise
            </h2>
            <p v-if="!peutModifierEntreprise" class="card__lead">
                Lecture seule — seul le gérant modifie ce texte (back-office
                gérant).
            </p>
            <p v-else class="card__lead">
                Les candidats lisent ce texte avant de choisir un poste.
            </p>
            <div
                v-if="peutModifierEntreprise && rhColleguesCount > 1"
                class="hint-box"
            >
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
            <form
                v-if="peutModifierEntreprise"
                @submit.prevent="entrepriseForm.put(paths.gerantEntreprise)"
            >
                <div class="form-group">
                    <label>Description de l'entreprise</label>
                    <textarea
                        v-model="entrepriseForm.description"
                        rows="5"
                        placeholder="Décrivez l'activité, les valeurs ou le lieu de travail…"
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
            <p v-else style="white-space: pre-wrap; margin: 0">
                {{ entreprise.description || "—" }}
            </p>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">Nouveau poste</h2>
            <form @submit.prevent="form.post('/rh/postes')">
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

            <div
                class="cvs-liste-toolbar cvs-liste-toolbar--wide cvs-liste-toolbar--collapsible postes-toolbar"
            >
                <div
                    class="cvs-liste-toolbar__filters-wrap cvs-liste-toolbar__filters-wrap--postes"
                >
                    <div class="cvs-liste-toolbar__primary">
                        <div class="form-group cvs-liste-toolbar__field">
                            <label>Rechercher</label>
                            <input
                                v-model="recherche"
                                type="search"
                                placeholder="Saisissez un titre ou une description…"
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn btn--secondary btn--sm cvs-liste-toolbar__toggle-filters"
                        :aria-expanded="filtresOuverts"
                        @click="filtresOuverts = !filtresOuverts"
                    >
                        {{
                            filtresOuverts
                                ? "Masquer les filtres"
                                : "Filtres et tri"
                        }}
                    </button>
                    <div
                        class="cvs-liste-toolbar__more cvs-liste-toolbar__grid cvs-liste-toolbar__grid--filters-postes"
                        :class="{
                            'cvs-liste-toolbar__grid--collapsed': !filtresOuverts,
                        }"
                    >
                        <div class="form-group cvs-liste-toolbar__field">
                            <label>Statut</label>
                            <select v-model="filtreStatut">
                                <option value="">— Tous —</option>
                                <option value="ouvert">Ouvert aux candidatures</option>
                                <option value="ferme">Fermé</option>
                            </select>
                        </div>
                        <div class="form-group cvs-liste-toolbar__field">
                            <label>Trier par</label>
                            <select
                                v-model="tri"
                                class="cvs-liste-toolbar__select--wide"
                            >
                                <option value="date_desc">
                                    Date de création (récent)
                                </option>
                                <option value="date_asc">
                                    Date de création (ancien)
                                </option>
                                <option value="titre_asc">Titre (A → Z)</option>
                                <option value="titre_desc">Titre (Z → A)</option>
                                <option value="statut_ouvert">
                                    Statut (ouverts en haut)
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <p class="cvs-liste-toolbar__hints postes-toolbar__count text-muted">
                    {{ postesFiltres.length }} poste(s) affiché(s)
                    <template v-if="postes?.length">
                        sur {{ postes.length }}
                    </template>
                </p>
            </div>

            <p v-if="!postes?.length" class="text-muted">Aucun poste.</p>
            <p
                v-else-if="!postesFiltres.length"
                class="text-muted"
            >
                Aucun poste ne correspond à votre recherche.
            </p>
            <div
                v-for="p in postesFiltres"
                :key="p.id"
                class="poste-edit-block"
            >
                <div class="poste-edit-block__head">
                    <span
                        :class="[
                            'badge',
                            p.est_ouvert ? 'badge--valide' : 'badge--refuse',
                        ]"
                    >
                        {{ p.est_ouvert ? "Ouvert" : "Fermé" }}
                    </span>
                    <span
                        v-if="p.created_at"
                        class="text-muted poste-edit-block__date"
                    >
                        Créé le {{ p.created_at }}
                    </span>
                </div>
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
                            @click="supprimerPoste(p)"
                        >
                            Supprimer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
