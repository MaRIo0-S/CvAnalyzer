<script setup>
import { Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PasswordInput from "@/Components/PasswordInput.vue";
import { useStaffPaths } from "@/composables/useStaffPaths";

const paths = useStaffPaths();

const props = defineProps({ entreprise: Object, rhList: Array });

const form = useForm({
    name: "",
    email: "",
    telephone: "",
    password: "",
    password_confirmation: "",
});

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
        if (tri.value === "date_desc") {
            return (b.created_at || "").localeCompare(a.created_at || "", "fr");
        }
        return (a.name || "").localeCompare(b.name || "", "fr");
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
            <p class="page-header__label">Gérant — {{ entreprise?.nom }}</p>
            <h1>Équipe RH</h1>
            <p>Comptes RH : création, modification et suivi.</p>
        </div>

        <p class="offres-back-link">
            <Link :href="paths.gerantDashboard">← Retour au back-office</Link>
        </p>

        <div class="card" style="margin-bottom: 1.25rem">
            <h2 class="card__title card__title--sm">Nouveau RH</h2>
            <form
                @submit.prevent="
                    form.post(`${paths.value.gerant}/rh`, {
                        preserveScroll: true,
                        onSuccess: () => {
                            form.reset();
                            form.clearErrors();
                        },
                    })
                "
            >
                <div class="form-group">
                    <label>Nom</label>
                    <input v-model="form.name" type="text" required />
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input v-model="form.email" type="email" required />
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input v-model="form.telephone" type="tel" required />
                </div>
                <PasswordInput
                    v-model="form.password"
                    label="Mot de passe"
                    input-id="rh-password"
                    required
                />
                <PasswordInput
                    v-model="form.password_confirmation"
                    label="Confirmer"
                    input-id="rh-password-2"
                    required
                />
                <button
                    type="submit"
                    class="btn btn--primary"
                    :disabled="form.processing"
                >
                    Ajouter le RH
                </button>
            </form>
        </div>

        <div class="card">
            <div class="section-head">
                <h2 class="card__title card__title--sm">Liste RH</h2>
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
                        <option value="date_desc">Plus récents</option>
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
                            <th>Depuis</th>
                            <th>Session</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="rh in rhFiltres" :key="rh.id">
                            <td>{{ rh.name }}</td>
                            <td>{{ rh.email }}</td>
                            <td>{{ rh.telephone || "—" }}</td>
                            <td>{{ rh.created_at }}</td>
                            <td>
                                <span
                                    :class="[
                                        'badge',
                                        rh.est_actif
                                            ? 'badge--success'
                                            : 'badge--danger',
                                    ]"
                                >
                                    {{
                                        rh.est_actif ? "Active" : "Désactivée"
                                    }}
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
    </AppLayout>
</template>
