// Dashboard functionality
class Dashboard {
    constructor() {
        this.revenueChart = null;
        this.expenseChart = null;
        this.init();
    }

    async init() {
        try {
            await this.loadKPIData();
            await this.loadRecentTransactions();
            this.setupEventListeners();
            
            // Small delay to ensure DOM is fully ready
            setTimeout(() => {
                this.initCharts();
            }, 100);
        } catch (error) {
            console.error('Failed to initialize dashboard:', error);
            this.showToast('Failed to load dashboard data', 'error');
        }
    }

    async loadKPIData() {
        try {
            const response = await fetch('/api/dashboard/kpi');
            const data = await response.json();
            
            document.getElementById('total-revenue').textContent = this.formatCurrency(data.totalRevenue);
            document.getElementById('total-expenses').textContent = this.formatCurrency(data.totalExpenses);
            document.getElementById('net-profit').textContent = this.formatCurrency(data.netProfit);
            document.getElementById('cash-balance').textContent = this.formatCurrency(data.cashBalance);
        } catch (error) {
            console.error('Failed to load KPI data:', error);
        }
    }

    async loadRecentTransactions() {
        try {
            const response = await fetch('/api/transactions/recent');
            const transactions = await response.json();
            
            const tbody = document.getElementById('recent-transactions');
            tbody.innerHTML = '';
            
            transactions.slice(0, 5).forEach(transaction => {
                const row = this.createTransactionRow(transaction);
                tbody.appendChild(row);
            });
        } catch (error) {
            console.error('Failed to load recent transactions:', error);
        }
    }

    createTransactionRow(transaction) {
        const row = document.createElement('tr');
        const amountClass = transaction.type === 'credit' ? 'positive' : 'negative';
        const amountSign = transaction.type === 'credit' ? '+' : '-';
        
        row.innerHTML = `
            <td>${this.formatDate(transaction.date)}</td>
            <td>${transaction.description}</td>
            <td>${transaction.category}</td>
            <td class="${amountClass}">${amountSign}${this.formatCurrency(Math.abs(transaction.amount))}</td>
            <td><span class="status-badge status-${transaction.status}">${transaction.status}</span></td>
        `;
        
        return row;
    }

    async initCharts() {
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
        
        console.log('Chart.js is available, version:', Chart.version);
        console.log('Initializing charts...');
        
        // Check if canvas elements exist
        const revenueCanvas = document.getElementById('revenue-chart');
        const expenseCanvas = document.getElementById('expense-chart');
        console.log('Revenue canvas found:', !!revenueCanvas);
        console.log('Expense canvas found:', !!expenseCanvas);
        
        await this.initRevenueChart();
        await this.initExpenseChart();
    }

    async initRevenueChart() {
        try {
            const response = await fetch('/api/dashboard/revenue-trends?period=30');
            const data = await response.json();
            
            const canvas = document.getElementById('revenue-chart');
            if (!canvas) {
                console.error('Revenue chart canvas not found');
                return;
            }
            
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('Could not get 2d context for revenue chart');
                return;
            }
            
            this.revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data.data,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Failed to initialize revenue chart:', error);
            this.showToast('Failed to load revenue chart', 'error');
        }
    }

    async initExpenseChart() {
        try {
            const response = await fetch('/api/dashboard/expense-breakdown');
            const data = await response.json();
            
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
                    labels: data.labels,
                    datasets: [{
                        data: data.data,
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
        } catch (error) {
            console.error('Failed to initialize expense chart:', error);
            this.showToast('Failed to load expense chart', 'error');
        }
    }

    setupEventListeners() {
        // Revenue period selector
        const periodSelect = document.getElementById('revenue-period');
        if (periodSelect) {
            periodSelect.addEventListener('change', () => {
                this.updateRevenueChart();
            });
        }

        // Refresh button
        const refreshBtn = document.getElementById('refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshData();
            });
        }
    }

    async updateRevenueChart() {
        try {
            const period = document.getElementById('revenue-period').value;
            const response = await fetch(`/api/dashboard/revenue-trends?period=${period}`);
            const data = await response.json();
            
            this.revenueChart.data.labels = data.labels;
            this.revenueChart.data.datasets[0].data = data.data;
            this.revenueChart.update();
        } catch (error) {
            console.error('Failed to update revenue chart:', error);
        }
    }

    async refreshData() {
        this.showToast('Refreshing data...', 'info');
        
        try {
            await this.loadKPIData();
            await this.loadRecentTransactions();
            await this.updateRevenueChart();
            this.showToast('Data refreshed successfully', 'success');
        } catch (error) {
            console.error('Failed to refresh data:', error);
            this.showToast('Failed to refresh data', 'error');
        }
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container');
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}



// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new Dashboard();
});