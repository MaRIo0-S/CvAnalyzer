<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    poste: Object,
    entreprise: Object,
    shareUrl: String,
    deposerUrl: String,
});

function copierLien() {
    navigator.clipboard?.writeText(props.shareUrl);
}
</script>

<template>
    <AppLayout landing>
        <div class="offres-page offres-show-page landing-inner landing-inner--narrow">
            <p style="margin-bottom: 1.25rem">
                <Link href="/offres" class="btn btn--accent btn--sm"
                    >← Autres offres</Link
                >
            </p>
            <h1>{{ poste.titre }}</h1>
            <p class="text-muted">
                {{ entreprise?.nom }} · Publié le {{ poste.created_at }}
            </p>

            <div class="card" style="margin: 1.5rem 0">
                <h2 class="card__title card__title--sm">Le poste</h2>
                <p style="white-space: pre-wrap">{{ poste.description }}</p>
            </div>

            <div v-if="entreprise" class="card" style="margin-bottom: 1.5rem">
                <h2 class="card__title card__title--sm">L'entreprise</h2>
                <p style="white-space: pre-wrap">
                    {{ entreprise.description || "—" }}
                </p>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem">
                <Link :href="deposerUrl" class="btn btn--accent"
                    >Déposer mon CV pour ce poste</Link
                >
                <button
                    type="button"
                    class="btn btn--secondary"
                    @click="copierLien"
                >
                    Copier le lien de l'offre
                </button>
            </div>
        </div>
    </AppLayout>
</template>
