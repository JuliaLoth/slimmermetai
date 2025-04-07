document.addEventListener('DOMContentLoaded', function() {
    // Dashboard tabs functionality
    const dashboardTabs = document.querySelectorAll('.dashboard-tab');
    const dashboardContents = document.querySelectorAll('.dashboard-content');
    
    if (dashboardTabs.length > 0) {
        dashboardTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.getAttribute('data-tab');
                
                // Update active tab
                dashboardTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show active content
                dashboardContents.forEach(content => {
                    if (content.getAttribute('id') === target) {
                        content.style.display = 'block';
                    } else {
                        content.style.display = 'none';
                    }
                });
                
                // Update URL without page refresh
                const newUrl = window.location.pathname + (target !== 'overview' ? '?tab=' + target : '');
                history.pushState({}, '', newUrl);
            });
        });
        
        // Check for URL parameter on load
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam) {
            const tabToActivate = document.querySelector(`.dashboard-tab[data-tab="${tabParam}"]`);
            if (tabToActivate) {
                tabToActivate.click();
            }
        }
    }
    
    // Profile edit toggle
    const editProfileBtn = document.getElementById('edit-profile-btn');
    const profileInfoSection = document.getElementById('profile-info');
    const profileEditSection = document.getElementById('profile-edit');
    
    if (editProfileBtn && profileInfoSection && profileEditSection) {
        editProfileBtn.addEventListener('click', function() {
            profileInfoSection.style.display = 'none';
            profileEditSection.style.display = 'block';
        });
        
        // Cancel edit button
        const cancelEditBtn = document.getElementById('cancel-edit-btn');
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', function(e) {
                e.preventDefault();
                profileInfoSection.style.display = 'block';
                profileEditSection.style.display = 'none';
            });
        }
    }
    
    // Profile picture upload preview
    const profilePictureInput = document.getElementById('profile-picture');
    const profilePicturePreview = document.getElementById('profile-picture-preview');
    
    if (profilePictureInput && profilePicturePreview) {
        profilePictureInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    profilePicturePreview.src = e.target.result;
                    profilePicturePreview.style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Delete account confirmation
    const deleteAccountBtn = document.getElementById('delete-account-btn');
    const deleteAccountModal = document.getElementById('delete-account-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    
    if (deleteAccountBtn && deleteAccountModal) {
        deleteAccountBtn.addEventListener('click', function(e) {
            e.preventDefault();
            deleteAccountModal.style.display = 'flex';
        });
        
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                deleteAccountModal.style.display = 'none';
            });
        }
        
        if (cancelDeleteBtn) {
            cancelDeleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                deleteAccountModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === deleteAccountModal) {
                deleteAccountModal.style.display = 'none';
            }
        });
    }
    
    // Favorites toggle
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            const itemType = this.getAttribute('data-type');
            
            // Toggle active state visually
            this.classList.toggle('active');
            
            // Send AJAX request to server to toggle favorite status
            fetch('includes/ajax/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${itemId}&type=${itemType}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update icon
                    const icon = this.querySelector('i');
                    if (data.is_favorite) {
                        icon.className = 'fas fa-heart';
                        this.title = 'Verwijderen uit favorieten';
                    } else {
                        icon.className = 'far fa-heart';
                        this.title = 'Toevoegen aan favorieten';
                    }
                } else {
                    // Revert visual state if there was an error
                    this.classList.toggle('active');
                    console.error('Error toggling favorite status');
                }
            })
            .catch(error => {
                // Revert visual state on error
                this.classList.toggle('active');
                console.error('Error:', error);
            });
        });
    });
    
    // E-learning progress update
    const progressBars = document.querySelectorAll('.progress-bar');
    
    progressBars.forEach(bar => {
        const progress = parseInt(bar.getAttribute('data-progress'));
        bar.style.width = `${progress}%`;
        
        // Color based on progress
        if (progress < 25) {
            bar.style.backgroundColor = '#ef4444';
        } else if (progress < 50) {
            bar.style.backgroundColor = '#f97316';
        } else if (progress < 75) {
            bar.style.backgroundColor = '#eab308';
        } else {
            bar.style.backgroundColor = '#22c55e';
        }
    });
}); 