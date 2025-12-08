-- PostgreSQL Initialization Script for DevFlow Pro
-- This script runs automatically when PostgreSQL container is first created

-- Set timezone to UTC
ALTER DATABASE devflow_pro SET timezone TO 'UTC';

-- Create required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";      -- UUID generation
CREATE EXTENSION IF NOT EXISTS "pg_trgm";         -- Trigram matching for better text search
CREATE EXTENSION IF NOT EXISTS "btree_gin";       -- Better indexing for arrays and JSONB
CREATE EXTENSION IF NOT EXISTS "btree_gist";      -- Better indexing for ranges
CREATE EXTENSION IF NOT EXISTS "pg_stat_statements"; -- Query performance tracking

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE devflow_pro TO devflow;
GRANT ALL PRIVILEGES ON SCHEMA public TO devflow;

-- Set default privileges for future tables
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO devflow;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO devflow;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON FUNCTIONS TO devflow;

-- Create custom functions for DevFlow Pro

-- Function to update updated_at timestamp automatically
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Function to generate slug from name
CREATE OR REPLACE FUNCTION generate_slug(input_text TEXT)
RETURNS TEXT AS $$
BEGIN
    RETURN lower(
        regexp_replace(
            regexp_replace(input_text, '[^a-zA-Z0-9\s-]', '', 'g'),
            '\s+', '-', 'g'
        )
    );
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- Performance tuning settings for DevFlow Pro workload
-- These can be overridden in postgresql.conf

COMMENT ON DATABASE devflow_pro IS 'DevFlow Pro - Multi-Project Deployment & Management System';
