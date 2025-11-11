-- Accounting System Database Migration
-- Single Admin, Multi-Company Architecture
-- MySQL 8.0

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing database and recreate with new schema
DROP DATABASE IF EXISTS accounting_system;
CREATE DATABASE accounting_system 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE accounting_system;

-- Companies table (unchanged - supports multi-company)
CREATE TABLE companies (
    company_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    tax_id VARCHAR(50) NULL,
    address TEXT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    currency_code VARCHAR(3) DEFAULT 'USD',
    fiscal_year_start DATE DEFAULT '2024-01-01',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_company_name (company_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- Simplified users table (single admin only)
CREATE TABLE users (
    user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    totp_secret VARCHAR(255) NULL,
    is_2fa_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- Accounting periods (simplified - no user tracking)
CREATE TABLE accounting_periods (
    period_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    period_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_closed BOOLEAN DEFAULT FALSE,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    UNIQUE KEY unique_company_period (company_id, start_date, end_date),
    INDEX idx_company_id (company_id),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_is_closed (is_closed)
) ENGINE=InnoDB;

-- Chart of Accounts (unchanged - perfect as-is)
CREATE TABLE accounts (
    account_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    account_code VARCHAR(20) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE') NOT NULL,
    parent_account_id BIGINT UNSIGNED NULL,
    opening_balance DECIMAL(15,2) DEFAULT 0.00,
    current_balance DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    is_contra BOOLEAN DEFAULT FALSE,
    description TEXT NULL,
    tax_rate DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_account_id) REFERENCES accounts(account_id) ON DELETE SET NULL,
    UNIQUE KEY unique_company_code (company_id, account_code),
    INDEX idx_company_id (company_id),
    INDEX idx_account_type (account_type),
    INDEX idx_parent_account (parent_account_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- Transactions (simplified - no user tracking)
CREATE TABLE transactions (
    transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    period_id BIGINT UNSIGNED NOT NULL,
    transaction_number VARCHAR(50) NOT NULL,
    transaction_date DATE NOT NULL,
    description TEXT NOT NULL,
    reference VARCHAR(100) NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    status ENUM('draft', 'posted', 'void') DEFAULT 'draft',
    posted_at TIMESTAMP NULL,
    voided_at TIMESTAMP NULL,
    void_reason TEXT NULL,
    attachment_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES accounting_periods(period_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_company_transaction (company_id, transaction_number),
    INDEX idx_company_id (company_id),
    INDEX idx_period_id (period_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_status (status),
    INDEX idx_total_amount (total_amount)
) ENGINE=InnoDB;

-- Transaction Lines (simplified - no user tracking)
CREATE TABLE transaction_lines (
    line_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    description TEXT NULL,
    debit_amount DECIMAL(15,2) DEFAULT 0.00,
    credit_amount DECIMAL(15,2) DEFAULT 0.00,
    reconciled BOOLEAN DEFAULT FALSE,
    reconciled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE RESTRICT,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_account_id (account_id),
    INDEX idx_debit_amount (debit_amount),
    INDEX idx_credit_amount (credit_amount),
    INDEX idx_reconciled (reconciled)
) ENGINE=InnoDB;

-- Budgets (unchanged - perfect as-is)
CREATE TABLE budgets (
    budget_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    period_id BIGINT UNSIGNED NOT NULL,
    budgeted_amount DECIMAL(15,2) NOT NULL,
    actual_amount DECIMAL(15,2) DEFAULT 0.00,
    variance DECIMAL(15,2) GENERATED ALWAYS AS (actual_amount - budgeted_amount) STORED,
    variance_percentage DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE 
            WHEN budgeted_amount != 0 THEN ((actual_amount - budgeted_amount) / ABS(budgeted_amount)) * 100
            ELSE 0 
        END
    ) STORED,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES accounting_periods(period_id) ON DELETE CASCADE,
    UNIQUE KEY unique_company_account_period (company_id, account_id, period_id),
    INDEX idx_company_id (company_id),
    INDEX idx_account_id (account_id),
    INDEX idx_period_id (period_id)
) ENGINE=InnoDB;

-- Simplified audit log (no user tracking)
CREATE TABLE audit_log (
    audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    record_id BIGINT UNSIGNED NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    INDEX idx_company_id (company_id),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- NEW: Admin settings table
CREATE TABLE admin_settings (
    setting_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB;

-- Insert sample companies
INSERT INTO companies (company_name, tax_id, address, phone, email, website, currency_code, fiscal_year_start) VALUES
('Demo Company Inc.', '12-3456789', '123 Business St, Suite 100, New York, NY 10001', '+1-555-0123', 'info@democompany.com', 'https://democompany.com', 'USD', '2024-01-01'),
('Tech Solutions LLC', '98-7654321', '456 Innovation Ave, Palo Alto, CA 94301', '+1-555-0456', 'contact@techsolutions.com', 'https://techsolutions.com', 'USD', '2024-01-01'),
('Retail Store Co.', '55-1234567', '789 Commerce Blvd, Chicago, IL 60601', '+1-555-0789', 'sales@retailstore.com', 'https://retailstore.com', 'USD', '2024-01-01');

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES
('admin', 'admin@accounting.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator');

-- Insert accounting periods for each company
INSERT INTO accounting_periods (company_id, period_name, start_date, end_date) VALUES
(1, 'Q1 2024', '2024-01-01', '2024-03-31'),
(1, 'Q2 2024', '2024-04-01', '2024-06-30'),
(1, 'Q3 2024', '2024-07-01', '2024-09-30'),
(1, 'Q4 2024', '2024-10-01', '2024-12-31'),
(2, 'Q1 2024', '2024-01-01', '2024-03-31'),
(2, 'Q2 2024', '2024-04-01', '2024-06-30'),
(2, 'Q3 2024', '2024-07-01', '2024-09-30'),
(2, 'Q4 2024', '2024-10-01', '2024-12-31'),
(3, 'Q1 2024', '2024-01-01', '2024-03-31'),
(3, 'Q2 2024', '2024-04-01', '2024-06-30'),
(3, 'Q3 2024', '2024-07-01', '2024-09-30'),
(3, 'Q4 2024', '2024-10-01', '2024-12-31');

-- Insert chart of accounts for each company
INSERT INTO accounts (company_id, account_code, account_name, account_type, opening_balance, description) VALUES
-- Demo Company Inc. Accounts
(1, '1000', 'ASSETS', 'ASSET', 0.00, 'Total Assets'),
(1, '1100', 'Current Assets', 'ASSET', 0.00, 'Current Assets'),
(1, '1110', 'Cash and Cash Equivalents', 'ASSET', 50000.00, 'Cash and bank accounts'),
(1, '1111', 'Checking Account', 'ASSET', 25000.00, 'Main business checking account'),
(1, '1120', 'Accounts Receivable', 'ASSET', 15000.00, 'Trade receivables'),
(1, '2000', 'LIABILITIES', 'LIABILITY', 0.00, 'Total Liabilities'),
(1, '2100', 'Accounts Payable', 'LIABILITY', 12000.00, 'Trade payables'),
(1, '3000', 'EQUITY', 'EQUITY', 0.00, 'Total Equity'),
(1, '3110', 'Owner''s Capital', 'EQUITY', 150000.00, 'Owner investment'),
(1, '4000', 'REVENUE', 'REVENUE', 0.00, 'Total Revenue'),
(1, '4100', 'Sales Revenue', 'REVENUE', 0.00, 'Product sales'),
(1, '5000', 'EXPENSES', 'EXPENSE', 0.00, 'Total Expenses'),
(1, '5100', 'Operating Expenses', 'EXPENSE', 0.00, 'Operating costs'),

-- Tech Solutions LLC Accounts
(2, '1000', 'ASSETS', 'ASSET', 0.00, 'Total Assets'),
(2, '1100', 'Current Assets', 'ASSET', 0.00, 'Current Assets'),
(2, '1110', 'Cash and Cash Equivalents', 'ASSET', 75000.00, 'Cash and bank accounts'),
(2, '1111', 'Checking Account', 'ASSET', 50000.00, 'Main business checking account'),
(2, '1120', 'Accounts Receivable', 'ASSET', 25000.00, 'Trade receivables'),
(2, '2000', 'LIABILITIES', 'LIABILITY', 0.00, 'Total Liabilities'),
(2, '2100', 'Accounts Payable', 'LIABILITY', 15000.00, 'Trade payables'),
(2, '3000', 'EQUITY', 'EQUITY', 0.00, 'Total Equity'),
(2, '3110', 'Owner''s Capital', 'EQUITY', 200000.00, 'Owner investment'),
(2, '4000', 'REVENUE', 'REVENUE', 0.00, 'Total Revenue'),
(2, '4100', 'Service Revenue', 'REVENUE', 0.00, 'Service income'),
(2, '5000', 'EXPENSES', 'EXPENSE', 0.00, 'Total Expenses'),
(2, '5100', 'Operating Expenses', 'EXPENSE', 0.00, 'Operating costs'),

-- Retail Store Co. Accounts
(3, '1000', 'ASSETS', 'ASSET', 0.00, 'Total Assets'),
(3, '1100', 'Current Assets', 'ASSET', 0.00, 'Current Assets'),
(3, '1110', 'Cash and Cash Equivalents', 'ASSET', 30000.00, 'Cash and bank accounts'),
(3, '1111', 'Checking Account', 'ASSET', 20000.00, 'Main business checking account'),
(3, '1120', 'Inventory', 'ASSET', 35000.00, 'Retail inventory'),
(3, '2000', 'LIABILITIES', 'LIABILITY', 0.00, 'Total Liabilities'),
(3, '2100', 'Accounts Payable', 'LIABILITY', 20000.00, 'Trade payables'),
(3, '3000', 'EQUITY', 'EQUITY', 0.00, 'Total Equity'),
(3, '3110', 'Owner''s Capital', 'EQUITY', 100000.00, 'Owner investment'),
(3, '4000', 'REVENUE', 'REVENUE', 0.00, 'Total Revenue'),
(3, '4100', 'Sales Revenue', 'REVENUE', 0.00, 'Retail sales'),
(3, '5000', 'EXPENSES', 'EXPENSE', 0.00, 'Total Expenses'),
(3, '5100', 'Cost of Goods Sold', 'EXPENSE', 0.00, 'Direct costs');

-- Update parent account relationships for Demo Company
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1000' AND company_id = 1) AS temp) WHERE account_code IN ('1100') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1100' AND company_id = 1) AS temp) WHERE account_code IN ('1110', '1120') AND company_id = 1;

-- Update parent account relationships for Tech Solutions
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1000' AND company_id = 2) AS temp) WHERE account_code IN ('1100') AND company_id = 2;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1100' AND company_id = 2) AS temp) WHERE account_code IN ('1110', '1120') AND company_id = 2;

-- Update parent account relationships for Retail Store
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1000' AND company_id = 3) AS temp) WHERE account_code IN ('1100') AND company_id = 3;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1100' AND company_id = 3) AS temp) WHERE account_code IN ('1110', '1120') AND company_id = 3;

-- Insert sample budgets for Demo Company Q1 2024
INSERT INTO budgets (company_id, account_id, period_id, budgeted_amount, notes) VALUES
(1, (SELECT account_id FROM accounts WHERE account_code = '4100' AND company_id = 1), 1, 100000.00, 'Q1 2024 Sales Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '5100' AND company_id = 1), 1, 60000.00, 'Q1 2024 Operating Expense Budget');

-- Insert sample budgets for Tech Solutions Q1 2024
INSERT INTO budgets (company_id, account_id, period_id, budgeted_amount, notes) VALUES
(2, (SELECT account_id FROM accounts WHERE account_code = '4100' AND company_id = 2), 5, 150000.00, 'Q1 2024 Service Revenue Budget'),
(2, (SELECT account_id FROM accounts WHERE account_code = '5100' AND company_id = 2), 5, 80000.00, 'Q1 2024 Operating Expense Budget');

-- Insert sample budgets for Retail Store Q1 2024
INSERT INTO budgets (company_id, account_id, period_id, budgeted_amount, notes) VALUES
(3, (SELECT account_id FROM accounts WHERE account_code = '4100' AND company_id = 3), 9, 200000.00, 'Q1 2024 Retail Sales Budget'),
(3, (SELECT account_id FROM accounts WHERE account_code = '5100' AND company_id = 3), 9, 120000.00, 'Q1 2024 Cost of Goods Sold Budget');

-- Insert default admin settings
INSERT INTO admin_settings (setting_key, setting_value, setting_type, description) VALUES
('default_company_id', '1', 'number', 'Default company to load on login'),
('session_timeout', '3600', 'number', 'Session timeout in seconds'),
('require_2fa', 'false', 'boolean', 'Require two-factor authentication'),
('backup_retention_days', '30', 'number', 'Number of days to retain backups'),
('default_currency', 'USD', 'string', 'Default currency code'),
('fiscal_year_start', '2024-01-01', 'string', 'Default fiscal year start date');

SET FOREIGN_KEY_CHECKS = 1;

-- Migration completed successfully
-- Database: accounting_system
-- Architecture: Single Admin, Multi-Company
-- Complexity: Simplified from multi-tenant to single-admin