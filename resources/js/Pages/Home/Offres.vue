<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import SearchSelect from "@/Components/SearchSelect.vue";

const page = usePage();
const candidatureEnCours = computed(() => page.props.candidatureEnCours);

const props = defineProps({
    postes: Array,
    entreprises: Array,
    filters: Object,
});

const entrepriseItems = computed(() =>
    (props.entreprises || []).map((e) => ({ id: e.id, label: e.nom }))
);

const q = ref(props.filters.q || "");
const entrepriseId = ref(props.filters.entreprise_id || "");
const tri = ref(props.filters.tri || "recent");

const postesFiltres = computed(() => {
    let list = [...(props.postes || [])];
    const term = q.value.trim().toLowerCase();
    if (term) {
        list = list.filter(
            (p) =>
                p.titre?.toLowerCase().includes(term) ||
                p.entreprise?.toLowerCase().includes(term) ||
                p.description?.toLowerCase().includes(term)
        );
    }
    if (entrepriseId.value) {
        list = list.filter(
            (p) => String(p.entreprise_id) === String(entrepriseId.value)
        );
    }
    return list;
});

function appliquerTri() {
    router.get(
        "/offres",
        {
            q: q.value || undefined,
            entreprise_id: entrepriseId.value || undefined,
            tri: tri.value,
        },
        { preserveState: true, replace: true }
    );
}

watch([tri, q, entrepriseId], appliquerTri);

function copierLien(url) {
    navigator.clipboard?.writeText(url);
}
</script>

<template>
    <AppLayout landing>
        <div class="offres-page landing-inner landing-inner--wide">
            <header class="offres-page__head offres-page__head--split">
                <div>
                    <p class="offres-page__eyebrow">Candidature</p>
                    <h1>Offres d'emploi</h1>
                    <p class="text-muted">
                        Parcourez les postes ouverts, filtrez, puis consultez
                        une offre avant de déposer votre CV.
                    </p>
                </div>
                <Link
                    v-if="candidatureEnCours"
                    :href="candidatureEnCours.url"
                    class="btn btn--accent"
                >
                    Modifier mon CV
                </Link>
            </header>

            <div class="card offres-page__filters">
                <div class="offres-page__filters-grid">
                    <div class="form-group">
                        <label>Recherche</label>
                        <input
                            v-model="q"
                            type="search"
                            placeholder="Saisissez un mot-clé, un titre ou une entreprise…"
                            @keyup.enter="appliquerTri"
                        />
                    </div>
                    <SearchSelect
                        v-model="entrepriseId"
                        :items="entrepriseItems"
                        label="Entreprise"
                        placeholder="Saisissez ou sélectionnez une entreprise…"
                    />
                    <div class="form-group">
                        <label>Trier</label>
                        <select v-model="tri">
                            <option value="recent">Plus récents</option>
                            <option value="ancien">Plus anciens</option>
                            <option value="entreprise">Par entreprise (A→Z)</option>
                        </select>
                    </div>
                </div>
            </div>

            <p class="offres-page__count text-muted">
                {{ postesFiltres.length }} offre(s) affichée(s)
            </p>

            <p v-if="!postesFiltres.length" class="text-empty">
                Aucune offre pour ces critères.
            </p>

            <div class="offres-page__list">
                <article
                    v-for="p in postesFiltres"
                    :key="p.id"
                    class="card offres-page__card"
                >
                    <div class="offres-page__card-head">
                        <div>
                            <h2 class="offres-page__card-title">{{ p.titre }}</h2>
                            <p class="text-muted">
                                {{ p.entreprise }} · {{ p.date }}
                            </p>
                        </div>
                    </div>
                    <p class="offres-page__card-desc">{{ p.description }}</p>
                    <div class="offres-page__card-actions">
                        <Link
                            :href="`/offres/${p.id}`"
                            class="btn btn--primary btn--sm"
                        >
                            Voir l'offre
                        </Link>
                        <button
                            type="button"
                            class="btn btn--secondary btn--sm"
                            @click="copierLien(p.share_url)"
                        >
                            Copier le lien
                        </button>
                    </div>
                </article>
            </div>
        </div>
    </AppLayout>
</template>
