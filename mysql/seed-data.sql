-- Database Seed Script
-- Realistic Sample Data for Single Admin, Multi-Company Accounting System
-- MySQL 8.0

USE accounting_system;

-- Clear existing data (for fresh seeding)
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM audit_log;
DELETE FROM transaction_lines;
DELETE FROM transactions;
DELETE FROM budgets;
DELETE FROM accounting_periods;
DELETE FROM accounts;
DELETE FROM companies;
DELETE FROM admin_settings;
SET FOREIGN_KEY_CHECKS = 1;

-- Insert admin settings
INSERT INTO admin_settings (setting_key, setting_value, setting_type, description) VALUES
('default_company_id', '1', 'number', 'Default company to load on login'),
('session_timeout', '3600', 'number', 'Session timeout in seconds'),
('require_2fa', 'false', 'boolean', 'Require two-factor authentication'),
('backup_retention_days', '30', 'number', 'Number of days to retain backups'),
('default_currency', 'USD', 'string', 'Default currency code'),
('fiscal_year_start', '2024-01-01', 'string', 'Default fiscal year start date'),
('auto_backup_enabled', 'true', 'boolean', 'Enable automatic backups'),
('max_login_attempts', '5', 'number', 'Maximum login attempts before lockout'),
('password_min_length', '8', 'number', 'Minimum password length');

-- Insert realistic companies
INSERT INTO companies (company_name, tax_id, address, phone, email, website, currency_code, fiscal_year_start, is_active) VALUES
('TechCorp Solutions Inc.', '12-3456789', '123 Innovation Drive, Palo Alto, CA 94301', '+1-650-555-0123', 'contact@techcorp.com', 'https://techcorp.com', 'USD', '2024-01-01', TRUE),
('Global Retail LLC', '98-7654321', '456 Commerce Boulevard, New York, NY 10001', '+1-212-555-0456', 'info@globalretail.com', 'https://globalretail.com', 'USD', '2024-01-01', TRUE),
('Digital Marketing Pro', '55-1234567', '789 Creative Avenue, Austin, TX 78701', '+1-512-555-0789', 'hello@digitalmarketingpro.com', 'https://digitalmarketingpro.com', 'USD', '2024-01-01', TRUE),
('Consulting Partners Group', '77-9876543', '321 Executive Plaza, Chicago, IL 60601', '+1-312-555-0321', 'admin@consultingpartners.com', 'https://consultingpartners.com', 'USD', '2024-01-01', FALSE);

-- Insert accounting periods for each company (2024)
INSERT INTO accounting_periods (company_id, period_name, start_date, end_date) VALUES
-- TechCorp Solutions
(1, 'Q1 2024', '2024-01-01', '2024-03-31'),
(1, 'Q2 2024', '2024-04-01', '2024-06-30'),
(1, 'Q3 2024', '2024-07-01', '2024-09-30'),
(1, 'Q4 2024', '2024-10-01', '2024-12-31'),
-- Global Retail
(2, 'Q1 2024', '2024-01-01', '2024-03-31'),
(2, 'Q2 2024', '2024-04-01', '2024-06-30'),
(2, 'Q3 2024', '2024-07-01', '2024-09-30'),
(2, 'Q4 2024', '2024-10-01', '2024-12-31'),
-- Digital Marketing Pro
(3, 'Q1 2024', '2024-01-01', '2024-03-31'),
(3, 'Q2 2024', '2024-04-01', '2024-06-30'),
(3, 'Q3 2024', '2024-07-01', '2024-09-30'),
(3, 'Q4 2024', '2024-10-01', '2024-12-31'),
-- Consulting Partners
(4, 'Q1 2024', '2024-01-01', '2024-03-31'),
(4, 'Q2 2024', '2024-04-01', '2024-06-30'),
(4, 'Q3 2024', '2024-07-01', '2024-09-30'),
(4, 'Q4 2024', '2024-10-01', '2024-12-31');

-- Insert comprehensive chart of accounts for each company
INSERT INTO accounts (company_id, account_code, account_name, account_type, opening_balance, description) VALUES
-- TechCorp Solutions Accounts (Company 1)
(1, '1000', 'ASSETS', 'ASSET', 0.00, 'Total Assets'),
(1, '1100', 'Current Assets', 'ASSET', 0.00, 'Current Assets'),
(1, '1110', 'Cash and Cash Equivalents', 'ASSET', 250000.00, 'Cash and bank accounts'),
(1, '1111', 'Business Checking Account', 'ASSET', 180000.00, 'Main business checking account'),
(1, '1112', 'Business Savings Account', 'ASSET', 50000.00, 'Business savings account'),
(1, '1113', 'Petty Cash', 'ASSET', 20000.00, 'Petty cash fund'),
(1, '1120', 'Accounts Receivable', 'ASSET', 85000.00, 'Trade receivables from clients'),
(1, '1130', 'Inventory', 'ASSET', 120000.00, 'Software and hardware inventory'),
(1, '1140', 'Prepaid Expenses', 'ASSET', 15000.00, 'Prepaid insurance and rent'),
(1, '1200', 'Fixed Assets', 'ASSET', 0.00, 'Long-term assets'),
(1, '1210', 'Computer Equipment', 'ASSET', 150000.00, 'Computers and servers'),
(1, '1211', 'Office Furniture', 'ASSET', 45000.00, 'Office furniture and fixtures'),
(1, '1220', 'Accumulated Depreciation - Equipment', 'ASSET', -35000.00, 'Depreciation on equipment', TRUE),
(1, '1300', 'Intangible Assets', 'ASSET', 0.00, 'Intangible assets'),
(1, '1310', 'Software Licenses', 'ASSET', 25000.00, 'Software and licenses'),

-- Liabilities for TechCorp
(1, '2000', 'LIABILITIES', 'LIABILITY', 0.00, 'Total Liabilities'),
(1, '2100', 'Current Liabilities', 'LIABILITY', 0.00, 'Current obligations'),
(1, '2110', 'Accounts Payable', 'LIABILITY', 45000.00, 'Trade payables to vendors'),
(1, '2120', 'Accrued Expenses', 'LIABILITY', 18000.00, 'Accrued salaries and expenses'),
(1, '2130', 'Taxes Payable', 'LIABILITY', 12000.00, 'Sales and payroll taxes'),
(1, '2140', 'Short-term Debt', 'LIABILITY', 25000.00, 'Short-term loans'),
(1, '2200', 'Long-term Liabilities', 'LIABILITY', 0.00, 'Long-term obligations'),
(1, '2210', 'Bank Loans', 'LIABILITY', 200000.00, 'Long-term bank financing'),

-- Equity for TechCorp
(1, '3000', 'EQUITY', 'EQUITY', 0.00, 'Total Equity'),
(1, '3110', 'Owner''s Capital', 'EQUITY', 300000.00, 'Owner investment'),
(1, '3120', 'Retained Earnings', 'EQUITY', 85000.00, 'Accumulated profits'),
(1, '3130', 'Common Stock', 'EQUITY', 100000.00, 'Common stock issued'),

-- Revenue for TechCorp
(1, '4000', 'REVENUE', 'REVENUE', 0.00, 'Total Revenue'),
(1, '4100', 'Service Revenue', 'REVENUE', 0.00, 'Service income'),
(1, '4110', 'Software Development', 'REVENUE', 0.00, 'Custom software development'),
(1, '4120', 'IT Consulting', 'REVENUE', 0.00, 'IT consulting services'),
(1, '4130', 'Support Contracts', 'REVENUE', 0.00, 'Annual support contracts'),
(1, '4200', 'Product Revenue', 'REVENUE', 0.00, 'Product sales'),
(1, '4210', 'Software Licenses', 'REVENUE', 0.00, 'Off-the-shelf software'),
(1, '4300', 'Other Revenue', 'REVENUE', 0.00, 'Miscellaneous income'),
(1, '4310', 'Training Services', 'REVENUE', 0.00, 'Technical training'),

-- Expenses for TechCorp
(1, '5000', 'EXPENSES', 'EXPENSE', 0.00, 'Total Expenses'),
(1, '5100', 'Cost of Services', 'EXPENSE', 0.00, 'Direct service costs'),
(1, '5110', 'Contractor Costs', 'EXPENSE', 0.00, 'Freelance contractors'),
(1, '5120', 'Software Costs', 'EXPENSE', 0.00, 'Third-party software'),
(1, '5200', 'Operating Expenses', 'EXPENSE', 0.00, 'Operating costs'),
(1, '5210', 'Salaries and Wages', 'EXPENSE', 0.00, 'Employee compensation'),
(1, '5220', 'Office Rent', 'EXPENSE', 0.00, 'Monthly office rent'),
(1, '5230', 'Utilities', 'EXPENSE', 0.00, 'Electric, water, internet'),
(1, '5240', 'Marketing', 'EXPENSE', 0.00, 'Advertising and promotion'),
(1, '5250', 'Professional Services', 'EXPENSE', 0.00, 'Legal and accounting'),
(1, '5300', 'Depreciation Expense', 'EXPENSE', 0.00, 'Asset depreciation'),

-- Global Retail Accounts (Company 2) - Similar structure but retail-focused
(2, '1000', 'ASSETS', 'ASSET', 0.00, 'Total Assets'),
(2, '1100', 'Current Assets', 'ASSET', 0.00, 'Current Assets'),
(2, '1110', 'Cash and Cash Equivalents', 'ASSET', 180000.00, 'Cash and bank accounts'),
(2, '1111', 'Store Cash Accounts', 'ASSET', 45000.00, 'Daily cash from stores'),
(2, '1112', 'Bank Accounts', 'ASSET', 135000.00, 'Business bank accounts'),
(2, '1120', 'Accounts Receivable', 'ASSET', 65000.00, 'Customer credit sales'),
(2, '1130', 'Inventory', 'ASSET', 280000.00, 'Retail inventory'),
(2, '1140', 'Prepaid Expenses', 'ASSET', 12000.00, 'Prepaid rent and insurance'),

-- Digital Marketing Pro Accounts (Company 3) - Service-focused
(3, '1000', 'ASSETS', 'ASSET', 0.00, 'Total Assets'),
(3, '1100', 'Current Assets', 'ASSET', 0.00, 'Current Assets'),
(3, '1110', 'Cash and Cash Equivalents', 'ASSET', 95000.00, 'Cash and bank accounts'),
(3, '1120', 'Accounts Receivable', 'ASSET', 55000.00, 'Client billings'),
(3, '1130', 'Work in Progress', 'ASSET', 35000.00, 'Unbilled work'),

-- Consulting Partners Group Accounts (Company 4) - Inactive company
(4, '1000', 'ASSETS', 'ASSET', 0.00, 'Total Assets'),
(4, '1100', 'Current Assets', 'ASSET', 0.00, 'Current Assets'),
(4, '1110', 'Cash and Cash Equivalents', 'ASSET', 45000.00, 'Cash and bank accounts');

-- Update parent account relationships for TechCorp
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1000' AND company_id = 1) AS temp) WHERE account_code IN ('1100', '1200', '1300') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1100' AND company_id = 1) AS temp) WHERE account_code IN ('1110', '1120', '1130', '1140') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1110' AND company_id = 1) AS temp) WHERE account_code IN ('1111', '1112', '1113') AND company_id = 1;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1200' AND company_id = 1) AS temp) WHERE account_code IN ('1210', '1211', '1220') AND company_id = 1;

-- Similar parent updates for other companies
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1000' AND company_id = 2) AS temp) WHERE account_code IN ('1100') AND company_id = 2;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1100' AND company_id = 2) AS temp) WHERE account_code IN ('1110', '1120', '1130', '1140') AND company_id = 2;

UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1000' AND company_id = 3) AS temp) WHERE account_code IN ('1100') AND company_id = 3;
UPDATE accounts SET parent_account_id = (SELECT account_id FROM (SELECT account_id FROM accounts WHERE account_code = '1100' AND company_id = 3) AS temp) WHERE account_code IN ('1110', '1120', '1130') AND company_id = 3;

-- Insert sample transactions for TechCorp (most recent quarter)
INSERT INTO transactions (company_id, period_id, transaction_number, transaction_date, description, reference, total_amount, status) VALUES
-- Revenue transactions
(1, 3, 'TC-2024-001', '2024-07-05', 'Software Development Project - Client A', 'INV-2024-001', 25000.00, 'posted'),
(1, 3, 'TC-2024-002', '2024-07-12', 'IT Consulting Retainer - Client B', 'INV-2024-002', 8500.00, 'posted'),
(1, 3, 'TC-2024-003', '2024-07-18', 'Support Contract Annual - Client C', 'INV-2024-003', 12000.00, 'posted'),
(1, 3, 'TC-2024-004', '2024-07-25', 'Custom Software License - Client D', 'INV-2024-004', 15000.00, 'posted'),
(1, 3, 'TC-2024-005', '2024-08-02', 'Training Services - Client E', 'INV-2024-005', 3500.00, 'posted'),
(1, 3, 'TC-2024-006', '2024-08-09', 'Emergency IT Support - Client F', 'INV-2024-006', 2200.00, 'posted'),
(1, 3, 'TC-2024-007', '2024-08-15', 'Software Development Phase 2 - Client A', 'INV-2024-007', 18000.00, 'posted'),
(1, 3, 'TC-2024-008', '2024-08-22', 'Network Infrastructure Project - Client G', 'INV-2024-008', 32000.00, 'posted'),
(1, 3, 'TC-2024-009', '2024-08-29', 'Monthly Retainer - Client B', 'INV-2024-009', 8500.00, 'posted'),
(1, 3, 'TC-2024-010', '2024-09-05', 'Database Optimization - Client H', 'INV-2024-010', 7500.00, 'posted'),

-- Expense transactions
(1, 3, 'TC-2024-011', '2024-07-08', 'Office Rent Payment', 'CHK-2024-001', -4500.00, 'posted'),
(1, 3, 'TC-2024-012', '2024-07-10', 'Software Licenses Renewal', 'CHK-2024-002', -1200.00, 'posted'),
(1, 3, 'TC-2024-013', '2024-07-15', 'Payroll Processing', 'ACH-2024-001', -15000.00, 'posted'),
(1, 3, 'TC-2024-014', '2024-07-20', 'Internet and Phone Services', 'CHK-2024-003', -850.00, 'posted'),
(1, 3, 'TC-2024-015', '2024-07-25', 'Marketing Campaign', 'CC-2024-001', -2200.00, 'posted'),
(1, 3, 'TC-2024-016', '2024-08-01', 'Payroll Processing', 'ACH-2024-002', -15000.00, 'posted'),
(1, 3, 'TC-2024-017', '2024-08-05', 'Professional Services - Legal', 'CHK-2024-004', -1800.00, 'posted'),
(1, 3, 'TC-2024-018', '2024-08-10', 'Office Supplies', 'CHK-2024-005', -650.00, 'posted'),
(1, 3, 'TC-2024-019', '2024-08-15', 'Payroll Processing', 'ACH-2024-003', -15000.00, 'posted'),
(1, 3, 'TC-2024-020', '2024-08-20', 'Contractor Payment - Developer', 'CHK-2024-006', -3500.00, 'posted');

-- Insert transaction lines (double-entry bookkeeping)
INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount) VALUES
-- Revenue transaction lines (debits to cash/accounts receivable, credits to revenue)
(1, (SELECT account_id FROM accounts WHERE account_code = '1120' AND company_id = 1), 'Software Development Project - Client A', 25000.00, 0.00),
(1, (SELECT account_id FROM accounts WHERE account_code = '4110' AND company_id = 1), 'Software Development Project - Client A', 0.00, 25000.00),

(2, (SELECT account_id FROM accounts WHERE account_code = '1120' AND company_id = 1), 'IT Consulting Retainer - Client B', 8500.00, 0.00),
(2, (SELECT account_id FROM accounts WHERE account_code = '4120' AND company_id = 1), 'IT Consulting Retainer - Client B', 0.00, 8500.00),

(3, (SELECT account_id FROM accounts WHERE account_code = '1120' AND company_id = 1), 'Support Contract Annual - Client C', 12000.00, 0.00),
(3, (SELECT account_id FROM accounts WHERE account_code = '4130' AND company_id = 1), 'Support Contract Annual - Client C', 0.00, 12000.00),

-- Expense transaction lines (debits to expense accounts, credits to cash/accounts payable)
(11, (SELECT account_id FROM accounts WHERE account_code = '5220' AND company_id = 1), 'Office Rent Payment', 4500.00, 0.00),
(11, (SELECT account_id FROM accounts WHERE account_code = '1111' AND company_id = 1), 'Office Rent Payment', 0.00, 4500.00),

(12, (SELECT account_id FROM accounts WHERE account_code = '5120' AND company_id = 1), 'Software Licenses Renewal', 1200.00, 0.00),
(12, (SELECT account_id FROM accounts WHERE account_code = '1111' AND company_id = 1), 'Software Licenses Renewal', 0.00, 1200.00),

(13, (SELECT account_id FROM accounts WHERE account_code = '5210' AND company_id = 1), 'Payroll Processing', 15000.00, 0.00),
(13, (SELECT account_id FROM accounts WHERE account_code = '1111' AND company_id = 1), 'Payroll Processing', 0.00, 15000.00);

-- Insert sample budgets for Q3 2024
INSERT INTO budgets (company_id, account_id, period_id, budgeted_amount, notes) VALUES
-- TechCorp Q3 2024 Budgets
(1, (SELECT account_id FROM accounts WHERE account_code = '4110' AND company_id = 1), 3, 85000.00, 'Q3 2024 Software Development Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '4120' AND company_id = 1), 3, 35000.00, 'Q3 2024 IT Consulting Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '4130' AND company_id = 1), 3, 25000.00, 'Q3 2024 Support Contracts Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '5210' AND company_id = 1), 3, 45000.00, 'Q3 2024 Salaries Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '5220' AND company_id = 1), 3, 13500.00, 'Q3 2024 Office Rent Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '5230' AND company_id = 1), 3, 2500.00, 'Q3 2024 Utilities Budget'),
(1, (SELECT account_id FROM accounts WHERE account_code = '5240' AND company_id = 1), 3, 8000.00, 'Q3 2024 Marketing Budget'),

-- Global Retail Q3 2024 Budgets
(2, (SELECT account_id FROM accounts WHERE account_code = '4100' AND company_id = 2), 7, 450000.00, 'Q3 2024 Sales Revenue Budget'),
(2, (SELECT account_id FROM accounts WHERE account_code = '5100' AND company_id = 2), 7, 280000.00, 'Q3 2024 Cost of Goods Sold Budget'),
(2, (SELECT account_id FROM accounts WHERE account_code = '5210' AND company_id = 2), 7, 75000.00, 'Q3 2024 Salaries Budget'),

-- Digital Marketing Pro Q3 2024 Budgets
(3, (SELECT account_id FROM accounts WHERE account_code = '4100' AND company_id = 3), 11, 120000.00, 'Q3 2024 Service Revenue Budget'),
(3, (SELECT account_id FROM accounts WHERE account_code = '5210' AND company_id = 3), 11, 35000.00, 'Q3 2024 Salaries Budget'),
(3, (SELECT account_id FROM accounts WHERE account_code = '5240' AND company_id = 3), 11, 15000.00, 'Q3 2024 Marketing Budget');

-- Insert sample audit log entries
INSERT INTO audit_log (company_id, table_name, record_id, action, old_values, new_values, ip_address, user_agent) VALUES
(1, 'companies', 1, 'INSERT', NULL, '{"company_name": "TechCorp Solutions Inc.", "tax_id": "12-3456789"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'transactions', 1, 'INSERT', NULL, '{"transaction_number": "TC-2024-001", "total_amount": 25000.00}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'accounts', 5, 'UPDATE', '{"current_balance": 80000.00}', '{"current_balance": 85000.00}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(2, 'companies', 2, 'INSERT', NULL, '{"company_name": "Global Retail LLC", "tax_id": "98-7654321"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(3, 'companies', 3, 'INSERT', NULL, '{"company_name": "Digital Marketing Pro", "tax_id": "55-1234567"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

-- Update actual amounts in budgets to reflect some activity
UPDATE budgets SET actual_amount = 68000.00 WHERE company_id = 1 AND account_id = (SELECT account_id FROM accounts WHERE account_code = '4110' AND company_id = 1) AND period_id = 3;
UPDATE budgets SET actual_amount = 28000.00 WHERE company_id = 1 AND account_id = (SELECT account_id FROM accounts WHERE account_code = '4120' AND company_id = 1) AND period_id = 3;
UPDATE budgets SET actual_amount = 45000.00 WHERE company_id = 1 AND account_id = (SELECT account_id FROM accounts WHERE account_code = '5210' AND company_id = 1) AND period_id = 3;
UPDATE budgets SET actual_amount = 13500.00 WHERE company_id = 1 AND account_id = (SELECT account_id FROM accounts WHERE account_code = '5220' AND company_id = 1) AND period_id = 3;

-- Display summary
SELECT 'Database seeding completed successfully!' as status;
SELECT COUNT(*) as total_companies FROM companies;
SELECT COUNT(*) as total_accounts FROM accounts;
SELECT COUNT(*) as total_transactions FROM transactions;
SELECT COUNT(*) as total_budgets FROM budgets;