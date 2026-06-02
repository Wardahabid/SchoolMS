function showToast(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = type === 'success' ? 'flash-success' : 'flash-error';
    t.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;min-width:260px;box-shadow:var(--shadow)';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

async function fetchJSON(url) {
    const r = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
    return r.json();
}

function confirmDelete(form) {
    if (confirm('Are you sure you want to delete this record? This cannot be undone.')) {
        form.submit();
    }
}
