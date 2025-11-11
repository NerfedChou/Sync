// Dashboard functionality
class Dashboard {
    constructor() {
        this.expenseChart = null;
        this.profitLossChart = null;
        this.trendChart = null;
        this.init();
    }

    async init() {
        try {
            // Wait for company context to be available
            let attempts = 0;
            while (!window.app?.getCurrentCompanyId() && attempts < 20) {
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }
            
            if (!window.app?.getCurrentCompanyId()) {
                console.warn('No company selected, proceeding with default data');
            }
            
            await this.loadKPIData();
            await this.loadAccountSummary();
            await this.loadRecentTransactions();
            this.setupEventListeners();
            this.setupCurrencyChangeListener();
            
            // Small delay to ensure DOM is fully ready
            setTimeout(() => {
                this.initExpenseChart();
                this.initProfitLossChart();
                this.initTrendChart();
            }, 100);
        } catch (error) {
            console.error('Failed to initialize dashboard:', error);
            this.showToast('Failed to load dashboard data', 'error');
        }
    }

    async loadKPIData() {
        try {
            const response = await apiService.getKPIData();
            const data = response.success ? response.data : response;
            
            document.getElementById('total-revenue').textContent = this.formatCurrency(data.totalRevenue);
            document.getElementById('total-expenses').textContent = this.formatCurrency(data.totalExpenses);
            document.getElementById('net-profit').textContent = this.formatCurrency(data.netProfit);
            document.getElementById('cash-balance').textContent = this.formatCurrency(data.cashBalance);
            
            // Calculate and display additional metrics
            const profitMargin = data.totalRevenue > 0 ? (data.netProfit / data.totalRevenue * 100) : 0;
            const expenseRatio = data.totalRevenue > 0 ? (data.totalExpenses / data.totalRevenue * 100) : 0;
            
            // Update additional KPI elements if they exist
            const profitMarginElement = document.getElementById('profit-margin');
            const expenseRatioElement = document.getElementById('expense-ratio');
            
            if (profitMarginElement) {
                profitMarginElement.textContent = profitMargin.toFixed(1) + '%';
            }
            if (expenseRatioElement) {
                expenseRatioElement.textContent = expenseRatio.toFixed(1) + '%';
            }
            
        } catch (error) {
            console.error('Failed to load KPI data:', error);
            this.showError('Failed to load KPI data');
        }
    }

    async loadAccountSummary() {
        try {
            const response = await apiService.getAccounts();
            const accounts = response.success ? response.data : response;
            
            // Calculate account summaries by type
            const summary = this.calculateAccountSummary(accounts);
            this.displayAccountSummary(summary);
            
        } catch (error) {
            console.error('Failed to load account summary:', error);
            this.showError('Failed to load account summary');
        }
    }

    calculateAccountSummary(accounts) {
        const summary = {
            totalAssets: 0,
            totalLiabilities: 0,
            totalEquity: 0,
            assetCount: 0,
            liabilityCount: 0,
            equityCount: 0,
            revenueCount: 0,
            expenseCount: 0
        };

        accounts.forEach(account => {
            const type = account.Type.toUpperCase();
            const balance = account.Balance;

            switch (type) {
                case 'ASSET':
                    summary.totalAssets += balance;
                    summary.assetCount++;
                    break;
                case 'LIABILITY':
                    summary.totalLiabilities += balance;
                    summary.liabilityCount++;
                    break;
                case 'EQUITY':
                    summary.totalEquity += balance;
                    summary.equityCount++;
                    break;
                case 'REVENUE':
                    summary.revenueCount++;
                    break;
                case 'EXPENSE':
                    summary.expenseCount++;
                    break;
            }
        });

        return summary;
    }

    displayAccountSummary(summary) {
        // Update account summary elements if they exist
        const totalAssetsElement = document.getElementById('summary-total-assets');
        const totalLiabilitiesElement = document.getElementById('summary-total-liabilities');
        const totalEquityElement = document.getElementById('summary-total-equity');
        const totalAccountsElement = document.getElementById('summary-total-accounts');

        if (totalAssetsElement) {
            totalAssetsElement.textContent = this.formatCurrency(summary.totalAssets);
        }
        if (totalLiabilitiesElement) {
            totalLiabilitiesElement.textContent = this.formatCurrency(summary.totalLiabilities);
        }
        if (totalEquityElement) {
            totalEquityElement.textContent = this.formatCurrency(summary.totalEquity);
        }
        if (totalAccountsElement) {
            const totalAccounts = summary.assetCount + summary.liabilityCount + summary.equityCount + summary.revenueCount + summary.expenseCount;
            totalAccountsElement.textContent = totalAccounts;
        }
    }

    async loadRecentTransactions() {
        try {
            const response = await apiService.getRecentTransactions(5);
            const transactions = response.success ? response.data : response;
            
            const tbody = document.getElementById('recent-transactions');
            tbody.innerHTML = '';
            
            if (Array.isArray(transactions)) {
                transactions.forEach(transaction => {
                    const row = this.createTransactionRow(transaction);
                    tbody.appendChild(row);
                });
            } else {
                console.error('Expected array but got:', typeof transactions, transactions);
            }
        } catch (error) {
            console.error('Failed to load recent transactions:', error);
            this.showError('Failed to load recent transactions');
        }
    }

    createTransactionRow(transaction) {
        const row = document.createElement('tr');
        const isCredit = transaction.Type === 'credit';
        const amountClass = isCredit ? 'positive' : 'negative';
        const amountSign = isCredit ? '+' : '';
        
        // Create category badge with color
        const categoryColor = this.getCategoryColor(transaction.Category);
        
        row.innerHTML = `
            <td>${this.formatDate(transaction.Date)}</td>
            <td>
                <div class="transaction-description">
                    <strong>${transaction.Description}</strong>
                    ${transaction.Name && transaction.Name !== transaction.Description ? `<br><small>${transaction.Name}</small>` : ''}
                </div>
            </td>
            <td>
                <span class="category-badge" style="background-color: ${categoryColor}; color: white;">
                    ${transaction.Category}
                </span>
            </td>
            <td class="${amountClass} font-medium">
                ${amountSign}${this.formatCurrency(Math.abs(transaction.Amount))}
            </td>
            <td>
                <span class="status-badge status-${transaction.Status}">
                    ${transaction.Status}
                </span>
            </td>
        `;
        
        return row;
    }

    getCategoryColor(category) {
        const colors = {
            'Service Revenue': '#10b981',
            'Operating Expenses': '#ef4444',
            'Software Expenses': '#f59e0b',
            'Utilities': '#3b82f6',
            'Capital Expenses': '#8b5cf6',
            'Office Expenses': '#64748b',
            'Payroll': '#dc2626',
            'default': '#6b7280'
        };
        
        return colors[category] || colors['default'];
    }

    async initExpenseChart() {
        try {
            const period = document.getElementById('revenue-period').value;
            const response = await apiService.getExpenseBreakdown(period);
            const data = response.success ? response.data : response;
            
            console.log('Expense breakdown raw response:', response);
            console.log('Expense breakdown processed data:', data);
            console.log('Expense labels:', data.labels);
            console.log('Expense amounts:', data.data);
            
            const canvas = document.getElementById('expense-chart');
            if (!canvas) {
                console.error('Expense chart canvas not found');
                return;
            }
            
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('Could not get 2d context for expense chart');
                return;
            }
            
            this.expenseChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        data: data.data || [],
                        backgroundColor: [
                            '#3b82f6',
                            '#10b981',
                            '#f59e0b',
                            '#ef4444',
                            '#8b5cf6',
                            '#64748b'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            console.log('Area chart created successfully with fill:', true);
             
        } catch (error) {
            console.error('Failed to initialize expense chart:', error);
            this.showError('Failed to load expense chart');
        }
    }





    async initProfitLossChart() {
        const canvas = document.getElementById('profit-loss-chart');
        if (!canvas) {
            console.error('Profit & Loss chart canvas not found');
            return;
        }
        
        // Wait for Chart.js to be available
        let attempts = 0;
        while (typeof Chart === 'undefined' && attempts < 10) {
            await new Promise(resolve => setTimeout(resolve, 100));
            attempts++;
        }
        
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded after waiting');
            this.showToast('Charts could not be loaded', 'error');
            return;
        }
        
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            console.error('Could not get 2d context for profit & loss chart');
            return;
        }
        
        try {
            const period = this.convertPeriodToDays(document.getElementById('revenue-period').value);
            const response = await apiService.getProfitLoss({ period: period });
            const data = response.success ? response.data : response;
            
            this.profitLossChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels || [],
                    datasets: [
                        {
                            label: 'Revenue',
                            data: data.revenue || [],
                            backgroundColor: 'rgba(34, 197, 94, 0.8)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Expenses',
                            data: data.expenses || [],
                            backgroundColor: 'rgba(239, 68, 68, 0.8)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Profit',
                            data: data.profit || [],
                            type: 'line',
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 2,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Profit & Loss Trend'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
            
            console.log('Area chart created successfully with fill:', true);

        } catch (error) {
            console.error('Failed to initialize trend chart:', error);
            this.showError('Failed to load trend chart');
        }
    }

    async initTrendChart() {
        // Wait for Chart.js to be available
        let attempts = 0;
        while (typeof Chart === 'undefined' && attempts < 10) {
            await new Promise(resolve => setTimeout(resolve, 100));
            attempts++;
        }
        
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded after waiting');
            this.showToast('Charts could not be loaded', 'error');
            return;
        }
        
        const canvas = document.getElementById('trend-chart');
        if (!canvas) {
            console.error('Trend chart canvas not found');
            return;
        }
        
        // Ensure canvas has proper dimensions
        const container = canvas.parentElement;
        if (container) {
            canvas.style.width = '100%';
            canvas.style.height = '400px';
        }
        
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            console.error('Could not get 2d context for trend chart');
            return;
        }
        
        try {
            const period = this.convertPeriodToDays(document.getElementById('revenue-period').value);
            console.log('Fetching revenue trends with period:', period);
            
            const response = await apiService.getRevenueTrends({ period: period });
            console.log('Raw API response:', response);
            
            const data = response.success ? response.data : response;
            console.log('Processed data:', data);
            
            console.log('Creating area chart with:', {
                labels: data.labels,
                dataPoints: data.data,
                labelsLength: data.labels?.length,
                dataLength: data.data?.length,
                fillEnabled: true,
                chartType: 'line with fill'
            });
            
            // Validate data before creating chart
            if (!data.labels || !data.data || data.labels.length === 0 || data.data.length === 0) {
                console.warn('No data available for trend chart, showing empty chart');
                // Create empty chart with placeholder data
                data.labels = ['No Data'];
                data.data = [0];
            }
            
            // Enhanced area chart for better visual impact
            this.trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: 'Revenue',
                        data: data.data || [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#3b82f6',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: ' + window.currencyUtils.formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return window.currencyUtils.formatCurrency(value);
                                },
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
            
            console.log('Area chart created successfully with fill:', true);

        } catch (error) {
            console.error('Failed to initialize trend chart:', error);
            this.showError('Failed to load trend chart');
        }
    }

    setupEventListeners() {
        // Revenue period selector
        const periodSelect = document.getElementById('revenue-period');
        if (periodSelect) {
            periodSelect.addEventListener('change', () => {
                this.updatePeriodDisplay();
                this.updateExpenseChart();
                this.updateProfitLossChart();
                this.updateTrendChart();
            });
        }

        // Refresh button
        const refreshBtn = document.getElementById('refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshData();
            });
        }
        
        // Initialize period display
        this.updatePeriodDisplay();
    }

    updatePeriodDisplay() {
        const periodSelect = document.getElementById('revenue-period');
        const profitLossPeriodDisplay = document.getElementById('revenue-period-display');
        const trendPeriodDisplay = document.getElementById('trend-period-display');
        
        if (periodSelect && profitLossPeriodDisplay) {
            const selectedOption = periodSelect.options[periodSelect.selectedIndex];
            const periodText = selectedOption.text;
            profitLossPeriodDisplay.textContent = periodText;
            if (trendPeriodDisplay) {
                trendPeriodDisplay.textContent = periodText;
            }
        }
    }

    async updateRevenueChart() {
        try {
            const period = document.getElementById('revenue-period').value;
            const response = await apiService.getRevenueTrends(period);
            const data = response.success ? response.data : response;
            
            this.revenueChart.data.labels = data.labels || [];
            this.revenueChart.data.datasets[0].data = data.data || [];
            this.revenueChart.update();
        } catch (error) {
            console.error('Failed to update revenue chart:', error);
            this.showError('Failed to update revenue chart');
        }
    }

    /**
     * Convert period selector value to days for API
     */
    convertPeriodToDays(period) {
        const periodMap = {
            'today': 1,
            'week': 7,
            'month': 30,
            'quarter': 90,
            'year': 365
        };
        
        // If it's already a number, return it
        if (!isNaN(period)) {
            return parseInt(period);
        }
        
        return periodMap[period] || 30; // Default to 30 days
    }

    async refreshData() {
        this.showToast('Refreshing data...', 'info');
        
        try {
            await this.loadKPIData();
            await this.loadAccountSummary();
            await this.loadRecentTransactions();
            await this.updateExpenseChart();
            await this.updateProfitLossChart();
            await this.updateTrendChart();
            this.showToast('Data refreshed successfully', 'success');
        } catch (error) {
            console.error('Failed to refresh data:', error);
            this.showToast('Failed to refresh data', 'error');
        }
    }

    async updateExpenseChart() {
        try {
            const period = document.getElementById('revenue-period').value;
            const response = await apiService.getExpenseBreakdown(period);
            const data = response.success ? response.data : response;
            
            this.expenseChart.data.labels = data.labels || [];
            this.expenseChart.data.datasets[0].data = data.data || [];
            this.expenseChart.update();
        } catch (error) {
            console.error('Failed to update expense chart:', error);
            this.showError('Failed to update expense chart');
        }
    }

    async updateProfitLossChart() {
        try {
            const period = this.convertPeriodToDays(document.getElementById('revenue-period').value);
            const response = await apiService.getProfitLoss({ period: period });
            const data = response.success ? response.data : response;
            
            this.profitLossChart.data.labels = data.labels || [];
            this.profitLossChart.data.datasets[0].data = data.revenue || [];
            this.profitLossChart.data.datasets[1].data = data.expenses || [];
            this.profitLossChart.data.datasets[2].data = data.profit || [];
            this.profitLossChart.update();
        } catch (error) {
            console.error('Failed to update profit & loss chart:', error);
            this.showError('Failed to update profit & loss chart');
        }
    }

    async updateTrendChart() {
        try {
            const period = this.convertPeriodToDays(document.getElementById('revenue-period').value);
            const response = await apiService.getRevenueTrends({ period: period });
            const data = response.success ? response.data : response;
            
            if (this.trendChart) {
                this.trendChart.data.labels = data.labels || [];
                this.trendChart.data.datasets[0].data = data.data || [];
                this.trendChart.update();
            }
        } catch (error) {
            console.error('Failed to update trend chart:', error);
            this.showError('Failed to update trend chart');
        }
    }

    /**
     * Setup currency change listener
     */
    setupCurrencyChangeListener() {
        window.addEventListener('currencyChanged', (e) => {
            console.log('Dashboard: Currency changed to', e.detail.currency);
            this.refreshAllData();
        });
    }

    /**
     * Refresh all dashboard data with new currency
     */
    async refreshAllData() {
        console.log('Dashboard: Refreshing all data');
        
        try {
            // Refresh KPI data
            await this.loadKPIData();
            
            // Refresh account summary
            await this.loadAccountSummary();
            
            // Refresh recent transactions
            await this.loadRecentTransactions();
            
            // Refresh charts
            this.refreshCharts();
            
            console.log('Dashboard: All data refreshed');
        } catch (error) {
            console.error('Dashboard: Failed to refresh data:', error);
        }
    }

    /**
     * Refresh all charts with new currency
     */
    refreshCharts() {
        // Update trend chart
        if (this.trendChart) {
            this.updateTrendChart();
        }
        
        // Update expense chart
        if (this.expenseChart) {
            this.updateExpenseChart();
        }
        
        // Update profit/loss chart
        if (this.profitLossChart) {
            this.updateProfitLossChart();
        }
    }

    formatCurrency(amount) {
        return window.currencyUtils.formatCurrency(amount);
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    showError(message) {
        if (window.app) {
            window.app.showError(message);
        } else {
            console.error(message);
        }
    }

    showSuccess(message) {
        if (window.app) {
            window.app.showSuccess(message);
        } else {
            console.log(message);
        }
    }
}



// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new Dashboard();
});