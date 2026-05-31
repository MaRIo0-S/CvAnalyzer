<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

defineProps({
    stats: Object,
    entreprises: Array,
    sessions: Array,
});
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Administration</p>
            <h1>Back-office</h1>
            <p>Vue d'ensemble et suivi des connexions RH.</p>
        </div>

        <div class="stats-grid" style="margin-bottom: 1.5rem">
            <div class="stat-card" style="--stat-accent: #818cf8">
                <strong>{{ stats.entreprises }}</strong>
                <span>Entreprises</span>
            </div>
            <div class="stat-card" style="--stat-accent: #22d3ee">
                <strong>{{ stats.rh }}</strong>
                <span>Sub-admins RH</span>
            </div>
            <div class="stat-card" style="--stat-accent: #34d399">
                <strong>{{ stats.candidats }}</strong>
                <span>Candidats inscrits</span>
            </div>
            <div class="stat-card" style="--stat-accent: #fbbf24">
                <strong>{{ stats.cvs }}</strong>
                <span>CV déposés</span>
            </div>
            <div class="stat-card" style="--stat-accent: #a78bfa">
                <strong>{{ stats.postes_ouverts }}</strong>
                <span>Postes ouverts</span>
            </div>
            <div class="stat-card" style="--stat-accent: #fb7185">
                <strong>{{ stats.sessions }}</strong>
                <span>Sessions RH actives</span>
            </div>
            <div class="stat-card" style="--stat-accent: #22d3ee">
                <strong>{{ stats.messages_contact }}</strong>
                <span>Messages contact</span>
            </div>
            <div class="stat-card" style="--stat-accent: #f472b6">
                <strong>{{ stats.messages_contact_non_lus }}</strong>
                <span>Messages non lus</span>
            </div>
        </div>

        <p class="text-muted" style="margin: -0.5rem 0 1.25rem; font-size: 0.9rem">
            Les demandes du formulaire d'accueil sont enregistrées en base.
            <Link href="/admin/messages-contact" class="link-inline"
                >Voir la boîte de réception</Link
            >.
        </p>

        <div class="card" style="margin-bottom: 1.25rem">
            <h2 class="card__title card__title--sm">Entreprises et RH</h2>
            <p v-if="!entreprises.length" class="text-muted">Aucune entreprise.</p>
            <details
                v-for="e in entreprises"
                :key="e.id"
                class="back-office-details"
            >
                <summary>
                    <strong>{{ e.nom }}</strong> — {{ e.rh_count }} RH
                </summary>
                <ul v-if="e.rh.length" class="back-office-list">
                    <li v-for="(r, i) in e.rh" :key="i">
                        {{ r.name }} · {{ r.email }}
                    </li>
                </ul>
                <p v-else class="text-muted">Aucun RH sur cette entreprise.</p>
            </details>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">Sessions RH ouvertes</h2>
            <p class="text-muted" style="margin: 0 0 1rem; font-size: 0.88rem">
                Connexions actives : inactivité de plus de
                {{ stats.session_minutes }} minutes = session expirée (réglage
                Laravel <code>SESSION_LIFETIME</code>).
            </p>
            <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Entreprise</th>
                        <th>Dernière activité</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(s, i) in sessions" :key="i">
                        <td>{{ s.name }}</td>
                        <td>{{ s.entreprise }}</td>
                        <td>{{ s.derniere_activite }}</td>
                    </tr>
                    <tr v-if="!sessions.length">
                        <td colspan="3" class="text-empty">
                            Aucune session RH active.
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </AppLayout>
</template>
