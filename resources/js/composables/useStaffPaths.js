import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

/**
 * Chemins staff (admin / gérant) en un seul computed top-level
 * pour que le template Vue déroule correctement les href (évite [object Object]).
 */
export function useStaffPaths() {
    const page = usePage();

    return computed(() => {
        const admin = page.props.staffPaths?.admin ?? "/espace-admin";
        const gerant = page.props.staffPaths?.gerant ?? "/espace-gerant";

        return {
            admin,
            gerant,
            adminBackOffice: `${admin}/back-office`,
            adminMessages: `${admin}/messages-contact`,
            adminGerants: `${admin}/super-admins`,
            adminExport: `${admin}/back-office/export`,
            gerantDashboard: gerant,
            gerantRh: `${gerant}/rh`,
            gerantEntreprise: `${gerant}/entreprise`,
            gerantExportRh: `${gerant}/export/rh`,
            gerantRhEdit: (id) => `${gerant}/rh/${id}/edit`,
            adminGerantEdit: (id) => `${admin}/super-admins/${id}/edit`,
            adminGerantToggle: (id) => `${admin}/super-admins/${id}/actif`,
        };
    });
}
