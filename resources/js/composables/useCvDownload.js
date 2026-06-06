import { useToastStore } from "@/stores/toast";

export async function telechargerCv(url, fallbackName = "cv.pdf") {
    const toast = useToastStore();

    try {
        const res = await fetch(url, { credentials: "same-origin" });
        const contentType = res.headers.get("content-type") || "";

        if (!res.ok || contentType.includes("text/html")) {
            toast.error(
                "Fichier CV introuvable sur le serveur. Si le problème persiste après un nouveau dépôt, vérifiez le volume de stockage.",
                8000
            );
            return;
        }

        const blob = await res.blob();
        let name = fallbackName;
        const disposition = res.headers.get("content-disposition") || "";
        const match = /filename\*?=(?:UTF-8''|")?([^";\n]+)/i.exec(
            disposition
        );
        if (match?.[1]) {
            name = decodeURIComponent(match[1].replace(/"/g, "").trim());
        }

        const objectUrl = URL.createObjectURL(blob);
        const anchor = document.createElement("a");
        anchor.href = objectUrl;
        anchor.download = name;
        anchor.click();
        URL.revokeObjectURL(objectUrl);
    } catch {
        toast.error("Impossible de télécharger le CV. Réessayez.");
    }
}
