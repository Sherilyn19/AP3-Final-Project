/**
 * ============================================================================
 * USER MANAGEMENT PAGE - JavaScript Functions
 * resources/js/admin-pages/user.js
 * ============================================================================
 * This file handles all user management functionality including:
 * - Inline editing of user data (name, email)
 * - User activation/deactivation
 * - Password reset with modal display
 * - User deletion with impact checking
 * - Bulk actions (select, deactivate, delete)
 * - Toast notifications for user feedback
 * ============================================================================
 */

// ============================================================================
// CSRF TOKEN - Required for all POST/PUT/DELETE requests
// ============================================================================
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// ============================================================================
// ORIGINAL ROW DATA STORAGE
// Stores the original values before editing so we can cancel changes
// ============================================================================
let originalRowData = {};

// ============================================================================
// INLINE EDITING FUNCTIONS
// Allow users to edit name and email directly in the table
// ============================================================================

/**
 * Toggle between view mode and edit mode for a user row
 * @param {number} userId - The ID of the user to edit
 */
window.toggleEdit = function(userId) {
    const row = document.getElementById(`user-row-${userId}`);
    const isEditing = row.classList.contains('is-editing');
    
    if (isEditing) {
        // --- SAVE CHANGES MODE ---
        const nameInput = document.getElementById(`name-edit-${userId}`);
        const emailInput = document.getElementById(`email-edit-${userId}`);
        
        // Auto-format: Capitalize each word in name
        const formattedName = nameInput.value.trim().split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
        
        // Auto-format: Lowercase email
        const formattedEmail = emailInput.value.trim().toLowerCase();
        
        // Split full name into first_name and last_name
        const nameParts = formattedName.split(' ');
        const firstName = nameParts.shift() || '';
        const lastName = nameParts.join(' ') || '';

        const payload = {
            first_name: firstName,
            last_name: lastName,
            user_email: formattedEmail,
            _method: 'PUT'
        };
        
        // Super Admins don't need name updates
        if (nameInput.disabled) {
            delete payload.first_name;
            delete payload.last_name;
        }

        updateUser(userId, payload);

    } else {
        // --- ENTER EDIT MODE ---
        // Store original values for cancellation
        originalRowData[userId] = {
            name: document.getElementById(`name-view-${userId}`).textContent.trim(),
            email: document.getElementById(`email-view-${userId}`).textContent.trim(),
        };

        // Show input fields, hide view text
        document.getElementById(`name-view-${userId}`).classList.add('hidden');
        document.getElementById(`email-view-${userId}`).classList.add('hidden');
        document.getElementById(`name-edit-${userId}`).classList.remove('hidden');
        document.getElementById(`email-edit-${userId}`).classList.remove('hidden');

        // Change edit icon to save icon
        document.getElementById(`edit-icon-${userId}`).classList.add('hidden');
        document.getElementById(`save-icon-${userId}`).classList.remove('hidden');
        document.getElementById(`cancel-btn-${userId}`).classList.remove('hidden');

        // Add auto-formatting event listeners
        const nameInput = document.getElementById(`name-edit-${userId}`);
        const emailInput = document.getElementById(`email-edit-${userId}`);
        
        // Auto-format name on input
        nameInput.addEventListener('input', function(e) {
            const cursorPos = this.selectionStart;
            const formatted = this.value.split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                .join(' ');
            this.value = formatted;
            this.setSelectionRange(cursorPos, cursorPos);
        });
        
        // Auto-format email to lowercase
        emailInput.addEventListener('input', function(e) {
            const cursorPos = this.selectionStart;
            this.value = this.value.toLowerCase();
            this.setSelectionRange(cursorPos, cursorPos);
        });

        // Disable name editing for Super Admins
        const fullName = originalRowData[userId].name.trim();
        if (fullName === 'Super Admin') {
            nameInput.disabled = true;
        }

        row.classList.add('is-editing', 'bg-yellow-50');
    }
};

/**
 * Cancel editing and restore original values
 * @param {number} userId - The ID of the user row
 */
window.cancelEdit = function(userId) {
    const row = document.getElementById(`user-row-${userId}`);

    // Check if originalRowData exists for this user
    if (!originalRowData[userId]) {
        console.warn(`No original data found for user ${userId}`);
        return;
    }

    // Restore original values
    document.getElementById(`name-view-${userId}`).textContent = originalRowData[userId].name;
    document.getElementById(`email-view-${userId}`).textContent = originalRowData[userId].email;
    document.getElementById(`name-edit-${userId}`).value = originalRowData[userId].name;
    document.getElementById(`email-edit-${userId}`).value = originalRowData[userId].email;
    
    // Toggle visibility back to view state
    document.getElementById(`name-view-${userId}`).classList.remove('hidden');
    document.getElementById(`email-view-${userId}`).classList.remove('hidden');
    document.getElementById(`name-edit-${userId}`).classList.add('hidden');
    document.getElementById(`email-edit-${userId}`).classList.add('hidden');

    // Restore icons
    document.getElementById(`edit-icon-${userId}`).classList.remove('hidden');
    document.getElementById(`save-icon-${userId}`).classList.add('hidden');
    document.getElementById(`cancel-btn-${userId}`).classList.add('hidden');

    row.classList.remove('is-editing', 'bg-yellow-50');
    delete originalRowData[userId];
};

/**
 * Cancel editing and restore original values
 * @param {number} userId - The ID of the user row
 */
window.cancelEdit = function(userId) {
    const row = document.getElementById(`user-row-${userId}`);

    // Restore original values
    document.getElementById(`name-view-${userId}`).textContent = originalRowData[userId].name;
    document.getElementById(`email-view-${userId}`).textContent = originalRowData[userId].email;
    document.getElementById(`name-edit-${userId}`).value = originalRowData[userId].name;
    document.getElementById(`email-edit-${userId}`).value = originalRowData[userId].email;
    
    // Toggle visibility back to view state
    document.getElementById(`name-view-${userId}`).classList.remove('hidden');
    document.getElementById(`email-view-${userId}`).classList.remove('hidden');
    document.getElementById(`name-edit-${userId}`).classList.add('hidden');
    document.getElementById(`email-edit-${userId}`).classList.add('hidden');

    // Restore icons
    document.getElementById(`edit-icon-${userId}`).classList.remove('hidden');
    document.getElementById(`save-icon-${userId}`).classList.add('hidden');
    document.getElementById(`cancel-btn-${userId}`).classList.add('hidden');

    row.classList.remove('is-editing', 'bg-yellow-50');
    delete originalRowData[userId];
};

/**
 * Send update request to server
 * @param {number} userId - The ID of the user to update
 * @param {object} payload - The data to send (first_name, last_name, user_email)
 */
async function updateUser(userId, payload) {
    try {
        const response = await fetch(`/admin/users/${userId}`, {
            method: 'POST', // Using POST with _method spoofing
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (!response.ok) {
            // Handle validation errors
            if (result.errors) {
                const errorMsg = Object.values(result.errors).flat().join('\n');
                showToast(errorMsg, 'error');
            } else {
                throw new Error(result.message || 'Update failed.');
            }
            return; // Stay in edit mode on error
        }

        showToast(result.message, 'success');
        
        // Update the view with new data
        document.getElementById(`name-view-${userId}`).textContent = payload.first_name ? `${payload.first_name} ${payload.last_name}`.trim() : 'Super Admin';
        document.getElementById(`email-view-${userId}`).textContent = payload.user_email;

        // Exit edit mode
        cancelEdit(userId);

    } catch (error) {
        showToast(error.message, 'error');
    }
}

// ============================================================================
// USER ACTIONS (Activate, Deactivate, Delete, Reset Password)
// ============================================================================

/**
 * Activate a user
 * @param {number} userId - The ID of the user
 */
window.activateUser = function(userId) {
    performUserAction(
        `/admin/users/${userId}/activate`, 
        'Are you sure you want to activate this user?', 
        'User activated successfully.'
    );
};

/**
 * Deactivate a user
 * @param {number} userId - The ID of the user
 */
window.deactivateUser = function(userId) {
    performUserAction(
        `/admin/users/${userId}/deactivate`, 
        'Are you sure you want to deactivate this user?', 
        'User deactivated successfully.'
    );
};

/**
 * Delete a user (with deletion impact check)
 * @param {number} userId - The ID of the user
 */
window.deleteUser = async function(userId) {
    try {
        // First, check what will be affected by deletion
        const impactRes = await fetch(`/admin/users/${userId}/deletion-impact`);
        if (!impactRes.ok) throw new Error('Could not fetch deletion impact.');
        const impactData = await impactRes.json();

        let impactMsg = 'This action will permanently delete this user.';
        
        // If there are related records, show them
        if (Object.keys(impactData.impact).length > 0) {
            const details = Object.entries(impactData.impact)
                .map(([key, value]) => `- ${value} ${key}`)
                .join('\n');
            impactMsg = `Permanently delete this user? This will also affect:\n${details}\n\nThis action cannot be undone.`;
        }

        if (confirm(impactMsg)) {
            await performUserAction(
                `/admin/users/${userId}`, 
                null, 
                'User deleted successfully.', 
                'DELETE'
            );
        }
    } catch (error) {
        showToast(error.message, 'error');
    }
};

/**
 * Reset user password and show new password in modal
 * @param {number} userId - The ID of the user
 */
window.resetPassword = async function(userId) {
    if (confirm('Are you sure you want to generate a new password for this user?')) {
        try {
            const response = await fetch(`/admin/users/${userId}/reset-password`, {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': csrfToken, 
                    'Accept': 'application/json' 
                },
            });
            
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Failed to reset password.');
            
            // Show modal with new password
            const modal = document.getElementById('reset-password-modal');
            modal.innerHTML = `
                <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-2xl animate-fade-in-up">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-primary-dark">New password generated</h3>
                        <button onclick="closeResetModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-700 mb-2">Copy this password and share it with the user:</p>
                    <div class="flex items-center gap-2 mb-2">
                        <input type="text" id="generated-password" readonly
                            class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg bg-gray-50 font-mono text-lg font-bold text-center select-all">
                        <button onclick="copyPassword()" class="bg-secondary-blue text-white px-6 py-3 rounded-lg font-semibold hover:bg-secondary-blue-dark transition-all">
                            Copy
                        </button>
                    </div>
                    <p class="text-xs text-warm-coral font-semibold mt-2">*This password will not be shown again.</p>
                    <button onclick="closeResetModal()" class="bg-forest-green text-white w-full mt-4 px-6 py-3 rounded-lg font-semibold hover:bg-forest-green-dark transition-all">
                        Done
                    </button>
                </div>
            `;

            // Assign the generated password through the DOM property so special
            // characters are not interpreted as part of the modal HTML.
            const passwordInput = modal.querySelector('#generated-password');

            if (!passwordInput) {
                throw new Error('Generated password field is unavailable.');
            }

            passwordInput.value = data.password;

            // Show the completed password-reset modal.
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            showToast('Password has been reset.', 'custom', '#377357');
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
};

/**
 * Close the reset password modal
 */
window.closeResetModal = function() {
    const modal = document.getElementById('reset-password-modal');
    modal.classList.add('hidden');
    modal.innerHTML = '';
};

/**
 * Copy the generated password to clipboard
 */
window.copyPassword = function() {
    const passwordInput = document.getElementById('generated-password');
    passwordInput.select();
    passwordInput.setSelectionRange(0, 99999); // For mobile
    document.execCommand('copy');
    showToast('Password copied to clipboard!', 'success');
};

// ============================================================================
// BULK ACTIONS
// Allow selecting multiple users and performing actions on them
// ============================================================================

/**
 * Toggle all user checkboxes on/off
 * @param {HTMLElement} checkbox - The "select all" checkbox
 */
window.toggleSelectAll = function(checkbox) {
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    userCheckboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkActions();
};

/**
 * Update the bulk actions bar based on selected users
 */
window.updateBulkActions = function() {
    const selected = document.querySelectorAll('.user-checkbox:checked');
    const bulkBar = document.getElementById('bulk-actions-bar');
    
    if (selected.length > 0) {
        bulkBar.innerHTML = `
            <div class="flex items-center justify-between flex-wrap gap-4 bg-warm-coral bg-opacity-10 border-l-4 border-warm-coral p-4 rounded">
                <span class="font-medium">${selected.length} user(s) selected</span>
                <div class="flex gap-2">
                    <button onclick="bulkDeactivate()" class="btn-secondary btn-sm">Deactivate</button>
                    <button onclick="bulkDelete()" class="btn-secondary btn-sm hover:bg-red-600 hover:text-white">Delete</button>
                    <button onclick="clearSelection()" class="btn-secondary btn-sm">Clear</button>
                </div>
            </div>
        `;
        bulkBar.classList.remove('hidden');
    } else {
        bulkBar.classList.add('hidden');
        bulkBar.innerHTML = '';
    }
};

/**
 * Clear all selections
 */
window.clearSelection = function() {
    document.querySelectorAll('.user-checkbox, #select-all').forEach(cb => cb.checked = false);
    updateBulkActions();
};

/**
 * Deactivate all selected users
 */
window.bulkDeactivate = function() {
    performBulkAction(
        '/admin/users/bulk-deactivate', 
        count => `Are you sure you want to deactivate ${count} users?`
    );
};

/**
 * Delete all selected users
 */
window.bulkDelete = function() {
    performBulkAction(
        '/admin/users/bulk-delete', 
        count => `Are you sure you want to permanently delete ${count} users? This action cannot be undone.`
    );
};

// ============================================================================
// HELPER FUNCTIONS
// Reusable functions for API calls and UI updates
// ============================================================================

/**
 * Generic function to perform a user action with confirmation
 * @param {string} url - API endpoint
 * @param {string|null} confirmMsg - Confirmation message (null = no confirmation)
 * @param {string} successMsg - Success message to display
 * @param {string} method - HTTP method (POST, DELETE, PUT)
 */
async function performUserAction(url, confirmMsg, successMsg, method = 'POST') {
    if (confirmMsg && !confirm(confirmMsg)) return;

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 
                'X-CSRF-TOKEN': csrfToken, 
                'Accept': 'application/json' 
            },
        });
        
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Action failed.');
        
        showToast(successMsg, 'success');
        setTimeout(() => window.location.reload(), 1500);
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Generic function to perform bulk actions on multiple users
 * @param {string} url - API endpoint
 * @param {function} confirmMsgCallback - Function that returns confirmation message based on count
 */
async function performBulkAction(url, confirmMsgCallback) {
    const userIds = Array.from(document.querySelectorAll('.user-checkbox:checked'))
        .map(cb => cb.value);
    
    if (userIds.length === 0) {
        showToast('No users selected.', 'error');
        return;
    }
    
    if (confirm(confirmMsgCallback(userIds.length))) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken 
                },
                body: JSON.stringify({ user_ids: userIds }),
            });
            
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Bulk action failed.');
            
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
}

/**
 * Display a toast notification
 * @param {string} message - The message to display
 * @param {string} type - 'success', 'error', or 'custom'
 * @param {string|null} customColor - Hex color for custom type
 */
function showToast(message, type = 'success', customColor = null) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    
    let bgColor;
    let icon;
    
    if (type === 'custom' && customColor) {
        toast.style.backgroundColor = customColor;
        icon = `<svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
    } else if (type === 'success') {
        bgColor = 'bg-forest-green';
        icon = `<svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
    } else { // error
        bgColor = 'bg-warm-coral';
        icon = `<svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
    }
    
    toast.className = `flex items-center p-4 rounded-lg shadow-lg text-white ${bgColor} animate-fade-in-up`;
    toast.innerHTML = `
        ${icon} 
        <span class="font-semibold flex-1">${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-2 hover:text-gray-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        toast.classList.add('animate-fade-out');
        toast.addEventListener('animationend', () => toast.remove());
    }, 5000);
}

// ============================================================================
// EXPORT FUNCTIONS TO WINDOW (for inline onclick handlers)
// ============================================================================
if (typeof window !== 'undefined') {
    window.toggleEdit = toggleEdit;
    window.cancelEdit = cancelEdit;
    window.activateUser = activateUser;
    window.deactivateUser = deactivateUser;
    window.deleteUser = deleteUser;
    window.resetPassword = resetPassword;
    window.copyPassword = copyPassword;
    window.closeResetModal = closeResetModal;
    window.toggleSelectAll = toggleSelectAll;
    window.updateBulkActions = updateBulkActions;
    window.clearSelection = clearSelection;
    window.bulkDeactivate = bulkDeactivate;
    window.bulkDelete = bulkDelete;
}

console.log('%c✓ User Management JS Loaded', 'color: #377357; font-weight: bold;');