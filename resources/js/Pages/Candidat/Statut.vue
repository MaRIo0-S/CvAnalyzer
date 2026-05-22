<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

defineProps({
    cv: Object,
});

const badges = {
    cv_recu: "badge--recu",
    en_cours_analyse: "badge--analyse",
    valide: "badge--valide",
    non_valide: "badge--refuse",
};
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Candidat</p>
            <h1>Ma candidature</h1>
            <p>
                Suivi de votre candidature et e-mails à chaque changement de
                statut. Les alertes sont dans la cloche en haut à droite.
            </p>
        </div>

        <div v-if="!cv" class="card">
            <p class="text-muted">Aucun CV déposé pour le moment.</p>
            <Link href="/deposer" class="btn btn--primary" style="margin-top: 1rem">
                Déposer un CV
            </Link>
        </div>

        <div v-else class="card">
            <p style="margin-bottom: 1rem">
                <span :class="['badge', badges[cv.statut] || 'badge--recu']">{{
                    cv.statut_label
                }}</span>
            </p>
            <ul class="info-list">
                <li><strong>Poste :</strong> {{ cv.poste }}</li>
                <li><strong>Entreprise :</strong> {{ cv.entreprise }}</li>
                <li><strong>Déposé le :</strong> {{ cv.date_depot }}</li>
                <li v-if="cv.peut_modifier">
                    <strong>Modification possible jusqu'au :</strong>
                    {{ cv.modifiable_jusqu }} —
                    <Link href="/deposer">modifier sur la page Déposer un CV</Link>
                </li>
            </ul>
        </div>
    </AppLayout>
</template>
