-- Add investor tracking fields to accounts table
ALTER TABLE accounts 
ADD COLUMN investor_name VARCHAR(255) NULL COMMENT 'Investor name for equity accounts',
ADD COLUMN ownership_percentage DECIMAL(5,2) NULL COMMENT 'Ownership percentage for equity accounts';

-- Create transaction_types table
CREATE TABLE transaction_types (
    transaction_type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert basic transaction types
INSERT INTO transaction_types (type_name, description) VALUES
('external_investment', 'External investment from investor creating asset and equity accounts'),
('profit_distribution', 'Distributing profits to equity accounts based on ownership percentages'),
('investor_exit', 'Investor selling equity stake back to company'),
('expense_payment', 'Payment toward expense accounts'),
('income', 'Revenue generation from business operations'),
('transfer', 'Transfer between asset accounts'),
('add_expense', 'Creating new expense obligations'),
('pay_debt', 'Payment toward liability accounts'),
('owner_investment', 'Owner investing personal funds into business'),
('loan_received', 'Company receiving external loan');

-- Update transactions table to support new transaction types
ALTER TABLE transactions 
ADD COLUMN transaction_type_id INT NULL COMMENT 'Reference to transaction_types table',
ADD COLUMN external_source VARCHAR(255) NULL COMMENT 'External source of funds/investor name',
ADD FOREIGN KEY (transaction_type_id) REFERENCES transaction_types(transaction_type_id);