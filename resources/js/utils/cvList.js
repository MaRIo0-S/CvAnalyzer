export function badgeClass(statut) {
    const map = {
        cv_recu: "badge--recu",
        en_cours_analyse: "badge--analyse",
        valide: "badge--valide",
        non_valide: "badge--refuse",
    };
    return map[statut] || "badge--recu";
}

export function badgeClassFromCv(cv) {
    return badgeClass(cv.statut_affichage || cv.statut);
}

export function trierCvs(a, b, tri, { nullSafeScores = false } = {}) {
    if (tri === "date_depot_asc") {
        return (a.date_depot_ts || 0) - (b.date_depot_ts || 0);
    }
    if (tri === "statut") {
        return a.statut_label.localeCompare(b.statut_label, "fr");
    }
    if (tri === "score_desc" || tri === "score_asc") {
        const desc = tri === "score_desc";
        if (nullSafeScores) {
            if (a.score == null && b.score == null) return 0;
            if (a.score == null) return 1;
            if (b.score == null) return -1;
        }
        return desc ? (b.score || 0) - (a.score || 0) : (a.score || 0) - (b.score || 0);
    }
    if (tri === "matches_desc" || tri === "matches_asc") {
        const desc = tri === "matches_desc";
        if (nullSafeScores) {
            if (a.nombre_matches == null && b.nombre_matches == null) return 0;
            if (a.nombre_matches == null) return 1;
            if (b.nombre_matches == null) return -1;
        }
        return desc
            ? (b.nombre_matches || 0) - (a.nombre_matches || 0)
            : (a.nombre_matches || 0) - (b.nombre_matches || 0);
    }
    if (tri === "date_analyse_desc" || tri === "date_analyse_asc") {
        const desc = tri === "date_analyse_desc";
        const va = a.date_analyse_ts || 0;
        const vb = b.date_analyse_ts || 0;
        if (!va && !vb) return 0;
        if (!va) return 1;
        if (!vb) return -1;
        return desc ? vb - va : va - vb;
    }
    return (b.date_depot_ts || 0) - (a.date_depot_ts || 0);
}

export function filtrerCvs(list, { recherche, filtrePoste, filtreStatut, filtreMotCle }) {
    let out = list || [];
    const q = (recherche || "").trim().toLowerCase();
    if (q) {
        out = out.filter(
            (cv) =>
                (cv.nom_candidat || "").toLowerCase().includes(q) ||
                (cv.email_candidat || "").toLowerCase().includes(q)
        );
    }
    if (filtrePoste) {
        out = out.filter((cv) => String(cv.poste_id) === String(filtrePoste));
    }
    if (filtreStatut) {
        out = out.filter(
            (cv) => (cv.statut_affichage || cv.statut) === filtreStatut
        );
    }
    const mot = (filtreMotCle || "").trim().toLowerCase();
    if (mot) {
        out = out.filter((cv) =>
            (cv.mots_cles_matches || []).some((m) =>
                String(m).toLowerCase().includes(mot)
            )
        );
    }
    return out;
}
