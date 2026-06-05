<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import ToastContainer from "@/Components/ToastContainer.vue";
import SiteLogo from "@/Components/SiteLogo.vue";
import NotificationBell from "@/Components/NotificationBell.vue";
import { useFlashToast } from "@/composables/useFlashToast";
import { useStaffPaths } from "@/composables/useStaffPaths";

const NAV_DESKTOP_MQ = "(min-width: 1024px)";

defineProps({
    landing: { type: Boolean, default: false },
});

useFlashToast();

const page = usePage();
const paths = useStaffPaths();
const user = computed(() => page.props.auth?.user);
const appName = computed(() => page.props.appName ?? "CV Analyzer");

const peutVoirOffres = computed(() => {
    if (!user.value) return true;
    return user.value.role === "candidat";
});

const peutCompte = computed(() => {
    if (!user.value) return false;
    return user.value.role === "candidat" || user.value.role === "super_admin";
});

const currentPath = computed(() => page.url.split("?")[0]);
const currentQuery = computed(() => {
    const i = page.url.indexOf("?");
    return i >= 0 ? page.url.slice(i + 1) : "";
});

function consulteDepuisAnalyse() {
    return (
        /^\/rh\/cvs\/\d+\/consulter/.test(currentPath.value) &&
        currentQuery.value.includes("depuis=analyse")
    );
}

function isNavActive(href) {
    const p = currentPath.value;

    if (href === "/rh") {
        return p === "/rh" || p === "/rh/";
    }
    if (href === "/rh/cvs/liste") {
        return (
            p === "/rh/cvs/liste" ||
            (/^\/rh\/cvs\/\d+\/consulter/.test(p) && !consulteDepuisAnalyse())
        );
    }
    if (href === "/rh/cvs") {
        return (
            p === "/rh/cvs" ||
            p === "/rh/filtrer" ||
            p === "/rh/filtrer/resultats" ||
            consulteDepuisAnalyse()
        );
    }
    if (href === "/rh/postes") {
        return p.startsWith("/rh/postes");
    }
    if (href === paths.value.adminGerants) {
        return (
            p.startsWith(`${paths.value.admin}/super-admins`) ||
            p.startsWith(`${paths.value.admin}/sous-admins`)
        );
    }
    if (href === paths.value.gerantDashboard) {
        return p === paths.value.gerant || p === `${paths.value.gerant}/`;
    }
    if (href === paths.value.gerantRh) {
        return p.startsWith(`${paths.value.gerant}/rh`);
    }
    if (href === "/rh/cvs/importer") {
        return p.startsWith("/rh/cvs/importer");
    }
    if (href === paths.value.adminBackOffice) {
        return p.startsWith(`${paths.value.admin}/back-office`);
    }
    if (href === paths.value.adminMessages) {
        return p.startsWith(`${paths.value.admin}/messages-contact`);
    }
    if (href === "/candidat/statut") {
        return p.startsWith("/candidat");
    }
    if (href === "/compte") {
        return p === "/compte" || p.startsWith("/compte/mot-de-passe");
    }
    if (href === "/deposer") {
        return p.startsWith("/deposer");
    }

    return p === href;
}

function navLinkClass(href) {
    return ["nav-link", { "nav-link--active": isNavActive(href) }];
}

const navOpen = ref(false);
const isDesktopNav = ref(false);

watch(
    () => page.url,
    () => {
        navOpen.value = false;
    }
);

let removeMqListener = null;

onMounted(() => {
    const mq = window.matchMedia(NAV_DESKTOP_MQ);
    const sync = () => {
        isDesktopNav.value = mq.matches;
        if (mq.matches) {
            navOpen.value = false;
        }
    };
    sync();
    mq.addEventListener("change", sync);
    removeMqListener = () => mq.removeEventListener("change", sync);
});

function lockBodyScroll(lock) {
    if (typeof document === "undefined") return;
    document.body.classList.toggle("nav-menu-open", lock);
}

watch(navOpen, (open) => lockBodyScroll(open));

onUnmounted(() => {
    removeMqListener?.();
    lockBodyScroll(false);
});

function toggleNav() {
    navOpen.value = !navOpen.value;
}

function closeNav() {
    navOpen.value = false;
}

const homeUrl = computed(() => {
    if (!user.value) {
        return "/";
    }

    switch (user.value.role) {
        case "admin":
            return paths.value.adminBackOffice;
        case "super_admin":
            return paths.value.gerantDashboard;
        case "sous_admin":
            return "/rh";
        case "candidat":
            return "/candidat/statut";
        default:
            return "/";
    }
});
</script>

<template>
    <div
        class="app-layout"
        :class="{ 'app-layout--landing': landing }"
    >
        <div class="app-shell-header">
            <header class="app-header">
                <Link :href="homeUrl" class="app-header__brand" @click="closeNav">
                    <SiteLogo />
                    <span class="app-header__name">{{ appName }}</span>
                </Link>
                <div class="app-header__end">
                    <NotificationBell
                        v-if="user?.role === 'candidat'"
                        class="app-header__notif-slot"
                    />
                    <button
                        type="button"
                        class="app-header__toggle"
                        :class="{ 'app-header__toggle--open': navOpen }"
                        :aria-expanded="navOpen"
                        aria-controls="app-header-nav"
                        @click="toggleNav"
                    >
                        <span class="sr-only">{{
                            navOpen ? "Fermer le menu" : "Ouvrir le menu"
                        }}</span>
                        <span class="app-header__toggle-bar" aria-hidden="true" />
                    </button>
                </div>
            </header>
            <Teleport to="body" :disabled="isDesktopNav">
                <nav
                    id="app-header-nav"
                    class="app-header__nav"
                    :class="{ 'app-header__nav--open': navOpen }"
                >
                <Link
                    v-if="peutVoirOffres"
                    href="/offres"
                    :class="navLinkClass('/offres')"
                    @click="closeNav"
                    >Offres d'emploi</Link
                >
                <template v-if="user">
                    <template v-if="user.role === 'admin'">
                        <Link
                            :href="paths.adminBackOffice"
                            :class="navLinkClass(paths.adminBackOffice)"
                            @click="closeNav"
                            >Back-office</Link
                        >
                        <Link
                            :href="paths.adminMessages"
                            :class="navLinkClass(paths.adminMessages)"
                            @click="closeNav"
                            >Messages</Link
                        >
                        <Link
                            :href="paths.adminGerants"
                            :class="navLinkClass(paths.adminGerants)"
                            @click="closeNav"
                            >Gérants</Link
                        >
                    </template>
                    <template v-else-if="user.role === 'super_admin'">
                        <Link
                            :href="paths.gerantDashboard"
                            :class="navLinkClass(paths.gerantDashboard)"
                            @click="closeNav"
                            >Back-office</Link
                        >
                        <Link
                            :href="paths.gerantRh"
                            :class="navLinkClass(paths.gerantRh)"
                            @click="closeNav"
                            >Équipe RH</Link
                        >
                    </template>
                    <template v-else-if="user.role === 'sous_admin'">
                        <Link
                            href="/rh"
                            :class="navLinkClass('/rh')"
                            @click="closeNav"
                            >Tableau de bord</Link
                        >
                        <Link
                            href="/rh/cvs/liste"
                            :class="navLinkClass('/rh/cvs/liste')"
                            @click="closeNav"
                            >CVs reçus</Link
                        >
                        <Link
                            href="/rh/cvs"
                            :class="navLinkClass('/rh/cvs')"
                            @click="closeNav"
                            >Analyser</Link
                        >
                        <Link
                            href="/rh/postes"
                            :class="navLinkClass('/rh/postes')"
                            @click="closeNav"
                            >Postes</Link
                        >
                    </template>
                    <template v-else-if="user.role === 'candidat'">
                        <Link
                            href="/candidat/statut"
                            :class="navLinkClass('/candidat/statut')"
                            @click="closeNav"
                            >Ma candidature</Link
                        >
                    </template>
                    <Link
                        v-if="peutCompte"
                        href="/compte"
                        :class="navLinkClass('/compte')"
                        @click="closeNav"
                        >Mon compte</Link
                    >
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="btn btn--ghost"
                        @click="closeNav"
                    >
                        Déconnexion
                    </Link>
                </template>
                <template v-else>
                    <Link
                        href="/inscription"
                        class="btn btn--secondary"
                        @click="closeNav"
                        >S'inscrire</Link
                    >
                    <Link
                        href="/login"
                        class="btn btn--primary"
                        @click="closeNav"
                        >Se connecter</Link
                    >
                </template>
                </nav>
            </Teleport>
        </div>

        <Teleport to="body" :disabled="isDesktopNav">
            <div
                class="app-header__backdrop"
                :class="{ 'app-header__backdrop--visible': navOpen }"
                aria-hidden="true"
                @click="closeNav"
            />
        </Teleport>

        <main class="app-main">
            <slot />
        </main>
        <ToastContainer />
    </div>
</template>
