<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { badgeClass } from "@/utils/cvList";

defineProps({
    cv: Object,
});
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
            <Link href="/offres" class="btn btn--primary" style="margin-top: 1rem">
                Parcourir les offres
            </Link>
        </div>

        <div v-else class="card">
            <p style="margin-bottom: 1rem">
                <span :class="['badge', badgeClass(cv.statut)]">{{
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
                    <Link href="/deposer">modifier votre dossier</Link>
                </li>
            </ul>
        </div>
    </AppLayout>
</template>
