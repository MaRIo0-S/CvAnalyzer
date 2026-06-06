export function telechargerZipCv(zipUrl, ids) {
    if (!ids?.length) {
        return false;
    }

    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = zipUrl;
    form.style.display = 'none';

    if (token) {
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = token;
        form.appendChild(csrf);
    }

    ids.forEach((id) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'cv_ids[]';
        input.value = String(id);
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    return true;
}
