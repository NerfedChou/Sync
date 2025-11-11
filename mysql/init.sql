-- Accounting System Database Schema
-- MySQL 8.0 with proper accounting principles

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS accounting_system 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE accounting_system;

-- Single admin user table (no auth, no password)
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Companies table for multi-company support
CREATE TABLE companies (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
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

-- Accounting periods for fiscal management
CREATE TABLE accounting_periods (
    period_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
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

-- Chart of Accounts
CREATE TABLE accounts (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    account_code VARCHAR(20) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE') NOT NULL,
    parent_account_id INT NULL,
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

-- Transactions (Journal Entries)
CREATE TABLE transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    period_id INT NOT NULL,
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

-- Transaction Lines (Double-entry bookkeeping)
CREATE TABLE transaction_lines (
    line_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    account_id INT NOT NULL,
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

-- Budgets for budgeting and variance analysis
CREATE TABLE budgets (
    budget_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    account_id INT NOT NULL,
    period_id INT NOT NULL,
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

-- Admin settings table
CREATE TABLE admin_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB;

-- Insert sample admin
INSERT INTO admin (name) VALUES ('Admin');

-- Insert sample company
INSERT INTO companies (company_name, tax_id, address, phone, email, website, currency_code, fiscal_year_start) VALUES
('Demo Company Inc.', '12-3456789', '123 Business St, Suite 100, New York, NY 10001', '+1-555-0123', 'info@democompany.com', 'https://democompany.com', 'USD', '2024-01-01');

-- Insert sample accounting periods
INSERT INTO accounting_periods (company_id, period_name, start_date, end_date) VALUES
(1, 'Q1 2024', '2024-01-01', '2024-03-31'),
(1, 'Q2 2024', '2024-04-01', '2024-06-30'),
(1, 'Q3 2024', '2024-07-01', '2024-09-30'),
(1, 'Q4 2024', '2024-10-01', '2024-12-31');

-- Insert sample chart of accounts
INSERT INTO accounts (company_id, account_code, account_name, account_type, opening_balance, description) VALUES
-- Assets
(1, '1000', 'ASSETS', 'ASSET', 0.00, 'Total Assets'),
(1, '1100', 'Current Assets', 'ASSET', 0.00, 'Current Assets'),
(1, '1110', 'Cash and Cash Equivalents', 'ASSET', 50000.00, 'Cash and bank accounts'),
(1, '1111', 'Checking Account', 'ASSET', 25000.00, 'Main business checking account'),
(1, '1112', 'Savings Account', 'ASSET', 15000.00, 'Business savings account'),
(1, '1113', 'Petty Cash', 'ASSET', 1000.00, 'Petty cash fund'),
(1, '1120', 'Accounts Receivable', 'ASSET', 15000.00, 'Trade receivables'),
(1, '1130', 'Inventory', 'ASSET', 25000.00, 'Inventory for resale'),
(1, '1200', 'Fixed Assets', 'ASSET', 0.00, 'Long-term assets'),
(1, '1210', 'Equipment', 'ASSET', 50000.00, 'Office and production equipment'),
(1, '1220', 'Accumulated Depreciation - Equipment', 'ASSET', -10000.00, 'Depreciation on equipment', TRUE),
(1, '1230', 'Buildings', 'ASSET', 200000.00, 'Buildings and improvements'),
(1, '1240', 'Accumulated Depreciation - Buildings', 'ASSET', -20000.00, 'Depreciation on buildings', TRUE),

-- Liabilities
(1, '2000', 'LIABILITIES', 'LIABILITY', 0.00, 'Total Liabilities'),
(1, '2100', 'Current Liabilities', 'LIABILITY', 0.00, 'Current obligations'),
(1, '2110', 'Accounts Payable', 'LIABILITY', 12000.00, 'Trade payables'),
(1, '2120', 'Accrued Expenses', 'LIABILITY', 3000.00, 'Accrued wages and expenses'),
(1, '2130', 'Taxes Payable', 'LIABILITY', 5000.00, 'Sales and payroll taxes'),
(1, '2200', 'Long-term Liabilities', 'LIABILITY', 0.00, 'Long-term obligations'),
(1, '2210', 'Bank Loans', 'LIABILITY', 100000.00, 'Long-term bank financing'),

-- Equity
(1, '3000', 'EQUITY', 'EQUITY', 0.00, 'Total Equity'),
(1, '3110', 'Owner''s Capital', 'EQUITY', 150000.00, 'Owner investment'),
(1, '3120', 'Retained Earnings', 'EQUITY', 35000.00, 'Accumulated profits'),
(1, '3130', 'Common Stock', 'EQUITY', 100000.00, 'Common stock issued'),

-- Revenue
(1, '4000', 'REVENUE', 'REVENUE', 0.00, 'Total Revenue'),
(1, '4100', 'Sales Revenue', 'REVENUE', 0.00, 'Product sales'),
(1, '4110', 'Product Sales', 'REVENUE', 0.00, 'Main product sales'),
(1, '4200', 'Service Revenue', 'REVENUE', 0.00, 'Service income'),
(1, '4210', 'Consulting Services', 'REVENUE', 0.00, 'Consulting fees'),
(1, '4300', 'Other Revenue', 'REVENUE', 0.00, 'Miscellaneous income'),
(1, '4310', 'Interest Income', 'REVENUE', 0.00, 'Interest earned'),

-- Expenses
(1, '5000', 'EXPENSES', 'EXPENSE', 0.00, 'Total Expenses'),
(1, '5100', 'Cost of Goods Sold', 'EXPENSE', 0.00, 'Direct costs of goods'),
(1, '5110', 'Materials', 'EXPENSE', 0.00, 'Raw materials'),
(1, '5120', 'Direct Labor', 'EXPENSE', 0.00, 'Production labor'),
(1, '5200', 'Operating Expenses', 'EXPENSE', 0.00, 'Operating costs'),
(1, '5210', 'Salaries and Wages', 'EXPENSE', 0.00, 'Employee compensation'),
(1, '5220', 'Rent Expense', 'EXPENSE', 0.00, 'Facility rent'),
(1, '5230', 'Utilities', 'EXPENSE', 0.00, 'Electric, water, gas'),
(1, '5240', 'Marketing', 'EXPENSE', 0.00, 'Advertising and promotion'),
(1, '5250', 'Office Supplies', 'EXPENSE', 0.00, 'Office materials'),
(1, '5300', 'Depreciation Expense', 'EXPENSE', 0.00, 'Asset depreciation'),
(1, '5310', 'Equipment Depreciation', 'EXPENSE', 0.00, 'Equipment depreciation'),
(1, '5320', 'Building Depreciation', 'EXPENSE', 0.00, 'Building depreciation'),
(1, '5400', 'Interest Expense', 'EXPENSE', 0.00, 'Interest on loans'),
(1, '5500', 'Tax Expense', 'EXPENSE', 0.00, 'Income taxes');

-- Update parent account relationships
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1000' AND company_id = 1) AS temp) WHERE account_code IN ('1100', '1200') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1100' AND company_id = 1) AS temp) WHERE account_code IN ('1110', '1120', '1130') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1110' AND company_id = 1) AS temp) WHERE account_code IN ('1111', '1112', '1113') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1200' AND company_id = 1) AS temp) WHERE account_code IN ('1210', '1220', '1230', '1240') AND company_id = 1;

UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '2000' AND company_id = 1) AS temp) WHERE account_code IN ('2100', '2200') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '2100' AND company_id = 1) AS temp) WHERE account_code IN ('2110', '2120', '2130') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '2200' AND company_id = 1) AS temp) WHERE account_code IN ('2210') AND company_id = 1;

UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '3000' AND company_id = 1) AS temp) WHERE account_code IN ('3110', '3120', '3130') AND company_id = 1;

UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '4000' AND company_id = 1) AS temp) WHERE account_code IN ('4100', '4200', '4300') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '4100' AND company_id = 1) AS temp) WHERE account_code IN ('4110') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '4200' AND company_id = 1) AS temp) WHERE account_code IN ('4210') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '4300' AND company_id = 1) AS temp) WHERE account_code IN ('4310') AND company_id = 1;

UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '5000' AND company_id = 1) AS temp) WHERE account_code IN ('5100', '5200', '5300', '5400', '5500') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '5100' AND company_id = 1) AS temp) WHERE account_code IN ('5110', '5120') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '5200' AND company_id = 1) AS temp) WHERE account_code IN ('5210', '5220', '5230', '5240', '5250') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '5300' AND company_id = 1) AS temp) WHERE account_code IN ('5310', '5320') AND company_id = 1;

-- Insert sample budgets for Q1 2024
INSERT INTO budgets (company_id, account_id, period_id, budgeted_amount, notes) VALUES
(1, (SELECT account_id FROM accounts WHERE account_code = '4110' AND company_id = 1), 1, 100000.00, 'Q1 2024 Product Sales Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '4210' AND company_id = 1), 1, 25000.00, 'Q1 2024 Consulting Services Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '5110' AND company_id = 1), 1, 40000.00, 'Q1 2024 Materials Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '5210' AND company_id = 1), 1, 30000.00, 'Q1 2024 Salaries Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '5220' AND company_id = 1), 1, 12000.00, 'Q1 2024 Rent Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '5230' AND company_id = 1), 1, 3000.00, 'Q1 2024 Utilities Budget');

SET FOREIGN_KEY_CHECKS = 1;