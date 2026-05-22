<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseChart from "@/Components/BaseChart.vue";

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    chartStatuts: { type: Array, default: () => [] },
    chartPostes: { type: Array, default: () => [] },
    entreprise: String,
});

const statutsChart = computed(() => ({
    labels: props.chartStatuts.map((s) => s.label),
    datasets: [
        {
            data: props.chartStatuts.map((s) => s.value),
            backgroundColor: [
                "rgba(99, 102, 241, 0.85)",
                "rgba(245, 158, 11, 0.85)",
                "rgba(16, 185, 129, 0.85)",
                "rgba(239, 68, 68, 0.85)",
            ],
            borderWidth: 0,
        },
    ],
}));

const postesChart = computed(() => ({
    labels: props.chartPostes.map((p) => p.label),
    datasets: [
        {
            label: "CV par poste",
            data: props.chartPostes.map((p) => p.value),
            backgroundColor: "rgba(79, 70, 229, 0.75)",
            borderRadius: 6,
        },
    ],
}));
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Sub-admin</p>
            <h1>Tableau de bord</h1>
            <p v-if="entreprise">
                Entreprise : <strong>{{ entreprise }}</strong> — vue d'ensemble
                des candidatures.
            </p>
            <p v-else>Vue d'ensemble des candidatures.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-card--indigo">
                <strong>{{ stats.total }}</strong>
                <span>CVs total</span>
            </div>
            <div class="stat-card stat-card--cyan">
                <strong>{{ stats.recus }}</strong>
                <span>Reçus</span>
            </div>
            <div class="stat-card stat-card--amber">
                <strong>{{ stats.analyses }}</strong>
                <span>En analyse</span>
            </div>
            <div class="stat-card stat-card--emerald">
                <strong>{{ stats.valides }}</strong>
                <span>Validés</span>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3 class="chart-card__title">Répartition par statut</h3>
                <BaseChart type="doughnut" :data="statutsChart" :height="280" />
            </div>
            <div class="chart-card">
                <h3 class="chart-card__title">CVs par poste</h3>
                <BaseChart
                    v-if="chartPostes.length"
                    type="bar"
                    :data="postesChart"
                    :height="280"
                    :options="{
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                    }"
                />
                <p v-else class="text-empty">Aucune donnée pour le moment.</p>
            </div>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">
                Analyser les candidatures
            </h2>
            <p class="card__lead">
                Saisissez des mots-clés pour filtrer, scorer et classer les CVs
                automatiquement.
            </p>
            <Link href="/rh/cvs" class="btn btn--primary"
                >Lancer une analyse</Link
            >
        </div>
    </AppLayout>
</template>
