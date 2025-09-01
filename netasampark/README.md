# NetaSampark - Political CRM & Campaign Management Platform

A comprehensive, production-ready Political CRM and Campaign Management SaaS platform built with Laravel 12 and React 18.

## 🚀 Features

### Core Platform
- **Multi-tenant Architecture**: Single-tenant SaaS with organization isolation
- **Role-Based Access Control**: Owner, Admin, Manager, Agent, Analyst roles
- **Subscription Management**: Starter/Pro/Enterprise plans with module toggles
- **White-label Branding**: Custom logos, colors, and domains per organization

### Voter CRM
- **Geographic Hierarchy**: State → District → Constituency → Ward → Booth
- **Voter Profiles**: Demographics, householding, influencer tagging
- **Smart Segmentation**: Dynamic lists, saved filters, consent management
- **Bulk Operations**: Import/export, deduplication, validation

### Communication Hub
- **Multi-channel Campaigns**: SMS, WhatsApp, Email, Voice
- **Template Management**: DLT compliance, WhatsApp templates
- **Two-way Communication**: Inbox management, auto-replies
- **Campaign Analytics**: Delivery rates, engagement metrics

### Campaign Management
- **Event Scheduling**: Rallies, meetings, press conferences
- **Volunteer Management**: Task assignment, productivity tracking
- **Survey System**: Voter preferences, issue tracking
- **Field Operations**: Geo-fencing, offline sync, QR codes

### Analytics & Intelligence
- **Real-time Dashboards**: Support index, GOTV progress, funnel analysis
- **Predictive Models**: Turnout likelihood, swing booth identification
- **Performance Metrics**: Cross-channel ROI, volunteer productivity
- **Automated Reports**: Daily briefs, weekly updates

### Financial Management
- **Expense Tracking**: EC compliance, category limits, approval workflows
- **Donor Management**: Receipts, GST compliance, reporting
- **Budget Monitoring**: Soft limits, alerts, forecasting

### Support & Compliance
- **Ticketing System**: SLA management, knowledge base, auto-routing
- **Audit Trails**: User actions, IP logging, compliance exports
- **Data Security**: E2E encryption, PII protection, India-first hosting

## 🛠 Tech Stack

### Backend
- **Laravel 12** - PHP 8.4+ framework
- **PostgreSQL 16** - Primary database with JSONB support
- **Redis 7** - Caching, queues, and sessions
- **Laravel Scout + Meilisearch** - Full-text search
- **S3-compatible Storage** - File management

### Frontend
- **React 18** - Modern UI framework
- **Inertia.js** - SPA-like experience with SSR
- **TailwindCSS** - Utility-first CSS framework
- **shadcn/ui** - Beautiful component library
- **Vite** - Fast build tool

### Messaging & Integrations
- **WhatsApp Business API** - Meta Cloud API integration
- **SMS Gateways** - MSG91, RouteMobile, Gupshup
- **Email Services** - AWS SES with DKIM/SPF
- **Voice/IVR** - Exotel, Twilio integration

### Security & Compliance
- **Laravel Sanctum** - API authentication
- **Spatie Permissions** - RBAC implementation
- **Cloudflare** - WAF, DDoS protection
- **2FA Enforcement** - Admin security

## 📋 Requirements

### System Requirements
- PHP 8.4+
- PostgreSQL 16+
- Redis 7+
- Node.js 20+
- Composer 2.8+

### Server Requirements
- Ubuntu 24.04 LTS (recommended)
- 4GB RAM minimum (8GB recommended)
- 50GB storage minimum
- SSL certificate required

## 🚀 Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd netasampark
```

### 2. Install Dependencies
```bash
# Backend
composer install

# Frontend
npm install
```

### 3. Environment Setup
```bash
cp .env.example .env
# Configure database, Redis, and external services
```

### 4. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 5. Build Frontend
```bash
npm run build
```

### 6. Start Development Server
```bash
php artisan serve
npm run dev
```

## 🔧 Configuration

### Environment Variables
```env
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=netasampark
DB_USERNAME=postgres
DB_PASSWORD=password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# External Services
WHATSAPP_API_KEY=your_whatsapp_key
SMS_GATEWAY_KEY=your_sms_key
AWS_SES_KEY=your_ses_key
```

### Organization Setup
1. Create organization via admin panel
2. Configure branding and modules
3. Set up user roles and permissions
4. Import voter data
5. Configure communication channels

## 📊 Database Schema

### Core Tables
- `organizations` - Multi-tenant isolation
- `users` - User management with RBAC
- `voters` - Voter database with geo-hierarchy
- `campaigns` - Communication campaigns
- `messages` - Message tracking and analytics
- `events` - Campaign events and scheduling
- `surveys` - Voter surveys and feedback
- `tickets` - Support ticket system
- `expenses` - Financial tracking

### Key Relationships
- Organization → Users (1:many)
- Organization → Voters (1:many)
- Organization → Campaigns (1:many)
- Campaign → Messages (1:many)
- User → Tickets (1:many)

## 🔐 Security Features

### Authentication & Authorization
- Multi-factor authentication
- Role-based access control
- API key management
- Session security

### Data Protection
- PII encryption at rest
- E2E encryption in transit
- Audit logging
- Data residency compliance

### Compliance
- TRAI DLT compliance
- Election Commission guidelines
- GDPR readiness
- SOC 2 preparation

## 📈 Performance & Scalability

### Optimization
- Database indexing and partitioning
- Redis caching layers
- CDN integration
- Queue processing

### Monitoring
- Laravel Horizon for queues
- Application performance monitoring
- Error tracking and alerting
- Health checks and uptime monitoring

## 🚀 Deployment

### Production Deployment
```bash
# Build production assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set up supervisor for queues
# Configure nginx/apache
# Set up SSL certificates
```

### Docker Deployment
```bash
docker-compose up -d
```

### Cloud Deployment
- AWS ECS Fargate
- DigitalOcean App Platform
- Laravel Forge
- Vercel (frontend)

## 📚 API Documentation

### Authentication
```bash
POST /api/auth/login
POST /api/auth/logout
POST /api/auth/refresh
```

### Voter Management
```bash
GET /api/voters
POST /api/voters
PUT /api/voters/{id}
DELETE /api/voters/{id}
```

### Campaign Management
```bash
GET /api/campaigns
POST /api/campaigns
PUT /api/campaigns/{id}
POST /api/campaigns/{id}/send
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Documentation**: [docs.netasampark.com](https://docs.netasampark.com)
- **Issues**: [GitHub Issues](https://github.com/netasampark/issues)
- **Email**: support@netasampark.com

## 🎯 Roadmap

### Phase 1 (Current)
- Core CRM functionality
- Basic communication tools
- User management

### Phase 2 (Q2 2025)
- Advanced analytics
- AI-powered insights
- Mobile app

### Phase 3 (Q3 2025)
- Social media integration
- Advanced reporting
- API marketplace

---

**Built with ❤️ for Indian Democracy**
