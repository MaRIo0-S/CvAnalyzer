<script setup>
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useCvDecision } from "@/composables/useCvDecision";
import { badgeClassFromCv } from "@/utils/cvList";

const props = defineProps({
    cv: Object,
    fichierUrl: String,
    telechargerUrl: String,
    retourUrl: { type: String, default: "/rh/cvs/liste" },
    retourLabel: { type: String, default: "Retour à la liste des CV" },
    lotEnAttente: { type: Boolean, default: false },
    depuisAnalyse: { type: Boolean, default: false },
});

const { peutDecider, aDecisionProvisoire, annulerDecision, valider, refuser, mailtoCandidat } =
    useCvDecision({
        lotEnAttente: props.lotEnAttente,
        afterSuccess: (cv, { valide, annulee }) => {
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
            if (props.lotEnAttente && !annulee) {
                router.reload({ preserveScroll: true });
                return;
            }
            if (props.lotEnAttente && annulee) {
                router.reload({ preserveScroll: true });
                return;
            }
            router.visit(props.retourUrl);
        },
    });
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Consultation</p>
            <h1>{{ cv.nom_candidat }}</h1>
            <p>
                {{ cv.poste }}
                <template v-if="cv.entreprise"> — {{ cv.entreprise }}</template>
                <span
                    :class="['badge', badgeClassFromCv(cv)]"
                    style="margin-left: 0.5rem"
                >
                    {{ cv.statut_label }}
                </span>
            </p>
        </div>

        <div class="cv-consult-actions table-actions">
            <Link :href="retourUrl" class="btn btn--ghost">
                {{ retourLabel }}
            </Link>
            <a :href="telechargerUrl" class="btn btn--secondary" download>
                Télécharger le CV
            </a>
            <a
                v-if="cv.fichier_preview"
                :href="fichierUrl"
                class="btn btn--accent"
                target="_blank"
                rel="noopener noreferrer"
            >
                Ouvrir le PDF dans un nouvel onglet
            </a>
        </div>

        <div
            v-if="lotEnAttente && (peutDecider(cv) || aDecisionProvisoire(cv))"
            class="card"
        >
            <h2 class="card__title card__title--sm">Décision provisoire</h2>
            <p class="card__lead">
                <template v-if="aDecisionProvisoire(cv)">
                    Décision enregistrée pour ce lot :
                    <strong>{{ cv.statut_label }}</strong>. Vous pouvez la
                    retirer ou la remplacer avant de confirmer l'analyse.
                </template>
                <template v-else>
                    Validez ou refusez ce CV ; la décision ne sera appliquée
                    qu'après confirmation sur la page des résultats.
                </template>
            </p>
            <div class="table-actions">
                <button
                    v-if="aDecisionProvisoire(cv)"
                    type="button"
                    class="btn btn--ghost btn--sm"
                    @click="annulerDecision(cv)"
                >
                    Annuler la décision
                </button>
                <button
                    v-if="peutDecider(cv) || cv.decision_provisoire !== 'valide'"
                    type="button"
                    class="btn btn--success btn--sm"
                    @click="valider(cv)"
                >
                    Valider
                </button>
                <button
                    v-if="peutDecider(cv) || cv.decision_provisoire !== 'non_valide'"
                    type="button"
                    class="btn btn--danger btn--sm"
                    @click="refuser(cv)"
                >
                    Refuser
                </button>
            </div>
        </div>

        <div v-else-if="peutDecider(cv)" class="card">
            <h2 class="card__title card__title--sm">Décision RH</h2>
            <p class="card__lead">
                Dossier en cours d'examen : validez ou refusez ci-dessous.
            </p>
            <div class="table-actions">
                <button
                    type="button"
                    class="btn btn--success btn--sm"
                    @click="valider(cv)"
                >
                    Valider
                </button>
                <button
                    type="button"
                    class="btn btn--danger btn--sm"
                    @click="refuser(cv)"
                >
                    Refuser
                </button>
            </div>
        </div>

        <div class="card cv-detail-card">
            <h2 class="card__title card__title--sm">Informations</h2>
            <dl class="cv-detail-grid">
                <div class="cv-detail-grid__item">
                    <dt>E-mail</dt>
                    <dd>{{ cv.email_candidat || "—" }}</dd>
                </div>
                <div class="cv-detail-grid__item">
                    <dt>Date de dépôt</dt>
                    <dd>{{ cv.date_depot }}</dd>
                </div>
                <div class="cv-detail-grid__item">
                    <dt>Format</dt>
                    <dd>{{ cv.format_fichier }}</dd>
                </div>
                <div v-if="cv.score != null" class="cv-detail-grid__item">
                    <dt>Score</dt>
                    <dd><span class="score-pill">{{ cv.score }}%</span></dd>
                </div>
                <div
                    v-if="cv.mots_cles_matches?.length"
                    class="cv-detail-grid__item cv-detail-grid__item--wide"
                >
                    <dt>Mots-clés trouvés</dt>
                    <dd>
                        <span
                            v-for="mot in cv.mots_cles_matches"
                            :key="mot"
                            class="cv-detail-tag"
                            >{{ mot }}</span
                        >
                    </dd>
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
    </AppLayout>
</template>
