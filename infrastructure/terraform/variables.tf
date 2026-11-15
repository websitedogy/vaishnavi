variable "aws_region" {
  description = "The AWS region to deploy the DOGY ePAPER infrastructure."
  type        = string
  default     = "ap-south-1"
}

variable "environment" {
  description = "Deployment environment name (e.g. production, staging)."
  type        = string
  default     = "production"
}

variable "project_name" {
  description = "Name of the project."
  type        = string
  default     = "dogy-epaper-saas"
}

variable "vpc_cidr" {
  description = "CIDR block for the VPC."
  type        = string
  default     = "10.0.0.0/16"
}

variable "app_domain" {
  description = "Primary platform domain name."
  type        = string
  default     = "dogyepaper.com"
}

variable "admin_domain" {
  description = "Super admin subdomain."
  type        = string
  default     = "admin.dogyepaper.com"
}

variable "db_instance_class" {
  description = "RDS instance class for Aurora / MySQL."
  type        = string
  default     = "db.r6g.xlarge"
}

variable "db_name" {
  description = "Database name."
  type        = string
  default     = "dogy_saas_production"
}

variable "db_username" {
  description = "Database master username."
  type        = string
  default     = "dogy_master"
}

variable "ecs_api_cpu" {
  description = "Fargate CPU units for API tasks."
  type        = number
  default     = 1024
}

variable "ecs_api_memory" {
  description = "Fargate memory (MB) for API tasks."
  type        = number
  default     = 2048
}

variable "ecs_worker_cpu" {
  description = "Fargate CPU units for PDF rendering workers."
  type        = number
  default     = 2048
}

variable "ecs_worker_memory" {
  description = "Fargate memory (MB) for PDF rendering workers."
  type        = number
  default     = 4096
}

variable "ecs_api_desired_count" {
  description = "Desired number of API containers."
  type        = number
  default     = 4
}

variable "ecs_worker_desired_count" {
  description = "Desired number of background PDF rendering workers."
  type        = number
  default     = 8
}
