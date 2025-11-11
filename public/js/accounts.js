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
            this.updateStatistics();
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
            this.accounts = await apiService.getAccounts();
            this.filteredAccounts = [...this.accounts];
            this.displayAccounts();
        } catch (error) {
            console.error('Error loading accounts:', error);
            // Use mock data for demo
            this.loadMockAccounts();
        }
    }

    /**
     * Load mock accounts for demo purposes
     */
    loadMockAccounts() {
        this.accounts = [
            {
                id: 1,
                code: '1000',
                name: 'Cash and Cash Equivalents',
                type: 'asset',
                description: 'Physical currency and demand deposits',
                balance: 25000.00,
                status: 'active',
                parent_id: null
            },
            {
                id: 2,
                code: '1010',
                name: 'Business Checking Account',
                type: 'asset',
                description: 'Primary business checking account',
                balance: 22500.00,
                status: 'active',
                parent_id: 1
            },
            {
                id: 3,
                code: '1020',
                name: 'Petty Cash',
                type: 'asset',
                description: 'Small cash fund for minor expenses',
                balance: 2500.00,
                status: 'active',
                parent_id: 1
            },
            {
                id: 4,
                code: '1200',
                name: 'Accounts Receivable',
                type: 'asset',
                description: 'Money owed to the company by customers',
                balance: 15000.00,
                status: 'active',
                parent_id: null
            },
            {
                id: 5,
                code: '2000',
                name: 'Accounts Payable',
                type: 'liability',
                description: 'Money owed by the company to suppliers',
                balance: -8500.00,
                status: 'active',
                parent_id: null
            },
            {
                id: 6,
                code: '3000',
                name: 'Owner\'s Equity',
                type: 'equity',
                description: 'Owner investment and retained earnings',
                balance: 31500.00,
                status: 'active',
                parent_id: null
            },
            {
                id: 7,
                code: '4000',
                name: 'Sales Revenue',
                type: 'revenue',
                description: 'Income from primary business operations',
                balance: 125430.50,
                status: 'active',
                parent_id: null
            },
            {
                id: 8,
                code: '5000',
                name: 'Operating Expenses',
                type: 'expense',
                description: 'Day-to-day business expenses',
                balance: -87320.75,
                status: 'active',
                parent_id: null
            }
        ];
        
        this.filteredAccounts = [...this.accounts];
        this.displayAccounts();
    }

    /**
     * Display accounts in the table
     */
    displayAccounts() {
        const tbody = document.getElementById('accounts-tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (this.filteredAccounts.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: #64748b;">
                        No accounts found
                    </td>
                </tr>
            `;
            return;
        }

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
        
        const typeClass = this.getAccountTypeClass(account.type);
        const statusClass = account.status === 'active' ? 'status-badge--active' : 'status-badge--inactive';
        const balanceClass = account.balance >= 0 ? 'text-green-600' : 'text-red-600';
        
        row.innerHTML = `
            <td class="account-code">${account.code}</td>
            <td class="account-name">
                <strong>${account.name}</strong>
                ${account.parent_id ? '<span class="account-sub">Sub-account</span>' : ''}
            </td>
            <td>
                <span class="account-type ${typeClass}">${this.formatAccountType(account.type)}</span>
            </td>
            <td class="account-description">${account.description || '-'}</td>
            <td class="${balanceClass} font-medium account-balance">
                ${this.formatCurrency(account.balance)}
            </td>
            <td>
                <span class="status-badge ${statusClass}">
                    ${account.status}
                </span>
            </td>
            <td class="account-actions">
                <button class="btn-icon btn-icon--edit" onclick="accountsPage.editAccount(${account.id})" title="Edit">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                    </svg>
                </button>
                <button class="btn-icon btn-icon--delete" onclick="accountsPage.deleteAccount(${account.id})" title="Delete">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                    </svg>
                </button>
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
            switch (account.type) {
                case 'asset':
                    totalAssets += account.balance;
                    break;
                case 'liability':
                    totalLiabilities += account.balance;
                    break;
                case 'equity':
                    totalEquity += account.balance;
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

        // Modal controls
        this.setupModalListeners();
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
        const title = document.getElementById('modal-title');
        const form = document.getElementById('account-form');

        if (!modal || !title || !form) return;

        // Reset form
        form.reset();
        this.currentEditId = null;

        if (account) {
            // Edit mode
            title.textContent = 'Edit Account';
            this.currentEditId = account.id;
            
            document.getElementById('account-code').value = account.code;
            document.getElementById('account-name').value = account.name;
            document.getElementById('account-type').value = account.type;
            document.getElementById('account-description').value = account.description || '';
            document.getElementById('account-parent').value = account.parent_id || '';
        } else {
            // Add mode
            title.textContent = 'Add New Account';
        }

        modal.classList.add('modal--active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Hide account modal
     */
    hideAccountModal() {
        const modal = document.getElementById('account-modal');
        if (!modal) return;

        modal.classList.remove('modal--active');
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
     * Delete account
     */
    async deleteAccount(id) {
        if (!confirm('Are you sure you want to delete this account? This action cannot be undone.')) {
            return;
        }

        try {
            await apiService.deleteAccount(id);
            this.accounts = this.accounts.filter(acc => acc.id !== id);
            this.filteredAccounts = this.filteredAccounts.filter(acc => acc.id !== id);
            this.displayAccounts();
            this.updateStatistics();
            this.showSuccess('Account deleted successfully');
        } catch (error) {
            console.error('Error deleting account:', error);
            this.showError('Failed to delete account');
        }
    }

    /**
     * Save account
     */
    async saveAccount() {
        const formData = {
            code: document.getElementById('account-code').value,
            name: document.getElementById('account-name').value,
            type: document.getElementById('account-type').value,
            description: document.getElementById('account-description').value,
            parent_id: document.getElementById('account-parent').value || null
        };

        try {
            if (this.currentEditId) {
                // Update existing account
                await apiService.updateAccount(this.currentEditId, formData);
                const index = this.accounts.findIndex(acc => acc.id === this.currentEditId);
                this.accounts[index] = { ...this.accounts[index], ...formData };
                this.showSuccess('Account updated successfully');
            } else {
                // Create new account
                const newAccount = await apiService.createAccount(formData);
                this.accounts.push(newAccount);
                this.showSuccess('Account created successfully');
            }

            this.filteredAccounts = [...this.accounts];
            this.displayAccounts();
            this.updateStatistics();
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
            account.name.toLowerCase().includes(term) ||
            account.code.toLowerCase().includes(term) ||
            (account.description && account.description.toLowerCase().includes(term))
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
            this.filteredAccounts = this.accounts.filter(account => account.type === type);
        }
        
        this.displayAccounts();
    }

    /**
     * Format currency value
     */
    formatCurrency(value) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(value);
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