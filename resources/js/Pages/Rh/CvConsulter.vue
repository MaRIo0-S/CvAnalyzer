<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useCvDecision } from "@/composables/useCvDecision";

const props = defineProps({
    cv: Object,
    fichierUrl: String,
    telechargerUrl: String,
    retourUrl: { type: String, default: "/rh/cvs/liste" },
    retourLabel: { type: String, default: "Retour à la liste des CV" },
    lotEnAttente: { type: Boolean, default: false },
});

const { peutDecider, valider, refuser, mailtoCandidat } = useCvDecision({
    lotEnAttente: props.lotEnAttente,
    afterSuccess: (cv, { valide }) => {
        if (!props.lotEnAttente && valide) {
            router.visit(props.retourUrl, {
                onFinish: () => {
                    setTimeout(() => {
                        window.location.href = mailtoCandidat(cv);
                    }, 200);
                },
            });
            return;
        }
        router.visit(props.retourUrl);
    },
});

const peutAfficherDecisions = computed(() => peutDecider(props.cv));

function validerCv() {
    valider(props.cv);
}

function refuserCv() {
    refuser(props.cv);
}
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Consultation</p>
            <h1>{{ cv.nom_candidat }}</h1>
            <p>
                {{ cv.poste }} — {{ cv.entreprise }}
                <span
                    :class="[
                        'badge',
                        `badge--${
                            cv.statut === 'valide'
                                ? 'valide'
                                : cv.statut === 'non_valide'
                                ? 'refuse'
                                : cv.statut === 'en_cours_analyse'
                                ? 'analyse'
                                : 'recu'
                        }`,
                    ]"
                >
                    {{ cv.statut_label }}
                </span>
            </p>
        </div>

        <div v-if="peutAfficherDecisions" class="card">
            <h2 class="card__title card__title--sm">Décision RH</h2>
            <p class="card__lead">
                <template v-if="lotEnAttente">
                    Décision provisoire : elle ne sera envoyée au candidat qu'après
                    confirmation de l'analyse sur la page des résultats.
                </template>
                <template v-else>
                    Dossier en cours d'examen : validez ou refusez ci-dessous.
                </template>
            </p>
            <div class="table-actions">
                <button
                    type="button"
                    class="btn btn--success btn--sm"
                    @click="validerCv"
                >
                    Valider
                </button>
                <button
                    type="button"
                    class="btn btn--danger btn--sm"
                    @click="refuserCv"
                >
                    Refuser
                </button>
            </div>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">Informations</h2>
            <dl class="detail-list">
                <div>
                    <dt>E-mail</dt>
                    <dd>{{ cv.email_candidat || "—" }}</dd>
                </div>
                <div>
                    <dt>Date de dépôt</dt>
                    <dd>{{ cv.date_depot }}</dd>
                </div>
                <div>
                    <dt>Format</dt>
                    <dd>{{ cv.format_fichier }}</dd>
                </div>
                <div v-if="cv.score != null">
                    <dt>Score</dt>
                    <dd>{{ cv.score }}%</dd>
                </div>
                <div v-if="cv.mots_cles_matches?.length">
                    <dt>Mots-clés trouvés</dt>
                    <dd>{{ cv.mots_cles_matches.join(", ") }}</dd>
                </div>
            </dl>
        </div>

        <div v-if="cv.fichier_preview" class="card">
            <h2 class="card__title card__title--sm">Aperçu du CV</h2>
            <iframe
                :src="fichierUrl"
                class="cv-preview"
                title="Aperçu du CV"
            />
        </div>

        <div v-else-if="cv.texte_extrait" class="card">
            <h2 class="card__title card__title--sm">Extrait du texte</h2>
            <pre class="cv-extrait">{{ cv.texte_extrait }}</pre>
        </div>

        <div class="table-actions">
            <Link :href="retourUrl" class="btn btn--ghost">{{ retourLabel }}</Link>
            <a :href="telechargerUrl" class="btn btn--secondary" download>
                Télécharger le CV
            </a>
        </div>
    </AppLayout>
</template>
