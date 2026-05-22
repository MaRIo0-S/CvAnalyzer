<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import SiteLogo from "@/Components/SiteLogo.vue";

const page = usePage();
const isGuest = computed(() => !page.props.auth?.user);

const valueProps = [
    {
        icon: "📄",
        title: "Dépôt en quelques minutes",
        text: "Choisissez l'entreprise et le poste, ajoutez vos coordonnées et envoyez votre CV — avec ou sans compte.",
        tone: "cyan",
    },
    {
        icon: "🔔",
        title: "Suivi de votre candidature",
        text: "Créez un compte pour consulter votre statut en ligne et recevoir un e-mail à chaque étape importante.",
        tone: "indigo",
    },
    {
        icon: "✏️",
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

const faqItems = [
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
                            <span class="value-prop__icon">{{
                                item.icon
                            }}</span>
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

            <section class="landing-section landing-section--muted">
                <div class="landing-inner landing-inner--narrow">
                    <div class="section-header">
                        <p class="section-label">FAQ</p>
                        <h2 class="section-title">Questions fréquentes</h2>
                    </div>
                    <div class="faq-list">
                        <details
                            v-for="(item, i) in faqItems"
                            :key="i"
                            class="faq-item"
                        >
                            <summary>{{ item.q }}</summary>
                            <p>{{ item.a }}</p>
                        </details>
                    </div>
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
