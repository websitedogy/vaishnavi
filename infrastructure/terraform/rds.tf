# RDS MySQL Database Infrastructure for DOGY ePAPER SaaS (10,000 Tenant Capacity)

resource "aws_db_subnet_group" "db" {
  name        = "${var.project_name}-db-subnet-group"
  description = "Subnet group for RDS MySQL Multi-AZ cluster"
  subnet_ids  = aws_subnet.private_data[*].id

  tags = {
    Name = "${var.project_name}-db-subnet-group"
  }
}

resource "aws_security_group" "db" {
  name        = "${var.project_name}-db-sg"
  description = "Access to RDS MySQL cluster from ECS tasks"
  vpc_id      = aws_vpc.main.id

  ingress {
    description     = "MySQL from ECS tasks"
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.ecs_tasks.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "${var.project_name}-db-sg"
  }
}

resource "aws_kms_key" "rds" {
  description             = "KMS key for encrypting RDS production databases"
  deletion_window_in_days = 30
  enable_key_rotation     = true

  tags = {
    Name = "${var.project_name}-rds-key"
  }
}

resource "aws_db_parameter_group" "mysql" {
  name   = "${var.project_name}-mysql-params"
  family = "mysql8.0"

  parameter {
    name  = "character_set_server"
    value = "utf8mb4"
  }

  parameter {
    name  = "collation_server"
    value = "utf8mb4_unicode_ci"
  }

  parameter {
    name  = "max_connections"
    value = "5000"
  }

  parameter {
    name  = "innodb_buffer_pool_size"
    value = "{DBInstanceClassMemory*3/4}"
  }
}

# Primary RDS MySQL Multi-AZ Instance
resource "aws_db_instance" "primary" {
  identifier                  = "${var.project_name}-db-primary"
  allocated_storage           = 100
  max_allocated_storage       = 1000
  storage_type                = "gp3"
  engine                      = "mysql"
  engine_version              = "8.0.35"
  instance_class              = var.db_instance_class
  db_name                     = var.db_name
  username                    = var.db_username
  manage_master_user_password = true

  multi_az               = true
  db_subnet_group_name   = aws_db_subnet_group.db.name
  vpc_security_group_ids = [aws_security_group.db.id]
  parameter_group_name   = aws_db_parameter_group.mysql.name

  storage_encrypted = true
  kms_key_id        = aws_kms_key.rds.arn

  backup_retention_period   = 35
  backup_window             = "01:00-03:00"
  maintenance_window        = "Sun:03:30-Sun:05:30"
  copy_tags_to_snapshot     = true
  deletion_protection       = true
  skip_final_snapshot       = false
  final_snapshot_identifier = "${var.project_name}-final-snapshot"

  performance_insights_enabled          = true
  performance_insights_retention_period = 7

  tags = {
    Name = "${var.project_name}-rds-primary"
    Role = "Primary"
  }
}

# Read Replica for Reporting, Telemetry, and High-Volume Tenant Analytics
resource "aws_db_instance" "replica" {
  identifier             = "${var.project_name}-db-replica-1"
  replicate_source_db    = aws_db_instance.primary.identifier
  instance_class         = var.db_instance_class
  storage_type           = "gp3"
  max_allocated_storage  = 1000
  vpc_security_group_ids = [aws_security_group.db.id]
  parameter_group_name   = aws_db_parameter_group.mysql.name

  auto_minor_version_upgrade = true
  skip_final_snapshot        = true

  performance_insights_enabled = true

  tags = {
    Name = "${var.project_name}-rds-read-replica-1"
    Role = "ReadReplica"
  }
}
