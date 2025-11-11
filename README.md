# Modern Accounting System

A comprehensive Docker-based accounting system built with PHP, MySQL, and Vue.js featuring double-entry bookkeeping, financial reporting, and real-time analytics.

## Architecture Overview

This system follows a modern microservices architecture using Docker containers:

- **Backend**: PHP 8.3 with RESTful API
- **Database**: MySQL 8.0 with proper accounting schema
- **Frontend**: Vue.js 3 SPA with modern UI
- **Web Server**: Nginx for reverse proxy
- **Development**: Hot-reload with Docker volumes

## Features

### Core Accounting
- ✅ Chart of Accounts management
- ✅ Double-entry bookkeeping system
- ✅ Transaction validation and audit trail
- ✅ Multi-period accounting

### Financial Reports
- ✅ Profit & Loss (Income Statement)
- ✅ Balance Sheet
- ✅ Cash Flow Statement
- ✅ Trial Balance
- ✅ General Ledger

### Analytics & Dashboard
- ✅ Real-time KPIs
- ✅ Interactive charts and graphs
- ✅ Period-over-period comparisons
- ✅ Budget vs Actual analysis

### Modern UI/UX
- ✅ Responsive design (mobile-first)
- ✅ Semantic HTML5 structure
- ✅ CSS custom properties with BEM methodology
- ✅ Modular JavaScript architecture
- ✅ Component-based Vue.js frontend

## Database Schema (ERD)

### Entity Relationship Diagram

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────────┐
│     companies   │    │   accounting_    │    │      accounts       │
│                 │    │     periods      │    │                     │
│ company_id (PK) │    │ period_id (PK)   │    │  account_id (PK)    │
│ company_name    │    │ company_id (FK)  │    │  company_id (FK)    │
│ tax_id          │    │ period_name      │    │  account_code       │
│ address         │    │ start_date       │    │  account_name       │
│ phone           │    │ end_date         │    │  account_type       │
│ email           │    │ is_closed        │    │  parent_account_id  │
│ created_at      │    │ created_at       │    │  opening_balance    │
│ updated_at      │    │ updated_at       │    │  is_active          │
└─────────────────┘    └──────────────────┘    │  created_at         │
         │                       │             │  updated_at         │
         │                       │             └─────────────────────┘
         │                       │                       │
         │                       │                       │
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────────┐
                    │    transactions     │
                    │                     │
                    │ transaction_id (PK) │
                    │ company_id (FK)     │
                    │ period_id (FK)      │
                    │ transaction_number  │
                    │ transaction_date    │
                    │ description         │
                    │ reference           │
                    │ total_amount        │
                    │ status              │
                    │ created_at          │
                    │ updated_at          │
                    └─────────────────────┘
                                 │
                                 │
                    ┌─────────────────────┐
                    │ transaction_lines   │
                    │                     │
                    │ line_id (PK)        │
                    │ transaction_id (FK) │
                    │ account_id (FK)      │
                    │ description         │
                    │ debit_amount        │
                    │ credit_amount       │
                    │ created_at          │
                    └─────────────────────┘
                                 │
                                 │
                    ┌─────────────────────┐
                    │        users        │
                    │                     │
                    │ user_id (PK)        │
                    │ company_id (FK)     │
                    │ username            │
                    │ email               │
                    │ password_hash       │
                    │ role                │
                    │ is_active           │
                    │ last_login          │
                    │ created_at          │
                    │ updated_at          │
                    └─────────────────────┘
```

### Table Relationships

1. **companies** → One-to-Many → **accounts**, **transactions**, **users**, **accounting_periods**
2. **accounting_periods** → One-to-Many → **transactions**
3. **transactions** → One-to-Many → **transaction_lines**
4. **accounts** → One-to-Many → **transaction_lines**

### Account Types

- **ASSET** (1000-1999): Cash, Accounts Receivable, Inventory, Fixed Assets
- **LIABILITY** (2000-2999): Accounts Payable, Loans, Accrued Expenses
- **EQUITY** (3000-3999): Owner's Equity, Retained Earnings
- **REVENUE** (4000-4999): Sales Revenue, Service Revenue, Other Income
- **EXPENSE** (5000-5999): Cost of Goods Sold, Operating Expenses, Other Expenses

## Quick Start

### Prerequisites
- Docker & Docker Compose
- Git

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd accounting-system
   ```

2. **Start the containers**
   ```bash
   docker-compose up -d
   ```

3. **Install PHP dependencies**
   ```bash
   docker-compose exec php composer install
   ``4. **Install frontend dependencies**
   ```bash
   docker-compose exec frontend npm install
   ```

5. **Access the application**
   - Frontend: http://localhost:3000
   - API: http://localhost:8080/api
   - phpMyAdmin: http://localhost:8081

### Development

#### Hot Reload
- PHP changes: Auto-reload via Docker volumes
- Frontend changes: Vite dev server with HMR
- Database: Persistent data in Docker volume

#### Database Management
- phpMyAdmin: http://localhost:8081
- Credentials: accounting_user / accounting_pass_123

#### API Documentation
- Swagger UI: http://localhost:8080/api/docs
- OpenAPI spec: http://localhost:8080/api/docs.json

## Project Structure

```
accounting-system/
├── docker-compose.yml          # Docker orchestration
├── README.md                   # This file
├── 
├── php/                        # PHP Backend
│   ├── Dockerfile             # PHP container config
│   ├── php.ini                # PHP configuration
│   ├── composer.json          # PHP dependencies
│   └── src/                   # Source code
│       ├── api/               # API endpoints
│       ├── models/            # Data models
│       ├── services/          # Business logic
│       ├── middleware/        # Authentication/validation
│       └── config/            # Configuration files
│
├── mysql/                     # Database
│   └── init.sql              # Initial schema & data
│
├── nginx/                     # Web Server
│   ├── Dockerfile            # Nginx container config
│   └── nginx.conf            # Nginx configuration
│
└── frontend/                  # Vue.js Frontend
    ├── Dockerfile            # Frontend container config
    ├── package.json          # Node.js dependencies
    ├── vite.config.js        # Vite configuration
    ├── public/               # Static assets
    └── src/                  # Source code
        ├── components/       # Vue components
        ├── views/           # Page views
        ├── services/        # API services
        ├── utils/           # Utility functions
        └── styles/          # CSS/SCSS files
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `POST /api/auth/refresh` - Refresh token

### Chart of Accounts
- `GET /api/accounts` - List all accounts
- `POST /api/accounts` - Create new account
- `GET /api/accounts/{id}` - Get account details
- `PUT /api/accounts/{id}` - Update account
- `DELETE /api/accounts/{id}` - Delete account

### Transactions
- `GET /api/transactions` - List transactions
- `POST /api/transactions` - Create transaction
- `GET /api/transactions/{id}` - Get transaction details
- `PUT /api/transactions/{id}` - Update transaction
- `DELETE /api/transactions/{id}` - Delete transaction

### Reports
- `GET /api/reports/profit-loss` - Profit & Loss statement
- `GET /api/reports/balance-sheet` - Balance Sheet
- `GET /api/reports/cash-flow` - Cash Flow statement
- `GET /api/reports/trial-balance` - Trial Balance

### Analytics
- `GET /api/analytics/dashboard` - Dashboard metrics
- `GET /api/analytics/revenue-trends` - Revenue trends
- `GET /api/analytics/expense-analysis` - Expense analysis

## Technology Stack

### Backend
- **PHP 8.3**: Modern PHP with type declarations
- **Slim Framework**: Lightweight REST API framework
- **Eloquent ORM**: Database abstraction layer
- **Firebase JWT**: Authentication tokens
- **Respect/Validation**: Input validation

### Frontend
- **Vue.js 3**: Progressive JavaScript framework
- **Vite**: Fast development build tool
- **Tailwind CSS**: Utility-first CSS framework
- **Chart.js**: Data visualization
- **Axios**: HTTP client for API calls

### Infrastructure
- **Docker**: Containerization platform
- **MySQL 8.0**: Relational database
- **Nginx**: High-performance web server
- **phpMyAdmin**: Database administration tool

## Security Features

- JWT-based authentication
- Input validation and sanitization
- SQL injection prevention
- CORS configuration
- Rate limiting
- Password hashing with bcrypt
- HTTPS ready (SSL certificates)

## Testing

### Backend Tests
```bash
docker-compose exec php vendor/bin/phpunit
```

### Frontend Tests
```bash
docker-compose exec frontend npm run test
```

## Deployment

### Production Deployment
1. Configure environment variables
2. Build production images
3. Run with Docker Compose
4. Set up reverse proxy with SSL
5. Configure backup strategy

### Environment Variables
```bash
# Database
DB_HOST=mysql
DB_NAME=accounting_system
DB_USER=accounting_user
DB_PASSWORD=accounting_pass_123

# JWT
JWT_SECRET=your_jwt_secret_key
JWT_EXPIRY=3600

# Application
APP_ENV=production
APP_DEBUG=false
```

## Contributing

1. Fork the repository
2. Create feature branch
3. Make your changes
4. Add tests
5. Submit pull request

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

## Support

For support and questions:
- Create an issue in the repository
- Check the documentation
- Review the API docs at `/api/docs`

---

**Note**: This is a comprehensive accounting system designed for educational and small business use. Always consult with accounting professionals for financial compliance and regulatory requirements.