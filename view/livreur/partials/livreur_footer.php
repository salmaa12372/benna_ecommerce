
</main><!-- /.main-content -->

<script>
// Auto-hide flash messages
setTimeout(() => {
    const flash = document.getElementById('flashMsg');
    if (flash) {
        flash.style.transition = 'opacity 0.5s';
        flash.style.opacity = '0';
        setTimeout(() => flash.remove(), 600);
    }
}, 5000);

// Close modal on outside click
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});
// Close with Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape')
        document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});
</script>
</body>
</html>