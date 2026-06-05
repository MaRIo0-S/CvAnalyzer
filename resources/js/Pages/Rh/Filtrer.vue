<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import SearchSelect from "@/Components/SearchSelect.vue";
import { useToastStore } from "@/stores/toast";

const props = defineProps({
    postes: { type: Array, default: () => [] },
    entreprise: String,
    hasDerniereAnalyse: { type: Boolean, default: false },
});

const toast = useToastStore();
const motsCles = ref([]);
const inputMot = ref("");

const form = useForm({
    poste_id: "",
    mots_cles: [],
    inclure_non_valides: false,
});

const annulerForm = useForm({});

const posteItems = computed(() =>
    (props.postes || []).map((p) => ({ id: p.id, label: p.titre }))
);

function annulerAnalyseEnCours() {
    if (
        !confirm(
            "Effacer l'analyse en cours ? Aucun statut ni e-mail ne sera modifié."
        )
    ) {
        return;
    }
    annulerForm.post("/rh/filtrer/annuler");
}

function ajouterMot() {
    const mot = inputMot.value.trim().toLowerCase();
    if (mot && !motsCles.value.includes(mot)) {
        motsCles.value.push(mot);
    }
    inputMot.value = "";
}

function supprimerMot(index) {
    motsCles.value.splice(index, 1);
}

function lancerFiltrage() {
    if (motsCles.value.length === 0) {
        toast.error(
            "Ajoutez au moins un mot-clé (bouton « Ajouter ») avant de lancer l'analyse."
        );
        return;
    }

    if (
        props.hasDerniereAnalyse &&
        !confirm(
            "Une analyse est en cours. Relancer avec de nouveaux mots-clés annulera les décisions non confirmées. Continuer ?"
        )
    ) {
        return;
    }

    form.mots_cles = [...motsCles.value];
    form.post("/rh/filtrer", {
        onSuccess: () => {
            motsCles.value = [];
            inputMot.value = "";
        },
    });
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Analyse</p>
            <h1>Analyse par mots-clés</h1>
            <p v-if="entreprise">
                Entreprise : <strong>{{ entreprise }}</strong>
            </p>
            <p class="text-muted" style="margin-top: 0.5rem">
                <strong>Deux analyses distinctes :</strong> soit les CV reçus
                (après 24 h) et en cours d'analyse, soit
                <strong>uniquement</strong> les CV non validés — jamais les deux
                en même temps. Les CV validés ne sont jamais réanalysés. Les CV
                non validés dont le <strong>dépôt date de plus de 30 jours</strong>
                ne peuvent pas être réanalysés.
            </p>
        </div>
        <div class="card">
            <h2 class="card__title card__title--sm">Critères de recherche</h2>

            <div
                v-if="hasDerniereAnalyse"
                class="alert alert--info"
                style="margin-bottom: 1rem"
            >
                Une analyse est en attente de confirmation.
                <Link
                    href="/rh/filtrer/resultats"
                    class="btn btn--ghost btn--sm"
                    style="margin-left: 0.35rem"
                >
                    Reprendre les résultats
                </Link>
                <button
                    type="button"
                    class="btn btn--ghost btn--sm btn--danger"
                    style="margin-left: 0.35rem"
                    :disabled="annulerForm.processing"
                    @click="annulerAnalyseEnCours"
                >
                    Effacer l'analyse
                </button>
            </div>

            <div class="keywords-bar">
                <p class="keywords-bar__label">Mots-clés sélectionnés</p>
                <div v-if="motsCles.length" class="keywords-bar__tags">
                    <span
                        v-for="(mot, i) in motsCles"
                        :key="`${mot}-${i}`"
                        class="keywords-bar__tag"
                    >
                        <span class="keywords-bar__tag-text">{{ mot }}</span>
                        <button
                            type="button"
                            class="keywords-bar__tag-remove"
                            aria-label="Retirer"
                            @click="supprimerMot(i)"
                        >
                            ×
                        </button>
                    </span>
                </div>
                <p v-else class="keywords-bar__empty">
                    Aucun mot-clé — ajoutez-en ci-dessous.
                </p>
            </div>

            <div class="keywords-bar__input-row">
                <input
                    v-model="inputMot"
                    type="text"
                    placeholder="Saisissez un mot-clé puis appuyez sur Entrée…"
                    @keyup.enter.prevent="ajouterMot"
                />
                <button type="button" class="btn btn--ghost" @click="ajouterMot">
                    Ajouter
                </button>
            </div>

            <SearchSelect
                v-model="form.poste_id"
                :items="posteItems"
                label="Filtrer par poste (optionnel)"
                placeholder="Saisissez ou sélectionnez un poste…"
            />

            <label class="form-check" style="margin: 1rem 0">
                <input v-model="form.inclure_non_valides" type="checkbox" />
                <span>
                    <strong>Analyser uniquement les CV non validés</strong>
                    (déposés il y a <strong>moins de 30 jours</strong> depuis la
                    date de dépôt) — analyse séparée, sans mélange avec les autres
                    statuts
                </span>
            </label>
            <p
                v-if="form.inclure_non_valides"
                class="form-hint form-hint--lot-non-valides"
            >
                Seuls les CV au statut « non validé » entrent dans ce lot. Au-delà
                de 30 jours après le dépôt, ils ne sont plus éligibles.
            </p>

            <div class="filtrer-actions">
                <button
                    type="button"
                    class="btn btn--primary"
                    :disabled="form.processing"
                    @click="lancerFiltrage"
                >
                    Analyser et trier les CVs
                </button>
            </div>
        </div>
    </AppLayout>
</template>
