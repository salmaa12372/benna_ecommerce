</main>

<script>
setTimeout(() => {
  const flash = document.getElementById('flashMsg');
  if (flash) {
    flash.style.transition = "opacity 0.5s";
    flash.style.opacity = "0";
    setTimeout(() => flash.remove(), 800);
  }
}, 2000);
</script>
</body>
</html>