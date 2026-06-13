<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useStaffPaths } from "@/composables/useStaffPaths";

const paths = useStaffPaths();
const props = defineProps({ entreprise: Object });

const form = useForm({
    description: props.entreprise?.description ?? "",
});
</script>

<template>
    <AppLayout>
        <p class="offres-back-link">
            <Link :href="paths.gerantDashboard">← Retour au back-office</Link>
        </p>

        <div class="page-header">
            <p class="page-header__label">Gérant</p>
            <h1>{{ entreprise?.nom }} — présentation</h1>
            <p>Texte visible par les candidats sur les offres.</p>
        </div>

        <div class="card">
            <form @submit.prevent="form.put(`${paths.value.gerant}/entreprise`)">
                <div class="form-group">
                    <label>Description</label>
                    <textarea v-model="form.description" rows="8" />
                </div>
                <button
                    type="submit"
                    class="btn btn--primary"
                    :disabled="form.processing"
                >
                    Enregistrer
                </button>
            </form>
        </div>
    </AppLayout>
</template>
