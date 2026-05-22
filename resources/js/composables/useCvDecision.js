import { router } from "@inertiajs/vue3";
import { useToastStore } from "@/stores/toast";

export function useCvDecision({ lotEnAttente = false, afterSuccess = null } = {}) {
    const toast = useToastStore();

    function peutDecider(cv) {
        return cv?.statut === "en_cours_analyse";
    }

    function mailtoCandidat(cv) {
        const subject = `Votre candidature — ${cv.poste}`;
        const body =
            `Bonjour ${cv.nom_candidat},\n\n` +
            `Nous avons le plaisir de vous informer que votre candidature pour le poste « ${cv.poste} » a retenu notre attention.\n\n` +
            `Cordialement,\n`;
        return `mailto:${cv.email_candidat}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    }

    function valider(cv) {
        if (!peutDecider(cv)) return;
        if (!cv.email_candidat) {
            toast.error("Adresse e-mail du candidat manquante.");
            return;
        }

        const msg = lotEnAttente
            ? "Marquer comme validé ? La décision sera appliquée après confirmation de l'analyse."
            : "Valider ce CV ? Le candidat recevra un e-mail de statut. Votre messagerie s'ouvrira pour un message personnel.";

        if (!confirm(msg)) return;

        router.patch(
            `/rh/cvs/${cv.id}/valider`,
            { valide: true },
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (typeof afterSuccess === "function") {
                        afterSuccess(cv, { valide: true });
                        return;
                    }
                    if (!lotEnAttente) {
                        router.reload({
                            onFinish: () => {
                                setTimeout(() => {
                                    window.location.href = mailtoCandidat(cv);
                                }, 200);
                            },
                        });
                    }
                },
            }
        );
    }

    function refuser(cv) {
        if (!peutDecider(cv)) return;

        const msg = lotEnAttente
            ? "Marquer comme refusé ? La décision sera appliquée après confirmation de l'analyse."
            : "Refuser cette candidature ?";

        if (!confirm(msg)) return;

        router.patch(
            `/rh/cvs/${cv.id}/valider`,
            { valide: false },
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (typeof afterSuccess === "function") {
                        afterSuccess(cv, { valide: false });
                    } else if (!lotEnAttente) {
                        router.reload();
                    }
                },
            }
        );
    }

    return { peutDecider, mailtoCandidat, valider, refuser };
}
