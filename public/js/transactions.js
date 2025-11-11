/**
 * Transactions Page Module
 * Handles all transaction-related functionality
 */

class TransactionsPage {
    constructor() {
        this.transactions = [];
        this.filteredTransactions = [];
        this.accounts = [];
        this.currentPage = 1;
        this.itemsPerPage = 25;
        this.currentEditId = null;
        this.init();
    }

    /**
     * Initialize transactions page
     */
    async init() {
        try {
            await this.loadData();
            this.setupEventListeners();
            this.updateStatistics();
        } catch (error) {
            console.error('Failed to initialize transactions page:', error);
            this.showError('Failed to load transactions');
        }
    }

    /**
     * Load all necessary data
     */
    async loadData() {
        try {
            // Load transactions and accounts in parallel
            const [transactions, accounts] = await Promise.all([
                this.loadTransactions(),
                this.loadAccounts()
            ]);
            
            this.displayTransactions();
            this.populateAccountSelects();
        } catch (error) {
            console.error('Error loading data:', error);
            this.showError('Failed to load transactions data');
        }
    }

    /**
     * Load transactions from API
     */
    async loadTransactions() {
        const response = await apiService.getTransactions();
        this.transactions = response.success ? response.data : response;
        this.filteredTransactions = [...this.transactions];
        return this.transactions;
    }

    /**
     * Load accounts from API
     */
    async loadAccounts() {
        const response = await apiService.getAccounts();
        this.accounts = response.success ? response.data : response;
        return this.accounts;
    }



    /**
     * Display transactions in the table
     */
    displayTransactions() {
        const tbody = document.getElementById('transactions-tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (this.filteredTransactions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: #64748b;">
                        No transactions found
                    </td>
                </tr>
            `;
            return;
        }

        // Calculate pagination
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const paginatedTransactions = this.filteredTransactions.slice(startIndex, endIndex);

        paginatedTransactions.forEach(transaction => {
            const row = this.createTransactionRow(transaction);
            tbody.appendChild(row);
        });

        this.updatePagination();
    }

    /**
     * Create a table row for a transaction
     */
    createTransactionRow(transaction) {
        const row = document.createElement('tr');
        
        // Handle positive/negative amounts
        const isCredit = transaction.Amount > 0;
        const amountClass = isCredit ? 'text-green-600' : 'text-red-600';
        const amountPrefix = isCredit ? '+' : '-';
        const statusClass = transaction.Status === 'completed' ? 'status-badge--active' : 'status-badge--pending';
        
        row.innerHTML = `
            <td>
                <input type="checkbox" class="checkbox transaction-checkbox" data-id="${transaction.id}">
            </td>
            <td>${this.formatDate(transaction.Date)}</td>
            <td class="transaction-description">
                <strong>${transaction.Description}</strong>
            </td>
            <td>${transaction.Account}</td>
            <td>
                <span class="transaction-category">${transaction.Category}</span>
            </td>
            <td class="${amountClass} font-medium transaction-amount">
                ${amountPrefix}$${Math.abs(transaction.Amount).toLocaleString()}
            </td>
            <td>
                <span class="status-badge ${statusClass}">
                    ${transaction.Status}
                </span>
            </td>
            <td class="transaction-actions">
                <button class="btn-icon btn-icon--edit" onclick="transactionsPage.editTransaction(${transaction.id})" title="Edit">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                    </svg>
                </button>
                <button class="btn-icon btn-icon--delete" onclick="transactionsPage.deleteTransaction(${transaction.id})" title="Delete">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                    </svg>
                </button>
            </td>
        `;
        
        return row;
    }

    /**
     * Populate account select dropdowns
     */
    populateAccountSelects() {
        const selects = [
            'transaction-account',
            'account-filter',
            'debit-account',
            'credit-account'
        ];

        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (!select) return;

            const currentValue = select.value;
            select.innerHTML = '';

            // Add default option
            if (selectId !== 'account-filter') {
                select.innerHTML = '<option value="">Select Account</option>';
            } else {
                select.innerHTML = '<option value="">All Accounts</option>';
            }

            // Add accounts
            this.accounts.forEach(account => {
                const option = document.createElement('option');
                option.value = account.id;
                option.textContent = account['Account Name'];
                select.appendChild(option);
            });

            // Restore previous value
            if (currentValue) {
                select.value = currentValue;
            }
        });

        // Populate category dropdown
        this.populateCategories();
    }

    /**
     * Populate category dropdown
     */
    populateCategories() {
        const categorySelect = document.getElementById('transaction-category');
        if (!categorySelect) return;

        const categories = [
            'Sales Revenue',
            'Service Revenue',
            'Interest Income',
            'Cost of Goods Sold',
            'Salaries',
            'Rent',
            'Utilities',
            'Marketing',
            'Office Supplies',
            'Insurance',
            'Equipment',
            'Software',
            'Professional Services',
            'Other Expenses'
        ];

        categorySelect.innerHTML = '<option value="">Select Category</option>';
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            categorySelect.appendChild(option);
        });
    }

    /**
     * Update transaction statistics
     */
    updateStatistics() {
        const stats = this.calculateStatistics();
        
        document.getElementById('total-income').textContent = this.formatCurrency(stats.totalIncome);
        document.getElementById('total-expenses').textContent = this.formatCurrency(Math.abs(stats.totalExpenses));
        document.getElementById('net-change').textContent = this.formatCurrency(stats.netChange);
        document.getElementById('total-transactions').textContent = this.transactions.length;
    }

    /**
     * Calculate transaction statistics
     */
    calculateStatistics() {
        let totalIncome = 0;
        let totalExpenses = 0;

        this.transactions.forEach(transaction => {
            const amount = transaction.Amount;
            
            if (amount > 0) {
                totalIncome += amount;
            } else {
                totalExpenses += Math.abs(amount);
            }
        });

        return {
            totalIncome,
            totalExpenses,
            netChange: totalIncome - totalExpenses
        };
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Add transaction button
        const addBtn = document.getElementById('add-transaction-btn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showTransactionModal());
        }

        // Search functionality
        const searchInput = document.getElementById('transaction-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterTransactions(e.target.value));
        }

        // Filter dropdowns
        const accountFilter = document.getElementById('account-filter');
        if (accountFilter) {
            accountFilter.addEventListener('change', () => this.applyFilters());
        }

        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', () => this.applyFilters());
        }

        const dateRange = document.getElementById('date-range');
        if (dateRange) {
            dateRange.addEventListener('change', () => this.applyFilters());
        }

        // Pagination
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.changePage(-1));
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.changePage(1));
        }

        // Select all checkbox
        const selectAll = document.getElementById('select-all');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => this.selectAllTransactions(e.target.checked));
        }

        // Export button
        const exportBtn = document.getElementById('export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportTransactions());
        }

        // Modal controls
        this.setupModalListeners();
    }

    /**
     * Setup modal event listeners
     */
    setupModalListeners() {
        const modal = document.getElementById('transaction-modal');
        const overlay = document.getElementById('modal-overlay');
        const closeBtn = document.getElementById('close-transaction-modal-btn');
        const cancelBtn = document.getElementById('cancel-transaction-btn');
        const form = document.getElementById('transaction-form');

        // Close modal events
        const closeModal = () => this.hideTransactionModal();
        
        if (overlay) overlay.addEventListener('click', closeModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        // Form submission
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveTransaction();
            });
        }

        // Transaction type change
        const typeSelect = document.getElementById('transaction-type');
        if (typeSelect) {
            typeSelect.addEventListener('change', (e) => this.handleTypeChange(e.target.value));
        }

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('modal--active')) {
                closeModal();
            }
        });
    }

    /**
     * Show transaction modal
     */
    showTransactionModal(transaction = null) {
        const modal = document.getElementById('transaction-modal');
        const title = document.getElementById('modal-title');
        const form = document.getElementById('transaction-form');

        if (!modal || !title || !form) return;

        // Reset form
        form.reset();
        this.currentEditId = null;

        // Set default date to today
        const dateInput = document.getElementById('transaction-date');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }

        if (transaction) {
            // Edit mode
            title.textContent = 'Edit Transaction';
            this.currentEditId = transaction.id;
            
            // Convert credit/debit to income/expense for form
            const formType = transaction.type === 'credit' ? 'income' : 'expense';
            
            const dateInput = document.getElementById('transaction-date');
            const typeInput = document.getElementById('transaction-type');
            const descInput = document.getElementById('transaction-description');
            const categoryInput = document.getElementById('transaction-category');
            const amountInput = document.getElementById('transaction-amount');
            const notesInput = document.getElementById('transaction-notes');
            
            if (dateInput) dateInput.value = transaction.Date;
            if (typeInput) typeInput.value = formType;
            if (descInput) descInput.value = transaction.Description;
            if (categoryInput) categoryInput.value = transaction.Category;
            if (amountInput) amountInput.value = Math.abs(transaction.Amount);
            if (notesInput) notesInput.value = transaction.Name || '';
            
            // Set account selection
            const accountSelect = document.getElementById('transaction-account');
            if (accountSelect) {
                // Find account by name
                const accountOption = Array.from(accountSelect.options).find(
                    option => option.text === transaction.Account
                );
                if (accountOption) {
                    accountSelect.value = accountOption.value;
                }
            }
        } else {
            // Add mode
            title.textContent = 'Add New Transaction';
        }

        modal.classList.add('modal--active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Hide transaction modal
     */
    hideTransactionModal() {
        const modal = document.getElementById('transaction-modal');
        if (!modal) return;

        modal.classList.remove('modal--active');
        document.body.style.overflow = '';
        this.currentEditId = null;
    }

    /**
     * Handle transaction type change
     */
    handleTypeChange(type) {
        const amountInput = document.getElementById('transaction-amount');
        if (!amountInput) return;

        if (type === 'expense') {
            amountInput.style.borderColor = 'var(--color-error)';
        } else {
            amountInput.style.borderColor = '';
        }
    }

    /**
     * Edit transaction
     */
    editTransaction(id) {
        const transaction = this.transactions.find(t => t.id === id);
        if (transaction) {
            this.showTransactionModal(transaction);
        }
    }

    /**
     * Delete transaction
     */
    async deleteTransaction(id) {
        if (!confirm('Are you sure you want to delete this transaction? This action cannot be undone.')) {
            return;
        }

        try {
            await apiService.deleteTransaction(id);
            this.transactions = this.transactions.filter(t => t.id !== id);
            this.filteredTransactions = this.filteredTransactions.filter(t => t.id !== id);
            this.displayTransactions();
            this.updateStatistics();
            this.showSuccess('Transaction deleted successfully');
        } catch (error) {
            console.error('Error deleting transaction:', error);
            this.showError('Failed to delete transaction');
        }
    }

    /**
     * Save transaction
     */
    async saveTransaction() {
        const transactionType = document.getElementById('transaction-type').value;
        const amount = parseFloat(document.getElementById('transaction-amount').value) || 0;
        
        // Convert income/expense to credit/debit
        let apiType = 'credit';
        if (transactionType === 'expense') {
            apiType = 'debit';
        }
        
        const formData = {
            date: document.getElementById('transaction-date').value,
            type: apiType,
            description: document.getElementById('transaction-description').value,
            account: document.getElementById('transaction-account').options[document.getElementById('transaction-account').selectedIndex].text,
            category: document.getElementById('transaction-category').value,
            amount: amount,
            notes: document.getElementById('transaction-notes').value || ''
        };

        try {
            if (this.currentEditId) {
                // Update existing transaction
                await apiService.updateTransaction(this.currentEditId, formData);
                const index = this.transactions.findIndex(t => t.id === this.currentEditId);
                this.transactions[index] = { ...this.transactions[index], ...formData };
                this.showSuccess('Transaction updated successfully');
            } else {
                // Create new transaction
                const newTransaction = await apiService.createTransaction(formData);
                this.transactions.push(newTransaction);
                this.showSuccess('Transaction created successfully');
            }

            this.filteredTransactions = [...this.transactions];
            this.displayTransactions();
            this.updateStatistics();
            this.hideTransactionModal();
        } catch (error) {
            console.error('Error saving transaction:', error);
            this.showError('Failed to save transaction');
        }
    }

    /**
     * Filter transactions
     */
    filterTransactions(searchTerm) {
        const term = searchTerm.toLowerCase();
        
        this.filteredTransactions = this.transactions.filter(transaction => 
            transaction.Description.toLowerCase().includes(term) ||
            transaction.Account.toLowerCase().includes(term) ||
            transaction.Category.toLowerCase().includes(term) ||
            (transaction.Name && transaction.Name.toLowerCase().includes(term))
        );
        
        this.currentPage = 1;
        this.displayTransactions();
    }

    /**
     * Apply all filters
     */
    applyFilters() {
        const accountFilter = document.getElementById('account-filter')?.value;
        const categoryFilter = document.getElementById('category-filter')?.value;
        const dateRange = document.getElementById('date-range')?.value;

        this.filteredTransactions = this.transactions.filter(transaction => {
            let matches = true;

            if (accountFilter) {
                matches = matches && transaction.account_id == accountFilter;
            }

            if (categoryFilter) {
                matches = matches && transaction.category === categoryFilter;
            }

            if (dateRange && dateRange !== 'custom') {
                const transactionDate = new Date(transaction.date);
                const now = new Date();
                const daysDiff = Math.floor((now - transactionDate) / (1000 * 60 * 60 * 24));
                matches = matches && daysDiff <= parseInt(dateRange);
            }

            return matches;
        });

        this.currentPage = 1;
        this.displayTransactions();
    }

    /**
     * Select all transactions
     */
    selectAllTransactions(checked) {
        const checkboxes = document.querySelectorAll('.transaction-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
    }

    /**
     * Change page
     */
    changePage(direction) {
        const totalPages = Math.ceil(this.filteredTransactions.length / this.itemsPerPage);
        const newPage = this.currentPage + direction;

        if (newPage >= 1 && newPage <= totalPages) {
            this.currentPage = newPage;
            this.displayTransactions();
        }
    }

    /**
     * Update pagination controls
     */
    updatePagination() {
        const totalPages = Math.ceil(this.filteredTransactions.length / this.itemsPerPage);
        const startIndex = (this.currentPage - 1) * this.itemsPerPage + 1;
        const endIndex = Math.min(startIndex + this.itemsPerPage - 1, this.filteredTransactions.length);

        // Update page info
        const pageInfo = document.getElementById('page-info');
        if (pageInfo) {
            pageInfo.textContent = `Showing ${startIndex}-${endIndex} of ${this.filteredTransactions.length} transactions`;
        }

        // Update button states
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');

        if (prevBtn) {
            prevBtn.disabled = this.currentPage === 1;
        }

        if (nextBtn) {
            nextBtn.disabled = this.currentPage === totalPages;
        }
    }

    /**
     * Export transactions
     */
    exportTransactions() {
        // Get selected transactions
        const selectedIds = Array.from(document.querySelectorAll('.transaction-checkbox:checked'))
            .map(checkbox => parseInt(checkbox.dataset.id));

        let transactionsToExport = selectedIds.length > 0 
            ? this.transactions.filter(t => selectedIds.includes(t.id))
            : this.filteredTransactions;

        // Convert to CSV
        const csv = this.convertToCSV(transactionsToExport);
        
        // Download file
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `transactions_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        this.showSuccess('Transactions exported successfully');
    }

    /**
     * Convert transactions to CSV
     */
    convertToCSV(transactions) {
        const headers = ['Date', 'Description', 'Account', 'Category', 'Amount', 'Status', 'Notes'];
        const rows = transactions.map(t => [
            t.Date,
            t.Description,
            t.Account,
            t.Category,
            t.Amount,
            t.Status,
            t.Name || ''
        ]);

        return [headers, ...rows]
            .map(row => row.map(cell => `"${cell}"`).join(','))
            .join('\n');
    }

    /**
     * Format date
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
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

// Initialize transactions page when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.transactionsPage = new TransactionsPage();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TransactionsPage;
}