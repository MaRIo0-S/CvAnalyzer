<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import SearchSelect from "@/Components/SearchSelect.vue";
import { useStaffPaths } from "@/composables/useStaffPaths";

const paths = useStaffPaths();

const props = defineProps({
    entreprise: Object,
    stats: Object,
    rhList: Array,
    lignesCandidats: Array,
    lignesPostes: Array,
    sessionsRh: Array,
});

const rechercheCandidats = ref("");
const filtreStatutCandidat = ref("");
const filtreImportCandidat = ref("");
const filtreRhCandidat = ref("");
const triCandidats = ref("date_desc");

const recherchePostes = ref("");
const filtreOuvertPoste = ref("");
const triPostes = ref("titre_asc");

const recherche = ref("");
const filtreActif = ref("");
const tri = ref("nom_asc");

const rhFiltres = computed(() => {
    let list = [...(props.rhList || [])];
    const q = recherche.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (r) =>
                r.name?.toLowerCase().includes(q) ||
                r.email?.toLowerCase().includes(q) ||
                (r.telephone || "").toLowerCase().includes(q)
        );
    }
    if (filtreActif.value === "actif") list = list.filter((r) => r.est_actif);
    if (filtreActif.value === "inactif") list = list.filter((r) => !r.est_actif);
    list.sort((a, b) => {
        if (tri.value === "postes_desc") {
            return (b.postes_count || 0) - (a.postes_count || 0);
        }
        if (tri.value === "postes_asc") {
            return (a.postes_count || 0) - (b.postes_count || 0);
        }
        if (tri.value === "nom_desc") {
            return (b.name || "").localeCompare(a.name || "", "fr");
        }
        return (a.name || "").localeCompare(b.name || "", "fr");
    });
    return list;
});

const rhCandidatsOptions = computed(() => {
    const map = new Map();
    for (const c of props.lignesCandidats || []) {
        if (c.rh_id && c.rh_nom && c.rh_nom !== "—") {
            map.set(c.rh_id, c.rh_nom);
        }
    }
    return [...map.entries()]
        .map(([id, nom]) => ({ id, label: nom }))
        .sort((a, b) => a.label.localeCompare(b.label, "fr"));
});

const candidatsFiltres = computed(() => {
    let list = [...(props.lignesCandidats || [])];
    const q = rechercheCandidats.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (c) =>
                String(c.numero_dossier || "").includes(q) ||
                c.nom?.toLowerCase().includes(q) ||
                c.email?.toLowerCase().includes(q) ||
                c.poste?.toLowerCase().includes(q) ||
                c.rh_nom?.toLowerCase().includes(q) ||
                c.compte_candidat?.toLowerCase().includes(q)
        );
    }
    if (filtreStatutCandidat.value) {
        list = list.filter((c) => c.statut_value === filtreStatutCandidat.value);
    }
    if (filtreImportCandidat.value === "oui") {
        list = list.filter((c) => c.importe_par_rh_bool);
    }
    if (filtreImportCandidat.value === "non") {
        list = list.filter((c) => !c.importe_par_rh_bool);
    }
    if (filtreRhCandidat.value) {
        list = list.filter(
            (c) => String(c.rh_id) === String(filtreRhCandidat.value)
        );
    }
    list.sort((a, b) => {
        if (triCandidats.value === "date_asc") {
            return (a.date_depot_ts || 0) - (b.date_depot_ts || 0);
        }
        if (triCandidats.value === "nom_asc") {
            return (a.nom || "").localeCompare(b.nom || "", "fr");
        }
        if (triCandidats.value === "nom_desc") {
            return (b.nom || "").localeCompare(a.nom || "", "fr");
        }
        if (triCandidats.value === "poste_asc") {
            return (a.poste || "").localeCompare(b.poste || "", "fr");
        }
        return (b.date_depot_ts || 0) - (a.date_depot_ts || 0);
    });
    return list;
});

const postesFiltres = computed(() => {
    let list = [...(props.lignesPostes || [])];
    const q = recherchePostes.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (p) =>
                p.titre?.toLowerCase().includes(q) ||
                p.rh_nom?.toLowerCase().includes(q) ||
                p.rh_email?.toLowerCase().includes(q)
        );
    }
    if (filtreOuvertPoste.value === "oui") {
        list = list.filter((p) => p.est_ouvert_bool);
    }
    if (filtreOuvertPoste.value === "non") {
        list = list.filter((p) => !p.est_ouvert_bool);
    }
    list.sort((a, b) => {
        if (triPostes.value === "date_desc") {
            return (b.date_creation_ts || 0) - (a.date_creation_ts || 0);
        }
        if (triPostes.value === "cvs_desc") {
            return (b.cvs_count || 0) - (a.cvs_count || 0);
        }
        if (triPostes.value === "rh_asc") {
            return (a.rh_nom || "").localeCompare(b.rh_nom || "", "fr");
        }
        if (triPostes.value === "titre_desc") {
            return (b.titre || "").localeCompare(a.titre || "", "fr");
        }
        return (a.titre || "").localeCompare(b.titre || "", "fr");
    });
    return list;
});

function toggleRh(id) {
    router.patch(`${paths.value.gerant}/rh/${id}/actif`, {}, { preserveScroll: true });
}

function supprimerRh(id) {
    if (confirm("Supprimer ce compte RH ?")) {
        router.delete(`${paths.value.gerant}/rh/${id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Gérant entreprise</p>
            <h1>Back-office — {{ entreprise?.nom || "Entreprise" }}</h1>
            <p>Gestion de l'équipe RH et suivi des sessions.</p>
        </div>

        <div class="stats-grid" style="margin-bottom: 1.5rem">
            <div class="stat-card" style="--stat-accent: #22d3ee">
                <strong>{{ stats.rh_actifs }}</strong>
                <span>RH actifs</span>
            </div>
            <div class="stat-card" style="--stat-accent: #a78bfa">
                <strong>{{ stats.rh_total }}</strong>
                <span>RH total</span>
            </div>
            <div class="stat-card" style="--stat-accent: #818cf8">
                <strong>{{ stats.postes_ouverts }}</strong>
                <span>Postes ouverts</span>
            </div>
            <div class="stat-card" style="--stat-accent: #34d399">
                <strong>{{ stats.cvs_recus }}</strong>
                <span>CV reçus</span>
            </div>
            <div class="stat-card" style="--stat-accent: #f472b6">
                <strong>{{ stats.sessions_rh }}</strong>
                <span>Sessions RH</span>
            </div>
        </div>

        <div class="card" style="margin-bottom: 1.25rem">
            <div class="bo-actions">
                <Link :href="paths.gerantRh" class="btn btn--primary"
                    >Équipe RH</Link
                >
                <Link :href="paths.gerantEntreprise" class="btn btn--secondary"
                    >Description entreprise</Link
                >
            </div>
        </div>

        <div class="card" style="margin-bottom: 1.25rem">
            <div class="section-head">
                <h2 class="card__title card__title--sm">Équipe RH</h2>
                <a
                    :href="paths.gerantExportRh"
                    class="btn btn--secondary btn--sm"
                    >Télécharger Excel</a
                >
            </div>
            <div class="toolbar-row toolbar-row--unified">
                <div class="form-group">
                    <label>Recherche</label>
                    <input
                        v-model="recherche"
                        type="search"
                        placeholder="Saisissez un nom ou un e-mail…"
                    />
                </div>
                <div class="form-group">
                    <label>Session</label>
                    <select v-model="filtreActif">
                        <option value="">Tous</option>
                        <option value="actif">Actifs</option>
                        <option value="inactif">Désactivés</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tri</label>
                    <select v-model="tri">
                        <option value="nom_asc">Nom (A→Z)</option>
                        <option value="nom_desc">Nom (Z→A)</option>
                        <option value="postes_desc">Postes (↓)</option>
                        <option value="postes_asc">Postes (↑)</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Tél.</th>
                            <th>Postes</th>
                            <th>Session</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="rh in rhFiltres" :key="rh.id">
                            <td>{{ rh.name }}</td>
                            <td>{{ rh.email }}</td>
                            <td>{{ rh.telephone }}</td>
                            <td>{{ rh.postes_count }}</td>
                            <td>
                                <span
                                    :class="[
                                        'badge',
                                        rh.est_actif
                                            ? 'badge--success'
                                            : 'badge--danger',
                                    ]"
                                >
                                    {{ rh.est_actif ? "Active" : "Désactivée" }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button
                                        type="button"
                                        class="btn btn--danger btn--sm"
                                        @click="supprimerRh(rh.id)"
                                    >
                                        Supprimer
                                    </button>
                                    <Link
                                        :href="paths.gerantRhEdit(rh.id)"
                                        class="btn btn--ghost btn--sm"
                                    >
                                        Modifier
                                    </Link>
                                    <button
                                        type="button"
                                        class="btn btn--secondary btn--sm"
                                        @click="toggleRh(rh.id)"
                                    >
                                        {{
                                            rh.est_actif
                                                ? "Désactiver"
                                                : "Réactiver"
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!rhFiltres.length">
                            <td colspan="6" class="text-empty">Aucun RH.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin-bottom: 1.25rem">
            <h2 class="card__title card__title--sm">Candidats et CV déposés</h2>
            <p class="text-muted" style="margin: 0 0 1rem; font-size: 0.88rem">
                Vue d'ensemble des dossiers sur votre entreprise (lecture
                seule).
            </p>
            <div class="toolbar-row toolbar-row--unified toolbar-row--cvs" style="margin-bottom: 1rem">
                <div class="form-group">
                    <label>Recherche</label>
                    <input
                        v-model="rechercheCandidats"
                        type="search"
                        placeholder="Saisissez un n° de dossier, nom ou poste…"
                    />
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select v-model="filtreStatutCandidat">
                        <option value="">Tous</option>
                        <option value="cv_recu">CV reçu</option>
                        <option value="en_cours_analyse">En cours d'analyse</option>
                        <option value="valide">Validé</option>
                        <option value="non_valide">Non validé</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Source</label>
                    <select v-model="filtreImportCandidat">
                        <option value="">Toutes</option>
                        <option value="non">Candidats</option>
                        <option value="oui">Import RH</option>
                    </select>
                </div>
                <SearchSelect
                    v-model="filtreRhCandidat"
                    :items="rhCandidatsOptions"
                    label="RH"
                    placeholder="Saisissez ou sélectionnez un RH…"
                />
                <div class="form-group">
                    <label>Tri</label>
                    <select v-model="triCandidats">
                        <option value="date_desc">Dépôt (récent)</option>
                        <option value="date_asc">Dépôt (ancien)</option>
                        <option value="nom_asc">Nom (A→Z)</option>
                        <option value="nom_desc">Nom (Z→A)</option>
                        <option value="poste_asc">Poste (A→Z)</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° dossier</th>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Poste</th>
                            <th>RH</th>
                            <th>Statut</th>
                            <th>Dépôt</th>
                            <th>Import RH</th>
                            <th>Compte candidat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in candidatsFiltres" :key="c.id">
                            <td>{{ c.numero_dossier }}</td>
                            <td>{{ c.nom }}</td>
                            <td>{{ c.email }}</td>
                            <td>{{ c.poste }}</td>
                            <td>{{ c.rh_nom }}</td>
                            <td>{{ c.statut }}</td>
                            <td>{{ c.date_depot }}</td>
                            <td>{{ c.importe_par_rh }}</td>
                            <td>{{ c.compte_candidat }}</td>
                        </tr>
                        <tr v-if="!candidatsFiltres.length">
                            <td colspan="9" class="text-empty">Aucun CV.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin-bottom: 1.25rem">
            <h2 class="card__title card__title--sm">Postes (offres)</h2>
            <div class="toolbar-row toolbar-row--unified" style="margin-bottom: 1rem">
                <div class="form-group">
                    <label>Recherche</label>
                    <input
                        v-model="recherchePostes"
                        type="search"
                        placeholder="Saisissez un titre ou un nom de RH…"
                    />
                </div>
                <div class="form-group">
                    <label>Ouvert</label>
                    <select v-model="filtreOuvertPoste">
                        <option value="">Tous</option>
                        <option value="oui">Ouverts</option>
                        <option value="non">Fermés</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tri</label>
                    <select v-model="triPostes">
                        <option value="titre_asc">Titre (A→Z)</option>
                        <option value="titre_desc">Titre (Z→A)</option>
                        <option value="date_desc">Plus récents</option>
                        <option value="cvs_desc">Plus de CV</option>
                        <option value="rh_asc">RH (A→Z)</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>RH créateur</th>
                            <th>E-mail RH</th>
                            <th>Ouvert</th>
                            <th>Créé le</th>
                            <th>CV reçus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in postesFiltres" :key="p.id">
                            <td>{{ p.titre }}</td>
                            <td>{{ p.rh_nom }}</td>
                            <td>{{ p.rh_email }}</td>
                            <td>{{ p.est_ouvert }}</td>
                            <td>{{ p.date_creation }}</td>
                            <td>{{ p.cvs_count }}</td>
                        </tr>
                        <tr v-if="!postesFiltres.length">
                            <td colspan="6" class="text-empty">Aucun poste.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">Sessions RH actives</h2>
            <p class="text-muted" style="margin: 0 0 1rem; font-size: 0.88rem">
                Inactivité &gt; {{ stats.session_minutes }} min = session expirée.
            </p>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Tél.</th>
                            <th>Dernière activité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in sessionsRh" :key="s.user_id">
                            <td>{{ s.name }}</td>
                            <td>{{ s.email }}</td>
                            <td>{{ s.telephone }}</td>
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
