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
            
            // Clone the response to allow for multiple body reads
            const responseClone = response.clone();
            
            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                // If JSON parsing fails, get text from the clone
                const text = await responseClone.text();
                throw new Error(`HTTP ${response.status}: ${text}`);
            }
            
            if (!response.ok) {
                throw new Error(data.error || `HTTP error! status: ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    /**
     * Get mock data for dashboard when backend is not available
     */
    getMockData(endpoint) {
        console.log('Returning mock data for:', endpoint);
        
        // Mock KPI data
        if (endpoint.includes('/dashboard/kpi')) {
            return {
                success: true,
                data: {
                    totalRevenue: 150000, // $150,000 USD base
                    totalExpenses: 85000,  // $85,000 USD base
                    netProfit: 65000,      // $65,000 USD base
                    cashBalance: 45000      // $45,000 USD base
                }
            };
        }
        
        // Mock revenue trends
        if (endpoint.includes('/dashboard/revenue-trends')) {
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            const data = [12000, 15000, 18000, 22000, 25000, 28000];
            return {
                success: true,
                data: {
                    labels: labels,
                    data: data
                }
            };
        }
        
        // Mock expense breakdown
        if (endpoint.includes('/dashboard/expense-breakdown')) {
            return {
                success: true,
                data: {
                    labels: ['Salaries', 'Rent', 'Marketing', 'Utilities', 'Supplies'],
                    data: [35000, 15000, 12000, 8000, 5000]
                }
            };
        }
        
        // Mock profit/loss
        if (endpoint.includes('/dashboard/profit-loss')) {
            return {
                success: true,
                data: {
                    revenue: [12000, 15000, 18000, 22000, 25000, 28000],
                    expenses: [8000, 9500, 11000, 13500, 15000, 16500],
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']
                }
            };
        }
        
        // Mock accounts
        if (endpoint.includes('/accounts')) {
            return {
                success: true,
                data: [
                    { id: 1, Name: 'Cash', Type: 'asset', Balance: 25000 },
                    { id: 2, Name: 'Accounts Receivable', Type: 'asset', Balance: 15000 },
                    { id: 3, Name: 'Accounts Payable', Type: 'liability', Balance: -8000 },
                    { id: 4, Name: 'Revenue', Type: 'revenue', Balance: 150000 },
                    { id: 5, Name: 'Office Expenses', Type: 'expense', Balance: -35000 }
                ]
            };
        }
        
        // Mock transactions
        if (endpoint.includes('/transactions')) {
            return {
                success: true,
                data: [
                    { id: 1, Date: '2024-01-15', Description: 'Client Payment', Category: 'Revenue', Account: 'Cash', Amount: 5000, Status: 'completed' },
                    { id: 2, Date: '2024-01-14', Description: 'Office Rent', Category: 'Rent', Account: 'Cash', Amount: -2000, Status: 'completed' },
                    { id: 3, Date: '2024-01-13', Description: 'Software License', Category: 'Supplies', Account: 'Cash', Amount: -500, Status: 'completed' },
                    { id: 4, Date: '2024-01-12', Description: 'Consulting Income', Category: 'Revenue', Account: 'Cash', Amount: 3000, Status: 'completed' },
                    { id: 5, Date: '2024-01-11', Description: 'Utilities', Category: 'Utilities', Account: 'Cash', Amount: -800, Status: 'completed' }
                ]
            };
        }
        
        // Default mock response
        return {
            success: true,
            data: []
        };
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
        
        // Handle both string period and object with period property
        let days;
        if (typeof period === 'object' && period.period) {
            days = period.period;
        } else {
            // Convert period to days for backend
            const periodMap = {
                'today': 1,
                'week': 7,
                'month': 30,
                'quarter': 90,
                'year': 365
            };
            
            days = !isNaN(period) ? parseInt(period) : (periodMap[period] || 30);
        }
        
        const params = { period: days };
        
        if (companyId) {
            params.company_id = companyId;
        }
        
        console.log('Making API call to /dashboard/revenue-trends with params:', params);
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
        return this.get('/dashboard/profit-loss', params);
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
        const companyId = window.app?.getCurrentCompanyId() || document.getElementById('company-id')?.value;
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/accounts', params);
    }

    async getAccount(id) {
        return this.get(`/accounts/${id}`);
    }

    async createAccount(accountData) {
        // Add company context if available
        const companyId = window.app?.getCurrentCompanyId() || document.getElementById('company-id')?.value;
        if (companyId) {
            // Send company_id as query parameter
            return this.post(`/accounts?company_id=${companyId}`, accountData);
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
        const companyId = window.app?.getCurrentCompanyId();
        if (companyId) {
            params.company_id = companyId;
        }
        return this.get('/transactions', params);
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

    async createExternalInvestment(investmentData) {
        return this.post('/transactions/external-investment', investmentData);
    }

    async createLiability(liabilityData) {
        return this.post('/transactions/liability', liabilityData);
    }

    /**
     * Create Asset (POST)
     */
    async createAsset(data = {}) {
        // POST to /transactions/create-asset
        return this.post('/transactions/create-asset', data);
    }

    async createMicroTransaction(transactionData) {
        return this.post('/transactions/micro-transaction', transactionData);
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