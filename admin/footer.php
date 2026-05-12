        </div><!-- /.admin-content -->
    </main><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<script>
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('collapsed');
});

// Auto-dismiss alerts after 4s
document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => { a.style.opacity='0'; setTimeout(()=>a.remove(),400); }, 4000);
});

// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', (e) => {
        if (!confirm(btn.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
});
</script>
</body>
</html>
