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
        return this.get('/dashboard');
    }

    async getKPIData() {
        return this.get('/dashboard/kpi');
    }

    async getRevenueTrends(period = 30) {
        return this.get('/dashboard/revenue-trends', { period });
    }

    async getExpenseBreakdown() {
        return this.get('/dashboard/expense-breakdown');
    }

    // Accounts endpoints
    async getAccounts(params = {}) {
        return this.get('/accounts', params);
    }

    async getAccount(id) {
        return this.get(`/accounts/${id}`);
    }

    async createAccount(accountData) {
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
        return this.get('/transactions', params);
    }

    async getTransaction(id) {
        return this.get(`/transactions/${id}`);
    }

    async createTransaction(transactionData) {
        return this.post('/transactions', transactionData);
    }

    async updateTransaction(id, transactionData) {
        return this.put(`/transactions/${id}`, transactionData);
    }

    async deleteTransaction(id) {
        return this.delete(`/transactions/${id}`);
    }

    async getRecentTransactions(limit = 10) {
        return this.get('/transactions/recent', { limit });
    }

    // Reports endpoints
    async getProfitLoss(params = {}) {
        return this.get('/reports/profit-loss', params);
    }

    async getBalanceSheet(params = {}) {
        return this.get('/reports/balance-sheet', params);
    }

    async getCashFlow(params = {}) {
        return this.get('/reports/cash-flow', params);
    }

    async getTrialBalance() {
        return this.get('/reports/trial-balance');
    }

    // Analytics endpoints
    async getAnalytics(params = {}) {
        return this.get('/analytics', params);
    }

    async getCategoryAnalytics() {
        return this.get('/analytics/categories');
    }

    async getAccountAnalytics() {
        return this.get('/analytics/accounts');
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