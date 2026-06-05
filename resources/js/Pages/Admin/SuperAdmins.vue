<script setup>
import { Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PasswordInput from "@/Components/PasswordInput.vue";
import SearchSelect from "@/Components/SearchSelect.vue";
import { useStaffPaths } from "@/composables/useStaffPaths";

const paths = useStaffPaths();

const props = defineProps({
    superAdmins: Array,
    entreprises: Array,
});

const form = useForm({
    name: "",
    email: "",
    telephone: "",
    password: "",
    password_confirmation: "",
    entreprise_nom: "",
});

const entrepriseFormId = ref("");

const entrepriseItems = computed(() => {
    const fromDb = (props.entreprises || []).map((e) => ({
        id: e.nom,
        label: e.nom,
    }));
    const fromGerants = (props.superAdmins || [])
        .map((g) => g.entreprise)
        .filter(Boolean)
        .map((nom) => ({ id: nom, label: nom }));
    const map = new Map();
    [...fromDb, ...fromGerants].forEach((i) => map.set(i.id, i));
    return [...map.values()].sort((a, b) =>
        a.label.localeCompare(b.label, "fr")
    );
});

watch(entrepriseFormId, (id) => {
    if (id) {
        form.entreprise_nom = String(id);
    }
});

const recherche = ref("");
const filtreEntreprise = ref("");
const filtreActif = ref("");

const listeFiltree = computed(() => {
    let list = [...(props.superAdmins || [])];
    const q = recherche.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (g) =>
                g.name?.toLowerCase().includes(q) ||
                g.email?.toLowerCase().includes(q) ||
                g.entreprise?.toLowerCase().includes(q)
        );
    }
    if (filtreEntreprise.value) {
        list = list.filter((g) => g.entreprise === filtreEntreprise.value);
    }
    if (filtreActif.value === "actif") {
        list = list.filter((g) => g.est_actif);
    }
    if (filtreActif.value === "inactif") {
        list = list.filter((g) => !g.est_actif);
    }
    return list;
});

const entrepriseFilterItems = computed(() =>
    [...new Set((props.superAdmins || []).map((g) => g.entreprise))]
        .filter(Boolean)
        .map((nom) => ({ id: nom, label: nom }))
);
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Administration plateforme</p>
            <h1>Gérants (super-admin)</h1>
            <p>
                Créez le responsable d'une entreprise. Il gère les RH et la
                description de l'entreprise.
            </p>
        </div>

        <div class="card" style="margin-bottom: 1.25rem">
            <h2 class="card__title card__title--sm">Nouveau gérant</h2>
            <form
                @submit.prevent="
                    form.post(paths.value.adminGerants, {
                        preserveScroll: true,
                        onSuccess: () => {
                            form.reset();
                            entrepriseFormId = '';
                            form.clearErrors();
                        },
                    })
                "
            >
                <div class="form-group">
                    <label>Nom complet</label>
                    <input v-model="form.name" type="text" required />
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input v-model="form.email" type="email" required />
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input v-model="form.telephone" type="tel" required />
                </div>
                <PasswordInput
                    v-model="form.password"
                    label="Mot de passe"
                    input-id="superadmin-password"
                    autocomplete="new-password"
                    required
                />
                <PasswordInput
                    v-model="form.password_confirmation"
                    label="Confirmer le mot de passe"
                    input-id="superadmin-password-2"
                    autocomplete="new-password"
                    required
                />
                <SearchSelect
                    v-model="entrepriseFormId"
                    :items="entrepriseItems"
                    label="Entreprise existante"
                    placeholder="Saisissez un nom ou un e-mail…"
                />
                <div v-if="!entrepriseFormId" class="form-group">
                    <label>Nouvelle entreprise</label>
                    <input
                        v-model="form.entreprise_nom"
                        type="text"
                        placeholder="Saisissez le nom de l'entreprise…"
                        required
                    />
                </div>
                <button
                    type="submit"
                    class="btn btn--primary"
                    :disabled="form.processing"
                >
                    Ajouter le gérant
                </button>
            </form>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">Liste des gérants</h2>
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
                    <label>Statut</label>
                    <select v-model="filtreActif">
                        <option value="">Tous</option>
                        <option value="actif">Actifs</option>
                        <option value="inactif">Désactivés</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Tél.</th>
                            <th>Entreprise</th>
                            <th>RH</th>
                            <th>Actif</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sa in listeFiltree" :key="sa.id">
                            <td>{{ sa.name }}</td>
                            <td>{{ sa.email }}</td>
                            <td>{{ sa.telephone || "—" }}</td>
                            <td>{{ sa.entreprise }}</td>
                            <td>
                                {{ sa.rh_actifs }} / {{ sa.rh_count }}
                            </td>
                            <td>
                                <span
                                    :class="[
                                        'badge',
                                        sa.est_actif
                                            ? 'badge--success'
                                            : 'badge--muted',
                                    ]"
                                >
                                    {{ sa.est_actif ? "Oui" : "Non" }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button
                                        type="button"
                                        class="btn btn--danger btn--sm"
                                        @click="
                                            router.delete(
                                                `${paths.adminGerants.value}/${sa.id}`,
                                                { preserveScroll: true }
                                            )
                                        "
                                    >
                                        Supprimer
                                    </button>
                                    <Link
                                        :href="paths.adminGerantEdit(sa.id)"
                                        class="btn btn--ghost btn--sm"
                                    >
                                        Modifier
                                    </Link>
                                    <button
                                        type="button"
                                        class="btn btn--secondary btn--sm"
                                        @click="
                                            router.patch(
                                                paths.value.adminGerantToggle(sa.id),
                                                {},
                                                { preserveScroll: true }
                                            )
                                        "
                                    >
                                        {{
                                            sa.est_actif
                                                ? "Désactiver"
                                                : "Réactiver"
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!listeFiltree.length">
                            <td colspan="7" class="text-empty">
                                Aucun gérant.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
