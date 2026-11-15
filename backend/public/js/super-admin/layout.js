/**
 * Super Admin layout — mobile sidebar toggle
 */
function toggleSidebar() {
    document.body.classList.toggle('sidebar-open');
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.body.classList.remove('sidebar-open');
    }
});
