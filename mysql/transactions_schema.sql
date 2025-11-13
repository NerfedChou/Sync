-- Complete transactions table updates
ALTER TABLE transactions 
ADD COLUMN external_source VARCHAR(255) NULL COMMENT 'External source of funds/investor name',
ADD COLUMN transaction_type_id INT NULL COMMENT 'Reference to transaction_types table',
ADD FOREIGN KEY (transaction_type_id) REFERENCES transaction_types(transaction_type_id);