-- Create transaction_types table for external investment and other transaction types
CREATE TABLE IF NOT EXISTS transaction_types (
    transaction_type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default transaction types
INSERT IGNORE INTO transaction_types (type_name, description) VALUES
('external_investment', 'External investment from investors'),
('micro_transaction', 'Micro transaction between accounts'),
('profit_distribution', 'Profit distribution to investors'),
('investor_exit', 'Investor buyout/exit'),
('investor_asset_protection', 'Investor asset protection'),
('standard_transaction', 'Standard accounting transaction');