-- Simple Seed Script for Essential Data
USE accounting_system;

-- Insert basic companies (keep existing company 1)
INSERT INTO companies (company_name, tax_id, address, phone, email, website, currency_code, fiscal_year_start, is_active) VALUES
('TechCorp Solutions Inc.', '12-3456789', '123 Innovation Drive, Palo Alto, CA 94301', '+1-650-555-0123', 'contact@techcorp.com', 'https://techcorp.com', 'USD', '2024-01-01', TRUE)
ON DUPLICATE KEY UPDATE company_name=VALUES(company_name);

-- Insert basic chart of accounts for company 1
INSERT INTO accounts (company_id, account_code, account_name, account_type, opening_balance, description) VALUES
-- Assets
(1, '1110', 'Cash and Cash Equivalents', 'ASSET', 250000.00, 'Cash and bank accounts'),
(1, '1111', 'Business Checking Account', 'ASSET', 180000.00, 'Main business checking account'),
(1, '1112', 'Business Savings Account', 'ASSET', 50000.00, 'Business savings account'),
(1, '1113', 'Petty Cash', 'ASSET', 20000.00, 'Petty cash fund'),
(1, '1120', 'Accounts Receivable', 'ASSET', 85000.00, 'Trade receivables from clients'),
(1, '1210', 'Computer Equipment', 'ASSET', 150000.00, 'Computers and servers'),
(1, '1211', 'Office Furniture', 'ASSET', 45000.00, 'Office furniture and fixtures'),

-- Liabilities
(1, '2110', 'Accounts Payable', 'LIABILITY', 45000.00, 'Trade payables to vendors'),
(1, '2120', 'Accrued Expenses', 'LIABILITY', 18000.00, 'Accrued salaries and expenses'),
(1, '2130', 'Taxes Payable', 'LIABILITY', 12000.00, 'Sales and payroll taxes'),
(1, '2210', 'Bank Loans', 'LIABILITY', 200000.00, 'Long-term bank financing'),

-- Equity
(1, '3110', 'Owner''s Capital', 'EQUITY', 300000.00, 'Owner investment'),
(1, '3120', 'Retained Earnings', 'EQUITY', 85000.00, 'Accumulated profits'),

-- Revenue
(1, '4100', 'Service Revenue', 'REVENUE', 0.00, 'Service income'),
(1, '4110', 'Software Development', 'REVENUE', 0.00, 'Custom software development'),
(1, '4120', 'IT Consulting', 'REVENUE', 0.00, 'IT consulting services'),

-- Expenses
(1, '5100', 'Operating Expenses', 'EXPENSE', 0.00, 'General operating expenses'),
(1, '5110', 'Rent Expense', 'EXPENSE', 0.00, 'Office and facility rent'),
(1, '5120', 'Salaries Expense', 'EXPENSE', 0.00, 'Employee salaries'),
(1, '5130', 'Utilities Expense', 'EXPENSE', 0.00, 'Utilities and services'),
(1, '5140', 'Office Supplies Expense', 'EXPENSE', 0.00, 'Office supplies and materials'),
(1, '5150', 'Software Expense', 'EXPENSE', 0.00, 'Software licenses and subscriptions')
ON DUPLICATE KEY UPDATE account_name=VALUES(account_name);