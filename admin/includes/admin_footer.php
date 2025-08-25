        </div>
    </main>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading...</p>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<!-- Admin Scripts -->
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
<script src="<?php echo SITE_URL; ?>/admin/assets/js/admin.js"></script>

<script>
// Toggle sidebar
function toggleSidebar() {
    document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
}

// Toggle notifications
function toggleNotifications() {
    const dropdown = document.getElementById('notificationsDropdown');
    dropdown.classList.toggle('active');
}

// Toggle user menu
function toggleUserMenu() {
    const dropdown = document.getElementById('adminUserDropdown');
    dropdown.classList.toggle('active');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.admin-notifications')) {
        document.getElementById('notificationsDropdown').classList.remove('active');
    }
    if (!e.target.closest('.admin-user-menu')) {
        document.getElementById('adminUserDropdown').classList.remove('active');
    }
});

// Mark notifications as read
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('mark-all-read')) {
        document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.classList.remove('unread');
        });
        document.querySelector('.notification-badge').style.display = 'none';
    }
});
</script>

</body>
</html>
