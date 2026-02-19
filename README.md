🚀 DevOps Lab – Laravel + Vue + Docker + Nginx + MySQL + Redis (Railway)

A production-style DevOps laboratory project demonstrating containerization, service orchestration, reverse proxying, and cloud deployment of a modern full-stack web application.

This project focuses on infrastructure, automation, and deployment practices rather than application business features.

TECH STACK

Backend

Laravel (PHP 8.2 FPM)

Frontend

Vue 3 + Vite

Infrastructure

Docker & Docker Compose

Nginx (Reverse Proxy & Frontend Server)

MySQL

Redis

Cloud & CI/CD

Railway (hosting)

GitHub Actions (CI pipelines)

ARCHITECTURE OVERVIEW

Browser
→ Nginx (Reverse Proxy)
→ Frontend (Vue)
→ Backend (Laravel API)
→ MySQL
→ Redis

Frontend served by Nginx

Backend runs in PHP-FPM container

MySQL handles persistent data

Redis handles cache & sessions

Nginx routes traffic between services

REPOSITORY STRUCTURE

devops-lab/
├── backend/
├── frontend/
├── nginx/
├── docker/
├── docker-compose.yml
└── .github/workflows/

LOCAL DEVELOPMENT (DOCKER COMPOSE)

Requirements

Docker

Docker Compose

Start all services
docker compose up --build

Access
Frontend: http://localhost

Backend API: http://localhost/api

phpMyAdmin: http://localhost/phpmyadmin

CLOUD DEPLOYMENT (RAILWAY)

Services deployed on Railway:

Frontend Service (Docker)

Backend Service (Docker)

MySQL Plugin

Redis Plugin

Environment variables are injected using Railway Variable References, for example:

DB_HOST=${{MySQL.MYSQLHOST}}
REDIS_URL=${{Redis.REDIS_URL}}

Each service builds directly from GitHub.

CI/CD (GITHUB ACTIONS)

Pipelines automatically:

Install dependencies

Build frontend

Validate backend boot

Build Docker images

Ensures broken builds never reach production.

CONFIGURATION MANAGEMENT

Environment variables for all secrets

No credentials committed to repository

Separate configs for local and production

WHAT THIS PROJECT DEMONSTRATES

Multi-container application design

Docker multi-stage builds

Reverse proxy with Nginx

Infrastructure-aware environment configuration

Cloud deployment using managed services

CI pipelines for build verification

Debugging and operational problem-solving

PURPOSE

This repository serves as a DevOps portfolio project showcasing real-world deployment workflows and infrastructure practices for modern web applications.

AUTHOR

Achraf Alfitouri
DevOps Laboratory & Portfolio Project