<script setup>
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PasswordInput from "@/Components/PasswordInput.vue";

defineProps({ sousAdmins: Array });

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    entreprise_nom: "",
});
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <p class="page-header__label">Administration</p>
            <h1>Gestion des sub-admins</h1>
            <p>
                Saisissez le nom de l'entreprise : si elle existe déjà, le
                sub-admin y sera rattaché ; sinon elle sera créée.
            </p>
        </div>

        <div class="card">
            <h2 class="card__title card__title--sm">Nouveau sub-admin</h2>
            <form
                @submit.prevent="
                    form.post('/admin/sous-admins', {
                        preserveScroll: true,
                        onSuccess: () => {
                            form.reset();
                            form.clearErrors();
                        },
                    })
                "
            >
                <div class="form-group">
                    <label>Nom</label>
                    <input v-model="form.name" type="text" required />
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input v-model="form.email" type="email" required />
                </div>
                <PasswordInput
                    v-model="form.password"
                    label="Mot de passe"
                    input-id="subadmin-password"
                    autocomplete="new-password"
                    required
                />
                <small style="display: block; margin-top: 0.35rem"
                    >Au moins 8 caractères.</small
                >
                <PasswordInput
                    v-model="form.password_confirmation"
                    label="Confirmer le mot de passe"
                    input-id="subadmin-password-2"
                    autocomplete="new-password"
                    required
                />
                <div class="form-group">
                    <label>Nom de l'entreprise</label>
                    <input
                        v-model="form.entreprise_nom"
                        type="text"
                        placeholder="Ex. TechCorp"
                        required
                        autocomplete="off"
                    />
                    <p v-if="form.errors.entreprise_nom" class="form-error">
                        {{ form.errors.entreprise_nom }}
                    </p>
                </div>
                <button
                    type="submit"
                    class="btn btn--primary"
                    :disabled="form.processing"
                >
                    Ajouter
                </button>
            </form>
        </div>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Entreprise</th>
                        <th>Depuis</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="sa in sousAdmins" :key="sa.id">
                        <td>{{ sa.name }}</td>
                        <td>{{ sa.email }}</td>
                        <td>{{ sa.entreprise?.nom ?? "—" }}</td>
                        <td>
                            {{
                                new Date(sa.created_at).toLocaleDateString(
                                    "fr-FR"
                                )
                            }}
                        </td>
                        <td>
                            <button
                                type="button"
                                class="btn btn--danger btn--sm"
                                @click="
                                    $inertia.delete(
                                        `/admin/sous-admins/${sa.id}`
                                    )
                                "
                            >
                                Supprimer
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
