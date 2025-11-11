USE accounting_system;

-- Clear existing data
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM transaction_lines;
DELETE FROM transactions;
DELETE FROM accounts;
DELETE FROM companies;
SET FOREIGN_KEY_CHECKS = 1;

-- Insert companies
INSERT INTO companies (company_name, tax_id, address, phone, email, website, currency_code, fiscal_year_start, is_active) VALUES
('TechCorp Solutions Inc.', '12-3456789', '123 Innovation Drive, Palo Alto, CA 94301', '+1-650-555-0123', 'contact@techcorp.com', 'https://techcorp.com', 'USD', '2024-01-01', true),
('Global Retail LLC', '98-7654321', '456 Commerce Boulevard, New York, NY 10001', '+1-212-555-0456', 'info@globalretail.com', 'https://globalretail.com', 'USD', '2024-01-01', true),
('Digital Marketing Pro', '55-1234567', '789 Creative Avenue, Austin, TX 78701', '+1-512-555-0789', 'hello@digitalmarketingpro.com', 'https://digitalmarketingpro.com', 'USD', '2024-01-01', true),
('Consulting Partners Group', '77-9876543', '321 Executive Plaza, Chicago, IL 60601', '+1-312-555-0321', 'admin@consultingpartners.com', 'https://consultingpartners.com', 'USD', '2024-01-01', false);

-- Insert accounts for each company
INSERT INTO accounts (company_id, account_name, account_type, account_code, description, is_active) VALUES
-- TechCorp Accounts (6)
(6, 'Business Checking', 'ASSET', '1001', 'Primary business checking account', true),
(6, 'Business Savings', 'ASSET', '1002', 'Business savings account', true),
(6, 'Accounts Receivable', 'ASSET', '1100', 'Money owed by customers', true),
(6, 'Office Equipment', 'ASSET', '1500', 'Office equipment and furniture', true),
(6, 'Accounts Payable', 'LIABILITY', '2001', 'Money owed to vendors', true),
(6, 'Business Loan', 'LIABILITY', '2100', 'Business bank loan', true),
(6, 'Owner''s Equity', 'EQUITY', '3000', 'Owner''s investment', true),
(6, 'Service Revenue', 'REVENUE', '4000', 'Revenue from services', true),
(6, 'Product Sales', 'REVENUE', '4100', 'Revenue from product sales', true),
(6, 'Office Rent', 'EXPENSE', '5001', 'Monthly office rent', true),
(6, 'Salaries', 'EXPENSE', '5002', 'Employee salaries', true),
(6, 'Utilities', 'EXPENSE', '5003', 'Office utilities', true),

-- Global Retail Accounts (7)
(7, 'Retail Checking', 'ASSET', '1001', 'Primary retail checking account', true),
(7, 'Inventory', 'ASSET', '1200', 'Retail inventory', true),
(7, 'Accounts Payable', 'LIABILITY', '2001', 'Money owed to suppliers', true),

-- Digital Marketing Pro Accounts (8)
(8, 'Marketing Revenue', 'REVENUE', '4000', 'Revenue from marketing services', true),
(8, 'Advertising Costs', 'EXPENSE', '5001', 'Advertising expenses', true);

-- Insert some sample transactions
INSERT INTO transactions (company_id, transaction_date, description, total_amount, status) VALUES
(6, '2024-11-01', 'Client payment for website development', 5000.00, 'posted'),
(6, '2024-11-05', 'Office rent payment', 2000.00, 'posted'),
(6, '2024-11-10', 'Software purchase', 500.00, 'posted'),
(7, '2024-11-02', 'Inventory purchase', 3000.00, 'posted'),
(7, '2024-11-08', 'Sales revenue', 4500.00, 'posted'),
(8, '2024-11-03', 'Marketing campaign payment', 2500.00, 'posted'),
(8, '2024-11-12', 'Ad spend', 800.00, 'posted'),
(9, '2024-11-01', 'Consulting fee received', 3500.00, 'posted');

-- Insert transaction lines (double-entry bookkeeping)
INSERT INTO transaction_lines (transaction_id, account_id, debit_amount, credit_amount) VALUES
-- Transaction 1: Client payment (debit cash, credit revenue)
(1, 1, 5000.00, 0.00),  -- Business Checking
(1, 8, 0.00, 5000.00),  -- Service Revenue

-- Transaction 2: Office rent (debit expense, credit cash)
(2, 11, 2000.00, 0.00), -- Office Rent
(2, 1, 0.00, 2000.00),  -- Business Checking

-- Transaction 3: Software purchase (debit expense, credit cash)
(3, 11, 500.00, 0.00),  -- Office Rent (using as general expense)
(3, 1, 0.00, 500.00),   -- Business Checking

-- Transaction 4: Inventory purchase (debit inventory, credit cash)
(4, 14, 3000.00, 0.00), -- Inventory
(4, 13, 0.00, 3000.00), -- Retail Checking

-- Transaction 5: Sales revenue (debit cash, credit revenue)
(5, 13, 4500.00, 0.00), -- Retail Checking
(5, 9, 0.00, 4500.00),  -- Product Sales

-- Transaction 6: Marketing payment (debit cash, credit revenue)
(6, 1, 2500.00, 0.00),  -- Business Checking
(6, 15, 0.00, 2500.00), -- Marketing Revenue

-- Transaction 7: Ad spend (debit expense, credit cash)
(7, 16, 800.00, 0.00),  -- Advertising Costs
(7, 1, 0.00, 800.00),   -- Business Checking

-- Transaction 8: Consulting fee (debit cash, credit revenue)
(8, 1, 3500.00, 0.00),  -- Business Checking
(8, 8, 0.00, 3500.00);  -- Service Revenue