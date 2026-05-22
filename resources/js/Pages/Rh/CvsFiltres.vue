<script setup>
import { Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseChart from "@/Components/BaseChart.vue";
import { useCvDecision } from "@/composables/useCvDecision";
import { useToastStore } from "@/stores/toast";
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
const toast = useToastStore();
const reloadListe = () =>
    router.visit("/rh/filtrer/resultats", { preserveScroll: true });
const { peutDecider, valider, refuser, mailtoCandidat } = useCvDecision({
    lotEnAttente: true,
    afterSuccess: (cv, { valide }) => {
        reloadListe();
        if (valide) {
            setTimeout(() => {
                window.location.href = mailtoCandidat(cv);
            }, 200);
        }
    },
});
const recherche = ref("");
const filtrePoste = ref("");
const filtreStatut = ref("");
const tri = ref("matches_desc");
const selectedIds = ref([]);

watch([filtrePoste, filtreStatut], () => {
    selectedIds.value = [];
});

const cvsFiltres = computed(() => {
    const list = filtrerCvs(props.cvs, {
        recherche: recherche.value,
        filtrePoste: filtrePoste.value,
        filtreStatut: filtreStatut.value,
    });
    return [...list].sort((a, b) => trierCvs(a, b, tri.value));
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
    const params = new URLSearchParams();
    if (ids?.length) {
        ids.forEach((id) => params.append("cv_ids[]", String(id)));
    } else if (filtrePoste.value) {
        params.set("poste_id", String(filtrePoste.value));
        if (filtreStatut.value) params.set("statut", filtreStatut.value);
    } else {
        toast.error("Cochez des CV ou filtrez par poste.");
        return;
    }
    window.location.href = `${props.zipUrl}?${params.toString()}`;
}

function confirmerAnalyse() {
    const msg =
        props.nbDecisions > 0
            ? `Confirmer l'analyse et appliquer ${props.nbDecisions} décision(s) ? Les e-mails seront envoyés.`
            : "Confirmer l'analyse ? Les CV sans décision restent en cours d'examen avec leurs scores.";
    if (!confirm(msg)) return;
    confirmerForm.post("/rh/filtrer/confirmer");
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Résultats</p>
            <h1>Résultats de l'analyse</h1>
            <p>
                Mots-clés : <strong>{{ mots_cles.join(", ") }}</strong>
                <span v-if="modeAnalyse === 'non_valides'" class="badge badge--refuse" style="margin-left: 0.5rem">
                    Lot : non validés uniquement (&lt; 30 j. depuis dépôt)
                </span>
            </p>
            <p class="text-muted" style="margin-top: 0.5rem">
                Validez ou refusez chaque CV, puis confirmez pour appliquer les
                décisions et envoyer les e-mails. Les CV laissés sans décision
                restent <strong>en cours d'analyse</strong>. Modifier les
                mots-clés annule cette session sans appliquer vos choix.
            </p>
            <div class="table-actions" style="margin-top: 0.75rem">
                <Link href="/rh/cvs" class="btn btn--ghost">
                    ← Modifier les mots-clés
                </Link>
                <button
                    type="button"
                    class="btn btn--primary"
                    :disabled="confirmerForm.processing"
                    @click="confirmerAnalyse"
                >
                    Confirmer l'analyse
                    <template v-if="nbDecisions">
                        ({{ nbDecisions }} décision{{ nbDecisions > 1 ? "s" : "" }})
                    </template>
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

        <div class="card cvs-liste-toolbar">
            <h2 class="card__title card__title--sm">Recherche et filtres</h2>
            <div class="cvs-liste-toolbar__grid">
                <div class="form-group">
                    <label>Rechercher</label>
                    <input
                        v-model="recherche"
                        type="search"
                        placeholder="Nom ou e-mail…"
                    />
                </div>
                <div class="form-group">
                    <label>Poste</label>
                    <select v-model="filtrePoste">
                        <option value="">— Tous —</option>
                        <option
                            v-for="p in postes"
                            :key="p.id"
                            :value="p.id"
                        >
                            {{ p.titre }}
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select v-model="filtreStatut">
                        <option value="">— Tous —</option>
                        <option value="en_cours_analyse">En analyse</option>
                        <option value="valide">Validé</option>
                        <option value="non_valide">Non validé</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Trier par</label>
                    <select v-model="tri">
                        <option value="matches_desc">Mots-clés (élevé)</option>
                        <option value="matches_asc">Mots-clés (faible)</option>
                        <option value="score_desc">Score % (élevé)</option>
                        <option value="score_asc">Score % (faible)</option>
                        <option value="statut">Statut</option>
                        <option value="date_depot_desc">Dépôt (récent)</option>
                    </select>
                </div>
            </div>
            <div class="cvs-liste-toolbar__actions">
                <label class="cvs-liste-select-all">
                    <input
                        type="checkbox"
                        :checked="allVisibleSelected"
                        @change="toggleSelectAll"
                    />
                    Tout sélectionner ({{ cvsFiltres.length }})
                </label>
                <button
                    type="button"
                    class="btn btn--accent"
                    :disabled="!selectedIds.length"
                    @click="telechargerZip(selectedIds)"
                >
                    ZIP — {{ selectedIds.length }} sélectionné(s)
                </button>
                <button
                    type="button"
                    class="btn btn--secondary"
                    :disabled="!filtrePoste"
                    @click="telechargerZip()"
                >
                    ZIP — poste filtré
                </button>
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
                            <dd>{{ cv.poste }}</dd>
                        </div>
                        <div class="cvs-row__meta-item">
                            <dt>Score</dt>
                            <dd>
                                <span class="score-pill">{{ cv.score }}%</span>
                            </dd>
                        </div>
                        <div class="cvs-row__meta-item">
                            <dt>Mots-clés</dt>
                            <dd>
                                {{ cv.nombre_matches }} —
                                <small v-if="cv.mots_cles_matches?.length">{{
                                    cv.mots_cles_matches.join(", ")
                                }}</small>
                            </dd>
                        </div>
                        <div class="cvs-row__meta-item">
                            <dt>Dépôt</dt>
                            <dd>{{ cv.date_depot }}</dd>
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
