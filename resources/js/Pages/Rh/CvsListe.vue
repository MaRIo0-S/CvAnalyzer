<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useCvDecision } from "@/composables/useCvDecision";
import { useToastStore } from "@/stores/toast";
import { badgeClassFromCv, filtrerCvs, trierCvs } from "@/utils/cvList";

const props = defineProps({
    cvs: Array,
    postes: Array,
    entreprise: String,
    zipUrl: String,
});

const recherche = ref("");
const filtreMotCle = ref("");
const filtrePoste = ref("");
const filtreStatut = ref("");
const filtreModification = ref("");
const tri = ref("date_depot_desc");
const filtresOuverts = ref(false);
const selectedIds = ref([]);
const toast = useToastStore();
const { peutDecider, valider, refuser } = useCvDecision();

const triAnalyseDisponible = computed(() => filtreStatut.value !== "cv_recu");

const toolbarHint = computed(() => {
    if (filtreModification.value === "encore_modifiable") {
        return "Filtre : CV reçus encore modifiables par le candidat (< 24 h).";
    }
    if (filtreModification.value === "pret_premiere_analyse") {
        return "Filtre : CV reçus dont les 24 h sont terminées — prêts pour la première analyse RH.";
    }
    if (!triAnalyseDisponible.value) {
        return "Tri par score ou mots-clés : choisissez un autre statut que « CV reçu » seul.";
    }
    return "";
});

watch(filtreStatut, (statut) => {
    if (
        statut === "cv_recu" &&
        (tri.value.includes("score") ||
            tri.value.includes("matches") ||
            tri.value.includes("date_analyse"))
    ) {
        tri.value = "date_depot_desc";
    }
    if (statut === "cv_recu") {
        filtreMotCle.value = "";
    }
});

watch([filtrePoste, filtreStatut, filtreMotCle, filtreModification], () => {
    selectedIds.value = [];
});

const cvsFiltres = computed(() => {
    const list = filtrerCvs(props.cvs, {
        recherche: recherche.value,
        filtrePoste: filtrePoste.value,
        filtreStatut: filtreStatut.value,
        filtreMotCle: filtreMotCle.value,
        filtreModification: filtreModification.value,
    });
    return [...list].sort((a, b) =>
        trierCvs(a, b, tri.value, { nullSafeScores: true }),
    );
});

const allVisibleSelected = computed(() => {
    if (!cvsFiltres.value.length) return false;
    return cvsFiltres.value.every((cv) => selectedIds.value.includes(cv.id));
});

function toggleSelect(cvId) {
    const i = selectedIds.value.indexOf(cvId);
    if (i >= 0) {
        selectedIds.value.splice(i, 1);
    } else {
        selectedIds.value.push(cvId);
    }
}

function toggleSelectAll() {
    if (allVisibleSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = cvsFiltres.value.map((cv) => cv.id);
    }
}

function telechargerZip(ids) {
    if (!ids?.length) {
        toast.error("Cochez au moins un CV (ou « Tout sélectionner »).");
        return;
    }
    const params = new URLSearchParams();
    ids.forEach((id) => params.append("cv_ids[]", String(id)));
    window.location.href = `${props.zipUrl}?${params.toString()}`;
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Sub-admin</p>
            <h1>CVs reçus</h1>
            <p v-if="entreprise">
                Entreprise : <strong>{{ entreprise }}</strong>
            </p>
        </div>

        <div
            class="card cvs-liste-toolbar cvs-liste-toolbar--wide cvs-liste-toolbar--collapsible"
        >
            <h2 class="card__title card__title--sm">Filtres et tri</h2>
            <div class="cvs-liste-toolbar__filters-wrap">
                <div class="cvs-liste-toolbar__primary">
                    <div class="form-group cvs-liste-toolbar__field">
                        <label>Rechercher</label>
                        <input
                            v-model="recherche"
                            type="search"
                            placeholder="Nom ou e-mail…"
                        />
                    </div>
                    <div class="form-group cvs-liste-toolbar__field">
                        <label
                            title="Filtre les CV dont l'analyse contient ce mot-clé (hors statut « CV reçu » seul)"
                        >
                            Mot-clé trouvé
                        </label>
                        <input
                            v-model="filtreMotCle"
                            type="search"
                            placeholder="Ex. Laravel, vue…"
                            :disabled="filtreStatut === 'cv_recu'"
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
                    class="cvs-liste-toolbar__more cvs-liste-toolbar__grid cvs-liste-toolbar__grid--filters"
                    :class="{
                        'cvs-liste-toolbar__grid--collapsed': !filtresOuverts,
                    }"
                >
                    <div class="form-group cvs-liste-toolbar__field">
                        <label>Poste</label>
                        <select v-model="filtrePoste">
                            <option value="">— Tous les postes —</option>
                            <option
                                v-for="p in postes"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.titre }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group cvs-liste-toolbar__field">
                        <label>Statut</label>
                        <select v-model="filtreStatut">
                            <option value="">— Tous —</option>
                            <option value="cv_recu">CV reçu</option>
                            <option value="en_cours_analyse">
                                En cours d'analyse
                            </option>
                            <option value="valide">Validé</option>
                            <option value="non_valide">Non validé</option>
                        </select>
                    </div>
                    <div class="form-group cvs-liste-toolbar__field">
                        <label
                            title="Période de modification 24 h après le dépôt"
                        >
                            Période 24 h
                        </label>
                        <select
                            v-model="filtreModification"
                            class="cvs-liste-toolbar__select--wide"
                        >
                            <option value="">— Tous —</option>
                            <option value="encore_modifiable">
                                Encore modifiable
                            </option>
                            <option value="pret_premiere_analyse">
                                Prêt pour analyse
                            </option>
                        </select>
                    </div>
                    <div class="form-group cvs-liste-toolbar__field">
                        <label>Trier par</label>
                        <select
                            v-model="tri"
                            class="cvs-liste-toolbar__select--wide"
                        >
                            <option value="date_depot_desc">
                                Date de dépôt (récent)
                            </option>
                            <option value="date_depot_asc">
                                Date de dépôt (ancien)
                            </option>
                            <option value="statut">Statut</option>
                            <option
                                value="score_desc"
                                :disabled="!triAnalyseDisponible"
                            >
                                Score % (élevé)
                            </option>
                            <option
                                value="score_asc"
                                :disabled="!triAnalyseDisponible"
                            >
                                Score % (faible)
                            </option>
                            <option
                                value="matches_desc"
                                :disabled="!triAnalyseDisponible"
                            >
                                Mots-clés (élevé)
                            </option>
                            <option
                                value="matches_asc"
                                :disabled="!triAnalyseDisponible"
                            >
                                Mots-clés (faible)
                            </option>
                            <option
                                value="date_analyse_desc"
                                :disabled="!triAnalyseDisponible"
                            >
                                Date d'analyse (récent)
                            </option>
                            <option
                                value="date_analyse_asc"
                                :disabled="!triAnalyseDisponible"
                            >
                                Date d'analyse (ancien)
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            <p v-if="toolbarHint" class="cvs-liste-toolbar__hints text-muted">
                {{ toolbarHint }}
            </p>
            <div class="cvs-liste-toolbar__actions">
                <label class="cvs-liste-select-all">
                    <input
                        type="checkbox"
                        :checked="allVisibleSelected"
                        @change="toggleSelectAll"
                    />
                    Tout sélectionner ({{ cvsFiltres.length }} affichés)
                </label>
                <button
                    type="button"
                    class="btn btn--accent"
                    :disabled="!selectedIds.length"
                    @click="telechargerZip(selectedIds)"
                >
                    ZIP — {{ selectedIds.length }} sélectionné(s)
                </button>
                <span class="text-muted">
                    {{ cvsFiltres.length }} CV affiché(s)
                </span>
            </div>
        </div>

        <div class="card cvs-liste-panel">
            <p v-if="!cvsFiltres.length" class="text-empty cvs-liste-empty">
                Aucun CV pour ces critères.
            </p>

            <article
                v-for="cv in cvsFiltres"
                :key="cv.id"
                class="cvs-row"
                :class="{ 'cvs-row--selected': selectedIds.includes(cv.id) }"
            >
                <div class="cvs-row__check">
                    <input
                        type="checkbox"
                        :checked="selectedIds.includes(cv.id)"
                        :aria-label="`Sélectionner ${cv.nom_candidat}`"
                        @change="toggleSelect(cv.id)"
                    />
                </div>

                <div class="cvs-row__body">
                    <div class="cvs-row__head">
                        <div>
                            <h3 class="cvs-row__name">
                                {{ cv.nom_candidat }}
                            </h3>
                            <p class="cvs-row__email">
                                {{ cv.email_candidat || "—" }}
                            </p>
                        </div>
                        <span :class="['badge', badgeClassFromCv(cv)]">
                            {{ cv.statut_label }}
                        </span>
                    </div>

                    <dl class="cvs-row__meta">
                        <div class="cvs-row__meta-item">
                            <dt>Poste</dt>
                            <dd>{{ cv.poste || "—" }}</dd>
                        </div>
                        <div class="cvs-row__meta-item">
                            <dt>Dépôt</dt>
                            <dd>{{ cv.date_depot }}</dd>
                        </div>
                        <div class="cvs-row__meta-item">
                            <dt>Score</dt>
                            <dd>
                                <span v-if="cv.score != null" class="score-pill"
                                    >{{ cv.score }}%</span
                                >
                                <span v-else class="text-muted">—</span>
                            </dd>
                        </div>
                        <div class="cvs-row__meta-item">
                            <dt>Mots-clés</dt>
                            <dd>
                                <template v-if="cv.nombre_matches != null">
                                    {{ cv.nombre_matches }} trouvé(s)
                                </template>
                                <span v-else class="text-muted">—</span>
                            </dd>
                        </div>
                        <div class="cvs-row__meta-item">
                            <dt>Analyse</dt>
                            <dd>
                                <span v-if="cv.date_analyse">{{
                                    cv.date_analyse
                                }}</span>
                                <span v-else class="text-muted">—</span>
                            </dd>
                        </div>
                        <div class="cvs-row__meta-item">
                            <dt>Format</dt>
                            <dd>{{ cv.format_fichier?.toUpperCase() }}</dd>
                        </div>
                    </dl>
                    <p
                        v-if="cv.modifiable_par_candidat"
                        class="cvs-row__hint text-muted"
                    >
                        Encore modifiable par le candidat (statut « CV reçu »,
                        moins de 24 h)
                    </p>
                </div>

                <div class="cvs-row__actions">
                    <a
                        :href="cv.download_url"
                        class="btn btn--secondary btn--sm"
                        download
                    >
                        Télécharger
                    </a>
                    <Link
                        :href="`/rh/cvs/${cv.id}/consulter`"
                        class="btn btn--primary btn--sm"
                    >
                        Consulter
                    </Link>
                    <button
                        v-if="peutDecider(cv)"
                        type="button"
                        class="btn btn--success btn--sm"
                        @click="valider(cv)"
                    >
                        Valider
                    </button>
                    <button
                        v-if="peutDecider(cv)"
                        type="button"
                        class="btn btn--danger btn--sm"
                        @click="refuser(cv)"
                    >
                        Refuser
                    </button>
                </div>
            </article>
        </div>
    </AppLayout>
</template>
