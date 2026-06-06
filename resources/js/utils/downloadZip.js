export function telechargerZipParPoste(zipUrl, ids, extra = {}) {
    if (!ids?.length) {
        return false;
    }

    const form = document.createElement("form");
    form.method = "POST";
    form.action = zipUrl;
    form.style.display = "none";

    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) {
        const csrf = document.createElement("input");
        csrf.type = "hidden";
        csrf.name = "_token";
        csrf.value = token;
        form.appendChild(csrf);
    }

    ids.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "cv_ids[]";
        input.value = String(id);
        form.appendChild(input);
    });

    Object.entries(extra).forEach(([name, value]) => {
        if (value === null || value === undefined || value === "") {
            return;
        }
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = String(value);
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    form.remove();

    return true;
}
