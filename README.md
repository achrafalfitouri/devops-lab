<div align="center">

# 🚀 DevOps Lab

### Production-Grade Full-Stack Deployment Laboratory

[![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![Vue.js](https://img.shields.io/badge/Vue.js-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org/)
[![Nginx](https://img.shields.io/badge/Nginx-009639?style=for-the-badge&logo=nginx&logoColor=white)](https://nginx.org/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Redis](https://img.shields.io/badge/Redis-DC382D?style=for-the-badge&logo=redis&logoColor=white)](https://redis.io/)

*A comprehensive DevOps laboratory showcasing containerization, orchestration, reverse proxying, and cloud deployment of a modern full-stack application.*

[🎯 Features](#-key-features) • [🏗️ Architecture](#-architecture-overview) • [🚀 Quick Start](#-quick-start) • [☁️ Deployment](#️-cloud-deployment)

</div>

---

## 📋 Table of Contents

- [About](#-about-the-project)
- [Key Features](#-key-features)
- [Tech Stack](#-tech-stack)
- [Architecture](#-architecture-overview)
- [Repository Structure](#-repository-structure)
- [Quick Start](#-quick-start)
- [Local Development](#-local-development)
- [Cloud Deployment](#️-cloud-deployment)
- [CI/CD Pipeline](#-cicd-pipeline)
- [Configuration](#-configuration-management)
- [Monitoring & Logs](#-monitoring--logs)
- [Troubleshooting](#-troubleshooting)
- [Roadmap](#-roadmap)
- [Contributing](#-contributing)
- [License](#-license)
- [Contact](#-contact)

---

## 🎯 About The Project

**DevOps Lab** is a production-style infrastructure project that demonstrates industry-standard DevOps practices, container orchestration, and cloud deployment workflows. This repository prioritizes **infrastructure as code**, **automation**, and **deployment excellence** over application business logic.

### What Makes This Project Special?

✅ **Real-world architecture** - Multi-container setup with proper service separation  
✅ **Production-ready** - Environment-based configuration and secrets management  
✅ **Automated workflows** - CI/CD pipelines with GitHub Actions  
✅ **Cloud-native** - Deployed on Railway with full infrastructure automation  
✅ **Best practices** - Docker multi-stage builds, reverse proxy patterns, caching strategies

---

## 🌟 Key Features

| Feature | Description |
|---------|-------------|
| 🐳 **Dockerized Stack** | Complete containerization with Docker Compose orchestration |
| 🔄 **Reverse Proxy** | Nginx-based routing for frontend/backend separation |
| 📦 **Multi-Stage Builds** | Optimized Docker images for production deployment |
| ☁️ **Cloud Deployment** | Railway integration with automated deployments |
| 🔐 **Secrets Management** | Environment-based configuration (no hardcoded credentials) |
| 🚀 **CI/CD Automation** | GitHub Actions for testing, building, and validation |
| 💾 **Data Persistence** | MySQL for relational data, Redis for caching/sessions |
| 📊 **Database Admin** | phpMyAdmin included for local development |

---

## 🧰 Tech Stack

### **Frontend**
- **Vue 3** - Progressive JavaScript framework
- **Vite** - Next-generation frontend tooling
- **Nginx** - Static file serving

### **Backend**
- **Laravel 10** - PHP framework
- **PHP 8.2 FPM** - FastCGI Process Manager
- **RESTful API** - JSON API architecture

### **Infrastructure**
- **Docker** - Containerization platform
- **Docker Compose** - Multi-container orchestration
- **Nginx** - Reverse proxy & web server
- **MySQL 8** - Relational database
- **Redis 7** - In-memory data store

### **DevOps & Cloud**
- **Railway** - Cloud deployment platform
- **GitHub Actions** - CI/CD automation
- **Docker Hub** - Container registry

---

## 🏗 Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                         BROWSER                             │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    NGINX (Port 80/443)                      │
│              Reverse Proxy & Static Server                  │
└────────┬────────────────────────────┬───────────────────────┘
         │                            │
         │ /                          │ /api/*
         ▼                            ▼
┌──────────────────┐         ┌──────────────────┐
│   Vue Frontend   │         │  Laravel Backend │
│   (Port 5173)    │         │   (Port 9000)    │
│   Static Files   │         │    PHP-FPM       │
└──────────────────┘         └────────┬─────────┘
                                      │
                     ┌────────────────┼────────────────┐
                     │                │                │
                     ▼                ▼                ▼
              ┌───────────┐    ┌──────────┐    ┌──────────┐
              │   MySQL   │    │  Redis   │    │  Queue   │
              │ (Port 3306)│    │(Port 6379)│    │  Worker  │
              └───────────┘    └──────────┘    └──────────┘
```

### Service Responsibilities

| Service | Role | Port |
|---------|------|------|
| **Nginx** | Serves frontend, proxies API requests | 80, 443 |
| **Frontend** | Vue 3 SPA, client-side routing | 5173 |
| **Backend** | Laravel API, business logic | 9000 |
| **MySQL** | Persistent data storage | 3306 |
| **Redis** | Cache, sessions, queue management | 6379 |
| **phpMyAdmin** | Database administration (dev only) | 8080 |

---

## 📁 Repository Structure

```
devops-lab/
├── 📂 backend/              # Laravel API application
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── Dockerfile
│
├── 📂 frontend/             # Vue 3 frontend application
│   ├── src/
│   ├── public/
│   ├── vite.config.js
│   └── Dockerfile
│
├── 📂 nginx/                # Nginx configuration
│   ├── nginx.conf
│   └── default.conf
│
├── 📂 docker/               # Docker-specific configs
│   ├── php/
│   └── mysql/
│
├── 📂 .github/
│   └── workflows/           # CI/CD pipelines
│       ├── backend.yml
│       └── frontend.yml
│
├── docker-compose.yml       # Local development orchestration
├── docker-compose.prod.yml  # Production configuration
├── .env.example             # Environment template
└── README.md
```

---

## 🚀 Quick Start

### Prerequisites

Ensure you have the following installed:

- [Docker](https://docs.docker.com/get-docker/) (v24.0+)
- [Docker Compose](https://docs.docker.com/compose/install/) (v2.20+)
- [Git](https://git-scm.com/downloads)

### Installation

```bash
# Clone the repository
git clone https://github.com/achrafalfitouri/devops-lab.git
cd devops-lab

# Copy environment file
cp .env.example .env

# Build and start all services
docker compose up --build -d

# Check service health
docker compose ps

# View logs
docker compose logs -f
```

### Access Points

| Service | URL | Description |
|---------|-----|-------------|
| 🌐 **Frontend** | [http://localhost](http://localhost) | Vue 3 application |
| 🔌 **Backend API** | [http://localhost/api](http://localhost/api) | Laravel REST API |
| 🗄️ **phpMyAdmin** | [http://localhost:8080](http://localhost:8080) | Database admin panel |
| 📊 **Redis Commander** | [http://localhost:8081](http://localhost:8081) | Redis GUI (optional) |

---

## 🛠 Local Development

### Starting Services

```bash
# Start all services
docker compose up -d

# Start specific service
docker compose up -d backend

# Rebuild after code changes
docker compose up --build
```

### Running Commands

```bash
# Laravel Artisan commands
docker compose exec backend php artisan migrate
docker compose exec backend php artisan cache:clear

# Composer packages
docker compose exec backend composer install

# NPM commands
docker compose exec frontend npm install
docker compose exec frontend npm run dev

# Database access
docker compose exec mysql mysql -u root -p
```

### Development Workflow

1. **Make code changes** in `backend/` or `frontend/`
2. **Hot reload** is enabled for frontend (Vite HMR)
3. **Backend changes** require container restart:
   ```bash
   docker compose restart backend
   ```
4. **Database migrations**:
   ```bash
   docker compose exec backend php artisan migrate
   ```

### Stopping Services

```bash
# Stop all services
docker compose down

# Stop and remove volumes (fresh start)
docker compose down -v

# Stop and remove images
docker compose down --rmi all
```

---

## ☁️ Cloud Deployment

This project is deployed on **[Railway](https://railway.app/)** with automated GitHub integration.

### Deployment Architecture

```
GitHub Push → Railway Webhook → Docker Build → Deploy Services
```

### Services Configuration

| Service | Type | Source |
|---------|------|--------|
| **Frontend** | Docker Container | `frontend/Dockerfile` |
| **Backend** | Docker Container | `backend/Dockerfile` |
| **MySQL** | Railway Plugin | Managed Database |
| **Redis** | Railway Plugin | Managed Cache |

### Environment Variables

Railway uses **Variable References** for service discovery:

```bash
# Backend .env
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

REDIS_HOST=${{Redis.REDISHOST}}
REDIS_PORT=${{Redis.REDISPORT}}
REDIS_PASSWORD=${{Redis.REDISPASSWORD}}

APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}
```

### Manual Deployment Steps

1. **Connect Repository** to Railway
2. **Create Services**:
   - Add MySQL plugin
   - Add Redis plugin
   - Deploy backend (auto-detects Dockerfile)
   - Deploy frontend (auto-detects Dockerfile)
3. **Configure Variables** using Railway's UI
4. **Set Public Domain** for frontend service
5. **Trigger Deploy** via GitHub push

---

## 🔄 CI/CD Pipeline

### GitHub Actions Workflows

#### Backend Pipeline (`.github/workflows/backend.yml`)

```yaml
Trigger: Push to main, Pull requests
Steps:
  ✅ Checkout code
  ✅ Set up PHP 8.2
  ✅ Install Composer dependencies
  ✅ Run Laravel tests
  ✅ Validate artisan boot
  ✅ Build Docker image
  ✅ Push to registry (optional)
```

#### Frontend Pipeline (`.github/workflows/frontend.yml`)

```yaml
Trigger: Push to main, Pull requests
Steps:
  ✅ Checkout code
  ✅ Set up Node.js 18
  ✅ Install npm dependencies
  ✅ Run linter
  ✅ Build production assets
  ✅ Build Docker image
  ✅ Push to registry (optional)
```

### Pipeline Benefits

- 🚫 **Prevents broken builds** from reaching production
- ✅ **Automated testing** on every commit
- 🔍 **Code quality checks** (linting, formatting)
- 🐳 **Image validation** before deployment
- 📊 **Build status badges** for transparency

---

## 🔐 Configuration Management

### Security Best Practices

✅ **No hardcoded credentials** - All secrets in environment variables  
✅ **Separate configurations** - Local vs. production environments  
✅ **Secret scanning** - GitHub secret detection enabled  
✅ **Minimal permissions** - Database users with restricted access  
✅ **HTTPS enforcement** - SSL/TLS in production

### Environment Files

```bash
# Local development
.env                    # Not committed, copied from .env.example

# Production
Railway Variables       # Managed through Railway dashboard
```

### Managing Secrets

```bash
# Local development
cp .env.example .env
# Edit .env with your local credentials

# Production (Railway)
# Add via Railway UI → Variables → New Variable
```

---

## 📊 Monitoring & Logs

### Viewing Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f backend
docker compose logs -f nginx

# Last 100 lines
docker compose logs --tail=100 backend

# With timestamps
docker compose logs -f -t
```

### Health Checks

```bash
# Check service status
docker compose ps

# Check resource usage
docker stats

# Inspect specific container
docker compose exec backend php artisan about
```

### Production Monitoring (Railway)

- **Real-time logs** in Railway dashboard
- **Resource metrics** (CPU, memory, network)
- **Deployment history** and rollback capability
- **Custom alerts** (optional setup)

---

## 🐛 Troubleshooting

### Common Issues

#### Services Won't Start

```bash
# Check for port conflicts
sudo lsof -i :80
sudo lsof -i :3306

# Remove old containers/volumes
docker compose down -v
docker compose up --build
```

#### Database Connection Failed

```bash
# Verify MySQL is running
docker compose ps mysql

# Check credentials
docker compose exec backend php artisan config:clear

# Test connection
docker compose exec mysql mysql -u root -p
```

#### Frontend Not Loading

```bash
# Rebuild frontend
docker compose up --build frontend

# Check Nginx config
docker compose exec nginx nginx -t

# View Nginx logs
docker compose logs nginx
```

#### Permission Issues

```bash
# Fix Laravel storage permissions
docker compose exec backend chmod -R 775 storage bootstrap/cache
docker compose exec backend chown -R www-data:www-data storage bootstrap/cache
```

---

## 🗺 Roadmap

### Current Version (v1.0)
- [x] Docker containerization
- [x] Nginx reverse proxy
- [x] Railway deployment
- [x] Basic CI/CD pipeline

### Planned Features (v2.0)
- [ ] Kubernetes manifests
- [ ] Terraform infrastructure as code
- [ ] Advanced monitoring (Prometheus + Grafana)
- [ ] Automated backups
- [ ] Multi-environment setup (staging, production)
- [ ] Load balancing with multiple replicas
- [ ] SSL/TLS certificate automation
- [ ] Blue-green deployment strategy

### Future Enhancements
- [ ] Integration tests
- [ ] Performance benchmarking
- [ ] Security scanning (Trivy, Snyk)
- [ ] Cost optimization analysis
- [ ] Documentation site

---

## 🤝 Contributing

Contributions are welcome! This is a learning project, and improvements are always appreciated.

### How to Contribute

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/AmazingFeature`)
3. **Commit** your changes (`git commit -m 'Add some AmazingFeature'`)
4. **Push** to the branch (`git push origin feature/AmazingFeature`)
5. **Open** a Pull Request

### Contribution Guidelines

- Follow existing code style and conventions
- Update documentation for any new features
- Add tests where applicable
- Ensure CI/CD pipeline passes
- Keep commits atomic and well-described

---

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

---

## 📧 Contact

**Achraf Alfitouri**

- GitHub: [@achrafalfitouri](https://github.com/achrafalfitouri)
- LinkedIn: [Your LinkedIn](https://linkedin.com/in/your-profile)
- Email: your.email@example.com
- Portfolio: [your-portfolio.com](https://your-portfolio.com)

---

## 🙏 Acknowledgments

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Guide](https://vuejs.org/guide/)
- [Docker Documentation](https://docs.docker.com/)
- [Railway Documentation](https://docs.railway.app/)
- [Nginx Documentation](https://nginx.org/en/docs/)

---

<div align="center">

### ⭐ Star this repository if you find it helpful!

**Built with ❤️ as a DevOps learning laboratory**

</div>
