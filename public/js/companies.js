/**
 * Companies Page Module
 * Handles all company-related functionality
 */

class CompaniesPage {
    constructor() {
        this.companies = [];
        this.filteredCompanies = [];
        this.currentPage = 1;
        this.itemsPerPage = 25;
        this.currentEditId = null;
        this.selectedCompanyId = null;
        this.init();
    }

    /**
     * Initialize companies page
     */
    async init() {
        try {
            await this.loadData();
            this.setupEventListeners();
            this.updateStatistics();
            this.setupCompanySelector();
        } catch (error) {
            console.error('Failed to initialize companies page:', error);
            this.showError('Failed to load companies');
        }
    }

    /**
     * Load all necessary data
     */
    async loadData() {
        try {
            const response = await apiService.getCompanies();
            this.companies = response.data || response; // Handle both response formats
            this.filteredCompanies = [...this.companies];
            this.displayCompanies();
            
            // Load selected company from localStorage
            this.selectedCompanyId = localStorage.getItem('selectedCompanyId') || null;
            
            return this.companies;
        } catch (error) {
            console.error('Error loading companies:', error);
            this.showError('Failed to load companies data');
            throw error;
        }
    }

    /**
     * Display companies in the table
     */
    displayCompanies() {
        const tbody = document.getElementById('companies-tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (this.filteredCompanies.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: #64748b;">
                        No companies found
                    </td>
                </tr>
            `;
            return;
        }

        // Calculate pagination
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const paginatedCompanies = this.filteredCompanies.slice(startIndex, endIndex);

        paginatedCompanies.forEach(company => {
            const row = this.createCompanyRow(company);
            tbody.appendChild(row);
        });

        this.updatePagination();
    }

    /**
     * Create a table row for a company
     */
    createCompanyRow(company) {
        const row = document.createElement('tr');
        
        const statusClass = company.is_active ? 'status-badge--active' : 'status-badge--inactive';
        const statusText = company.is_active ? 'Active' : 'Inactive';
        
        row.innerHTML = `
            <td>
                <input type="checkbox" class="checkbox company-checkbox" data-id="${company.id}">
            </td>
            <td>
                <div class="company-info">
                    <strong>${company.company_name}</strong>
                    ${company.website ? `<br><small><a href="${company.website}" target="_blank">${company.website}</a></small>` : ''}
                </div>
            </td>
            <td>${company.tax_id || '-'}</td>
            <td>
                <div class="contact-info">
                    ${company.email ? `<div><i class="fas fa-envelope"></i> ${company.email}</div>` : ''}
                    ${company.phone ? `<div><i class="fas fa-phone"></i> ${company.phone}</div>` : ''}
                </div>
            </td>
            <td>
                <span class="currency-badge">${company.currency_code}</span>
            </td>
            <td>
                <span class="status-badge ${statusClass}">
                    ${statusText}
                </span>
            </td>
            <td>${this.formatDate(company.created_at)}</td>
            <td class="company-actions">
                <button class="btn-icon btn-icon--view" onclick="companiesPage.viewCompany(${company.id})" title="View Details">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                    </svg>
                </button>
                <button class="btn-icon btn-icon--edit" onclick="companiesPage.editCompany(${company.id})" title="Edit">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                    </svg>
                </button>
                <button class="btn-icon btn-icon--delete" onclick="companiesPage.deleteCompany(${company.id})" title="Delete">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                    </svg>
                </button>
                <button class="btn-icon btn-icon--select" onclick="companiesPage.selectCompany(${company.id})" title="Select Company">
                    <svg viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                </button>
            </td>
        `;
        
        return row;
    }

    /**
     * Update company statistics
     */
    updateStatistics() {
        const stats = this.calculateStatistics();
        
        document.getElementById('total-companies').textContent = this.companies.length;
        document.getElementById('active-companies').textContent = stats.activeCompanies;
        document.getElementById('total-assets').textContent = this.formatCurrency(stats.totalAssets);
        document.getElementById('total-revenue').textContent = this.formatCurrency(stats.totalRevenue);
    }

    /**
     * Calculate company statistics
     */
    calculateStatistics() {
        const activeCompanies = this.companies.filter(c => c.is_active).length;
        
        // Mock financial data - in real implementation, this would come from API
        const totalAssets = this.companies.reduce((sum, company) => {
            return sum + (Math.random() * 1000000); // Mock data
        }, 0);
        
        const totalRevenue = this.companies.reduce((sum, company) => {
            return sum + (Math.random() * 500000); // Mock data
        }, 0);

        return {
            activeCompanies,
            totalAssets,
            totalRevenue
        };
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Add company button
        const addBtn = document.getElementById('add-company-btn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showCompanyModal());
        }

        // Search functionality
        const searchInput = document.getElementById('company-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterCompanies(e.target.value));
        }

        // Status filter
        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.applyFilters());
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
            selectAll.addEventListener('change', (e) => this.selectAllCompanies(e.target.checked));
        }

        // Export button
        const exportBtn = document.getElementById('export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportCompanies());
        }

        // Modal controls
        this.setupModalListeners();
    }

    /**
     * Setup modal event listeners
     */
    setupModalListeners() {
        const modal = document.getElementById('company-modal');
        const detailsModal = document.getElementById('company-details-modal');
        const overlay = document.getElementById('modal-overlay');
        
        // Close modal events
        const closeModal = () => this.hideCompanyModal();
        const closeDetailsModal = () => this.hideDetailsModal();
        
        if (overlay) {
            overlay.addEventListener('click', () => {
                this.hideCompanyModal();
                this.hideDetailsModal();
            });
        }

        // Company modal close buttons
        const closeBtn = document.getElementById('close-company-modal-btn');
        const cancelBtn = document.getElementById('cancel-company-btn');
        const form = document.getElementById('company-form');

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        // Details modal close button
        const closeDetailsBtn = document.getElementById('close-details-modal-btn');
        if (closeDetailsBtn) closeDetailsBtn.addEventListener('click', closeDetailsModal);

        // Form submission
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveCompany();
            });
        }

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (modal.classList.contains('modal--active')) {
                    closeModal();
                }
                if (detailsModal.classList.contains('modal--active')) {
                    closeDetailsModal();
                }
            }
        });
    }

    /**
     * Setup company selector in navigation
     */
    setupCompanySelector() {
        const selector = document.getElementById('current-company');
        if (!selector) return;

        // Clear existing options
        selector.innerHTML = '<option value="">Select Company</option>';

        // Add companies to selector
        this.companies.forEach(company => {
            if (company.is_active) {
                const option = document.createElement('option');
                option.value = company.id;
                option.textContent = company.company_name;
                selector.appendChild(option);
            }
        });

        // Set selected company
        if (this.selectedCompanyId) {
            selector.value = this.selectedCompanyId;
        }

        // Add change event listener
        selector.addEventListener('change', (e) => {
            const companyId = e.target.value;
            if (companyId) {
                this.selectCompany(parseInt(companyId));
            }
        });
    }

    /**
     * Show company modal
     */
    showCompanyModal(company = null) {
        const modal = document.getElementById('company-modal');
        const title = document.getElementById('modal-title');
        const form = document.getElementById('company-form');

        if (!modal || !title || !form) return;

        // Reset form
        form.reset();
        this.currentEditId = null;

        if (company) {
            // Edit mode
            title.textContent = 'Edit Company';
            this.currentEditId = company.id;
            
            // Populate form fields
            const nameInput = document.getElementById('company-name');
            const taxIdInput = document.getElementById('tax-id');
            const addressInput = document.getElementById('address');
            const phoneInput = document.getElementById('phone');
            const emailInput = document.getElementById('email');
            const websiteInput = document.getElementById('website');
            const currencyInput = document.getElementById('currency-code');
            const fiscalYearInput = document.getElementById('fiscal-year-start');
            const activeInput = document.getElementById('is-active');
            
            if (nameInput) nameInput.value = company.company_name;
            if (taxIdInput) taxIdInput.value = company.tax_id || '';
            if (addressInput) addressInput.value = company.address || '';
            if (phoneInput) phoneInput.value = company.phone || '';
            if (emailInput) emailInput.value = company.email || '';
            if (websiteInput) websiteInput.value = company.website || '';
            if (currencyInput) currencyInput.value = company.currency_code;
            if (fiscalYearInput) fiscalYearInput.value = company.fiscal_year_start;
            if (activeInput) activeInput.value = company.is_active.toString();
        } else {
            // Add mode
            title.textContent = 'Add New Company';
            // Set default fiscal year start
            const fiscalYearInput = document.getElementById('fiscal-year-start');
            if (fiscalYearInput) {
                fiscalYearInput.value = '2024-01-01';
            }
        }

        modal.classList.add('modal--active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Hide company modal
     */
    hideCompanyModal() {
        const modal = document.getElementById('company-modal');
        if (!modal) return;

        modal.classList.remove('modal--active');
        document.body.style.overflow = '';
        this.currentEditId = null;
    }

    /**
     * View company details
     */
    async viewCompany(id) {
        const company = this.companies.find(c => c.id === id);
        if (!company) return;

        const modal = document.getElementById('company-details-modal');
        const content = document.getElementById('company-details-content');

        if (!modal || !content) return;

        content.innerHTML = `
            <div class="company-details">
                <div class="detail-section">
                    <h4>Company Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Company Name:</label>
                            <span>${company.company_name}</span>
                        </div>
                        <div class="detail-item">
                            <label>Tax ID:</label>
                            <span>${company.tax_id || 'Not specified'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Currency:</label>
                            <span>${company.currency_code}</span>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <span class="status-badge ${company.is_active ? 'status-badge--active' : 'status-badge--inactive'}">
                                ${company.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <div class="detail-item">
                            <label>Fiscal Year Start:</label>
                            <span>${company.fiscal_year_start}</span>
                        </div>
                        <div class="detail-item">
                            <label>Created:</label>
                            <span>${this.formatDate(company.created_at)}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h4>Contact Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Address:</label>
                            <span>${company.address || 'Not specified'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Phone:</label>
                            <span>${company.phone || 'Not specified'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <span>${company.email || 'Not specified'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Website:</label>
                            <span>${company.website ? `<a href="${company.website}" target="_blank">${company.website}</a>` : 'Not specified'}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h4>Financial Summary</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Total Accounts:</label>
                            <span>${Math.floor(Math.random() * 50) + 10}</span>
                        </div>
                        <div class="detail-item">
                            <label>Total Transactions:</label>
                            <span>${Math.floor(Math.random() * 1000) + 100}</span>
                        </div>
                        <div class="detail-item">
                            <label>Current Balance:</label>
                            <span>${this.formatCurrency(Math.random() * 100000)}</span>
                        </div>
                        <div class="detail-item">
                            <label>YTD Revenue:</label>
                            <span>${this.formatCurrency(Math.random() * 500000)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        modal.classList.add('modal--active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Hide details modal
     */
    hideDetailsModal() {
        const modal = document.getElementById('company-details-modal');
        if (!modal) return;

        modal.classList.remove('modal--active');
        document.body.style.overflow = '';
    }

    /**
     * Edit company
     */
    editCompany(id) {
        const company = this.companies.find(c => c.id === id);
        if (company) {
            this.showCompanyModal(company);
        }
    }

    /**
     * Delete company
     */
    async deleteCompany(id) {
        const company = this.companies.find(c => c.id === id);
        if (!company) return;

        if (!confirm(`Are you sure you want to delete "${company.company_name}"? This action cannot be undone and will remove all associated accounting data.`)) {
            return;
        }

        try {
            await apiService.deleteCompany(id);
            this.companies = this.companies.filter(c => c.id !== id);
            this.filteredCompanies = this.filteredCompanies.filter(c => c.id !== id);
            this.displayCompanies();
            this.updateStatistics();
            this.setupCompanySelector();
            this.showSuccess('Company deleted successfully');
        } catch (error) {
            console.error('Error deleting company:', error);
            this.showError('Failed to delete company');
        }
    }

    /**
     * Select company as active
     */
    selectCompany(id) {
        const company = this.companies.find(c => c.id === id);
        if (!company) return;

        this.selectedCompanyId = id;
        localStorage.setItem('selectedCompanyId', id);
        localStorage.setItem('selectedCompanyName', company.company_name);
        
        // Update selector
        const selector = document.getElementById('current-company');
        if (selector) {
            selector.value = id;
        }

        this.showSuccess(`Switched to ${company.company_name}`);
        
        // Reload page data to reflect company change
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }

    /**
     * Save company
     */
    async saveCompany() {
        const formData = {
            company_name: document.getElementById('company-name').value,
            tax_id: document.getElementById('tax-id').value,
            address: document.getElementById('address').value,
            phone: document.getElementById('phone').value,
            email: document.getElementById('email').value,
            website: document.getElementById('website').value,
            currency_code: document.getElementById('currency-code').value,
            fiscal_year_start: document.getElementById('fiscal-year-start').value,
            is_active: document.getElementById('is-active').value === 'true'
        };

        try {
            if (this.currentEditId) {
                // Update existing company
                await apiService.updateCompany(this.currentEditId, formData);
                const index = this.companies.findIndex(c => c.id === this.currentEditId);
                this.companies[index] = { ...this.companies[index], ...formData };
                this.showSuccess('Company updated successfully');
            } else {
                // Create new company
                const newCompany = await apiService.createCompany(formData);
                this.companies.push(newCompany);
                this.showSuccess('Company created successfully');
            }

            this.filteredCompanies = [...this.companies];
            this.displayCompanies();
            this.updateStatistics();
            this.setupCompanySelector();
            this.hideCompanyModal();
        } catch (error) {
            console.error('Error saving company:', error);
            this.showError('Failed to save company');
        }
    }

    /**
     * Filter companies
     */
    filterCompanies(searchTerm) {
        const term = searchTerm.toLowerCase();
        
        this.filteredCompanies = this.companies.filter(company => 
            company.company_name.toLowerCase().includes(term) ||
            (company.tax_id && company.tax_id.toLowerCase().includes(term)) ||
            (company.email && company.email.toLowerCase().includes(term)) ||
            (company.phone && company.phone.toLowerCase().includes(term)) ||
            (company.website && company.website.toLowerCase().includes(term))
        );
        
        this.currentPage = 1;
        this.displayCompanies();
    }

    /**
     * Apply all filters
     */
    applyFilters() {
        const statusFilter = document.getElementById('status-filter')?.value;
        const searchTerm = document.getElementById('company-search')?.value || '';

        this.filteredCompanies = this.companies.filter(company => {
            let matches = true;

            if (statusFilter) {
                const isActive = statusFilter === 'active';
                matches = matches && company.is_active === isActive;
            }

            if (searchTerm) {
                const term = searchTerm.toLowerCase();
                matches = matches && (
                    company.company_name.toLowerCase().includes(term) ||
                    (company.tax_id && company.tax_id.toLowerCase().includes(term)) ||
                    (company.email && company.email.toLowerCase().includes(term)) ||
                    (company.phone && company.phone.toLowerCase().includes(term)) ||
                    (company.website && company.website.toLowerCase().includes(term))
                );
            }

            return matches;
        });

        this.currentPage = 1;
        this.displayCompanies();
    }

    /**
     * Select all companies
     */
    selectAllCompanies(checked) {
        const checkboxes = document.querySelectorAll('.company-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
    }

    /**
     * Change page
     */
    changePage(direction) {
        const totalPages = Math.ceil(this.filteredCompanies.length / this.itemsPerPage);
        const newPage = this.currentPage + direction;

        if (newPage >= 1 && newPage <= totalPages) {
            this.currentPage = newPage;
            this.displayCompanies();
        }
    }

    /**
     * Update pagination controls
     */
    updatePagination() {
        const totalPages = Math.ceil(this.filteredCompanies.length / this.itemsPerPage);
        const startIndex = (this.currentPage - 1) * this.itemsPerPage + 1;
        const endIndex = Math.min(startIndex + this.itemsPerPage - 1, this.filteredCompanies.length);

        // Update page info
        const pageInfo = document.getElementById('page-info');
        if (pageInfo) {
            pageInfo.textContent = `Showing ${startIndex}-${endIndex} of ${this.filteredCompanies.length} companies`;
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
     * Export companies
     */
    exportCompanies() {
        // Get selected companies
        const selectedIds = Array.from(document.querySelectorAll('.company-checkbox:checked'))
            .map(checkbox => parseInt(checkbox.dataset.id));

        let companiesToExport = selectedIds.length > 0 
            ? this.companies.filter(c => selectedIds.includes(c.id))
            : this.filteredCompanies;

        // Convert to CSV
        const csv = this.convertToCSV(companiesToExport);
        
        // Download file
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `companies_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        this.showSuccess('Companies exported successfully');
    }

    /**
     * Convert companies to CSV
     */
    convertToCSV(companies) {
        const headers = ['Company Name', 'Tax ID', 'Address', 'Phone', 'Email', 'Website', 'Currency', 'Status', 'Created'];
        const rows = companies.map(c => [
            c.company_name,
            c.tax_id || '',
            c.address || '',
            c.phone || '',
            c.email || '',
            c.website || '',
            c.currency_code,
            c.is_active ? 'Active' : 'Inactive',
            c.created_at
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

// Initialize companies page when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.companiesPage = new CompaniesPage();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CompaniesPage;
}