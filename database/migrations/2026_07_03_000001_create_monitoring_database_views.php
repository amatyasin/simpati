<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // Drop existing views to avoid conflicts
            DB::statement('DROP VIEW IF EXISTS view_media_completeness');
            DB::statement('DROP VIEW IF EXISTS view_expired_documents');
            DB::statement('DROP VIEW IF EXISTS view_expiring_soon_documents');
            DB::statement('DROP VIEW IF EXISTS view_verification_statistics');
            DB::statement('DROP VIEW IF EXISTS view_media_ranking');

            // 1. view_media_completeness
            DB::statement('
                CREATE VIEW view_media_completeness AS
                SELECT 
                    m.id, 
                    m.brand_name, 
                    m.company_name, 
                    mc.name AS category_name, 
                    m.completeness_percentage, 
                    m.verification_status
                FROM media m
                LEFT JOIN media_categories mc ON m.media_category_id = mc.id
                WHERE m.deleted_at IS NULL
            ');

            // 2. view_expired_documents
            DB::statement("
                CREATE VIEW view_expired_documents AS
                SELECT 
                    md.id, 
                    md.media_id, 
                    m.brand_name, 
                    dt.name AS document_type_name, 
                    md.document_number, 
                    md.expiration_date
                FROM media_documents md
                JOIN media m ON md.media_id = m.id
                JOIN document_types dt ON md.document_type_id = dt.id
                WHERE md.expiration_date < date('now') 
                  AND md.deleted_at IS NULL 
                  AND m.deleted_at IS NULL
            ");

            // 3. view_expiring_soon_documents
            DB::statement("
                CREATE VIEW view_expiring_soon_documents AS
                SELECT 
                    md.id, 
                    md.media_id, 
                    m.brand_name, 
                    dt.name AS document_type_name, 
                    md.document_number, 
                    md.expiration_date, 
                    CAST(julianday(md.expiration_date) - julianday('now') AS INTEGER) AS days_left
                FROM media_documents md
                JOIN media m ON md.media_id = m.id
                JOIN document_types dt ON md.document_type_id = dt.id
                WHERE md.expiration_date >= date('now') 
                  AND md.expiration_date <= date('now', '+30 days') 
                  AND md.deleted_at IS NULL 
                  AND m.deleted_at IS NULL
            ");

            // 4. view_verification_statistics
            DB::statement("
                CREATE VIEW view_verification_statistics AS
                SELECT
                    SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) AS total_pending,
                    SUM(CASE WHEN verification_status = 'approved' THEN 1 ELSE 0 END) AS total_approved,
                    SUM(CASE WHEN verification_status = 'revision' THEN 1 ELSE 0 END) AS total_revision,
                    SUM(CASE WHEN verification_status = 'rejected' THEN 1 ELSE 0 END) AS total_rejected,
                    COUNT(*) AS total_documents
                FROM media_documents
                WHERE deleted_at IS NULL
            ");

            // 5. view_media_ranking
            DB::statement('
                CREATE VIEW view_media_ranking AS
                SELECT
                    m.id,
                    m.brand_name,
                    m.company_name,
                    mc.name AS category_name,
                    m.verification_score,
                    m.completeness_percentage,
                    ((m.verification_score * 0.8) + (m.completeness_percentage * 0.2)) AS ranking_score,
                    RANK() OVER (ORDER BY ((m.verification_score * 0.8) + (m.completeness_percentage * 0.2)) DESC) as `rank`
                FROM media m
                LEFT JOIN media_categories mc ON m.media_category_id = mc.id
                WHERE m.deleted_at IS NULL
            ');
        } else {
            // MySQL / MariaDB / Default
            // 1. view_media_completeness
            DB::statement('
                CREATE OR REPLACE VIEW view_media_completeness AS
                SELECT 
                    m.id, 
                    m.brand_name, 
                    m.company_name, 
                    mc.name AS category_name, 
                    m.completeness_percentage, 
                    m.verification_status
                FROM media m
                LEFT JOIN media_categories mc ON m.media_category_id = mc.id
                WHERE m.deleted_at IS NULL
            ');

            // 2. view_expired_documents
            DB::statement('
                CREATE OR REPLACE VIEW view_expired_documents AS
                SELECT 
                    md.id, 
                    md.media_id, 
                    m.brand_name, 
                    dt.name AS document_type_name, 
                    md.document_number, 
                    md.expiration_date
                FROM media_documents md
                JOIN media m ON md.media_id = m.id
                JOIN document_types dt ON md.document_type_id = dt.id
                WHERE md.expiration_date < CURRENT_DATE() 
                  AND md.deleted_at IS NULL 
                  AND m.deleted_at IS NULL
            ');

            // 3. view_expiring_soon_documents
            DB::statement('
                CREATE OR REPLACE VIEW view_expiring_soon_documents AS
                SELECT 
                    md.id, 
                    md.media_id, 
                    m.brand_name, 
                    dt.name AS document_type_name, 
                    md.document_number, 
                    md.expiration_date, 
                    DATEDIFF(md.expiration_date, CURRENT_DATE()) AS days_left
                FROM media_documents md
                JOIN media m ON md.media_id = m.id
                JOIN document_types dt ON md.document_type_id = dt.id
                WHERE md.expiration_date >= CURRENT_DATE() 
                  AND md.expiration_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) 
                  AND md.deleted_at IS NULL 
                  AND m.deleted_at IS NULL
            ');

            // 4. view_verification_statistics
            DB::statement("
                CREATE OR REPLACE VIEW view_verification_statistics AS
                SELECT
                    SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) AS total_pending,
                    SUM(CASE WHEN verification_status = 'approved' THEN 1 ELSE 0 END) AS total_approved,
                    SUM(CASE WHEN verification_status = 'revision' THEN 1 ELSE 0 END) AS total_revision,
                    SUM(CASE WHEN verification_status = 'rejected' THEN 1 ELSE 0 END) AS total_rejected,
                    COUNT(*) AS total_documents
                FROM media_documents
                WHERE deleted_at IS NULL
            ");

            // 5. view_media_ranking
            DB::statement('
                CREATE OR REPLACE VIEW view_media_ranking AS
                SELECT
                    m.id,
                    m.brand_name,
                    m.company_name,
                    mc.name AS category_name,
                    m.verification_score,
                    m.completeness_percentage,
                    ((m.verification_score * 0.8) + (m.completeness_percentage * 0.2)) AS ranking_score,
                    RANK() OVER (ORDER BY ((m.verification_score * 0.8) + (m.completeness_percentage * 0.2)) DESC) as `rank`
                FROM media m
                LEFT JOIN media_categories mc ON m.media_category_id = mc.id
                WHERE m.deleted_at IS NULL
            ');
        }
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_media_completeness');
        DB::statement('DROP VIEW IF EXISTS view_expired_documents');
        DB::statement('DROP VIEW IF EXISTS view_expiring_soon_documents');
        DB::statement('DROP VIEW IF EXISTS view_verification_statistics');
        DB::statement('DROP VIEW IF EXISTS view_media_ranking');
    }
};
