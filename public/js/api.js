/**
 * API Service Module
 * Handles all HTTP requests to the backend API
 */

class ApiService {
    constructor() {
        this.baseURL = '/api';
        this.defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
    }

    /**
     * Generic request method
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: { ...this.defaultHeaders, ...options.headers },
            ...options
        };

        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        
        return this.request(url, {
            method: 'GET'
        });
    }

    /**
     * POST request
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * PUT request
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    /**
     * DELETE request
     */
    async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    }

    // Authentication endpoints
    async login(credentials) {
        return this.post('/auth/login', credentials);
    }

    async logout() {
        return this.post('/auth/logout');
    }

    async refreshToken() {
        return this.post('/auth/refresh');
    }

    // Dashboard endpoints
    async getDashboardData() {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        const params = {};
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/dashboard', params);
    }

    async getKPIData() {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        const params = {};
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/dashboard/kpi', params);
    }

    async getRevenueTrends(period = 'month') {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        
        // Convert period to days for backend
        const periodMap = {
            'today': 1,
            'week': 7,
            'month': 30,
            'quarter': 90,
            'year': 365
        };
        
        const days = !isNaN(period) ? parseInt(period) : (periodMap[period] || 30);
        const params = { period: days };
        
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/dashboard/revenue-trends', params);
    }

    async getExpenseBreakdown(period = 'month') {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        
        // Convert period to days for backend
        const periodMap = {
            'today': 1,
            'week': 7,
            'month': 30,
            'quarter': 90,
            'year': 365
        };
        
        const days = !isNaN(period) ? parseInt(period) : (periodMap[period] || 30);
        const params = { period: days };
        
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/dashboard/expense-breakdown', params);
    }

    async getProfitLoss(period = 'month') {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        
        // Convert period to days for backend
        const periodMap = {
            'today': 1,
            'week': 7,
            'month': 30,
            'quarter': 90,
            'year': 365
        };
        
        const days = !isNaN(period) ? parseInt(period) : (periodMap[period] || 30);
        const params = { period: days };
        
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/profit-loss', params);
    }

    // Companies endpoints
    async getCompanies(params = {}) {
        return this.get('/companies', params);
    }

    async getCompany(id) {
        return this.get(`/companies/${id}`);
    }

    async createCompany(companyData) {
        return this.post('/companies', companyData);
    }

    async updateCompany(id, companyData) {
        return this.put(`/companies/${id}`, companyData);
    }

    async deleteCompany(id) {
        return this.delete(`/companies/${id}`);
    }

    // Accounts endpoints
    async getAccounts(params = {}) {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/accounts/simple', params);
    }

    async getAccount(id) {
        return this.get(`/accounts/${id}`);
    }

    async createAccount(accountData) {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        if (companyId) {
            accountData.company_id = companyId;
        }
        return this.post('/accounts', accountData);
    }

    async updateAccount(id, accountData) {
        return this.put(`/accounts/${id}`, accountData);
    }

    async deleteAccount(id) {
        return this.delete(`/accounts/${id}`);
    }

    // Transactions endpoints
    async getTransactions(params = {}) {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/transactions/simple', params);
    }

    async getTransaction(id) {
        return this.get(`/transactions/${id}`);
    }

    async createTransaction(transactionData) {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        if (companyId) {
            transactionData.company_id = companyId;
        }
        return this.post('/transactions', transactionData);
    }

    async updateTransaction(id, transactionData) {
        return this.put(`/transactions/${id}`, transactionData);
    }

    async deleteTransaction(id) {
        return this.delete(`/transactions/${id}`);
    }

    async getRecentTransactions(limit = 10) {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        const params = { limit };
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/transactions/simple', params);
    }

    // Reports endpoints
    async getProfitLoss(params = {}) {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/profit-loss', params);
    }

    async getBalanceSheet(params = {}) {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/reports/balance-sheet', params);
    }

    async getCashFlow(params = {}) {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/reports/cash-flow', params);
    }

    async getTrialBalance() {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        const params = {};
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/reports/trial-balance', params);
    }

    // Analytics endpoints
    async getAnalytics(params = {}) {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/analytics', params);
    }

    async getCategoryAnalytics() {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        const params = {};
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/analytics/categories', params);
    }

    async getAccountAnalytics() {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId();
        const params = {};
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/analytics/accounts', params);
    }
}

// Create singleton instance
const apiService = new ApiService();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = apiService;
} else {
    window.apiService = apiService;
}