<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HelpContent;
use App\Models\HelpContentTranslation;
use Illuminate\Database\Seeder;

class HelpContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $helpContents = [
            [
                'key' => 'deploy-button',
                'category' => 'deployments',
                'ui_element_type' => 'button',
                'icon' => 'rocket',
                'title' => 'Deploy Project',
                'brief' => 'Triggers a new deployment of your project to the server',
                'details' => [
                    'step_1' => 'Pulls latest code from your Git repository',
                    'step_2' => 'Runs build and optimization commands',
                    'step_3' => 'Restarts application containers',
                    'step_4' => 'Runs database migrations if configured',
                ],
                'docs_url' => 'https://docs.devflowpro.com/deployments',
                'video_url' => null,
                'is_active' => true,
                'ar_brief' => 'يطلق نشرًا جديدًا لمشروعك على الخادم',
                'ar_details' => [
                    'step_1' => 'يسحب أحدث كود من مستودع Git الخاص بك',
                    'step_2' => 'يقوم بتشغيل أوامر البناء والتحسين',
                    'step_3' => 'يعيد تشغيل حاويات التطبيق',
                    'step_4' => 'يقوم بتشغيل ترحيل قاعدة البيانات إذا تم تكوينه',
                ],
            ],
            [
                'key' => 'project-create',
                'category' => 'projects',
                'ui_element_type' => 'modal',
                'icon' => 'plus-circle',
                'title' => 'Create New Project',
                'brief' => 'Add a new project to DevFlow Pro for automated management',
                'details' => [
                    'name' => 'Choose a unique name for your project',
                    'repository' => 'Provide the Git repository URL (GitHub, GitLab, Bitbucket)',
                    'server' => 'Select the server where this project will be deployed',
                    'framework' => 'Specify the framework (Laravel, Symfony, etc.)',
                ],
                'docs_url' => 'https://docs.devflowpro.com/projects/create',
                'video_url' => null,
                'is_active' => true,
                'ar_brief' => 'أضف مشروعًا جديدًا إلى DevFlow Pro للإدارة الآلية',
                'ar_details' => [
                    'name' => 'اختر اسمًا فريدًا لمشروعك',
                    'repository' => 'قدم عنوان URL لمستودع Git (GitHub، GitLab، Bitbucket)',
                    'server' => 'حدد الخادم حيث سيتم نشر هذا المشروع',
                    'framework' => 'حدد الإطار (Laravel، Symfony، إلخ)',
                ],
            ],
            [
                'key' => 'ssl-toggle',
                'category' => 'domains',
                'ui_element_type' => 'toggle',
                'icon' => 'lock',
                'title' => 'Enable SSL/HTTPS',
                'brief' => 'Automatically provisions and renews Let\'s Encrypt SSL certificates',
                'details' => [
                    'auto_provision' => 'Automatically obtains SSL certificate from Let\'s Encrypt',
                    'auto_renew' => 'Certificates auto-renew 30 days before expiration',
                    'https_redirect' => 'All HTTP traffic is redirected to HTTPS',
                    'requirements' => 'Domain must be pointed to your server and publicly accessible',
                ],
                'docs_url' => 'https://docs.devflowpro.com/ssl',
                'video_url' => null,
                'is_active' => true,
                'ar_brief' => 'يوفر ويجدد شهادات SSL من Let\'s Encrypt تلقائيًا',
                'ar_details' => [
                    'auto_provision' => 'يحصل تلقائيًا على شهادة SSL من Let\'s Encrypt',
                    'auto_renew' => 'تتجدد الشهادات تلقائيًا قبل 30 يومًا من انتهاء الصلاحية',
                    'https_redirect' => 'يتم إعادة توجيه جميع حركة HTTP إلى HTTPS',
                    'requirements' => 'يجب أن يشير النطاق إلى خادمك ويكون متاحًا للجمهور',
                ],
            ],
            [
                'key' => 'server-metrics',
                'category' => 'servers',
                'ui_element_type' => 'inline',
                'icon' => 'chart-bar',
                'title' => 'Server Metrics',
                'brief' => 'Real-time monitoring of server resources and performance',
                'details' => [
                    'cpu' => 'CPU usage percentage and load average',
                    'memory' => 'RAM usage and available memory',
                    'disk' => 'Disk space usage and I/O statistics',
                    'network' => 'Network traffic in/out',
                    'refresh' => 'Metrics update every 5 minutes',
                ],
                'docs_url' => 'https://docs.devflowpro.com/monitoring',
                'video_url' => null,
                'is_active' => true,
                'ar_brief' => 'مراقبة في الوقت الفعلي لموارد الخادم والأداء',
                'ar_details' => [
                    'cpu' => 'نسبة استخدام وحدة المعالجة المركزية ومتوسط التحميل',
                    'memory' => 'استخدام الذاكرة والذاكرة المتاحة',
                    'disk' => 'استخدام مساحة القرص وإحصائيات الإدخال/الإخراج',
                    'network' => 'حركة الشبكة الواردة/الصادرة',
                    'refresh' => 'يتم تحديث المقاييس كل 5 دقائق',
                ],
            ],
            [
                'key' => 'auto-deploy-toggle',
                'category' => 'deployments',
                'ui_element_type' => 'toggle',
                'icon' => 'sync',
                'title' => 'Auto-Deploy on Push',
                'brief' => 'Automatically deploy when code is pushed to the main branch',
                'details' => [
                    'webhook' => 'Requires webhook configuration in your Git provider',
                    'branch' => 'Only deploys when pushing to the configured branch',
                    'safety' => 'Failed deployments automatically roll back',
                    'notifications' => 'Get notified of deployment status via email/Slack',
                ],
                'docs_url' => 'https://docs.devflowpro.com/auto-deploy',
                'video_url' => null,
                'is_active' => true,
                'ar_brief' => 'النشر التلقائي عند دفع الكود إلى الفرع الرئيسي',
                'ar_details' => [
                    'webhook' => 'يتطلب تكوين webhook في موفر Git الخاص بك',
                    'branch' => 'ينشر فقط عند الدفع إلى الفرع المكون',
                    'safety' => 'تتراجع عمليات النشر الفاشلة تلقائيًا',
                    'notifications' => 'احصل على إشعارات بحالة النشر عبر البريد الإلكتروني/Slack',
                ],
            ],
            [
                'key' => 'database-backup',
                'category' => 'backups',
                'ui_element_type' => 'button',
                'icon' => 'database',
                'title' => 'Database Backup',
                'brief' => 'Create an instant backup of your project database',
                'details' => [
                    'types' => 'Supports MySQL, PostgreSQL, and Redis',
                    'storage' => 'Backups stored locally and optionally in S3/GCS',
                    'retention' => 'Automatic cleanup based on retention policy',
                    'restore' => 'One-click restore from any backup point',
                ],
                'docs_url' => 'https://docs.devflowpro.com/backups',
                'video_url' => null,
                'is_active' => true,
                'ar_brief' => 'إنشاء نسخة احتياطية فورية من قاعدة بيانات مشروعك',
                'ar_details' => [
                    'types' => 'يدعم MySQL و PostgreSQL و Redis',
                    'storage' => 'يتم تخزين النسخ الاحتياطية محليًا واختياريًا في S3/GCS',
                    'retention' => 'تنظيف تلقائي بناءً على سياسة الاحتفاظ',
                    'restore' => 'استعادة بنقرة واحدة من أي نقطة نسخ احتياطي',
                ],
            ],
        ];

        foreach ($helpContents as $data) {
            $arBrief = $data['ar_brief'] ?? '';
            $arDetails = $data['ar_details'] ?? [];
            unset($data['ar_brief'], $data['ar_details']);

            $helpContent = HelpContent::create($data);

            // Create Arabic translation if exists
            if ($arBrief) {
                HelpContentTranslation::create([
                    'help_content_id' => $helpContent->id,
                    'locale' => 'ar',
                    'brief' => $arBrief,
                    'details' => $arDetails,
                ]);
            }
        }

        $this->command->info('Help content seeded successfully!');
    }
}
