🚀 DevOps Lab
Laravel • Vue • Docker • Nginx • MySQL • Redis • Railway

A production-style DevOps laboratory project demonstrating containerization, service orchestration, reverse proxying, and cloud deployment of a modern full-stack web application.

This repository focuses on infrastructure, automation, and deployment practices rather than application business logic.

🧰 Tech Stack
Backend

Laravel (PHP 8.2 FPM)

Frontend

Vue 3

Vite

Infrastructure

Docker

Docker Compose

Nginx (Reverse Proxy & Web Server)

MySQL

Redis

Cloud & CI/CD

Railway

GitHub Actions

🏗 Architecture Overview

Browser
→ Nginx (Reverse Proxy)
→ Frontend (Vue)
→ Backend (Laravel API)
→ MySQL
→ Redis

Responsibilities

Nginx serves frontend and proxies API requests

Laravel runs as PHP-FPM service

MySQL stores application data

Redis handles cache, sessions, and queues

📁 Repository Structure
devops-lab/
├── backend/
├── frontend/
├── nginx/
├── docker/
├── docker-compose.yml
└── .github/workflows/

▶ Local Development
Requirements

Docker

Docker Compose

Start services
docker compose up --build

Access

Frontend

http://localhost


Backend API

http://localhost/api


phpMyAdmin

http://localhost/phpmyadmin

☁️ Cloud Deployment (Railway)

Deployed services:

Frontend (Docker container)

Backend (Docker container)

MySQL plugin

Redis plugin

Environment variables are injected using Railway Variable References:

DB_HOST=${{MySQL.MYSQLHOST}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

REDIS_URL=${{Redis.REDIS_URL}}


Each service builds directly from GitHub.

🔄 CI/CD (GitHub Actions)

Pipelines automatically:

Install dependencies

Build frontend

Validate backend boot

Build Docker images

This ensures broken builds never reach production.

🔐 Configuration Management

All secrets stored as environment variables

No credentials committed

Separate local and production configurations

🎯 What This Project Demonstrates

Multi-container architecture

Docker multi-stage builds

Reverse proxy with Nginx

Cloud deployment using Railway

CI pipelines for quality control

Infrastructure debugging and optimization

📌 Purpose

This project serves as a DevOps portfolio laboratory, showcasing real-world deployment workflows and infrastructure practices for modern web applications.

👤 Author

Achraf Alfitouri
DevOps Laboratory & Portfolio Project
