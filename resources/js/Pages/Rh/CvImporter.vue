<script setup>

import { Link, useForm } from "@inertiajs/vue3";

import { computed, ref } from "vue";

import AppLayout from "@/Layouts/AppLayout.vue";

import SearchSelect from "@/Components/SearchSelect.vue";



const props = defineProps({

    postes: Array,

    entreprise: String,

});



const posteItems = computed(() =>

    (props.postes || []).map((p) => ({

        id: p.id,

        label: p.titre,

        description: p.description || "",

    }))

);



const form = useForm({

    poste_id: "",

    fichiers: [],

});



const fileNames = ref([]);



function onFiles(event) {

    const files = [...(event.target.files || [])];

    form.fichiers = files;

    fileNames.value = files.map((f) => f.name);

}



function submit() {

    form.post("/rh/cvs/importer", {

        preserveScroll: true,

        forceFormData: true,

        onSuccess: () => {

            form.reset();

            fileNames.value = [];

        },

    });

}

</script>



<template>

    <AppLayout>

        <div class="page-header">

            <p class="page-header__label">RH</p>

            <h1>Importer des CV</h1>

            <p v-if="entreprise">

                Entreprise : <strong>{{ entreprise }}</strong>

            </p>

        </div>



        <p class="offres-back-link">

            <Link href="/rh/cvs/liste">← Retour aux CVs reçus</Link>

        </p>



        <div class="card">

            <p class="card__lead">

                Choisissez le poste concerné, puis sélectionnez un ou plusieurs

                fichiers (PDF, DOC, DOCX). Chaque CV reçoit un numéro de dossier.

                Le nom et l'e-mail se complètent en consultant le fichier.

            </p>

            <form @submit.prevent="submit">

                <SearchSelect

                    v-model="form.poste_id"

                    :items="posteItems"

                    label="Poste"

                    placeholder="Saisissez ou sélectionnez un poste…"

                />

                <div class="form-group">

                    <label>Fichiers CV</label>

                    <div class="file-upload">

                        <label class="file-upload__trigger">

                            <input

                                type="file"

                                class="file-upload__input"

                                accept=".pdf,.doc,.docx"

                                multiple

                                required

                                @change="onFiles"

                            />

                            <span class="btn btn--accent">Choisir des fichiers</span>

                        </label>

                        <p class="file-upload__name">

                            {{

                                fileNames.length

                                    ? fileNames.join(", ")

                                    : "Aucun fichier choisi"

                            }}

                        </p>

                    </div>

                    <p

                        v-if="form.errors.fichiers"

                        class="form-error"

                        style="margin-top: 0.35rem"

                    >

                        {{ form.errors.fichiers }}

                    </p>

                </div>

                <button

                    type="submit"

                    class="btn btn--accent"

                    :disabled="

                        form.processing ||

                        !form.poste_id ||

                        !form.fichiers.length

                    "

                >

                    Importer

                </button>

            </form>

        </div>

    </AppLayout>

</template>

