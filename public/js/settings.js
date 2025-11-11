// Settings page functionality
class SettingsManager {
    constructor() {
        this.currentTab = 'general';
        this.settings = this.loadSettings();
        this.init();
    }

    init() {
        this.setupTabNavigation();
        this.loadSettingsToForm();
        this.setupFormHandlers();
        this.setupValidation();
    }

    setupTabNavigation() {
        const navItems = document.querySelectorAll('.settings-nav-item');
        const tabs = document.querySelectorAll('.settings-tab');

        navItems.forEach(item => {
            item.addEventListener('click', () => {
                const tabName = item.dataset.tab;
                this.switchTab(tabName);
            });
        });
    }

    switchTab(tabName) {
        // Update navigation
        document.querySelectorAll('.settings-nav-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        // Update content
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.getElementById(`${tabName}-tab`).classList.add('active');

        this.currentTab = tabName;
    }

    loadSettings() {
        const defaultSettings = {
            general: {
                companyName: 'My Business',
                companyEmail: 'contact@mybusiness.com',
                companyPhone: '+1 234 567 8900',
                companyAddress: '123 Business St\nSuite 100\nBusiness City, BC 12345',
                defaultCurrency: 'PHP',
                fiscalYearStart: '2024-01-01',
                timezone: 'America/New_York'
            },
            account: {
                fullName: 'John Doe',
                emailAddress: 'john.doe@example.com',
                username: 'johndoe',
                emailNotifications: true,
                transactionAlerts: true,
                monthlyReports: false,
                systemUpdates: true
            },
            preferences: {
                theme: 'light',
                language: 'en',
                dateFormat: 'MM/DD/YYYY',
                dashboardView: 'overview',
                itemsPerPage: '25',
                showCharts: true
            },
            security: {
                sessionTimeout: 30,
                rememberDevice: true,
                enable2FA: false
            },
            data: {
                autoBackup: true,
                backupFrequency: 'weekly',
                retentionPeriod: '5'
            }
        };

        const saved = localStorage.getItem('accountingSettings');
        return saved ? { ...defaultSettings, ...JSON.parse(saved) } : defaultSettings;
    }

    loadSettingsToForm() {
        // General settings
        document.getElementById('companyName').value = this.settings.general.companyName;
        document.getElementById('companyEmail').value = this.settings.general.companyEmail;
        document.getElementById('companyPhone').value = this.settings.general.companyPhone;
        document.getElementById('companyAddress').value = this.settings.general.companyAddress;
        document.getElementById('defaultCurrency').value = this.settings.general.defaultCurrency;
        document.getElementById('fiscalYearStart').value = this.settings.general.fiscalYearStart;
        document.getElementById('timezone').value = this.settings.general.timezone;

        // Account settings
        document.getElementById('fullName').value = this.settings.account.fullName;
        document.getElementById('emailAddress').value = this.settings.account.emailAddress;
        document.getElementById('username').value = this.settings.account.username;
        document.getElementById('emailNotifications').checked = this.settings.account.emailNotifications;
        document.getElementById('transactionAlerts').checked = this.settings.account.transactionAlerts;
        document.getElementById('monthlyReports').checked = this.settings.account.monthlyReports;
        document.getElementById('systemUpdates').checked = this.settings.account.systemUpdates;

        // Preferences
        document.getElementById('theme').value = this.settings.preferences.theme;
        document.getElementById('language').value = this.settings.preferences.language;
        document.getElementById('dateFormat').value = this.settings.preferences.dateFormat;
        document.getElementById('dashboardView').value = this.settings.preferences.dashboardView;
        document.getElementById('itemsPerPage').value = this.settings.preferences.itemsPerPage;
        document.getElementById('showCharts').checked = this.settings.preferences.showCharts;

        // Security
        document.getElementById('sessionTimeout').value = this.settings.security.sessionTimeout;
        document.getElementById('rememberDevice').checked = this.settings.security.rememberDevice;
        document.getElementById('enable2FA').checked = this.settings.security.enable2FA;

        // Data management
        document.getElementById('autoBackup').checked = this.settings.data.autoBackup;
        document.getElementById('backupFrequency').value = this.settings.data.backupFrequency;
        document.getElementById('retentionPeriod').value = this.settings.data.retentionPeriod;
    }

    setupFormHandlers() {
        // Auto-save on change
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                this.updateSetting(input);
            });
        });

        // Button event listeners
        this.setupButtonListeners();
    }

    setupButtonListeners() {
        // Security buttons
        const generateBackupCodesBtn = document.getElementById('generate-backup-codes-btn');
        if (generateBackupCodesBtn) {
            generateBackupCodesBtn.addEventListener('click', () => this.generateBackupCodes());
        }

        // Data management buttons
        const createBackupBtn = document.getElementById('create-backup-btn');
        if (createBackupBtn) {
            createBackupBtn.addEventListener('click', () => this.createBackup());
        }

        const restoreBackupBtn = document.getElementById('restore-backup-btn');
        if (restoreBackupBtn) {
            restoreBackupBtn.addEventListener('click', () => this.restoreBackup());
        }

        const exportDataBtn = document.getElementById('export-data-btn');
        if (exportDataBtn) {
            exportDataBtn.addEventListener('click', () => this.exportData());
        }

        const importDataBtn = document.getElementById('import-data-btn');
        if (importDataBtn) {
            importDataBtn.addEventListener('click', () => this.importData());
        }

        const cleanupOldDataBtn = document.getElementById('cleanup-old-data-btn');
        if (cleanupOldDataBtn) {
            cleanupOldDataBtn.addEventListener('click', () => this.cleanupOldData());
        }

        // Action buttons
        const saveSettingsBtn = document.getElementById('save-settings-btn');
        if (saveSettingsBtn) {
            saveSettingsBtn.addEventListener('click', () => this.saveSettings());
        }

        const resetSettingsBtn = document.getElementById('reset-settings-btn');
        if (resetSettingsBtn) {
            resetSettingsBtn.addEventListener('click', () => this.resetSettings());
        }
    }

    setupValidation() {
        // Email validation
        const emailInputs = document.querySelectorAll('input[type="email"]');
        emailInputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateEmail(input);
            });
        });

        // Password confirmation validation
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        
        if (newPassword && confirmPassword) {
            confirmPassword.addEventListener('input', () => {
                this.validatePasswordMatch();
            });
        }
    }

    validateEmail(input) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(input.value)) {
            input.classList.add('error');
            this.showToast('Please enter a valid email address', 'error');
            return false;
        } else {
            input.classList.remove('error');
            return true;
        }
    }

    validatePasswordMatch() {
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.classList.add('error');
            this.showToast('Passwords do not match', 'error');
            return false;
        } else {
            confirmPassword.classList.remove('error');
            return true;
        }
    }

    updateSetting(input) {
        const section = this.getSectionFromInput(input);
        const setting = input.id;
        let value;

        if (input.type === 'checkbox') {
            value = input.checked;
        } else if (input.type === 'number') {
            value = parseInt(input.value);
        } else {
            value = input.value;
        }

        if (this.settings[section] && this.settings[section][setting] !== undefined) {
            this.settings[section][setting] = value;
            this.saveSettings();
        }
    }

    getSectionFromInput(input) {
        const tab = input.closest('.settings-tab');
        if (tab) {
            const tabId = tab.id;
            return tabId.replace('-tab', '');
        }
        return 'general';
    }

    saveSettings() {
        localStorage.setItem('accountingSettings', JSON.stringify(this.settings));
        this.showToast('Settings saved successfully', 'success');
    }

    resetSettings() {
        if (confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.')) {
            localStorage.removeItem('accountingSettings');
            this.settings = this.loadSettings();
            this.loadSettingsToForm();
            this.showToast('Settings reset to default values', 'info');
        }
    }

    // Security functions
    changePassword(currentPassword, newPassword, confirmPassword) {
        if (!currentPassword || !newPassword || !confirmPassword) {
            this.showToast('Please fill in all password fields', 'error');
            return false;
        }

        if (newPassword !== confirmPassword) {
            this.showToast('New passwords do not match', 'error');
            return false;
        }

        if (newPassword.length < 8) {
            this.showToast('Password must be at least 8 characters long', 'error');
            return false;
        }

        // Simulate password change
        setTimeout(() => {
            this.showToast('Password changed successfully', 'success');
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
        }, 1000);

        return true;
    }

    generateBackupCodes() {
        const codes = [];
        for (let i = 0; i < 10; i++) {
            codes.push(Math.random().toString(36).substring(2, 10).toUpperCase());
        }

        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Backup Codes</h3>
                    <button class="close-btn" onclick="this.closest('.modal').remove()">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Save these backup codes in a secure location. Each code can only be used once.</p>
                    <div class="backup-codes">
                        ${codes.map(code => `<div class="backup-code">${code}</div>`).join('')}
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" onclick="downloadBackupCodes([${codes.map(c => `'${c}'`).join(',')}])">
                            <i class="fas fa-download"></i> Download Codes
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Data management functions
    createBackup() {
        this.showToast('Creating backup...', 'info');
        
        setTimeout(() => {
            const backupData = {
                timestamp: new Date().toISOString(),
                settings: this.settings,
                version: '1.0.0'
            };

            const blob = new Blob([JSON.stringify(backupData, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `accounting-backup-${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            URL.revokeObjectURL(url);

            this.showToast('Backup created successfully', 'success');
        }, 2000);
    }

    restoreBackup() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        
        input.onchange = (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const backupData = JSON.parse(e.target.result);
                        if (backupData.settings) {
                            this.settings = { ...this.settings, ...backupData.settings };
                            this.saveSettings();
                            this.loadSettingsToForm();
                            this.showToast('Backup restored successfully', 'success');
                        } else {
                            this.showToast('Invalid backup file', 'error');
                        }
                    } catch (error) {
                        this.showToast('Error reading backup file', 'error');
                    }
                };
                reader.readAsText(file);
            }
        };
        
        input.click();
    }

    exportData() {
        this.showToast('Exporting data...', 'info');
        
        setTimeout(() => {
            const exportData = {
                timestamp: new Date().toISOString(),
                settings: this.settings,
                exportType: 'user_settings'
            };

            const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `accounting-export-${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            URL.revokeObjectURL(url);

            this.showToast('Data exported successfully', 'success');
        }, 1500);
    }

    importData() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        
        input.onchange = (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const importData = JSON.parse(e.target.result);
                        if (importData.settings) {
                            this.settings = { ...this.settings, ...importData.settings };
                            this.saveSettings();
                            this.loadSettingsToForm();
                            this.showToast('Data imported successfully', 'success');
                        } else {
                            this.showToast('Invalid import file', 'error');
                        }
                    } catch (error) {
                        this.showToast('Error reading import file', 'error');
                    }
                };
                reader.readAsText(file);
            }
        };
        
        input.click();
    }

    cleanupOldData() {
        const retentionPeriod = document.getElementById('retentionPeriod').value;
        
        if (retentionPeriod === 'forever') {
            this.showToast('Data retention is set to forever. No cleanup needed.', 'info');
            return;
        }

        if (confirm(`Are you sure you want to delete data older than ${retentionPeriod} year(s)? This action cannot be undone.`)) {
            this.showToast('Cleaning up old data...', 'info');
            
            setTimeout(() => {
                this.showToast(`Data cleanup completed. Removed data older than ${retentionPeriod} year(s).`, 'success');
            }, 2000);
        }
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.getElementById('toast-container').appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Global functions for button handlers


function downloadBackupCodes(codes) {
    const content = `Two-Factor Authentication Backup Codes\nGenerated: ${new Date().toLocaleString()}\n\n${codes.join('\n')}`;
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `backup-codes-${new Date().toISOString().split('T')[0]}.txt`;
    a.click();
    URL.revokeObjectURL(url);
}

// Initialize settings manager
document.addEventListener('DOMContentLoaded', () => {
    window.settingsManager = new SettingsManager();
});