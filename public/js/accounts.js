/**
 * Accounts Page Module
 * Handles all accounts-related functionality
 */

class AccountsPage {
    constructor() {
        this.accounts = [];
        this.filteredAccounts = [];
        this.currentEditId = null;
        this.init();
    }

    /**
     * Initialize accounts page
     */
    async init() {
        try {
            await this.loadAccounts();
            this.setupEventListeners();
            this.setupCurrencyChangeListener();
        } catch (error) {
            console.error('Failed to initialize accounts page:', error);
            this.showError('Failed to load accounts');
        }
    }

    /**
     * Load accounts from API
     */
    async loadAccounts() {
        try {
            const response = await apiService.getAccounts();
            console.log('Accounts API response:', response);
            this.accounts = response.success ? response.data : response;
            console.log('Accounts loaded:', this.accounts);
            this.filteredAccounts = [...this.accounts];
            this.displayAccounts();
            this.updateStatistics();
        } catch (error) {
            console.error('Error loading accounts:', error);
            this.showError('Failed to load accounts');
        }
    }



    /**
     * Display accounts in the table
     */
    displayAccounts() {
        const tbody = document.getElementById('accounts-tbody');
        console.log('Display accounts, tbody:', tbody);
        console.log('Filtered accounts length:', this.filteredAccounts.length);
        if (!tbody) return;

        tbody.innerHTML = '';

        if (this.filteredAccounts.length === 0) {
            console.log('No accounts found');
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: #64748b;">
                        No accounts found
                    </td>
                </tr>
            `;
            return;
        }

        console.log('Creating rows for accounts:', this.filteredAccounts);
        this.filteredAccounts.forEach(account => {
            const row = this.createAccountRow(account);
            tbody.appendChild(row);
        });
    }

    /**
     * Create a table row for an account
     */
    createAccountRow(account) {
        const row = document.createElement('tr');
        
        const typeClass = this.getAccountTypeClass(account.Type);
        const statusClass = account.Status === 'active' ? 'status-badge--active' : 'status-badge--inactive';
        const balanceClass = account.Balance >= 0 ? 'text-green-600' : 'text-red-600';
        const isActive = account.Status === 'active';
        
        row.innerHTML = `
            <td class="account-name">
                <strong>${account['Account Name']}</strong>
            </td>
            <td>
                <span class="account-type ${typeClass}">${this.formatAccountType(account.Type)}</span>
            </td>
            <td class="${balanceClass} font-medium account-balance">
                ${this.formatCurrency(account.Balance)}
            </td>
            <td>
                <span class="status-badge ${statusClass}">
                    ${account.Status}
                </span>
            </td>
            <td class="account-actions">
                <div class="action-dropdown">
                    <button class="btn btn-actions" data-account-id="${account.id}" aria-label="Account actions">
                        <ion-icon name="ellipsis-horizontal-outline"></ion-icon>
                    </button>
                    <div class="dropdown-menu">
                        <button class="dropdown-item edit-btn" data-account-id="${account.id}" aria-label="Edit account">
                            <ion-icon name="create-outline"></ion-icon>
                            Edit
                        </button>
                        <button class="dropdown-item toggle-status-btn" data-account-id="${account.id}" data-status="${isActive ? 'inactive' : 'active'}" aria-label="${isActive ? 'Deactivate account' : 'Activate account'}">
                            <ion-icon name="${isActive ? 'pause-circle-outline' : 'play-circle-outline'}"></ion-icon>
                            ${isActive ? 'Deactivate' : 'Activate'}
                        </button>
                        <button class="dropdown-item delete-btn" data-account-id="${account.id}" aria-label="Delete account">
                            <ion-icon name="trash-outline"></ion-icon>
                            Delete
                        </button>
                    </div>
                </div>
            </td>
        `;
        
        return row;
    }

    /**
     * Get CSS class for account type
     */
    getAccountTypeClass(type) {
        const classes = {
            'asset': 'account-type--asset',
            'liability': 'account-type--liability',
            'equity': 'account-type--equity',
            'revenue': 'account-type--revenue',
            'expense': 'account-type--expense'
        };
        return classes[type] || '';
    }

    /**
     * Format account type for display
     */
    formatAccountType(type) {
        if (!type) return '';
        return type.charAt(0).toUpperCase() + type.slice(1);
    }

    /**
     * Update account statistics
     */
    updateStatistics() {
        const stats = this.calculateStatistics();
        
        document.getElementById('total-assets').textContent = this.formatCurrency(stats.totalAssets);
        document.getElementById('total-liabilities').textContent = this.formatCurrency(Math.abs(stats.totalLiabilities));
        document.getElementById('total-equity').textContent = this.formatCurrency(stats.totalEquity);
        document.getElementById('total-accounts').textContent = this.accounts.length;
    }

    /**
     * Calculate account statistics
     */
    calculateStatistics() {
        let totalAssets = 0;
        let totalLiabilities = 0;
        let totalEquity = 0;

        this.accounts.forEach(account => {
            const type = account.Type;
            const balance = account.Balance;
            
            if (!type || balance === undefined || balance === null) return;
            
            switch (type.toLowerCase()) {
                case 'asset':
                    totalAssets += balance;
                    break;
                case 'liability':
                    totalLiabilities += balance;
                    break;
                case 'equity':
                    totalEquity += balance;
                    break;
            }
        });

        return {
            totalAssets,
            totalLiabilities,
            totalEquity
        };
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Add account button
        const addBtn = document.getElementById('add-account-btn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showAccountModal());
        }

        // Search functionality
        const searchInput = document.getElementById('account-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterAccounts(e.target.value));
        }

        // Type filter
        const typeFilter = document.getElementById('account-type-filter');
        if (typeFilter) {
            typeFilter.addEventListener('change', (e) => this.filterByType(e.target.value));
        }

        // Action dropdown handlers (using event delegation)
        this.setupActionHandlers();

        // Modal controls
        this.setupModalListeners();
    }

    /**
     * Setup action dropdown handlers
     */
    setupActionHandlers() {
        const tbody = document.getElementById('accounts-tbody');
        
        // Use event delegation for dynamic content
        tbody.addEventListener('click', (e) => {
            const target = e.target.closest('.dropdown-item, .btn-actions');
            
            if (!target) return;
            
            if (target.classList.contains('btn-actions')) {
                // Toggle dropdown
                e.stopPropagation();
                this.toggleDropdown(target);
            } else if (target.classList.contains('edit-btn')) {
                // Edit account
                const accountId = target.dataset.accountId;
                this.editAccount(parseInt(accountId));
                this.closeAllDropdowns();
            } else if (target.classList.contains('toggle-status-btn')) {
                // Toggle account status
                const accountId = target.dataset.accountId;
                const newStatus = target.dataset.status;
                this.toggleAccountStatus(parseInt(accountId), newStatus);
                this.closeAllDropdowns();
            } else if (target.classList.contains('delete-btn')) {
                // Delete account
                const accountId = target.dataset.accountId;
                this.deleteAccount(parseInt(accountId));
                this.closeAllDropdowns();
            }
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.action-dropdown')) {
                this.closeAllDropdowns();
            }
        });

        // Close dropdowns on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllDropdowns();
            }
        });
    }

    /**
     * Toggle dropdown menu
     */
    toggleDropdown(button) {
        const dropdown = button.nextElementSibling;
        const isOpen = dropdown.classList.contains('dropdown-menu--active');
        
        // Close all other dropdowns
        this.closeAllDropdowns();
        
        // Toggle current dropdown
        if (!isOpen) {
            dropdown.classList.add('dropdown-menu--active');
            button.setAttribute('aria-expanded', 'true');
            this.adjustDropdownPosition(dropdown);
        }
    }

    /**
     * Adjust dropdown position to prevent viewport overflow
     */
    adjustDropdownPosition(dropdown) {
        // Reset classes
        dropdown.classList.remove('dropdown-menu--top', 'dropdown-menu--left');
        
        // Get dropdown and button dimensions
        const dropdownRect = dropdown.getBoundingClientRect();
        const buttonRect = dropdown.previousElementSibling.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;
        
        // Check if dropdown goes below viewport
        if (dropdownRect.bottom > viewportHeight - 10) {
            dropdown.classList.add('dropdown-menu--top');
        }
        
        // Check if dropdown goes beyond right edge (with some padding)
        if (dropdownRect.right > viewportWidth - 10) {
            dropdown.classList.add('dropdown-menu--left');
        }
    }

    /**
     * Close all dropdown menus
     */
    closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu--active').forEach(menu => {
            menu.classList.remove('dropdown-menu--active');
        });
        document.querySelectorAll('.btn-actions').forEach(btn => {
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    /**
     * Setup modal event listeners
     */
    setupModalListeners() {
        const modal = document.getElementById('account-modal');
        const overlay = document.getElementById('modal-overlay');
        const closeBtn = document.getElementById('close-account-modal-btn');
        const cancelBtn = document.getElementById('cancel-account-btn');
        const form = document.getElementById('account-form');

        // Close modal events
        const closeModal = () => this.hideAccountModal();
        
        if (overlay) overlay.addEventListener('click', closeModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        // Form submission
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveAccount();
            });
        }

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('modal--active')) {
                closeModal();
            }
        });
    }

    /**
     * Show account modal
     */
    showAccountModal(account = null) {
        const modal = document.getElementById('account-modal');
        const overlay = document.getElementById('modal-overlay');
        const title = document.getElementById('modal-title');
        const form = document.getElementById('account-form');

        if (!modal || !overlay || !title || !form) return;

        // Reset form
        form.reset();
        this.currentEditId = null;

        if (account) {
            // Edit mode
            this.currentEditId = account.id;
            title.textContent = 'Edit Account';
            const saveBtn = document.getElementById('save-account-btn');
            if (saveBtn) {
                saveBtn.textContent = 'Save Account';
            }
            document.getElementById('account-id').value = account.id;
            document.getElementById('account-name').value = account['Account Name'];
            document.getElementById('account-type').value = account.Type;
            document.getElementById('account-balance').value = Math.abs(account.Balance);
            document.getElementById('account-description').value = account.description || '';
        } else {
            // Add mode
            title.textContent = 'Add Account';
            const saveBtn = document.getElementById('save-account-btn');
            if (saveBtn) {
                saveBtn.textContent = 'Add Account';
            }
        }

        modal.classList.add('modal--active');
        overlay.classList.add('modal--active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Hide account modal
     */
    hideAccountModal() {
        const modal = document.getElementById('account-modal');
        const overlay = document.getElementById('modal-overlay');
        if (!modal || !overlay) return;

        modal.classList.remove('modal--active');
        overlay.classList.remove('modal--active');
        document.body.style.overflow = '';
        this.currentEditId = null;
    }

    /**
     * Edit account
     */
    editAccount(id) {
        const account = this.accounts.find(acc => acc.id === id);
        if (account) {
            this.showAccountModal(account);
        }
    }

    /**
     * Toggle account status
     */
    async toggleAccountStatus(id, newStatus) {
        try {
            const isActive = newStatus === 'active';
            await apiService.updateAccount(id, { is_active: isActive });
            this.showSuccess(`Account ${isActive ? 'activated' : 'deactivated'} successfully`);
            
            // Requery all data from server to get fresh data
            await this.loadAccounts();
        } catch (error) {
            console.error('Error toggling account status:', error);
            this.showError(`Failed to ${newStatus} account`);
        }
    }

    /**
     * Delete account
     */
    async deleteAccount(id) {
        if (!confirm('Are you sure you want to delete this account? This action cannot be undone.')) {
            return;
        }

        try {
            await apiService.deleteAccount(id);
            this.showSuccess('Account deleted successfully');
            
            // Requery all data from server to get fresh data
            await this.loadAccounts();
        } catch (error) {
            console.error('Error deleting account:', error);
            this.showError('Failed to delete account');
        }
    }

    /**
     * Save account
     */
    async saveAccount() {
        const accountType = document.getElementById('account-type').value;
        const balance = parseFloat(document.getElementById('account-balance').value) || 0;
        
        // Adjust balance based on account type
        let adjustedBalance = balance;
        if (accountType === 'liability' || accountType === 'expense') {
            adjustedBalance = -balance;
        }
        
        const formData = {
            name: document.getElementById('account-name').value,
            type: accountType,
            balance: adjustedBalance,
            description: document.getElementById('account-description').value || ''
        };

        try {
            if (this.currentEditId) {
                // Update existing account
                await apiService.updateAccount(this.currentEditId, formData);
                this.showSuccess('Account updated successfully');
            } else {
                // Create new account
                await apiService.createAccount(formData);
                this.showSuccess('Account created successfully');
            }

            // Requery all data from server to get fresh data
            await this.loadAccounts();
            this.hideAccountModal();
        } catch (error) {
            console.error('Error saving account:', error);
            this.showError('Failed to save account');
        }
    }

    /**
     * Filter accounts by search term
     */
    filterAccounts(searchTerm) {
        const term = searchTerm.toLowerCase();
        
        this.filteredAccounts = this.accounts.filter(account => 
            account['Account Name'].toLowerCase().includes(term)
        );
        
        this.displayAccounts();
    }

    /**
     * Filter accounts by type
     */
    filterByType(type) {
        if (!type) {
            this.filteredAccounts = [...this.accounts];
        } else {
            this.filteredAccounts = this.accounts.filter(account => account.Type === type);
        }
        
        this.displayAccounts();
    }

    /**
     * Setup currency change listener
     */
    setupCurrencyChangeListener() {
        window.addEventListener('currencyChanged', (e) => {
            console.log('Accounts page: Currency changed to', e.detail.currency);
            this.refreshAllDisplays();
        });
    }

    /**
     * Refresh all currency displays on accounts page
     */
    refreshAllDisplays() {
        console.log('Accounts page: Refreshing all displays');
        
        // Refresh statistics
        this.updateStatistics();
        
        // Refresh accounts table
        this.displayAccounts();
        
        // Refresh any modal forms if open
        const modal = document.getElementById('account-modal');
        if (modal && modal.style.display !== 'none') {
            this.refreshModalCurrencyDisplays();
        }
    }

    /**
     * Refresh currency displays in modal
     */
    refreshModalCurrencyDisplays() {
        const balanceInput = document.getElementById('account-balance');
        if (balanceInput && balanceInput.value) {
            // Keep the raw value, just update placeholder if needed
            const currentValue = parseFloat(balanceInput.value);
            if (!isNaN(currentValue)) {
                console.log('Updated modal balance display');
            }
        }
    }

    /**
     * Format currency value
     */
    formatCurrency(value) {
        return window.currencyUtils.formatCurrency(value);
    }

    /**
     * Show error message
     */
    showError(message) {
        if (window.app) {
            window.app.showError(message);
        } else {
            alert(message);
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        if (window.app) {
            window.app.showSuccess(message);
        } else {
            alert(message);
        }
    }
}

// Initialize accounts page when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.accountsPage = new AccountsPage();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AccountsPage;
}