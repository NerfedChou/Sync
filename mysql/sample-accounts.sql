-- Insert sample accounts data
USE accounting_system;

INSERT INTO accounts (company_id, account_code, account_name, account_type, current_balance, is_active, status, description) VALUES
-- Assets
(1, '1001', 'Cash and Bank Accounts', 'ASSET', 25000.00, 1, 'active', 'Physical cash and bank deposits'),
(1, '1002', 'Accounts Receivable', 'ASSET', 15000.00, 1, 'active', 'Money owed by customers'),
(1, '1003', 'Inventory', 'ASSET', 8000.00, 1, 'active', 'Goods held for sale'),
(1, '1004', 'Equipment', 'ASSET', 12000.00, 1, 'active', 'Office and computer equipment'),
(1, '1005', 'Furniture and Fixtures', 'ASSET', 5000.00, 1, 'active', 'Office furniture and fixtures'),

-- Liabilities
(1, '2001', 'Accounts Payable', 'LIABILITY', 8500.00, 1, 'active', 'Money owed to suppliers'),
(1, '2002', 'Accrued Expenses', 'LIABILITY', 3200.00, 1, 'active', 'Expenses incurred but not yet paid'),
(1, '2003', 'Short-term Loans', 'LIABILITY', 10000.00, 1, 'active', 'Loans due within one year'),
(1, '2004', 'Long-term Loans', 'LIABILITY', 25000.00, 1, 'active', 'Loans due after one year'),

-- Equity
(1, '3001', 'Owner''s Capital', 'EQUITY', 50000.00, 1, 'active', 'Initial and additional capital contributions'),
(1, '3002', 'Retained Earnings', 'EQUITY', 12000.00, 1, 'active', 'Accumulated profits retained in business'),

-- Revenue
(1, '4001', 'Service Revenue', 'REVENUE', 0.00, 1, 'active', 'Income from consulting services'),
(1, '4002', 'Product Sales', 'REVENUE', 0.00, 1, 'active', 'Income from product sales'),
(1, '4003', 'Interest Income', 'REVENUE', 0.00, 1, 'active', 'Interest earned on investments'),

-- Expenses
(1, '5001', 'Cost of Goods Sold', 'EXPENSE', 0.00, 1, 'active', 'Direct costs of goods sold'),
(1, '5002', 'Salaries and Wages', 'EXPENSE', 0.00, 1, 'active', 'Employee salaries and wages'),
(1, '5003', 'Rent Expense', 'EXPENSE', 0.00, 1, 'active', 'Office and facility rent'),
(1, '5004', 'Utilities Expense', 'EXPENSE', 0.00, 1, 'active', 'Electricity, water, gas bills'),
(1, '5005', 'Office Supplies Expense', 'EXPENSE', 0.00, 1, 'active', 'Office supplies and stationery'),
(1, '5006', 'Software Expense', 'EXPENSE', 0.00, 1, 'active', 'Software licenses and subscriptions'),
(1, '5007', 'Marketing Expense', 'EXPENSE', 0.00, 1, 'active', 'Marketing and advertising costs'),
(1, '5008', 'Insurance Expense', 'EXPENSE', 0.00, 1, 'active', 'Business insurance premiums'),
(1, '5009', 'Professional Fees', 'EXPENSE', 0.00, 1, 'active', 'Legal and accounting fees'),
(1, '5010', 'Travel Expense', 'EXPENSE', 0.00, 1, 'active', 'Business travel costs');

COMMIT;