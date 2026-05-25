<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted } from "vue";
import ToastContainer from "@/Components/ToastContainer.vue";
import SiteLogo from "@/Components/SiteLogo.vue";
import NotificationBell from "@/Components/NotificationBell.vue";
import { useFlashToast } from "@/composables/useFlashToast";

defineProps({
    landing: { type: Boolean, default: false },
});

useFlashToast();

function cleanupStaleOverlays() {
    const bar = document.getElementById("nprogress");
    if (bar) {
        try {
            bar.hidePopover();
        } catch {}
    }
}

onMounted(cleanupStaleOverlays);

const removeFinishListener = router.on("finish", cleanupStaleOverlays);
onUnmounted(() => removeFinishListener());

const page = usePage();
const user = computed(() => page.props.auth?.user);
const appName = computed(() => page.props.appName ?? "CV Analyzer");

const peutDeposer = computed(() => {
    if (!user.value) return true;
    return user.value.role === "candidat";
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
    if (href === "/admin/sous-admins") {
        return p.startsWith("/admin/sous-admins");
    }
    if (href === "/admin/back-office") {
        return p.startsWith("/admin/back-office");
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

const homeUrl = computed(() => {
    if (!user.value) {
        return "/";
    }

    switch (user.value.role) {
        case "admin":
            return "/admin/sous-admins";
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
    <div class="app-layout" :class="{ 'app-layout--landing': landing }">
        <header class="app-header">
            <Link :href="homeUrl" class="app-header__brand">
                <SiteLogo />
                <span class="app-header__name">{{ appName }}</span>
            </Link>
            <nav class="app-header__nav">
                <Link
                    v-if="peutDeposer"
                    href="/deposer"
                    :class="navLinkClass('/deposer')"
                    >Déposer un CV</Link
                >
                <template v-if="user">
                    <template v-if="user.role === 'admin'">
                        <Link
                            href="/admin/back-office"
                            :class="navLinkClass('/admin/back-office')"
                            >Back-office</Link
                        >
                        <Link
                            href="/admin/sous-admins"
                            :class="navLinkClass('/admin/sous-admins')"
                            >Sub-admins</Link
                        >
                    </template>
                    <template v-else-if="user.role === 'sous_admin'">
                        <Link href="/rh" :class="navLinkClass('/rh')"
                            >Tableau de bord</Link
                        >
                        <Link
                            href="/rh/cvs/liste"
                            :class="navLinkClass('/rh/cvs/liste')"
                            >CVs reçus</Link
                        >
                        <Link href="/rh/cvs" :class="navLinkClass('/rh/cvs')"
                            >Analyser</Link
                        >
                        <Link
                            href="/rh/postes"
                            :class="navLinkClass('/rh/postes')"
                            >Postes</Link
                        >
                    </template>
                    <template v-else-if="user.role === 'candidat'">
                        <NotificationBell />
                        <Link
                            href="/candidat/statut"
                            :class="navLinkClass('/candidat/statut')"
                            >Ma candidature</Link
                        >
                    </template>
                    <Link
                        v-if="
                            user.role === 'candidat' ||
                            user.role === 'sous_admin'
                        "
                        href="/compte"
                        :class="navLinkClass('/compte')"
                        >Mon compte</Link
                    >
                    <Link
                        v-else
                        href="/compte/mot-de-passe"
                        :class="navLinkClass('/compte')"
                        >Mot de passe</Link
                    >
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="btn btn--ghost"
                    >
                        Déconnexion
                    </Link>
                </template>
                <template v-else>
                    <Link href="/inscription" class="btn btn--secondary"
                        >S'inscrire</Link
                    >
                    <Link href="/login" class="btn btn--primary"
                        >Se connecter</Link
                    >
                </template>
            </nav>
        </header>

        <main class="app-main">
            <slot />
        </main>
        <ToastContainer />
    </div>
</template>
