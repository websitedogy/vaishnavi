# DOGY ePAPER SaaS – AWS Production Deployment Guide

## 1. Prerequisites & Tooling

To deploy the DOGY ePAPER Super Admin platform to AWS in production, ensure you have:
1. **AWS CLI v2** configured with Administrator IAM credentials in `ap-south-1` (Mumbai).
2. **Terraform CLI >= 1.5.0**.
3. **Docker Engine & Docker Compose** for image building and container push.
4. Registered platform domain (e.g. `dogyepaper.com`) hosted in AWS Route 53.

---

## 2. Infrastructure Provisioning via Terraform

### Step 1: Initialize Terraform State
```bash
cd infrastructure/terraform
terraform init
```

### Step 2: Validate Plan
```bash
terraform plan -out=tfplan
```
Review the provisioned AWS resources:
- Custom VPC with public, private app, and private data subnets across 3 Availability Zones.
- Multi-AZ RDS MySQL database with read replica and automated backup retention.
- Amazon ECS Fargate cluster with IAM execution/task roles and CloudWatch log groups.
- Amazon S3 bucket with Intelligent-Tiering and CloudFront CDN with Origin Access Control.

### Step 3: Apply Infrastructure
```bash
terraform apply tfplan
```

---

## 3. Container Images & ECR Deployment

### Step 1: Authenticate Docker to AWS ECR
```bash
aws ecr get-login-password --region ap-south-1 | docker login --username AWS --password-stdin 123456789012.dkr.ecr.ap-south-1.amazonaws.com
```

### Step 2: Build & Push API Image
```bash
docker build -t dogy-api:latest -f infrastructure/docker/Dockerfile.api .
docker tag dogy-api:latest 123456789012.dkr.ecr.ap-south-1.amazonaws.com/dogy-api:latest
docker push 123456789012.dkr.ecr.ap-south-1.amazonaws.com/dogy-api:latest
```

### Step 3: Build & Push PDF Worker Image
```bash
docker build -t dogy-worker:latest -f infrastructure/docker/Dockerfile.worker .
docker tag dogy-worker:latest 123456789012.dkr.ecr.ap-south-1.amazonaws.com/dogy-worker:latest
docker push 123456789012.dkr.ecr.ap-south-1.amazonaws.com/dogy-worker:latest
```

---

## 4. Database Migrations & Initial Seeding

Run database migrations and seed baseline RBAC permissions from the ECS bastion or via task execution:

```bash
# Execute migrations
php artisan migrate --force

# Seed Super Admin Enterprise baseline (Roles, Permissions, Default Master Account)
php artisan db:seed --class=SuperAdminEnterpriseSeeder --force
```

### Default Provisioned Platform Owner Credentials:
- **Email**: `admin@dogyepaper.com`
- **Password**: `SuperAdminPassword123!`
- **Role**: `platform_owner` (Level 1 Root Hierarchy)

---

## 5. Frontend Production Build & Hosting

The Next.js Super Admin application is built for enterprise performance:

```bash
cd frontend
npm ci
npm run build
```

Deploy the Next.js bundle to an ECS Fargate container or AWS Amplify / S3 + CloudFront distribution configured to proxy `/api/*` requests to the Application Load Balancer.

---

## 6. Zero-Downtime CI/CD Pipeline

The included GitHub Actions workflow (`.github/workflows/deploy.yml` or `infrastructure/deployment/github-actions.yml`) executes:
1. Automated backend test suite execution (`SuperAdminApiTest`).
2. Frontend lint and Next.js production build verification.
3. Multi-architecture Docker container build and push to Amazon ECR.
4. AWS ECS rolling update with canary verification and automatic rollback on health-check failure.
