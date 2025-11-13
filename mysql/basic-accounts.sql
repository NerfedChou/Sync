-- Basic accounts for testing
USE accounting_system;

INSERT INTO accounts (company_id, account_code, account_name, account_type, opening_balance, current_balance, is_active, created_at) VALUES
(1, '1110', 'Cash and Cash Equivalents', 'ASSET', 250000.00, 250000.00, 1, NOW()),
(1, '1120', 'Accounts Receivable', 'ASSET', 85000.00, 85000.00, 1, NOW()),
(1, '2110', 'Accounts Payable', 'LIABILITY', 45000.00, 45000.00, 1, NOW()),
(1, '3110', 'Owner''s Capital', 'EQUITY', 300000.00, 300000.00, 1, NOW()),
(1, '4100', 'Service Revenue', 'REVENUE', 0.00, 1175000.00, 1, NOW()),
(1, '5110', 'Rent Expense', 'EXPENSE', 0.00, 125000.00, 1, NOW()),
(1, '5120', 'Salaries Expense', 'EXPENSE', 0.00, 625000.00, 1, NOW()),
(1, '5130', 'Utilities Expense', 'EXPENSE', 0.00, 34000.00, 1, NOW()),
(1, '5140', 'Office Supplies Expense', 'EXPENSE', 0.00, 17500.00, 1, NOW()),
(1, '5150', 'Software Expense', 'EXPENSE', 0.00, 60000.00, 1, NOW());