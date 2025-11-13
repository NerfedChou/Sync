-- Sample transactions with unique numbers
USE accounting_system;

INSERT INTO transactions (company_id, period_id, transaction_number, transaction_date, description, total_amount, status, created_at) VALUES
(1, 1, 'TXN-2025-001', '2025-01-10', 'Client payment for services', 425000.00, 'posted', NOW()),
(1, 1, 'TXN-2025-002', '2025-01-30', 'Consulting revenue', 750000.00, 'posted', NOW()),
(1, 1, 'TXN-2025-003', '2025-01-01', 'Office rent payment', 125000.00, 'posted', NOW()),
(1, 1, 'TXN-2025-004', '2025-01-05', 'Software license', 60000.00, 'posted', NOW()),
(1, 1, 'TXN-2025-005', '2025-01-15', 'Office supplies', 17500.00, 'posted', NOW()),
(1, 1, 'TXN-2025-006', '2025-01-20', 'Utilities payment', 34000.00, 'posted', NOW()),
(1, 1, 'TXN-2025-007', '2025-01-25', 'Salary payment', 625000.00, 'posted', NOW());

-- Insert transaction lines
INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount) VALUES
-- Revenue transactions
(1, 5, 'Service revenue', 0, 425000.00),
(2, 5, 'Service revenue', 0, 750000.00),
-- Expense transactions  
(3, 6, 'Office rent', 125000.00, 0),
(4, 10, 'Software expense', 60000.00, 0),
(5, 9, 'Office supplies', 17500.00, 0),
(6, 8, 'Utilities expense', 34000.00, 0),
(7, 7, 'Salaries expense', 625000.00, 0);