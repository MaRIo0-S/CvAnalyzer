<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import SearchSelect from "@/Components/SearchSelect.vue";
import { useStaffPaths } from "@/composables/useStaffPaths";

const paths = useStaffPaths();

const props = defineProps({
    stats: Object,
    lignes: Array,
    sessionsRh: Array,
    sessionsGerants: Array,
});

const recherche = ref("");
const filtreEntreprise = ref("");
const filtreType = ref("");
const filtreActif = ref("");

const entreprisesOptions = computed(() =>
    [...new Set(props.lignes.map((l) => l.entreprise))].sort()
);

const entrepriseFilterItems = computed(() =>
    entreprisesOptions.value.map((nom) => ({ id: nom, label: nom }))
);

function toggleGerant(id) {
    router.patch(paths.value.adminGerantToggle(id), {}, { preserveScroll: true });
}

function supprimerGerant(id) {
    if (confirm("Supprimer ce gérant et son équipe RH ?")) {
        router.delete(`${paths.value.adminGerants}/${id}`, { preserveScroll: true });
    }
}

const lignesFiltrees = computed(() => {
    let list = [...props.lignes];
    const q = recherche.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (l) =>
                l.entreprise?.toLowerCase().includes(q) ||
                l.gerant_nom?.toLowerCase().includes(q) ||
                l.gerant_email?.toLowerCase().includes(q) ||
                l.rh_nom?.toLowerCase().includes(q) ||
                l.rh_email?.toLowerCase().includes(q)
        );
    }
    if (filtreEntreprise.value) {
        list = list.filter((l) => l.entreprise === filtreEntreprise.value);
    }
    if (filtreType.value === "gerant") {
        list = list.filter((l) => l.gerant_id);
    }
    if (filtreType.value === "rh") {
        list = list.filter((l) => l.rh_id);
    }
    if (filtreActif.value === "actif") {
        list = list.filter(
            (l) =>
                (l.rh_id && l.rh_actif) ||
                (!l.rh_id && l.gerant_actif)
        );
    }
    if (filtreActif.value === "inactif") {
        list = list.filter(
            (l) =>
                (l.rh_id && !l.rh_actif) ||
                (!l.rh_id && !l.gerant_actif)
        );
    }
    return list;
});


</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Administration</p>
            <h1>Back-office</h1>
            <p>Organisation, sessions et export des données.</p>
        </div>

        <div class="stats-grid" style="margin-bottom: 1.5rem">
            <div class="stat-card" style="--stat-accent: #818cf8">
                <strong>{{ stats.entreprises }}</strong>
                <span>Entreprises</span>
            </div>
            <div class="stat-card" style="--stat-accent: #a78bfa">
                <strong>{{ stats.gerants }}</strong>
                <span>Gérants</span>
            </div>
            <div class="stat-card" style="--stat-accent: #22d3ee">
                <strong>{{ stats.rh }}</strong>
                <span>RH</span>
            </div>
            <div class="stat-card" style="--stat-accent: #fb7185">
                <strong>{{ stats.sessions_gerants }}</strong>
                <span>Sessions gérants</span>
            </div>
            <div class="stat-card" style="--stat-accent: #f472b6">
                <strong>{{ stats.sessions_rh }}</strong>
                <span>Sessions RH</span>
            </div>
            <div class="stat-card" style="--stat-accent: #34d399">
                <strong>{{ stats.cvs }}</strong>
                <span>CV déposés</span>
            </div>
            <div class="stat-card" style="--stat-accent: #22d3ee">
                <strong>{{ stats.messages_contact_non_lus }}</strong>
                <span>Messages non lus</span>
            </div>
        </div>

        <div class="card" style="margin-bottom: 1.25rem">
            <div
                style="
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.75rem;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 1rem;
                "
            >
                <h2 class="card__title card__title--sm" style="margin: 0">
                    Entreprises, gérants et RH
                </h2>
                <a
                    :href="paths.adminExport"
                    class="btn btn--secondary btn--sm"
                    >Télécharger Excel</a
                >
            </div>

            <div
                class="offres-page__filters-grid"
                style="margin-bottom: 1rem"
            >
                <div class="form-group">
                    <label>Recherche</label>
                    <input
                        v-model="recherche"
                        type="search"
                        placeholder="Saisissez un nom ou un e-mail…"
                    />
                </div>
                <SearchSelect
                    v-model="filtreEntreprise"
                    :items="entrepriseFilterItems"
                    label="Entreprise"
                    placeholder="Saisissez ou sélectionnez une entreprise…"
                />
                <div class="form-group">
                    <label>Type</label>
                    <select v-model="filtreType">
                        <option value="">Tous</option>
                        <option value="gerant">Ligne gérant</option>
                        <option value="rh">Ligne RH</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Statut session</label>
                    <select v-model="filtreActif">
                        <option value="">Tous</option>
                        <option value="actif">Compte actif</option>
                        <option value="inactif">Compte désactivé</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Entreprise</th>
                            <th>Gérant</th>
                            <th>E-mail gérant</th>
                            <th>Tél. gérant</th>
                            <th>Gérant actif</th>
                            <th>RH</th>
                            <th>E-mail RH</th>
                            <th>Tél. RH</th>
                            <th>RH actif</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(l, i) in lignesFiltrees" :key="i">
                            <td>{{ l.entreprise }}</td>
                            <td>{{ l.gerant_nom }}</td>
                            <td>{{ l.gerant_email }}</td>
                            <td>{{ l.gerant_telephone }}</td>
                            <td>
                                <span
                                    v-if="l.gerant_id"
                                    :class="[
                                        'badge',
                                        l.gerant_actif
                                            ? 'badge--success'
                                            : 'badge--danger',
                                    ]"
                                >
                                    {{ l.gerant_actif ? "Oui" : "Non" }}
                                </span>
                                <span v-else>—</span>
                            </td>
                            <td>{{ l.rh_nom }}</td>
                            <td>{{ l.rh_email }}</td>
                            <td>{{ l.rh_telephone }}</td>
                            <td>
                                <span
                                    v-if="l.rh_id"
                                    :class="[
                                        'badge',
                                        l.rh_actif
                                            ? 'badge--success'
                                            : 'badge--danger',
                                    ]"
                                >
                                    {{ l.rh_actif ? "Oui" : "Non" }}
                                </span>
                                <span v-else>—</span>
                            </td>
                            <td>
                                <div
                                    v-if="l.gerant_id"
                                    class="table-actions"
                                >
                                    <button
                                        type="button"
                                        class="btn btn--danger btn--sm"
                                        @click="supprimerGerant(l.gerant_id)"
                                    >
                                        Supprimer
                                    </button>
                                    <Link
                                        :href="paths.adminGerantEdit(l.gerant_id)"
                                        class="btn btn--ghost btn--sm"
                                    >
                                        Modifier
                                    </Link>
                                    <button
                                        type="button"
                                        class="btn btn--secondary btn--sm"
                                        @click="toggleGerant(l.gerant_id)"
                                    >
                                        {{
                                            l.gerant_actif
                                                ? "Désactiver"
                                                : "Réactiver"
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!lignesFiltrees.length">
                            <td colspan="10" class="text-empty">
                                Aucune ligne.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin-bottom: 1.25rem">
            <h2 class="card__title card__title--sm">Sessions gérants actives</h2>
            <p class="text-muted" style="margin: 0 0 1rem; font-size: 0.88rem">
                Inactivité &gt; {{ stats.session_minutes }} min = session expirée.
            </p>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Entreprise</th>
                            <th>Dernière activité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in sessionsGerants" :key="s.user_id">
                            <td>{{ s.name }}</td>
                            <td>{{ s.email }}</td>
                            <td>{{ s.entreprise }}</td>
                            <td>{{ s.derniere_activite }}</td>
                        </tr>
                        <tr v-if="!sessionsGerants.length">
                            <td colspan="4" class="text-empty">Aucune.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">Sessions RH actives</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Entreprise</th>
                            <th>Dernière activité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in sessionsRh" :key="s.user_id">
                            <td>{{ s.name }}</td>
                            <td>{{ s.email }}</td>
                            <td>{{ s.entreprise }}</td>
                            <td>{{ s.derniere_activite }}</td>
                        </tr>
                        <tr v-if="!sessionsRh.length">
                            <td colspan="4" class="text-empty">Aucune.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
