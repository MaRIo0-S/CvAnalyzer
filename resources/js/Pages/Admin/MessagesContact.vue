<script setup>
import { router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import SearchSelect from "@/Components/SearchSelect.vue";
import { useStaffPaths } from "@/composables/useStaffPaths";

const paths = useStaffPaths();

const props = defineProps({
    messages: { type: Array, default: () => [] },
    stats: { type: Object, required: true },
});

const recherche = ref("");
const filtreLu = ref("");
const filtreEntreprise = ref("");
const periode = ref("");
const dateDebut = ref("");
const dateFin = ref("");
const tri = ref("date_desc");
const filtresOuverts = ref(false);

function marquerLu(id) {
    router.patch(`${paths.value.admin}/messages-contact/${id}/lu`, {}, { preserveScroll: true });
}

function reinitialiserFiltres() {
    recherche.value = "";
    filtreLu.value = "";
    filtreEntreprise.value = "";
    periode.value = "";
    dateDebut.value = "";
    dateFin.value = "";
    tri.value = "date_desc";
}

const entreprisesOptions = computed(() =>
    [...new Set(props.messages.map((m) => m.entreprise))]
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b, "fr"))
);

const entrepriseFilterItems = computed(() =>
    entreprisesOptions.value.map((nom) => ({ id: nom, label: nom }))
);

const filtresActifs = computed(
    () =>
        !!(
            recherche.value ||
            filtreLu.value ||
            filtreEntreprise.value ||
            periode.value ||
            dateDebut.value ||
            dateFin.value ||
            tri.value !== "date_desc"
        )
);

function debutJour(d) {
    const x = new Date(d);
    x.setHours(0, 0, 0, 0);
    return x;
}

function finJour(d) {
    const x = new Date(d);
    x.setHours(23, 59, 59, 999);
    return x;
}

function dansPeriode(recuAt) {
    if (!recuAt) return true;
    const date = new Date(recuAt);
    if (Number.isNaN(date.getTime())) return true;

    if (!periode.value && !dateDebut.value && !dateFin.value) {
        return true;
    }

    const now = new Date();

    if (periode.value === "aujourdhui") {
        return date >= debutJour(now) && date <= finJour(now);
    }
    if (periode.value === "7j") {
        const depuis = new Date(now);
        depuis.setDate(depuis.getDate() - 7);
        return date >= debutJour(depuis);
    }
    if (periode.value === "30j") {
        const depuis = new Date(now);
        depuis.setDate(depuis.getDate() - 30);
        return date >= debutJour(depuis);
    }

    if (dateDebut.value && date < debutJour(dateDebut.value)) {
        return false;
    }
    if (dateFin.value && date > finJour(dateFin.value)) {
        return false;
    }

    return true;
}

function correspondRecherche(m, q) {
    if (!q) return true;
    const needle = q.trim().toLowerCase();
    const hay = [m.nom, m.email, m.telephone, m.entreprise, m.message]
        .filter(Boolean)
        .join(" ")
        .toLowerCase();
    return hay.includes(needle);
}

function trierMessages(a, b, mode) {
    switch (mode) {
        case "date_asc":
            return new Date(a.recu_at) - new Date(b.recu_at);
        case "nom_asc":
            return (a.nom || "").localeCompare(b.nom || "", "fr");
        case "nom_desc":
            return (b.nom || "").localeCompare(a.nom || "", "fr");
        case "entreprise_asc":
            return (a.entreprise || "").localeCompare(b.entreprise || "", "fr");
        case "entreprise_desc":
            return (b.entreprise || "").localeCompare(a.entreprise || "", "fr");
        case "date_desc":
        default:
            return new Date(b.recu_at) - new Date(a.recu_at);
    }
}

const messagesFiltres = computed(() => {
    let list = props.messages.filter((m) => {
        if (filtreLu.value === "lu" && !m.lu) return false;
        if (filtreLu.value === "non_lu" && m.lu) return false;
        if (
            filtreEntreprise.value &&
            m.entreprise !== filtreEntreprise.value
        ) {
            return false;
        }
        if (!correspondRecherche(m, recherche.value)) return false;
        if (!dansPeriode(m.recu_at)) return false;
        return true;
    });

    return [...list].sort((a, b) => trierMessages(a, b, tri.value));
});

const toolbarHint = computed(() => {
    if (periode.value === "aujourdhui") {
        return "Période : messages reçus aujourd'hui.";
    }
    if (periode.value === "7j") {
        return "Période : 7 derniers jours.";
    }
    if (periode.value === "30j") {
        return "Période : 30 derniers jours.";
    }
    if (dateDebut.value || dateFin.value) {
        return "Période personnalisée (dates de début / fin).";
    }
    return "";
});
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Administration</p>
            <h1>Messages de contact</h1>
            <p>
                Demandes envoyées depuis le formulaire de la page d'accueil
                (section « Parlons de votre besoin »).
            </p>
        </div>

        <div class="stats-grid" style="margin-bottom: 1.5rem">
            <div class="stat-card" style="--stat-accent: #22d3ee">
                <strong>{{ stats.total }}</strong>
                <span>Messages reçus</span>
            </div>
            <div class="stat-card" style="--stat-accent: #fb7185">
                <strong>{{ stats.non_lus }}</strong>
                <span>Non lus</span>
            </div>
        </div>

        <div
            class="card cvs-liste-toolbar cvs-liste-toolbar--wide cvs-liste-toolbar--collapsible"
        >
            <h2 class="card__title card__title--sm">Filtres et tri</h2>
            <div class="cvs-liste-toolbar__top">
                <div class="form-group cvs-liste-toolbar__field">
                    <label for="msg-recherche">Recherche</label>
                    <input
                        id="msg-recherche"
                        v-model="recherche"
                        type="search"
                        placeholder="Saisissez un nom, e-mail ou message…"
                        autocomplete="off"
                    />
                </div>
                <button
                    type="button"
                    class="btn btn--secondary btn--sm cvs-liste-toolbar__toggle-filters"
                    :aria-expanded="filtresOuverts"
                    @click="filtresOuverts = !filtresOuverts"
                >
                    {{ filtresOuverts ? "Masquer" : "Filtres" }}
                </button>
            </div>
            <div
                class="cvs-liste-toolbar__extra"
                :class="{ 'cvs-liste-toolbar__extra--open': filtresOuverts }"
            >
                <div class="cvs-liste-toolbar__actions">
                    <button
                        v-if="filtresActifs"
                        type="button"
                        class="btn btn--secondary btn--sm"
                        @click="reinitialiserFiltres"
                    >
                        Réinitialiser
                    </button>
                </div>
                <div
                    class="cvs-liste-toolbar__grid cvs-liste-toolbar__grid--filters"
                >
                    <div class="form-group cvs-liste-toolbar__field">
                    <label for="msg-lu">Lecture</label>
                    <select id="msg-lu" v-model="filtreLu">
                        <option value="">Tous</option>
                        <option value="non_lu">Non lus</option>
                        <option value="lu">Lus</option>
                    </select>
                </div>
                <SearchSelect
                    v-model="filtreEntreprise"
                    :items="entrepriseFilterItems"
                    label="Entreprise"
                    placeholder="Saisissez ou sélectionnez une entreprise…"
                />
                <div class="form-group cvs-liste-toolbar__field">
                    <label for="msg-periode">Période</label>
                    <select id="msg-periode" v-model="periode">
                        <option value="">Toutes les dates</option>
                        <option value="aujourdhui">Aujourd'hui</option>
                        <option value="7j">7 derniers jours</option>
                        <option value="30j">30 derniers jours</option>
                    </select>
                </div>
                <div class="form-group cvs-liste-toolbar__field">
                    <label for="msg-date-debut">Du</label>
                    <input
                        id="msg-date-debut"
                        v-model="dateDebut"
                        type="date"
                        :disabled="!!periode"
                    />
                </div>
                <div class="form-group cvs-liste-toolbar__field">
                    <label for="msg-date-fin">Au</label>
                    <input
                        id="msg-date-fin"
                        v-model="dateFin"
                        type="date"
                        :disabled="!!periode"
                    />
                </div>
                <div class="form-group cvs-liste-toolbar__field">
                    <label for="msg-tri">Tri</label>
                    <select id="msg-tri" v-model="tri">
                        <option value="date_desc">Date (récent → ancien)</option>
                        <option value="date_asc">Date (ancien → récent)</option>
                        <option value="nom_asc">Nom (A → Z)</option>
                        <option value="nom_desc">Nom (Z → A)</option>
                        <option value="entreprise_asc">
                            Entreprise (A → Z)
                        </option>
                        <option value="entreprise_desc">
                            Entreprise (Z → A)
                        </option>
                    </select>
                </div>
            </div>
            </div>
            <p class="cvs-liste-toolbar__hints text-muted">
                <template v-if="toolbarHint">{{ toolbarHint }} </template>
                {{ messagesFiltres.length }} message(s) affiché(s) sur
                {{ messages.length }}.
            </p>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">Boîte de réception</h2>
            <p v-if="!messages.length" class="text-muted">
                Aucun message pour le moment.
            </p>
            <p v-else-if="!messagesFiltres.length" class="text-muted">
                Aucun message ne correspond à ces critères. Utilisez
                « Réinitialiser » pour effacer les filtres.
            </p>
            <div v-else class="contact-inbox">
                <article
                    v-for="m in messagesFiltres"
                    :key="m.id"
                    class="contact-inbox__item"
                    :class="{ 'contact-inbox__item--unread': !m.lu }"
                >
                    <header class="contact-inbox__header">
                        <div>
                            <strong>{{ m.nom }}</strong>
                            <span class="text-muted"> · {{ m.entreprise }}</span>
                        </div>
                        <time class="text-muted">{{ m.recu_le }}</time>
                    </header>
                    <p class="contact-inbox__email">
                        <a :href="`mailto:${m.email}`">{{ m.email }}</a>
                        <span v-if="m.telephone" class="text-muted">
                            · {{ m.telephone }}</span
                        >
                    </p>
                    <p class="contact-inbox__body">{{ m.message }}</p>
                    <footer class="contact-inbox__footer">
                        <span v-if="!m.lu" class="badge badge--analyse"
                            >Nouveau</span
                        >
                        <span v-else class="badge badge--recu">Lu</span>
                        <button
                            v-if="!m.lu"
                            type="button"
                            class="btn btn--secondary btn--sm"
                            @click="marquerLu(m.id)"
                        >
                            Marquer comme lu
                        </button>
                    </footer>
                </article>
            </div>
        </div>
    </AppLayout>
</template>
