<script setup>
import { Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseChart from "@/Components/BaseChart.vue";
import { useCvDecision } from "@/composables/useCvDecision";
import { useToastStore } from "@/stores/toast";
import { telechargerZipCv } from "@/composables/useZipDownload";
import { badgeClassFromCv, filtrerCvs, trierCvs } from "@/utils/cvList";

const props = defineProps({
    cvs: { type: Array, default: () => [] },
    mots_cles: { type: Array, default: () => [] },
    chartClassement: { type: Array, default: () => [] },
    postes: { type: Array, default: () => [] },
    zipUrl: String,
    modeAnalyse: { type: String, default: "standard" },
    nbDecisions: { type: Number, default: 0 },
});

const confirmerForm = useForm({});
const annulerForm = useForm({});
const toast = useToastStore();
const reloadListe = () =>
    router.visit("/rh/filtrer/resultats", { preserveScroll: true });
const { peutDecider, aDecisionProvisoire, annulerDecision, valider, refuser } =
    useCvDecision({
        lotEnAttente: true,
        afterSuccess: () => reloadListe(),
    });
const recherche = ref("");
const filtreMotCle = ref("");
const filtrePoste = ref("");
const filtreStatut = ref("");
const tri = ref("matches_desc");
const filtresOuverts = ref(false);
const selectedIds = ref([]);

watch([filtrePoste, filtreStatut, filtreMotCle], () => {
    selectedIds.value = [];
});

const cvsFiltres = computed(() => {
    const list = filtrerCvs(props.cvs, {
        recherche: recherche.value,
        filtrePoste: filtrePoste.value,
        filtreStatut: filtreStatut.value,
        filtreMotCle: filtreMotCle.value,
    });
    return [...list].sort((a, b) =>
        trierCvs(a, b, tri.value, { nullSafeScores: true }),
    );
});

const allVisibleSelected = computed(() => {
    if (!cvsFiltres.value.length) return false;
    return cvsFiltres.value.every((cv) => selectedIds.value.includes(cv.id));
});

const matchesChart = computed(() => ({
    labels: props.chartClassement.map((c) => c.label),
    datasets: [
        {
            label: "Mots-clés trouvés",
            data: props.chartClassement.map((c) => c.matches),
            backgroundColor: "rgba(99, 102, 241, 0.8)",
            borderRadius: 6,
        },
    ],
}));

const scoresChart = computed(() => ({
    labels: props.chartClassement.map((c) => c.label),
    datasets: [
        {
            label: "Score (%)",
            data: props.chartClassement.map((c) => c.score),
            borderColor: "rgba(34, 211, 238, 1)",
            backgroundColor: "rgba(34, 211, 238, 0.15)",
            fill: true,
            tension: 0.3,
        },
    ],
}));

function toggleSelect(cvId) {
    const i = selectedIds.value.indexOf(cvId);
    if (i >= 0) selectedIds.value.splice(i, 1);
    else selectedIds.value.push(cvId);
}

function toggleSelectAll() {
    if (allVisibleSelected.value) selectedIds.value = [];
    else selectedIds.value = cvsFiltres.value.map((cv) => cv.id);
}

function telechargerZip(ids) {
    if (!telechargerZipCv(props.zipUrl, ids)) {
        toast.error("Cochez au moins un CV (ou « Tout sélectionner »).");
    }
}

function confirmerAnalyse() {
    const msg =
        props.nbDecisions > 0
            ? `Confirmer l'analyse et appliquer ${props.nbDecisions} décision(s) ? Les e-mails seront envoyés.`
            : "Confirmer l'analyse ? Les CV sans décision passent en cours d'examen (e-mails envoyés).";
    if (!confirm(msg)) return;
    confirmerForm.post("/rh/filtrer/confirmer");
}

function annulerAnalyse() {
    if (
        !confirm(
            "Effacer cette analyse ? Les scores provisoires seront supprimés, sans modifier les statuts ni envoyer d'e-mails.",
        )
    ) {
        return;
    }
    annulerForm.post("/rh/filtrer/annuler");
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Résultats</p>
            <h1>Résultats de l'analyse</h1>
            <p>
                Mots-clés : <strong>{{ mots_cles.join(", ") }}</strong>
                <span
                    v-if="modeAnalyse === 'non_valides'"
                    class="badge badge--refuse"
                    style="margin-left: 0.5rem"
                >
                    Lot : non validés uniquement (&lt; 30 j. depuis dépôt)
                </span>
            </p>
            <p class="text-muted" style="margin-top: 0.5rem">
                Ici, les CV apparaissent
                <strong>en cours d'analyse</strong> pour travailler sur le lot.
                Validez ou refusez, puis confirmez : seule la confirmation
                applique les statuts définitifs et envoie les e-mails (la liste
                des CV reçus et la page candidat restent inchangées avant cela).
            </p>
            <div class="table-actions" style="margin-top: 0.75rem">
                <Link href="/rh/cvs" class="btn btn--ghost">
                    ← Modifier les mots-clés
                </Link>
                <button
                    type="button"
                    class="btn btn--primary"
                    :disabled="
                        confirmerForm.processing || annulerForm.processing
                    "
                    @click="confirmerAnalyse"
                >
                    Confirmer l'analyse
                    <template v-if="nbDecisions">
                        ({{ nbDecisions }} décision{{
                            nbDecisions > 1 ? "s" : ""
                        }})
                    </template>
                </button>
                <button
                    type="button"
                    class="btn btn--ghost btn--danger"
                    :disabled="
                        confirmerForm.processing || annulerForm.processing
                    "
                    @click="annulerAnalyse"
                >
                    Effacer cette analyse
                </button>
            </div>
        </div>

        <div v-if="chartClassement.length" class="charts-grid">
            <div class="chart-card">
                <h3 class="chart-card__title">Mots-clés trouvés par CV</h3>
                <BaseChart
                    type="bar"
                    :data="matchesChart"
                    :height="300"
                    :options="{
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                    }"
                />
            </div>
            <div class="chart-card">
                <h3 class="chart-card__title">Score de pertinence (%)</h3>
                <BaseChart type="line" :data="scoresChart" :height="300" />
            </div>
        </div>

        <div
            class="card cvs-liste-toolbar cvs-liste-toolbar--wide cvs-liste-toolbar--collapsible"
        >
            <h2 class="card__title card__title--sm">Filtres et tri</h2>
            <div
                class="cvs-liste-toolbar__filters-wrap cvs-liste-toolbar__filters-wrap--analyse"
            >
                <div class="cvs-liste-toolbar__primary">
                    <div class="form-group cvs-liste-toolbar__field">
                        <label>Rechercher</label>
                        <input
                            v-model="recherche"
                            type="search"
                            placeholder="Saisissez un nom ou un e-mail…"
                        />
                    </div>
                    <div class="form-group cvs-liste-toolbar__field">
                        <label>Mot-clé trouvé</label>
                        <input
                            v-model="filtreMotCle"
                            type="search"
                            placeholder="Saisissez un mot-clé (ex. Laravel, Vue)…"
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
                    class="cvs-liste-toolbar__more cvs-liste-toolbar__grid cvs-liste-toolbar__grid--filters-analyse"
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
                        <label>Statut affiché</label>
                        <select v-model="filtreStatut">
                            <option value="">— Tous —</option>
                            <option value="en_cours_analyse">
                                En cours d'analyse
                            </option>
                            <option value="valide">
                                Validé (à confirmer inclus)
                            </option>
                            <option value="non_valide">
                                Refusé (à confirmer inclus)
                            </option>
                        </select>
                    </div>
                    <div class="form-group cvs-liste-toolbar__field">
                        <label>Trier par</label>
                        <select
                            v-model="tri"
                            class="cvs-liste-toolbar__select--wide"
                        >
                            <option value="matches_desc">
                                Mots-clés (élevé)
                            </option>
                            <option value="matches_asc">
                                Mots-clés (faible)
                            </option>
                            <option value="score_desc">Score % (élevé)</option>
                            <option value="score_asc">Score % (faible)</option>
                            <option value="date_analyse_desc">
                                Date d'analyse (récent)
                            </option>
                            <option value="date_analyse_asc">
                                Date d'analyse (ancien)
                            </option>
                            <option value="statut">Statut</option>
                            <option value="date_depot_desc">
                                Date de dépôt (récent)
                            </option>
                            <option value="date_depot_asc">
                                Date de dépôt (ancien)
                            </option>
                        </select>
                    </div>
                </div>
            </div>
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
                Aucun CV analysé pour ces critères.
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
                        @change="toggleSelect(cv.id)"
                    />
                </div>
                <div class="cvs-row__body">
                    <div class="cvs-row__head">
                        <div>
                            <h3 class="cvs-row__name">{{ cv.nom_candidat }}</h3>
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
                        :href="`/rh/cvs/${cv.id}/consulter?depuis=analyse`"
                        class="btn btn--primary btn--sm"
                    >
                        Consulter
                    </Link>
                    <div
                        v-if="peutDecider(cv) || aDecisionProvisoire(cv)"
                        class="cvs-row__actions-lot"
                    >
                        <button
                            v-if="aDecisionProvisoire(cv)"
                            type="button"
                            class="btn btn--ghost btn--sm"
                            @click="annulerDecision(cv)"
                        >
                            Annuler la décision
                        </button>
                        <button
                            v-if="
                                peutDecider(cv) ||
                                cv.decision_provisoire !== 'valide'
                            "
                            type="button"
                            class="btn btn--success btn--sm"
                            @click="valider(cv)"
                        >
                            Valider
                        </button>
                        <button
                            v-if="
                                peutDecider(cv) ||
                                cv.decision_provisoire !== 'non_valide'
                            "
                            type="button"
                            class="btn btn--danger btn--sm"
                            @click="refuser(cv)"
                        >
                            Refuser
                        </button>
                    </div>
                </div>
            </article>
        </div>
    </AppLayout>
</template>
