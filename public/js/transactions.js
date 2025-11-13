/**
 * Transaction Management JavaScript
 * Handles two-tier transaction system: External Investment & Micro Transactions
 */

class TransactionManager {

    constructor() {

        this.currentTransactionType = 'external-investment';

        this.accounts = {

            assets: [],

            liabilities: [],

            expenses: []

        };

        this.init();

    }



    init() {

        this.setupEventListeners();

        this.setDefaultDate();

        this.loadAccounts();

        this.setupCompanyChangeListener();

    }



    setupCompanyChangeListener() {

        const companySelect = document.getElementById('current-company');

        if (companySelect) {

            companySelect.addEventListener('change', () => {

                this.loadAccounts();

            });

        }

    }



    setupEventListeners() {

        // Transaction type switching

        document.querySelectorAll('.type-btn').forEach(btn => {

            btn.addEventListener('click', (e) => {

                this.switchTransactionType(e.target.dataset.type);

            });

        });



        // Percentage slider

        const percentageSlider = document.getElementById('ownership-percentage');

        const percentageValue = document.getElementById('percentage-value');

        

        if (percentageSlider && percentageValue) {

            percentageSlider.addEventListener('input', (e) => {

                percentageValue.textContent = parseFloat(e.target.value).toFixed(1);

            });

        }



        // Form submission

        const submitBtn = document.getElementById('submit-transaction');

        if (submitBtn) {

            submitBtn.addEventListener('click', () => {

                this.submitTransaction();

            });

        }



        // Modal reset on close

        const modal = document.getElementById('transaction-modal');

        if (modal) {

            modal.addEventListener('hidden.bs.modal', () => {

                this.resetForms();

            });

        }



        // Add transaction button

        const addBtn = document.getElementById('add-transaction-btn');

        if (addBtn) {

            addBtn.addEventListener('click', () => {

                this.showTransactionModal();

            });

        }



        // Liability form auto-check existing accounts

        const liabilityName = document.getElementById('liability-name');

        const liabilityType = document.getElementById('liability-type');

        

        if (liabilityName) {

            liabilityName.addEventListener('blur', () => {

                if (liabilityName.value.trim() && liabilityType?.value) {

                    setTimeout(() => this.checkExistingLiabilityAccounts(), 500);

                }

            });

        }

        

        if (liabilityType) {

            liabilityType.addEventListener('change', () => {

                if (liabilityType.value && liabilityName?.value.trim()) {

                    setTimeout(() => this.checkExistingLiabilityAccounts(), 500);

                }

            });

        }



        // Modal close buttons

        const closeBtn = document.getElementById('close-transaction-modal-btn');

        const cancelBtn = document.getElementById('cancel-transaction-btn');

        

        if (closeBtn) {

            closeBtn.addEventListener('click', () => {

                this.hideTransactionModal();

            });

        }

        

        if (cancelBtn) {

            cancelBtn.addEventListener('click', () => {

                this.hideTransactionModal();

            });

        }

    }



    switchTransactionType(type) {

        this.currentTransactionType = type;

        

        // Update button states

        document.querySelectorAll('.type-btn').forEach(btn => {

            btn.classList.remove('active');

        });

        document.querySelector(`[data-type="${type}"]`).classList.add('active');

        

        // Switch forms

        document.querySelectorAll('.transaction-form').forEach(form => {

            form.classList.remove('active');

        });

        document.getElementById(`${type}-form`).classList.add('active');

    }



    setDefaultDate() {

        const today = new Date().toISOString().split('T')[0];

        const externalDate = document.getElementById('external-date');

        const microDate = document.getElementById('micro-date');

        const liabilityDate = document.getElementById('liability-date');

        

        if (externalDate) externalDate.value = today;

        if (microDate) microDate.value = today;

        if (liabilityDate) liabilityDate.value = today;

    }



    async loadAccounts() {

        try {

            // Force refresh with current company context

            const companyId = this.getCurrentCompanyId();

            const response = await apiService.getAccounts({ company_id: companyId });

            

            if (response.success && response.data) {

                this.categorizeAccounts(response.data);

                this.populateAccountSelects();

            }

        } catch (error) {

            console.error('Error loading accounts:', error);

            this.showAlert('warning', 'Failed to load accounts. Please refresh the page.');

        }

    }



    getCurrentCompanyId() {

        // Get company ID from company selector or app context

        const companySelect = document.getElementById('current-company');

        if (companySelect && companySelect.value) {

            return parseInt(companySelect.value);

        }

        

        // Fallback to app context

        if (window.app && window.app.getCurrentCompanyId) {

            return window.app.getCurrentCompanyId();

        }

        

        // Default fallback

        return 1;

    }



    categorizeAccounts(accounts) {

        this.accounts = {

            assets: [],

            liabilities: [],

            expenses: []

        };



        accounts.forEach(account => {

            // Handle both API response formats (account_type or Type)
            const type = (account.account_type || account.Type || '').toLowerCase();

            if (type === 'asset') {

                this.accounts.assets.push(account);

            } else if (type === 'liability') {

                this.accounts.liabilities.push(account);

            } else if (type === 'expense') {

                this.accounts.expenses.push(account);

            }

        });

    }



    populateAccountSelects() {

        // Populate from account (assets only)

        const fromSelect = document.getElementById('from-account');

        if (fromSelect) {

            fromSelect.innerHTML = '<option value="">Select asset account...</option>';

            

            this.accounts.assets.forEach(account => {

                const option = document.createElement('option');

                option.value = account.account_id || account.id;

                option.textContent = `${account.account_name || account['Account Name']} (${parseFloat(account.current_balance || account.Balance || 0).toFixed(2)})`;

                fromSelect.appendChild(option);

            });

        }



        // Populate to account (liabilities + expenses)

        const toSelect = document.getElementById('to-account');

        if (toSelect) {

            toSelect.innerHTML = '<option value="">Select liability or expense account...</option>';

            

            const liabilityAndExpenseAccounts = [...this.accounts.liabilities, ...this.accounts.expenses];

            liabilityAndExpenseAccounts.forEach(account => {

                const option = document.createElement('option');

                option.value = account.account_id || account.id;

                option.textContent = `${account.account_name || account['Account Name']} (${parseFloat(account.current_balance || account.Balance || 0).toFixed(2)})`;

                toSelect.appendChild(option);

            });

        }

    }



    validateExternalInvestment() {

        const investorName = document.getElementById('investor-name')?.value?.trim();

        const amount = parseFloat(document.getElementById('investment-amount')?.value);

        const percentage = parseFloat(document.getElementById('ownership-percentage')?.value);

        const date = document.getElementById('external-date')?.value;

        const description = document.getElementById('external-description')?.value?.trim();

        const companyId = this.getCurrentCompanyId();



        const errors = [];



        if (!investorName) errors.push('Investor name is required');

        if (!amount || amount <= 0) errors.push('Investment amount must be greater than 0');

        if (!percentage || percentage <= 0 || percentage > 100) errors.push('Ownership percentage must be between 0.1 and 100');

        if (!date) errors.push('Transaction date is required');

        if (!description) errors.push('Description is required');

        if (!companyId) errors.push('Company must be selected');



        if (errors.length > 0) {

            this.showAlert('danger', errors.join('<br>'));

            return false;

        }



        return {

            investor_name: investorName,

            amount: amount,

            ownership_percentage: percentage,

            date: date,

            description: description,

            company_id: companyId

        };

    }



    validateMicroTransaction() {

        const fromAccountId = document.getElementById('from-account')?.value;

        const toAccountId = document.getElementById('to-account')?.value;

        const amount = parseFloat(document.getElementById('micro-amount')?.value);

        const date = document.getElementById('micro-date')?.value;

        const description = document.getElementById('micro-description')?.value?.trim();

        const companyId = this.getCurrentCompanyId();



        const errors = [];



        if (!fromAccountId) errors.push('From account is required');

        if (!toAccountId) errors.push('To account is required');

        if (fromAccountId === toAccountId) errors.push('From and to accounts cannot be the same');

        if (!amount || amount <= 0) errors.push('Amount must be greater than 0');

        if (!date) errors.push('Transaction date is required');

        if (!description) errors.push('Description is required');

        if (!companyId) errors.push('Company must be selected');



        if (errors.length > 0) {

            this.showAlert('danger', errors.join('<br>'));

            return false;

        }



        return {

            from_account_id: parseInt(fromAccountId),

            to_account_id: parseInt(toAccountId),

            amount: amount,

            date: date,

            description: description,

            company_id: companyId

        };

    }



    validateLiability() {

        const liabilityName = document.getElementById('liability-name')?.value?.trim();

        const liabilityType = document.getElementById('liability-type')?.value;

        const amount = parseFloat(document.getElementById('liability-amount')?.value);

        const interestRate = parseFloat(document.getElementById('liability-interest-rate')?.value) || 0;

        const date = document.getElementById('liability-date')?.value;

        const description = document.getElementById('liability-description')?.value?.trim();

        const companyId = parseInt(document.getElementById('liability-company-id')?.value) || this.getCurrentCompanyId();



        const errors = [];



        if (!liabilityName) errors.push('Liability name is required');

        if (!liabilityType) errors.push('Liability type is required');

        if (!amount || amount <= 0) errors.push('Amount must be greater than 0');

        if (!date) errors.push('Transaction date is required');

        if (!description) errors.push('Description is required');

        if (!companyId) errors.push('Company must be selected');



        if (errors.length > 0) {

            this.showAlert('danger', errors.join('<br>'));

            return false;

        }



        return {

            liability_name: liabilityName,

            liability_type: liabilityType,

            amount: amount,

            interest_rate: interestRate,

            date: date,

            description: description,

            company_id: companyId

        };

    }



    async checkExistingLiabilityAccounts() {

        const liabilityName = document.getElementById('liability-name')?.value?.trim();

        const liabilityType = document.getElementById('liability-type')?.value;

        const companyId = this.getCurrentCompanyId();



        if (!liabilityName || !liabilityType) return;



        try {

            const response = await apiService.getAccounts({ company_id: companyId });

            

            if (response.success && response.data) {

                const assetName = `${liabilityType.charAt(0).toUpperCase() + liabilityType.slice(1)} - ${liabilityName}`;

                const liabilityNameFull = `${liabilityName} - ${liabilityType.charAt(0).toUpperCase() + liabilityType.slice(1)}`;

                

                const existingAssets = response.data.filter(account => 

                    account['Account Name'].toLowerCase().includes(liabilityName.toLowerCase()) && 

                    account.Type === 'asset'

                );

                

                const existingLiabilities = response.data.filter(account => 

                    account['Account Name'].toLowerCase().includes(liabilityName.toLowerCase()) && 

                    account.Type === 'liability'

                );

                

                this.displayExistingAccounts(existingAssets, existingLiabilities);

            }

        } catch (error) {

            console.error('Error checking existing accounts:', error);

        }

    }



    displayExistingAccounts(assets, liabilities) {

        const container = document.getElementById('existing-accounts-section');

        const list = document.getElementById('existing-accounts-list');



        if (!container || !list) return;



        list.innerHTML = '';



        if (assets.length === 0 && liabilities.length === 0) {

            list.innerHTML = '<p style="color: #64748b; font-style: italic;">No existing accounts found for this entity.</p>';

        } else {

            if (assets.length > 0) {

                const assetTitle = document.createElement('h6');

                assetTitle.textContent = 'Existing Asset Accounts:';

                assetTitle.style.color = '#059669';

                assetTitle.style.marginBottom = '10px';

                list.appendChild(assetTitle);



                assets.forEach(account => {

                    const item = document.createElement('div');

                    item.className = 'existing-account-item';

                    item.style.cssText = 'background: #f0fdf4; padding: 8px; margin-bottom: 6px; border-radius: 4px; border-left: 3px solid #059669;';

                    item.innerHTML = `

                        <strong>${account['Account Name']}</strong><br>

                        <small>Type: ${account.Type} | Balance: $${parseFloat(account.Balance || 0).toFixed(2)} | Code: ${account.code}</small>

                    `;

                    list.appendChild(item);

                });

            }



            if (liabilities.length > 0) {

                const liabilityTitle = document.createElement('h6');

                liabilityTitle.textContent = 'Existing Liability Accounts:';

                liabilityTitle.style.color = '#dc2626';

                liabilityTitle.style.marginBottom = '10px';

                liabilityTitle.style.marginTop = '15px';

                list.appendChild(liabilityTitle);



                liabilities.forEach(account => {

                    const item = document.createElement('div');

                    item.className = 'existing-account-item';

                    item.style.cssText = 'background: #fef2f2; padding: 8px; margin-bottom: 6px; border-radius: 4px; border-left: 3px solid #dc2626;';

                    item.innerHTML = `

                        <strong>${account['Account Name']}</strong><br>

                        <small>Type: ${account.Type} | Balance: $${parseFloat(account.Balance || 0).toFixed(2)} | Code: ${account.code}</small>

                    `;

                    list.appendChild(item);

                });

            }

        }



        container.style.display = 'block';

    }



    async submitTransaction() {

        let transactionData;



        if (this.currentTransactionType === 'external-investment') {

            transactionData = this.validateExternalInvestment();

            if (transactionData) {

                await this.createExternalInvestment(transactionData);

            }

        } else if (this.currentTransactionType === 'liability') {

            transactionData = this.validateLiability();

            if (transactionData) {

                await this.createLiability(transactionData);

            }

        } else {

            transactionData = this.validateMicroTransaction();

            if (transactionData) {

                await this.createMicroTransaction(transactionData);

            }

        }

    }



    async createExternalInvestment(transactionData) {

        try {

            this.setSubmitButtonLoading(true);



            const result = await apiService.createExternalInvestment(transactionData);



            if (result.success) {

                this.showAlert('success', result.message || 'External investment created successfully!');

                this.hideTransactionModal();

                this.loadAccounts(); // Reload accounts to update balances

                

                // Refresh transactions list if function exists

                if (window.transactionsPage && window.transactionsPage.loadData) {

                    window.transactionsPage.loadData();

                }

            } else {

                this.showAlert('danger', result.message || 'Failed to create external investment');

            }

        } catch (error) {

            console.error('External investment submission error:', error);

            this.showAlert('danger', 'Network error. Please try again.');

        } finally {

            this.setSubmitButtonLoading(false);

        }

    }



    async createLiability(transactionData) {

        try {

            this.setSubmitButtonLoading(true);



            const result = await apiService.createLiability(transactionData);



            if (result.success) {

                this.showAlert('success', result.message || 'Liability and corresponding asset created successfully!');

                this.hideTransactionModal();

                this.loadAccounts(); // Reload accounts to update balances

                

                // Refresh transactions list if function exists

                if (window.transactionsPage && window.transactionsPage.loadData) {

                    window.transactionsPage.loadData();

                }

            } else {

                this.showAlert('danger', result.message || 'Failed to create liability');

            }

        } catch (error) {

            console.error('Liability submission error:', error);

            this.showAlert('danger', 'Network error. Please try again.');

        } finally {

            this.setSubmitButtonLoading(false);

        }

    }



        async createMicroTransaction(transactionData) {



            try {



                this.setSubmitButtonLoading(true);



    



                const result = await apiService.createMicroTransaction(transactionData);



    



                if (result.success) {



                    this.showAlert('success', result.message || 'Micro transaction created successfully!');



                    this.hideTransactionModal();



                    this.loadAccounts(); // Reload accounts to update balances



                    



                    // Refresh transactions list if function exists



                    if (window.transactionsPage && window.transactionsPage.loadData) {



                        window.transactionsPage.loadData();



                    }



                } else {



                    this.showAlert('danger', result.message || 'Failed to create micro transaction');



                }



            } catch (error) {



                console.error('Micro transaction submission error:', error);



                this.showAlert('danger', 'Network error. Please try again.');



            } finally {



                this.setSubmitButtonLoading(false);



            }



        }



    setSubmitButtonLoading(loading) {

        const submitBtn = document.getElementById('submit-transaction');

        

        if (submitBtn) {

            if (loading) {

                submitBtn.disabled = true;

                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            } else {

                submitBtn.disabled = false;

                submitBtn.innerHTML = '<i class="fas fa-save"></i> Create Transaction';

            }

        }

    }



    showTransactionModal() {

        const modal = document.getElementById('transaction-modal');

        if (modal) {

            modal.style.display = 'flex';

            modal.classList.add('modal--active');

            document.body.style.overflow = 'hidden';

            // Load accounts when modal opens
            this.loadAccounts();

        }

    }



    hideTransactionModal() {

        const modal = document.getElementById('transaction-modal');

        if (modal) {

            modal.style.display = 'none';

            modal.classList.remove('modal--active');

            document.body.style.overflow = '';

        }

    }



    resetForms() {

        // Reset external investment form

        const investorName = document.getElementById('investor-name');

        const investmentAmount = document.getElementById('investment-amount');

        const ownershipPercentage = document.getElementById('ownership-percentage');

        const percentageValue = document.getElementById('percentage-value');

        const externalDescription = document.getElementById('external-description');

        

        if (investorName) investorName.value = '';

        if (investmentAmount) investmentAmount.value = '';

        if (ownershipPercentage) ownershipPercentage.value = '10';

        if (percentageValue) percentageValue.textContent = '10.0';

        if (externalDescription) externalDescription.value = 'External investment from investor';

        

        // Reset micro transaction form

        const fromAccount = document.getElementById('from-account');

        const toAccount = document.getElementById('to-account');

        const microAmount = document.getElementById('micro-amount');

        const microDescription = document.getElementById('micro-description');

        

        if (fromAccount) fromAccount.value = '';

        if (toAccount) toAccount.value = '';

        if (microAmount) microAmount.value = '';

        if (microDescription) microDescription.value = 'Micro transaction between accounts';

        

        // Reset liability form

        const liabilityName = document.getElementById('liability-name');

        const liabilityType = document.getElementById('liability-type');

        const liabilityAmount = document.getElementById('liability-amount');

        const liabilityInterestRate = document.getElementById('liability-interest-rate');

        const liabilityDescription = document.getElementById('liability-description');

        const liabilityCompanyId = document.getElementById('liability-company-id');

        const existingAccountsSection = document.getElementById('existing-accounts-section');

        

        if (liabilityName) liabilityName.value = '';

        if (liabilityType) liabilityType.value = '';

        if (liabilityAmount) liabilityAmount.value = '';

        if (liabilityInterestRate) liabilityInterestRate.value = '0';

        if (liabilityDescription) liabilityDescription.value = 'Liability created with corresponding asset';

        if (liabilityCompanyId) liabilityCompanyId.value = this.getCurrentCompanyId() || '1';

        if (existingAccountsSection) existingAccountsSection.style.display = 'none';

        

        // Reset date to today

        this.setDefaultDate();

        

        // Clear any alerts

        const alertContainer = document.getElementById('alert-container') || document.getElementById('toast-container');

        if (alertContainer) {

            alertContainer.innerHTML = '';

        }

    }



    showAlert(type, message) {

        // Try to use existing app alert system first

        if (window.app && window.app.showError) {

            if (type === 'success') {

                window.app.showSuccess(message);

            } else {

                window.app.showError(message);

            }

            return;

        }



        // Fallback to custom alert system

        let alertContainer = document.getElementById('alert-container');

        if (!alertContainer) {

            alertContainer = document.getElementById('toast-container');

        }

        

        if (!alertContainer) {

            // Create alert container if it doesn't exist

            alertContainer = document.createElement('div');

            alertContainer.id = 'alert-container';

            alertContainer.style.cssText = `

                position: fixed;

                top: 20px;

                right: 20px;

                z-index: 9999;

                max-width: 400px;

            `;

            document.body.appendChild(alertContainer);

        }

        

        const alertId = 'alert-' + Date.now();

        

        const alertHtml = `

            <div class="alert alert-${type} alert-dismissible fade show" role="alert" id="${alertId}">

                ${message}

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

            </div>

        `;

        

        alertContainer.innerHTML = alertHtml;

        

        // Auto-dismiss after 5 seconds

        setTimeout(() => {

            const alert = document.getElementById(alertId);

            if (alert) {

                alert.remove();

            }

        }, 5000);

    }

}



class TransactionsPage {

    constructor() {

        this.transactions = [];

        this.filteredTransactions = [];

        this.init();

    }



    async init() {

        try {

            await this.loadData();

            this.setupEventListeners();

        } catch (error) {

            console.error('Failed to initialize transactions page:', error);

        }

    }



        async loadData() {



            try {



                const response = await apiService.getTransactions();



                this.transactions = response.success ? response.data.data : [];



                this.filteredTransactions = [...this.transactions];



                this.displayTransactions();



            } catch (error) {



                console.error('Error loading transactions:', error);



            }



        }



    displayTransactions() {

        const tbody = document.getElementById('transactions-tbody');

        if (!tbody) return;



        tbody.innerHTML = '';



        if (this.filteredTransactions.length === 0) {

            tbody.innerHTML = `

                <tr>

                    <td colspan="9" style="text-align: center; padding: 2rem; color: #64748b;">

                        No transactions found

                    </td>

                </tr>

            `;

            return;

        }



        this.filteredTransactions.forEach(transaction => {

            const row = this.createTransactionRow(transaction);

            tbody.appendChild(row);

        });

    }



    createTransactionRow(transaction) {

        const row = document.createElement('tr');

        row.innerHTML = `

            <td><input type="checkbox" class="transaction-checkbox" data-id="${transaction.id}"></td>

            <td>${transaction.Date}</td>

            <td>${transaction.Description}</td>

            <td>${transaction.from_account_name || ''}</td>

            <td>${transaction.to_account_name || ''}</td>

            <td>${window.currencyUtils.formatCurrency(transaction.Amount)}</td>

            <td>${transaction.Type}</td>

            <td><span class="status-badge status-${transaction.Status}">${transaction.Status}</span></td>

            <td>
 
                ${transaction.Status === 'draft' || transaction.Status === 'pending' ? 
                    `<button class="btn btn-sm btn-outline-warning edit-btn" data-id="${transaction.id}">
                        <i class="fas fa-edit"></i> Edit
                    </button>` : 
                    `<span class="text-muted" style="font-size: 0.875rem;">
                        <i class="fas fa-lock"></i> Posted
                    </span>`
                }
 
                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${transaction.id}">
                    <i class="fas fa-trash"></i> Delete
                </button>
 
            </td>

        `;

        return row;

    }



        setupEventListeners() {



            const tbody = document.getElementById('transactions-tbody');



            if (!tbody) return;



    



            tbody.addEventListener('click', (e) => {



                if (e.target.classList.contains('delete-btn')) {



                    const transactionId = e.target.dataset.id;



                    this.deleteTransaction(transactionId);



                }



            });



        }



    



        async deleteTransaction(id) {



            if (!confirm('Are you sure you want to delete this transaction?')) {



                return;



            }



    



            try {



                await apiService.deleteTransaction(id);



                this.loadData();



            } catch (error) {



                console.error('Error deleting transaction:', error);



            }



        }



    }



// Initialize when DOM is loaded

document.addEventListener('DOMContentLoaded', () => {

    // Initialize transaction manager

    window.transactionManager = new TransactionManager();

    

    // Initialize transactions page

    window.transactionsPage = new TransactionsPage();

});



// Export for use in other modules

if (typeof module !== 'undefined' && module.exports) {

    module.exports = { TransactionManager, TransactionsPage };

}
