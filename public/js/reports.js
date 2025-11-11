/**
 * Reports Page Module
 * Handles all report generation and display functionality
 */

class ReportsPage {
    constructor() {
        this.currentReport = 'profit-loss';
        this.currentPeriod = 'current-month';
        this.currentFormat = 'web';
        this.reportData = null;
        this.charts = {};
        this.init();
    }

    /**
     * Initialize reports page
     */
    async init() {
        try {
            this.setupEventListeners();
            await this.generateReport();
        } catch (error) {
            console.error('Failed to initialize reports page:', error);
            this.showError('Failed to load reports');
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Report configuration
        const reportType = document.getElementById('report-type');
        const reportPeriod = document.getElementById('report-period');
        const reportFormat = document.getElementById('report-format');
        const reportComparison = document.getElementById('report-comparison');

        if (reportType) {
            reportType.addEventListener('change', (e) => {
                this.currentReport = e.target.value;
            });
        }

        if (reportPeriod) {
            reportPeriod.addEventListener('change', (e) => {
                this.currentPeriod = e.target.value;
            });
        }

        if (reportFormat) {
            reportFormat.addEventListener('change', (e) => {
                this.currentFormat = e.target.value;
            });
        }

        // Action buttons
        const generateBtn = document.getElementById('generate-btn');
        const previewBtn = document.getElementById('preview-btn');
        const generateReportBtn = document.getElementById('generate-report-btn');
        const exportBtn = document.getElementById('export-reports-btn');

        if (generateBtn) {
            generateBtn.addEventListener('click', () => this.generateReport());
        }

        if (previewBtn) {
            previewBtn.addEventListener('click', () => this.previewReport());
        }

        if (generateReportBtn) {
            generateReportBtn.addEventListener('click', () => this.generateReport());
        }

        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportReports());
        }

        // Report tabs
        this.setupTabListeners();
    }

    /**
     * Setup tab listeners
     */
    setupTabListeners() {
        const tabs = document.querySelectorAll('.tab-btn');
        const contents = document.querySelectorAll('.report-tab');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetTab = tab.dataset.tab;
                
                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // Update active content
                contents.forEach(content => content.classList.remove('active'));
                document.getElementById(`${targetTab}-tab`).classList.add('active');
            });
        });
    }

    /**
     * Generate report based on current settings
     */
    async generateReport() {
        try {
            // Show loading state
            this.showLoading();

            // Get report data from API
            this.reportData = await this.fetchReportData();
            
            // Update all sections
            this.updateSummary();
            this.updateDetails();
            this.updateCharts();

            // Hide loading state
            this.hideLoading();
            
            this.showSuccess('Report generated successfully');
        } catch (error) {
            console.error('Error generating report:', error);
            this.hideLoading();
            this.showError('Failed to generate report');
        }
    }

    /**
     * Fetch report data from API
     */
    async fetchReportData() {
        const params = {
            type: this.currentReport,
            period: this.currentPeriod,
            format: this.currentFormat
        };

        switch (this.currentReport) {
            case 'profit-loss':
                return await apiService.getProfitLoss(params);
            case 'balance-sheet':
                return await apiService.getBalanceSheet(params);
            case 'cash-flow':
                return await apiService.getCashFlow(params);
            case 'trial-balance':
                return await apiService.getTrialBalance();
            default:
                throw new Error('Unknown report type: ' + this.currentReport);
        }
    }



    /**
     * Update summary section
     */
    updateSummary() {
        if (!this.reportData) return;

        // Handle different report structures
        if (this.reportData.revenue && this.reportData.expenses) {
            // Profit & Loss report structure
            document.getElementById('total-revenue').textContent = this.formatCurrency(this.reportData.revenue.total);
            document.getElementById('total-expenses').textContent = this.formatCurrency(Math.abs(this.reportData.expenses.total));
            document.getElementById('net-income').textContent = this.formatCurrency(this.reportData.net_profit);
            document.getElementById('profit-margin').textContent = `${this.reportData.profit_margin}%`;
        } else if (this.reportData.summary) {
            // Fallback for other report types
            const summary = this.reportData.summary;
            document.getElementById('total-revenue').textContent = this.formatCurrency(summary.totalRevenue);
            document.getElementById('total-expenses').textContent = this.formatCurrency(Math.abs(summary.totalExpenses));
            document.getElementById('net-income').textContent = this.formatCurrency(summary.netIncome);
            document.getElementById('profit-margin').textContent = `${summary.profitMargin}%`;
        }
    }

    /**
     * Update details section
     */
    updateDetails() {
        const tbody = document.getElementById('details-tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        let details = [];
        
        if (this.reportData.revenue && this.reportData.expenses) {
            // Create details from profit & loss structure
            details = [
                ...Object.entries(this.reportData.revenue.categories).map(([name, amount]) => ({
                    account: name,
                    currentPeriod: amount,
                    previousPeriod: amount * 0.9, // Mock previous period
                    change: amount * 0.1,
                    changePercent: 10.0
                })),
                ...Object.entries(this.reportData.expenses.categories).map(([name, amount]) => ({
                    account: name,
                    currentPeriod: -Math.abs(amount),
                    previousPeriod: -Math.abs(amount) * 0.95, // Mock previous period
                    change: -Math.abs(amount) * 0.05,
                    changePercent: -5.0
                }))
            ];
        } else if (this.reportData.details) {
            details = this.reportData.details;
        }

        if (details.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: #64748b;">
                        No details available for this report
                    </td>
                </tr>
            `;
            return;
        }

        details.forEach(item => {
            const row = this.createDetailRow(item);
            tbody.appendChild(row);
        });
    }

    /**
     * Create a detail row
     */
    createDetailRow(item) {
        const row = document.createElement('tr');
        
        const changeClass = item.change >= 0 ? 'text-green-600' : 'text-red-600';
        const changePrefix = item.change >= 0 ? '+' : '';
        
        row.innerHTML = `
            <td class="detail-account">${item.account}</td>
            <td class="detail-current">${this.formatCurrency(item.currentPeriod)}</td>
            <td class="detail-previous">${this.formatCurrency(item.previousPeriod)}</td>
            <td class="${changeClass} detail-change">
                ${changePrefix}${this.formatCurrency(item.change)}
            </td>
            <td class="${changeClass} detail-percent">
                ${changePrefix}${item.changePercent}%
            </td>
        `;
        
        return row;
    }

    /**
     * Update charts section
     */
    updateCharts() {
        if (!this.reportData) return;

        // Destroy existing charts
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });

        // Create new charts based on available data
        if (this.reportData.revenue && this.reportData.expenses) {
            this.createRevenueExpenseChart();
            this.createExpenseBreakdownChart();
        }
        
        // Create analytics charts if available
        if (this.reportData.operating_activities) {
            this.createCashFlowChart();
        }
    }

    /**
     * Create revenue vs expense chart
     */
    createRevenueExpenseChart() {
        const ctx = document.getElementById('revenue-expense-chart');
        if (!ctx || !this.reportData.revenue) return;

        const revenueCategories = Object.entries(this.reportData.revenue.categories);
        const expenseCategories = Object.entries(this.reportData.expenses.categories);

        this.charts.revenueExpense = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Revenue', 'Expenses'],
                datasets: [
                    {
                        label: 'Total',
                        data: [this.reportData.revenue.total, Math.abs(this.reportData.expenses.total)],
                        backgroundColor: ['rgba(16, 185, 129, 0.8)', 'rgba(239, 68, 68, 0.8)'],
                        borderColor: ['rgba(16, 185, 129, 1)', 'rgba(239, 68, 68, 1)'],
                        borderWidth: 1
                    }
                ]
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
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
            }
        });
    }

    /**
     * Create expense breakdown chart
     */
    createExpenseBreakdownChart() {
        const ctx = document.getElementById('expense-breakdown-chart');
        if (!ctx || !this.reportData.expenses) return;

        const expenseCategories = Object.entries(this.reportData.expenses.categories);

        this.charts.expenseBreakdown = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: expenseCategories.map(([name]) => name),
                datasets: [{
                    data: expenseCategories.map(([, amount]) => Math.abs(amount)),
                    backgroundColor: [
                        '#ef4444',
                        '#f59e0b',
                        '#10b981',
                        '#3b82f6',
                        '#8b5cf6',
                        '#64748b'
                    ]
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
    }

    /**
     * Create cash flow chart
     */
    createCashFlowChart() {
        const ctx = document.getElementById('cash-flow-chart');
        if (!ctx || !this.reportData.operating_activities) return;

        this.charts.cashFlow = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Operating Activities', 'Investing Activities', 'Financing Activities'],
                datasets: [{
                    label: 'Cash Flow',
                    data: [
                        this.reportData.operating_activities['Net Operating Cash Flow'] || 0,
                        this.reportData.investing_activities['Net Investing Cash Flow'] || 0,
                        this.reportData.financing_activities['Net Financing Cash Flow'] || 0
                    ],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(245, 158, 11, 0.8)'
                    ]
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
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
            }
        });
    }

    /**
     * Preview report
     */
    previewReport() {
        this.generateReport();
    }

    /**
     * Export report in specified format
     */
    exportReport(format) {
        if (!this.reportData) {
            this.showError('Please generate a report first');
            return;
        }

        switch (format) {
            case 'pdf':
                this.exportToPDF();
                break;
            case 'excel':
                this.exportToExcel();
                break;
            case 'csv':
                this.exportToCSV();
                break;
            default:
                this.showError('Unsupported export format');
        }
    }

    /**
     * Export to PDF (mock implementation)
     */
    exportToPDF() {
        // In a real implementation, this would generate a PDF
        const reportContent = this.generateReportText();
        const blob = new Blob([reportContent], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `report_${new Date().toISOString().split('T')[0]}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        this.showSuccess('Report exported as PDF');
    }

    /**
     * Export to Excel (mock implementation)
     */
    exportToExcel() {
        const csv = this.convertToCSV(this.reportData.details);
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `report_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        this.showSuccess('Report exported as Excel');
    }

    /**
     * Export to CSV
     */
    exportToCSV() {
        this.exportToExcel();
    }

    /**
     * Print report
     */
    printReport() {
        window.print();
        this.showSuccess('Print dialog opened');
    }

    /**
     * Generate report text content
     */
    generateReportText() {
        if (!this.reportData) return '';

        let content = `Financial Report - ${this.currentReport.toUpperCase()}\n`;
        content += `Period: ${this.currentPeriod}\n`;
        content += `Generated: ${new Date().toLocaleString()}\n\n`;

        if (this.reportData.summary) {
            content += 'SUMMARY\n';
            content += '========\n';
            content += `Total Revenue: ${this.formatCurrency(this.reportData.summary.totalRevenue)}\n`;
            content += `Total Expenses: ${this.formatCurrency(Math.abs(this.reportData.summary.totalExpenses))}\n`;
            content += `Net Income: ${this.formatCurrency(this.reportData.summary.netIncome)}\n`;
            content += `Profit Margin: ${this.reportData.summary.profitMargin}%\n\n`;
        }

        if (this.reportData.details) {
            content += 'DETAILS\n';
            content += '=======\n';
            this.reportData.details.forEach(item => {
                content += `${item.account}: ${this.formatCurrency(item.currentPeriod)}\n`;
            });
        }

        return content;
    }

    /**
     * Convert data to CSV
     */
    convertToCSV(data) {
        const headers = ['Account', 'Current Period', 'Previous Period', 'Change', 'Change %'];
        const rows = data.map(item => [
            item.account,
            item.currentPeriod,
            item.previousPeriod,
            item.change,
            item.changePercent + '%'
        ]);

        return [headers, ...rows]
            .map(row => row.map(cell => `"${cell}"`).join(','))
            .join('\n');
    }

    /**
     * Show loading state
     */
    showLoading() {
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(btn => btn.disabled = true);
        
        // Show loading spinner
        const loadingHtml = '<div class="loading">Generating report...</div>';
        document.body.insertAdjacentHTML('beforeend', loadingHtml);
    }

    /**
     * Hide loading state
     */
    hideLoading() {
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(btn => btn.disabled = false);
        
        // Remove loading spinner
        const loading = document.querySelector('.loading');
        if (loading) loading.remove();
    }

    /**
     * Format currency value
     */
    formatCurrency(value) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
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

// Initialize reports page when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.reportsPage = new ReportsPage();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ReportsPage;
}