-- Migration to align database structure with simplified ERD requirements
-- This script will modify existing tables to match the specified structure

USE accounting_system;

-- Update companies table to match simplified structure
ALTER TABLE companies 
ADD COLUMN contact VARCHAR(255) AFTER phone,
MODIFY COLUMN company_name VARCHAR(255) NOT NULL COMMENT 'Company Name',
MODIFY COLUMN tax_id VARCHAR(50) NULL COMMENT 'Tax ID',
MODIFY COLUMN currency_code VARCHAR(3) NOT NULL DEFAULT 'USD' COMMENT 'Currency',
MODIFY COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Status (1=active, 0=inactive)',
ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'Status as text';

-- Update the contact field with existing email/phone info
UPDATE companies SET contact = CONCAT(phone, ' | ', email) WHERE phone IS NOT NULL AND email IS NOT NULL;
UPDATE companies SET contact = phone WHERE phone IS NOT NULL AND (email IS NULL OR contact IS NULL);
UPDATE companies SET contact = email WHERE email IS NOT NULL AND contact IS NULL;

-- Create simplified accounts table structure
ALTER TABLE accounts
ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'Status as text' AFTER is_active,
MODIFY COLUMN account_name VARCHAR(255) NOT NULL COMMENT 'Account Name',
MODIFY COLUMN account_type ENUM('ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE') NOT NULL COMMENT 'Type',
MODIFY COLUMN current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Balance';

-- Create a new simplified transactions table that matches the requirements
CREATE TABLE IF NOT EXISTS transactions_simple (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Transaction Name',
    date DATE NOT NULL COMMENT 'Transaction Date',
    description TEXT COMMENT 'Description',
    category VARCHAR(100) COMMENT 'Category',
    account VARCHAR(255) NOT NULL COMMENT 'Account',
    amount DECIMAL(15,2) NOT NULL COMMENT 'Amount',
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'Status',
    company_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(company_id),
    INDEX idx_date (date),
    INDEX idx_status (status),
    INDEX idx_account (account),
    INDEX idx_company (company_id)
);

-- Insert some sample data into the new simplified transactions table
INSERT INTO transactions_simple (name, date, description, category, account, amount, status, company_id) VALUES
('Office Rent Payment', '2025-01-01', 'Monthly office rent for January', 'Operating Expenses', 'Rent Expense', 2500.00, 'completed', 1),
('Software License', '2025-01-05', 'Annual software license renewal', 'Software Expenses', 'Software Expense', 1200.00, 'completed', 1),
('Client Payment', '2025-01-10', 'Payment from ABC Corp for consulting services', 'Service Revenue', 'Accounts Receivable', 8500.00, 'completed', 1),
('Office Supplies', '2025-01-15', 'Purchase of office supplies and stationery', 'Office Expenses', 'Office Supplies Expense', 350.00, 'pending', 1),
('Utilities Payment', '2025-01-20', 'Electricity and water bills for January', 'Utilities', 'Utilities Expense', 680.00, 'completed', 1),
('Salary Payment', '2025-01-25', 'Monthly salary for staff', 'Payroll', 'Salaries Expense', 12500.00, 'pending', 1),
('Equipment Purchase', '2025-01-28', 'New laptop for development team', 'Capital Expenses', 'Equipment Asset', 2200.00, 'pending', 1),
('Consulting Revenue', '2025-01-30', 'Revenue from XYZ Corp consulting project', 'Service Revenue', 'Service Revenue', 15000.00, 'completed', 1);

-- Create views for simplified access
CREATE OR REPLACE VIEW companies_simple AS
SELECT 
    company_id as id,
    company_name as "Company Name",
    tax_id as "Tax ID",
    contact as "Contact",
    currency_code as "Currency",
    status as "Status",
    created_at as "Created"
FROM companies WHERE is_active = 1;

CREATE OR REPLACE VIEW accounts_simple AS
SELECT 
    account_id as id,
    account_name as "Account Name",
    account_type as "Type",
    current_balance as "Balance",
    status as "Status"
FROM accounts WHERE is_active = 1;

CREATE OR REPLACE VIEW transactions_simple_view AS
SELECT 
    id,
    name as "Name",
    date as "Date",
    description as "Description",
    category as "Category",
    account as "Account",
    amount as "Amount",
    status as "Status"
FROM transactions_simple;

-- Update the existing data to ensure consistency
UPDATE companies SET status = 'active' WHERE is_active = 1;
UPDATE companies SET status = 'inactive' WHERE is_active = 0;

UPDATE accounts SET status = 'active' WHERE is_active = 1;
UPDATE accounts SET status = 'inactive' WHERE is_active = 0;

COMMIT;