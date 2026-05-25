<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import SearchSelect from "@/Components/SearchSelect.vue";

const props = defineProps({
    entreprises: Array,
    postes: Array,
    graceHours: { type: Number, default: 24 },
    cvModifiable: Object,
    isLoggedIn: Boolean,
    userDefaults: Object,
});

const form = useForm({
    nom_candidat: props.userDefaults?.nom_candidat ?? "",
    email_candidat: props.userDefaults?.email_candidat ?? "",
    entreprise_id: "",
    poste_id: "",
    fichier: null,
});

const modifyForm = useForm({
    nom_candidat: "",
    email_candidat: "",
    entreprise_id: "",
    poste_id: "",
    fichier: null,
});

const entrepriseItems = computed(() =>
    (props.entreprises || []).map((e) => ({
        id: e.id,
        label: e.nom,
        description: e.description || "",
    }))
);

function posteItemsFor(entrepriseId) {
    return (props.postes || [])
        .filter(
            (p) =>
                !entrepriseId ||
                String(p.entreprise_id) === String(entrepriseId)
        )
        .map((p) => ({
            id: p.id,
            label: p.titre,
            description: p.description || "",
        }));
}

const posteItems = computed(() => posteItemsFor(form.entreprise_id));
const posteItemsModify = computed(() =>
    posteItemsFor(modifyForm.entreprise_id)
);

const selectedEntreprise = computed(() =>
    props.entreprises?.find(
        (e) => String(e.id) === String(form.entreprise_id)
    )
);

const selectedPoste = computed(() =>
    props.postes?.find((p) => String(p.id) === String(form.poste_id))
);

const selectedEntrepriseModify = computed(() =>
    props.entreprises?.find(
        (e) => String(e.id) === String(modifyForm.entreprise_id)
    )
);

const selectedPosteModify = computed(() =>
    props.postes?.find((p) => String(p.id) === String(modifyForm.poste_id))
);

const canShowDepotForm = computed(
    () => form.entreprise_id && form.poste_id && !props.cvModifiable
);

watch(
    () => props.cvModifiable,
    (cv) => {
        if (cv) {
            modifyForm.nom_candidat = cv.nom_candidat ?? "";
            modifyForm.email_candidat = cv.email_candidat ?? "";
            modifyForm.entreprise_id = cv.entreprise_id ?? "";
            modifyForm.poste_id = cv.poste_id ?? "";
        }
    },
    { immediate: true }
);

watch(
    () => form.entreprise_id,
    () => {
        form.poste_id = "";
    }
);

watch(
    () => modifyForm.entreprise_id,
    () => {
        modifyForm.poste_id = "";
    }
);

function submit() {
    form.post("/deposer", {
        forceFormData: true,
        onSuccess: () => {
            form.reset("fichier");
            depotFileName.value = "";
        },
    });
}

function modifierCandidature() {
    if (!props.cvModifiable) return;
    modifyForm.post(`/deposer/${props.cvModifiable.id}`, {
        forceFormData: true,
        onSuccess: () => {
            modifyForm.reset("fichier");
            modifyFileName.value = "";
        },
    });
}

const depotFileName = ref("");
const modifyFileName = ref("");

function onDepotFile(event) {
    const file = event.target.files[0];
    form.fichier = file || null;
    depotFileName.value = file?.name ?? "";
}

function onModifyFile(event) {
    const file = event.target.files[0];
    modifyForm.fichier = file || null;
    modifyFileName.value = file?.name ?? "";
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Candidat</p>
            <h1>Déposer un CV</h1>
            <p>
                Consultez l'entreprise et le poste, puis envoyez votre
                candidature sur cette même page.
            </p>
        </div>

        <div class="hint-box">
            <strong>À savoir</strong>
            <ul>
                <li>
                    Pendant <strong>{{ graceHours }} h</strong> après le dépôt,
                    vous pouvez modifier votre dossier — avec ou sans compte.
                </li>
                <li>
                    <strong>Avec un compte</strong> (<Link href="/inscription"
                        >inscription</Link
                    >) : suivi du statut + e-mails automatiques.
                </li>
                <li>
                    <strong>Sans compte</strong> : pas de suivi en ligne ni
                    d'e-mail automatique.
                </li>
            </ul>
        </div>

        <div v-if="cvModifiable" class="modify-panel">
            <h3>
                Modifier ma candidature (dossier n°{{ cvModifiable.reference }})
            </h3>
            <p>
                Modifiable jusqu'au
                <strong>{{ cvModifiable.modifiable_jusqu }}</strong>.
            </p>
            <form @submit.prevent="modifierCandidature">
                <div class="depot-steps">
                    <section class="depot-step card">
                        <h2 class="card__title card__title--sm">
                            1. Entreprise
                        </h2>
                        <SearchSelect
                            v-model="modifyForm.entreprise_id"
                            :items="entrepriseItems"
                            label="Rechercher une entreprise"
                            placeholder="Saisir le nom de l'entreprise…"
                        />
                        <div
                            v-if="selectedEntrepriseModify"
                            class="info-panel"
                        >
                            <h3>{{ selectedEntrepriseModify.nom }}</h3>
                            <p
                                v-if="selectedEntrepriseModify.description"
                                class="info-panel__text"
                            >
                                {{ selectedEntrepriseModify.description }}
                            </p>
                            <p v-else class="info-panel__muted">
                                Aucune présentation détaillée pour le moment.
                            </p>
                        </div>
                    </section>

                    <section class="depot-step card">
                        <h2 class="card__title card__title--sm">2. Poste</h2>
                        <SearchSelect
                            v-model="modifyForm.poste_id"
                            :items="posteItemsModify"
                            label="Rechercher un poste"
                            placeholder="Saisir le titre du poste…"
                            :disabled="!modifyForm.entreprise_id"
                            empty-text="Choisissez d'abord une entreprise."
                        />
                        <div v-if="selectedPosteModify" class="info-panel">
                            <h3>{{ selectedPosteModify.titre }}</h3>
                            <p
                                v-if="selectedPosteModify.description"
                                class="info-panel__text"
                            >
                                {{ selectedPosteModify.description }}
                            </p>
                            <p v-else class="info-panel__muted">
                                Aucune description détaillée pour ce poste.
                            </p>
                        </div>
                    </section>
                </div>

                <div class="form-group">
                    <label>Nom complet</label>
                    <input
                        v-model="modifyForm.nom_candidat"
                        type="text"
                        required
                    />
                </div>
                <div class="form-group">
                    <label>Email de contact</label>
                    <input
                        v-model="modifyForm.email_candidat"
                        type="email"
                        required
                    />
                </div>
                <div class="form-group">
                    <label>Nouveau CV (optionnel)</label>
                    <div class="file-upload">
                        <label class="file-upload__trigger">
                            <input
                                type="file"
                                class="file-upload__input"
                                accept=".pdf,.doc,.docx"
                                @change="onModifyFile"
                            />
                            <span class="btn btn--accent">Importer un CV</span>
                        </label>
                        <p class="file-upload__name">
                            {{ modifyFileName || "Aucun fichier choisi" }}
                        </p>
                    </div>
                </div>
                <button
                    type="submit"
                    class="btn btn--accent"
                    :disabled="modifyForm.processing"
                >
                    Enregistrer les modifications
                </button>
            </form>
        </div>

        <div v-if="!cvModifiable" class="depot-steps">
            <section class="depot-step card">
                <h2 class="card__title card__title--sm">1. Choisir l'entreprise</h2>
                <p class="card__lead">
                    Recherchez et sélectionnez l'entreprise qui recrute.
                </p>
                <SearchSelect
                    v-model="form.entreprise_id"
                    :items="entrepriseItems"
                    label="Entreprise"
                    placeholder="Saisir le nom de l'entreprise…"
                />
                <div v-if="selectedEntreprise" class="info-panel">
                    <h3>{{ selectedEntreprise.nom }}</h3>
                    <p
                        v-if="selectedEntreprise.description"
                        class="info-panel__text"
                    >
                        {{ selectedEntreprise.description }}
                    </p>
                    <p v-else class="info-panel__muted">
                        Le recruteur n'a pas encore publié de présentation pour
                        cette entreprise.
                    </p>
                </div>
            </section>

            <section class="depot-step card">
                <h2 class="card__title card__title--sm">2. Choisir le poste</h2>
                <p class="card__lead">
                    Les postes listés appartiennent à l'entreprise sélectionnée.
                </p>
                <SearchSelect
                    v-model="form.poste_id"
                    :items="posteItems"
                    label="Poste"
                    placeholder="Saisir le titre du poste…"
                    :disabled="!form.entreprise_id"
                    empty-text="Sélectionnez d'abord une entreprise."
                />
                <div v-if="selectedPoste" class="info-panel">
                    <h3>{{ selectedPoste.titre }}</h3>
                    <p v-if="selectedPoste.description" class="info-panel__text">
                        {{ selectedPoste.description }}
                    </p>
                    <p v-else class="info-panel__muted">
                        Aucune description détaillée pour ce poste.
                    </p>
                </div>
            </section>

            <section v-if="canShowDepotForm" class="depot-step card">
                <h2 class="card__title card__title--sm">
                    3. Envoyer votre CV
                </h2>
                <p class="card__lead">
                    L'analyse RH commencera {{ graceHours }} h après l'envoi.
                </p>

                <form @submit.prevent="submit">
                    <div class="form-group">
                        <label>Nom complet</label>
                        <input
                            v-model="form.nom_candidat"
                            type="text"
                            required
                        />
                    </div>
                    <div class="form-group">
                        <label>Email de contact</label>
                        <input
                            v-model="form.email_candidat"
                            type="email"
                            required
                        />
                        <small
                            >Utilisé par le recruteur pour vous contacter.</small
                        >
                    </div>
                    <div class="form-group">
                        <label>CV (PDF, DOC, DOCX — max 5 Mo)</label>
                        <div class="file-upload">
                            <label class="file-upload__trigger">
                                <input
                                    type="file"
                                    class="file-upload__input"
                                    accept=".pdf,.doc,.docx"
                                    required
                                    @change="onDepotFile"
                                />
                                <span class="btn btn--accent"
                                    >Importer un CV</span
                                >
                            </label>
                            <p class="file-upload__name">
                                {{
                                    depotFileName || "Aucun fichier choisi"
                                }}
                            </p>
                        </div>
                    </div>
                    <p class="depot-recap text-muted">
                        <strong>Récapitulatif :</strong>
                        {{ selectedEntreprise?.nom }} — {{ selectedPoste?.titre }}
                    </p>
                    <button
                        type="submit"
                        class="btn btn--primary"
                        :disabled="form.processing"
                    >
                        Envoyer mon CV
                    </button>
                </form>
            </section>

            <p
                v-else-if="form.entreprise_id && !form.poste_id"
                class="text-muted depot-hint"
            >
                Sélectionnez un poste pour afficher le formulaire d'envoi.
            </p>
        </div>

        <p v-else-if="cvModifiable" class="text-muted" style="margin-top: 1rem">
            Un seul dépôt en cours de modification est autorisé pour cette
            session.
        </p>
    </AppLayout>
</template>
