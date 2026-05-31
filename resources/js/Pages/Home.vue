<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import SiteLogo from "@/Components/SiteLogo.vue";

const props = defineProps({
    landingStats: { type: Array, default: () => [] },
});

const page = usePage();
const isGuest = computed(() => !page.props.auth?.user);

const contactForm = useForm({
    nom: "",
    email: "",
    entreprise: "",
    message: "",
});

function submitContact() {
    contactForm.post("/contact", {
        preserveScroll: true,
        onSuccess: () => {
            contactForm.reset();
            const el = document.getElementById("contact");
            el?.scrollIntoView({ behavior: "smooth", block: "start" });
        },
    });
}

const rhBenefits = [
    {
        icon: "keywords",
        title: "Analyse par mots-clés",
        text: "Filtrez et classez les CV en quelques secondes selon les compétences recherchées.",
    },
    {
        icon: "chart",
        title: "Vue d'ensemble",
        text: "Tableau de bord, statuts clairs et historique des décisions pour toute l'équipe RH.",
    },
    {
        icon: "mail",
        title: "Candidats informés",
        text: "Notifications automatiques à chaque changement de statut — moins de relances manuelles.",
    },
];

const valueProps = [
    {
        icon: "document",
        title: "Dépôt en quelques minutes",
        text: "Choisissez l'entreprise et le poste, ajoutez vos coordonnées et envoyez votre CV — avec ou sans compte.",
        tone: "cyan",
    },
    {
        icon: "bell",
        title: "Suivi de votre candidature",
        text: "Créez un compte pour consulter votre statut en ligne et recevoir un e-mail à chaque étape importante.",
        tone: "indigo",
    },
    {
        icon: "edit",
        title: "24 h pour corriger",
        text: "Après l'envoi, vous pouvez encore modifier le poste, vos coordonnées ou remplacer votre fichier.",
        tone: "emerald",
    },
];

const steps = [
    {
        num: 1,
        title: "Choisissez votre offre",
        text: "Sélectionnez l'entreprise et le poste qui vous intéressent.",
    },
    {
        num: 2,
        title: "Envoyez votre CV",
        text: "PDF, DOC ou DOCX — jusqu'à 5 Mo. Vos coordonnées restent sur le dossier.",
    },
    {
        num: 3,
        title: "Suivez l'avancement",
        text: "Avec un compte : statut en direct et alertes e-mail. Sans compte : le recruteur vous contacte si besoin.",
    },
    {
        num: 4,
        title: "Recevez la réponse",
        text: "Vous êtes informé lorsque votre candidature progresse ou aboutit.",
        accent: true,
    },
];

const faqCandidat = [
    {
        q: "Faut-il créer un compte pour postuler ?",
        a: "Non. Vous pouvez déposer sans inscription. Un compte vous permet en plus de suivre votre statut et de recevoir des notifications par e-mail.",
    },
    {
        q: "Quels formats de CV sont acceptés ?",
        a: "PDF, DOC et DOCX, jusqu'à 5 Mo.",
    },
    {
        q: "Puis-je modifier mon CV après l'envoi ?",
        a: "Oui, pendant 24 h après le dépôt : entreprise, poste, coordonnées ou fichier.",
    },
    {
        q: "Comment savoir où en est ma candidature ?",
        a: "Connectez-vous à votre espace candidat ou consultez les e-mails envoyés automatiquement à chaque changement de statut.",
    },
    {
        q: "Que se passe-t-il si je postule sans compte ?",
        a: "Votre dossier est bien enregistré. Le recruteur dispose de vos coordonnées pour vous recontacter en cas de suite favorable.",
    },
];

const faqRh = [
    {
        q: "Comment accéder à l'espace recrutement ?",
        a: "Votre administrateur crée un compte RH. Après connexion, vous accédez au tableau de bord, à la liste des CV et à l'analyse par mots-clés pour vos postes.",
    },
    {
        q: "Chaque recruteur voit-il tous les CV de l'entreprise ?",
        a: "Non. Chaque responsable RH ne consulte que les CV rattachés à ses propres postes, pour un périmètre clair et confidentiel.",
    },
    {
        q: "Comment fonctionne l'analyse par mots-clés ?",
        a: "Vous saisissez des mots-clés (compétences, outils…). La plateforme extrait le texte des CV et calcule un score de correspondance. Vous validez ou refusez, puis confirmez le lot pour appliquer les statuts.",
    },
    {
        q: "Les candidats sont-ils notifiés pendant l'analyse ?",
        a: "Les statuts et e-mails ne sont envoyés qu'après confirmation de l'analyse. Avant cela, le candidat conserve le statut affiché précédemment.",
    },
    {
        q: "Puis-je annuler une analyse lancée par erreur ?",
        a: "Oui. Tant que vous n'avez pas confirmé, vous pouvez effacer l'analyse : les scores provisoires sont supprimés sans modifier les dossiers ni envoyer d'e-mails.",
    },
];

const openFaqCandidat = ref(null);
const openFaqRh = ref(null);

function toggleFaqCandidat(index) {
    openFaqCandidat.value = openFaqCandidat.value === index ? null : index;
}

function toggleFaqRh(index) {
    openFaqRh.value = openFaqRh.value === index ? null : index;
}
</script>

<template>
    <AppLayout landing>
        <div class="landing-page">
            <section class="hero">
                <div
                    class="hero__bg-glow hero__bg-glow--left"
                    aria-hidden="true"
                />
                <div
                    class="hero__bg-glow hero__bg-glow--right"
                    aria-hidden="true"
                />

                <div class="hero__inner landing-inner landing-inner--wide">
                    <div class="hero__grid">
                        <div class="hero__content">
                            <span class="hero__badge">
                                <span
                                    class="hero__badge-dot"
                                    aria-hidden="true"
                                />
                                Votre candidature, simplifiée
                            </span>
                            <h1 class="hero__title">
                                Postulez en ligne,<br />
                                <span class="hero__title-accent"
                                    >suivez votre dossier</span
                                >
                            </h1>
                            <p class="hero__subtitle">
                                Déposez votre CV en quelques clics, corrigez-le
                                si besoin dans les 24 h, et restez informé de
                                l'avancement — avec ou sans compte.
                            </p>
                            <div v-if="isGuest" class="hero__actions">
                                <Link
                                    href="/deposer"
                                    class="btn btn--cta btn--lg"
                                >
                                    Déposer mon CV
                                </Link>
                                <Link
                                    href="/inscription"
                                    class="btn btn--outline-light btn--lg"
                                >
                                    Créer un compte
                                </Link>
                                <Link
                                    href="/login"
                                    class="btn btn--ghost-light btn--lg"
                                >
                                    Se connecter
                                </Link>
                            </div>
                        </div>

                        <div class="hero__visual" aria-hidden="true">
                            <div class="landing-mock landing-mock--candidat">
                                <div class="landing-mock__bar">
                                    <span /><span /><span />
                                </div>
                                <p class="landing-mock__title">
                                    Ma candidature
                                </p>
                                <div class="landing-mock__status">
                                    <span
                                        class="landing-mock__dot landing-mock__dot--done"
                                    />
                                    <div>
                                        <strong>CV reçu</strong>
                                        <small>12 mai · 14:32</small>
                                    </div>
                                </div>
                                <div
                                    class="landing-mock__status landing-mock__status--active"
                                >
                                    <span
                                        class="landing-mock__dot landing-mock__dot--active"
                                    />
                                    <div>
                                        <strong>En cours d'examen</strong>
                                        <small
                                            >Vous serez notifié par
                                            e-mail</small
                                        >
                                    </div>
                                </div>
                                <div
                                    class="landing-mock__status landing-mock__status--muted"
                                >
                                    <span class="landing-mock__dot" />
                                    <div>
                                        <strong>Décision</strong>
                                        <small>Prochaine étape</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="trust-bar">
                    <div class="trust-bar__item">PDF, DOC & DOCX</div>
                    <div class="trust-bar__sep" aria-hidden="true" />
                    <div class="trust-bar__item">Sans compte possible</div>
                    <div class="trust-bar__sep" aria-hidden="true" />
                    <div class="trust-bar__item">Suivi en ligne</div>
                    <div class="trust-bar__sep" aria-hidden="true" />
                    <div class="trust-bar__item">Alertes e-mail</div>
                </div>
            </section>

            <section class="landing-section">
                <div class="landing-inner">
                    <div class="value-props">
                        <article
                            v-for="item in valueProps"
                            :key="item.title"
                            class="value-prop"
                            :class="`value-prop--${item.tone}`"
                        >
                            <span
                                class="value-prop__icon"
                                :class="`value-prop__icon--${item.tone}`"
                                aria-hidden="true"
                            >
                                <svg
                                    v-if="item.icon === 'document'"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                                    />
                                    <path d="M14 2v6h6" />
                                    <path d="M8 13h8M8 17h5" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'bell'"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"
                                    />
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'edit'"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"
                                    />
                                </svg>
                            </span>
                            <h3>{{ item.title }}</h3>
                            <p>{{ item.text }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="landing-section landing-section--muted">
                <div class="landing-inner">
                    <div class="section-header">
                        <p class="section-label">Parcours candidat</p>
                        <h2 class="section-title">Comment ça marche ?</h2>
                        <p class="section-subtitle">
                            Quatre étapes simples, de l'envoi du CV à la
                            réponse.
                        </p>
                    </div>
                    <div class="flow-steps">
                        <div
                            v-for="step in steps"
                            :key="step.num"
                            class="flow-step"
                            :class="{ 'flow-step--last': step.accent }"
                        >
                            <div
                                class="flow-step__num"
                                :class="{
                                    'flow-step__num--accent': step.accent,
                                }"
                            >
                                {{ step.num }}
                            </div>
                            <h4>{{ step.title }}</h4>
                            <p>{{ step.text }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="landing-section">
                <div class="landing-inner">
                    <div class="section-header">
                        <p class="section-label">Votre choix</p>
                        <h2 class="section-title">Avec ou sans compte</h2>
                        <p class="section-subtitle">
                            Postulez immédiatement, ou créez un compte pour un
                            suivi complet.
                        </p>
                    </div>
                    <div class="who-grid">
                        <div class="who-card">
                            <div class="who-card__header">
                                <div
                                    class="who-card__avatar who-card__avatar--indigo"
                                >
                                    👤
                                </div>
                                <div>
                                    <h3>Sans inscription</h3>
                                    <span
                                        class="who-card__tag who-card__tag--indigo"
                                        >Rapide</span
                                    >
                                </div>
                            </div>
                            <ul class="who-card__list">
                                <li
                                    class="who-card__list-item who-card__list-item--ok"
                                >
                                    Dépôt immédiat, sans créer de compte
                                </li>
                                <li
                                    class="who-card__list-item who-card__list-item--ok"
                                >
                                    Coordonnées conservées pour vous recontacter
                                </li>
                                <li
                                    class="who-card__list-item who-card__list-item--info"
                                >
                                    Pas de suivi en ligne du statut
                                </li>
                            </ul>
                            <Link
                                v-if="isGuest"
                                href="/deposer"
                                class="btn btn--secondary btn--block"
                                style="margin-top: 1rem"
                            >
                                Déposer sans compte
                            </Link>
                        </div>
                        <div class="who-card who-card--featured">
                            <div class="who-card__recommended">Recommandé</div>
                            <div class="who-card__header">
                                <div
                                    class="who-card__avatar who-card__avatar--cyan"
                                >
                                    ✅
                                </div>
                                <div>
                                    <h3>Avec compte</h3>
                                    <span
                                        class="who-card__tag who-card__tag--cyan"
                                        >Suivi complet</span
                                    >
                                </div>
                            </div>
                            <ul class="who-card__list">
                                <li
                                    class="who-card__list-item who-card__list-item--ok"
                                >
                                    Statut en temps réel sur le site
                                </li>
                                <li
                                    class="who-card__list-item who-card__list-item--ok"
                                >
                                    E-mail à chaque changement de statut
                                </li>
                                <li
                                    class="who-card__list-item who-card__list-item--ok"
                                >
                                    Modification du dossier pendant 24 h
                                </li>
                            </ul>
                            <Link
                                v-if="isGuest"
                                href="/inscription"
                                class="btn btn--cta btn--block"
                                style="margin-top: 1rem"
                            >
                                Créer mon compte
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="faq-candidat"
                class="landing-section landing-section--muted"
            >
                <div class="landing-inner landing-inner--narrow">
                    <div class="section-header">
                        <p class="section-label">FAQ candidats</p>
                        <h2 class="section-title">Questions fréquentes</h2>
                        <p class="section-subtitle">
                            Tout ce qu'il faut savoir avant de déposer votre CV.
                        </p>
                    </div>
                    <div class="faq-list">
                        <div
                            v-for="(item, i) in faqCandidat"
                            :key="`c-${i}`"
                            class="faq-item"
                            :class="{
                                'faq-item--open': openFaqCandidat === i,
                            }"
                        >
                            <button
                                type="button"
                                class="faq-item__trigger"
                                :aria-expanded="openFaqCandidat === i"
                                @click="toggleFaqCandidat(i)"
                            >
                                <span class="faq-item__question">{{
                                    item.q
                                }}</span>
                                <span
                                    class="faq-item__icon"
                                    aria-hidden="true"
                                />
                            </button>
                            <div
                                class="faq-item__collapse"
                                :class="{
                                    'faq-item__collapse--open':
                                        openFaqCandidat === i,
                                }"
                            >
                                <div class="faq-item__panel">
                                    <p>{{ item.a }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="entreprises"
                class="landing-section landing-section--enterprise"
            >
                <div class="landing-inner">
                    <div class="section-header">
                        <p class="section-label">Espace recrutement</p>
                        <h2 class="section-title">
                            Vous recrutez ? Centralisez vos CV
                        </h2>
                        <p class="section-subtitle">
                            CV Analyzer aide les équipes RH à gagner du temps
                            sur le tri des candidatures, avec un parcours
                            fluide côté candidats.
                        </p>
                    </div>

                    <div class="stats-grid landing-stats-grid">
                        <div
                            v-for="(stat, i) in landingStats"
                            :key="i"
                            class="stat-card"
                            :class="`stat-card--${stat.tone}`"
                        >
                            <strong>{{ stat.value }}</strong>
                            <span>{{ stat.label }}</span>
                        </div>
                    </div>

                    <div class="rh-benefits">
                        <article
                            v-for="item in rhBenefits"
                            :key="item.title"
                            class="rh-benefit"
                        >
                            <span class="rh-benefit__icon" aria-hidden="true">
                                <svg
                                    v-if="item.icon === 'keywords'"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <circle cx="11" cy="11" r="7" />
                                    <path d="M20 20l-3-3" />
                                    <path d="M8 11h6" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'chart'"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M4 19V5" />
                                    <path d="M4 19h16" />
                                    <path d="M8 17V11" />
                                    <path d="M12 17V7" />
                                    <path d="M16 17v-4" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'mail'"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M4 6h16v12H4V6z"
                                    />
                                    <path d="M4 7l8 6 8-6" />
                                </svg>
                            </span>
                            <div>
                                <h3>{{ item.title }}</h3>
                                <p>{{ item.text }}</p>
                            </div>
                        </article>
                    </div>

                    <div class="landing-enterprise-cta">
                        <a href="#contact" class="btn btn--cta">
                            Nous contacter
                        </a>
                    </div>
                </div>
            </section>

            <section
                id="faq-rh"
                class="landing-section landing-section--muted"
            >
                <div class="landing-inner landing-inner--narrow">
                    <div class="section-header">
                        <p class="section-label">FAQ recrutement</p>
                        <h2 class="section-title">Questions fréquentes RH</h2>
                        <p class="section-subtitle">
                            Fonctionnement de l'espace recrutement et des
                            analyses.
                        </p>
                    </div>
                    <div class="faq-list">
                        <div
                            v-for="(item, i) in faqRh"
                            :key="`r-${i}`"
                            class="faq-item"
                            :class="{ 'faq-item--open': openFaqRh === i }"
                        >
                            <button
                                type="button"
                                class="faq-item__trigger"
                                :aria-expanded="openFaqRh === i"
                                @click="toggleFaqRh(i)"
                            >
                                <span class="faq-item__question">{{
                                    item.q
                                }}</span>
                                <span
                                    class="faq-item__icon"
                                    aria-hidden="true"
                                />
                            </button>
                            <div
                                class="faq-item__collapse"
                                :class="{
                                    'faq-item__collapse--open':
                                        openFaqRh === i,
                                }"
                            >
                                <div class="faq-item__panel">
                                    <p>{{ item.a }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="contact"
                class="landing-section landing-section--muted landing-section--contact"
            >
                <div class="landing-inner landing-inner--narrow">
                    <div class="section-header">
                        <p class="section-label">Contact</p>
                        <h2 class="section-title">Parlons de votre besoin</h2>
                        <p class="section-subtitle">
                            Une question, une démo ou un déploiement pour votre
                            équipe ? Laissez-nous un message.
                        </p>
                    </div>

                    <form
                        class="landing-contact-form card"
                        @submit.prevent="submitContact"
                    >
                        <div class="landing-contact-form__row">
                            <div class="form-group">
                                <label for="contact-nom">Nom complet</label>
                                <input
                                    id="contact-nom"
                                    v-model="contactForm.nom"
                                    type="text"
                                    required
                                    autocomplete="name"
                                    placeholder="Jean Dupont"
                                />
                            </div>
                            <div class="form-group">
                                <label for="contact-email">E-mail</label>
                                <input
                                    id="contact-email"
                                    v-model="contactForm.email"
                                    type="email"
                                    required
                                    autocomplete="email"
                                    placeholder="vous@entreprise.com"
                                />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="contact-entreprise">Entreprise</label>
                            <input
                                id="contact-entreprise"
                                v-model="contactForm.entreprise"
                                type="text"
                                required
                                autocomplete="organization"
                                placeholder="Nom de votre société"
                            />
                        </div>
                        <div class="form-group">
                            <label for="contact-message">Message</label>
                            <textarea
                                id="contact-message"
                                v-model="contactForm.message"
                                required
                                rows="5"
                                placeholder="Décrivez votre besoin"
                            />
                        </div>
                        <button
                            type="submit"
                            class="btn btn--primary btn--block"
                            :disabled="contactForm.processing"
                        >
                            {{
                                contactForm.processing
                                    ? "Envoi en cours…"
                                    : "Envoyer le message"
                            }}
                        </button>
                    </form>
                </div>
            </section>

            <section v-if="isGuest" class="landing-cta-section">
                <div class="landing-cta">
                    <h2>Prêt à postuler ?</h2>
                    <p>
                        Envoyez votre CV maintenant ou créez un compte pour
                        suivre chaque étape de votre candidature.
                    </p>
                    <div class="landing-cta__actions">
                        <Link href="/deposer" class="btn btn--cta btn--lg"
                            >Déposer mon CV</Link
                        >
                        <Link
                            href="/inscription"
                            class="btn btn--outline-light btn--lg"
                        >
                            Créer un compte
                        </Link>
                    </div>
                </div>
            </section>

            <footer class="landing-footer">
                <div class="landing-inner landing-footer__inner">
                    <Link href="/" class="landing-footer__brand">
                        <SiteLogo compact />
                        <span>CV Analyzer</span>
                    </Link>
                    <nav class="landing-footer__links">
                        <Link href="/deposer">Déposer un CV</Link>
                        <a href="#entreprises">Entreprises</a>
                        <a href="#contact">Contact</a>
                        <Link href="/inscription">S'inscrire</Link>
                        <Link href="/login">Connexion</Link>
                    </nav>
                    <p class="landing-footer__copy">
                        Plateforme de candidature en ligne.
                    </p>
                </div>
            </footer>
        </div>
    </AppLayout>
</template>
