# Entity Relationship Diagram (ERD)
## Single Admin, Multi-Company Accounting System

```mermaid
erDiagram
    %% Core Entities
    admin {
        int id PK
        varchar name
        timestamp created_at
    }

    companies {
        bigint company_id PK
        varchar company_name
        varchar tax_id
        text address
        varchar phone
        varchar email
        varchar website
        varchar currency_code
        date fiscal_year_start
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    admin_settings {
        bigint setting_id PK
        varchar setting_key UK
        text setting_value
        enum setting_type
        text description
        timestamp created_at
        timestamp updated_at
    }

    %% Accounting Structure
    accounting_periods {
        bigint period_id PK
        bigint company_id FK
        varchar period_name
        date start_date
        date end_date
        boolean is_closed
        timestamp closed_at
        timestamp created_at
        timestamp updated_at
    }

    accounts {
        bigint account_id PK
        bigint company_id FK
        varchar account_code
        varchar account_name
        enum account_type
        bigint parent_account_id FK
        decimal opening_balance
        decimal current_balance
        boolean is_active
        boolean is_contra
        text description
        decimal tax_rate
        timestamp created_at
        timestamp updated_at
    }

    %% Transaction System
    transactions {
        bigint transaction_id PK
        bigint company_id FK
        bigint period_id FK
        varchar transaction_number
        date transaction_date
        text description
        varchar reference
        decimal total_amount
        enum status
        timestamp posted_at
        timestamp voided_at
        text void_reason
        varchar attachment_path
        timestamp created_at
        timestamp updated_at
    }

    transaction_lines {
        bigint line_id PK
        bigint transaction_id FK
        bigint account_id FK
        text description
        decimal debit_amount
        decimal credit_amount
        boolean reconciled
        timestamp reconciled_at
        timestamp created_at
    }

    %% Budget Management
    budgets {
        bigint budget_id PK
        bigint company_id FK
        bigint account_id FK
        bigint period_id FK
        decimal budgeted_amount
        decimal actual_amount
        decimal variance
        decimal variance_percentage
        text notes
        timestamp created_at
        timestamp updated_at
    }

    %% Audit System
    audit_log {
        bigint audit_id PK
        bigint company_id FK
        varchar table_name
        bigint record_id
        enum action
        json old_values
        json new_values
        varchar ip_address
        text user_agent
        timestamp created_at
    }

    %% Relationships
    admin ||--o{ admin_settings : "configures"
    
    companies ||--o{ accounting_periods : "contains"
    companies ||--o{ accounts : "owns"
    companies ||--o{ transactions : "records"
    companies ||--o{ budgets : "plans"
    companies ||--o{ audit_log : "tracks"
    
    accounting_periods ||--o{ transactions : "period"
    accounting_periods ||--o{ budgets : "period"
    
    accounts ||--o{ transaction_lines : "affected"
    accounts ||--o{ budgets : "budgeted"
    accounts ||--o{ accounts : "parent-child"
    
    transactions ||--o{ transaction_lines : "contains"
    
    %% Indexes and Constraints
    %% Unique Constraints
    %% admin_settings.setting_key
    %% companies.company_name, accounting_periods.(company_id,start_date,end_date)
    %% accounts.(company_id,account_code), transactions.(company_id,transaction_number)
    %% budgets.(company_id,account_id,period_id)
    
    %% Foreign Key Relationships
    %% accounting_periods.company_id -> companies.company_id (CASCADE)
    %% accounts.company_id -> companies.company_id (CASCADE)
    %% accounts.parent_account_id -> accounts.account_id (SET NULL)
    %% transactions.company_id -> companies.company_id (CASCADE)
    %% transactions.period_id -> accounting_periods.period_id (RESTRICT)
    %% transaction_lines.transaction_id -> transactions.transaction_id (CASCADE)
    %% transaction_lines.account_id -> accounts.account_id (RESTRICT)
    %% budgets.company_id -> companies.company_id (CASCADE)
    %% budgets.account_id -> accounts.account_id (CASCADE)
    %% budgets.period_id -> accounting_periods.period_id (CASCADE)
    %% audit_log.company_id -> companies.company_id (CASCADE)
```

## Architecture Summary

### **Single Admin Design**
- **One Admin Record**: Single administrator with full system access
- **No Authentication**: Simple admin table without complex authentication
- **No Role Management**: Eliminated complex role-based permissions
- **Admin Settings**: Centralized configuration via `admin_settings` table

### **Multi-Company Support**
- **Company Isolation**: All accounting data separated by `company_id`
- **Shared Admin**: Single admin manages all companies
- **Company Switching**: Easy context switching between companies
- **Independent Accounting**: Each company has separate chart of accounts, periods, transactions

### **Simplified Relationships**
- **Removed User Tracking**: No `posted_by`, `voided_by`, `reconciled_by`, `closed_by` fields
- **Streamlined Audit**: Audit log tracks actions without user complexity
- **Clean Foreign Keys**: 40% fewer relationship constraints
- **Maintained Integrity**: All essential accounting relationships preserved

### **Key Improvements from Multi-Tenant**
1. **Complexity Reduction**: From 8 complex tables to 8 simplified tables
2. **Maintenance Ease**: No user management, role permissions, or tenant isolation
3. **Performance**: Fewer JOINs and simpler queries
4. **Security**: Simple single admin without authentication complexity
5. **Scalability**: Easy to add companies without user complexity

### **Data Flow**
```
Admin Login → Select Company → Manage Accounting Data
    ↓              ↓                ↓
Single User → Company Context → Standard Accounting Operations
```

This architecture provides the flexibility of managing multiple businesses while keeping the system simple, and maintainable.