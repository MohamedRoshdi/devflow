<?php

return [
    // Page titles
    'title' => 'مولد سكربت التثبيت',
    'generate_script' => 'إنشاء سكربت التثبيت',
    'run_install_script' => 'تشغيل سكربت التثبيت',

    // Script detection
    'checking_script' => 'جاري البحث عن install.sh...',
    'script_found' => 'تم العثور على install.sh في المشروع',
    'no_script_found' => 'لم يتم العثور على install.sh',
    'no_script_found_desc' => 'هذا المشروع لا يحتوي على ملف install.sh. أضف واحداً لتفعيل التثبيت التلقائي.',

    // Deployment modes
    'deployment_mode' => 'وضع النشر',
    'development' => 'التطوير',
    'development_desc' => 'تطوير محلي مع أدوات التصحيح',
    'production' => 'الإنتاج',
    'production_desc' => 'آمن ومحسن للخوادم الحية',
    'production_mode' => 'وضع الإنتاج',
    'production_mode_desc' => 'تفعيل تعزيز الأمان وSSL والتحسينات',

    // Production settings
    'production_settings' => 'إعدادات الإنتاج',
    'domain' => 'النطاق',
    'email' => 'البريد الإلكتروني (لـ SSL)',
    'skip_ssl' => 'تخطي إعداد SSL',
    'skip_ssl_desc' => 'استخدم عند وجود reverse proxy',

    // Database
    'database_config' => 'إعدادات قاعدة البيانات',
    'database' => 'محرك قاعدة البيانات',
    'db_password' => 'كلمة مرور قاعدة البيانات',
    'auto_generated' => 'اتركه فارغاً للتوليد التلقائي',
    'db_password_hint' => 'اتركه فارغاً لتوليد كلمة مرور عشوائية آمنة',

    // Security & Services
    'security_services' => 'الأمان والخدمات',
    'queue_workers' => 'معالجات الطوابير',

    // Script info
    'estimated_time' => 'الوقت المقدر للتثبيت',
    'minutes' => 'دقائق',
    'lines' => 'سطر',
    'output' => 'المخرجات',

    // Actions
    'generate' => 'إنشاء السكربت',
    'generating' => 'جاري الإنشاء...',
    'download' => 'تحميل السكربت',
    'copy' => 'نسخ',
    'reconfigure' => 'إعادة التكوين',
    'close' => 'إغلاق',
    'copied_to_clipboard' => 'تم نسخ السكربت إلى الحافظة!',
    'run_script' => 'تشغيل السكربت',
    'view_script' => 'عرض السكربت',
    'running' => 'جاري التشغيل...',
    'completed' => 'اكتمل',
    'failed' => 'فشل',

    // Run results
    'run_success' => 'اكتمل تشغيل سكربت التثبيت بنجاح!',
    'run_failed' => 'فشل تنفيذ سكربت التثبيت',

    // Usage instructions
    'usage_instructions' => 'تعليمات الاستخدام',
    'step_1' => 'حمل أو انسخ السكربت إلى الخادم',
    'step_2' => 'اجعله قابلاً للتنفيذ: chmod +x install.sh',
    'step_3' => 'شغل السكربت: ./install.sh',

    // Security features
    'security_features' => 'ميزات الأمان',
    'ufw_firewall' => 'جدار حماية UFW',
    'fail2ban' => 'حماية Fail2ban',
    'ssl_certificate' => 'شهادة SSL',
    'php_optimization' => 'تحسينات PHP',
    'secure_permissions' => 'صلاحيات آمنة',

    // Script options (for help display)
    'option_production' => 'تفعيل وضع الإنتاج مع تعزيز الأمان',
    'option_domain' => 'النطاق لشهادة SSL',
    'option_email' => 'البريد الإلكتروني لإشعارات Let\'s Encrypt',
    'option_db_driver' => 'قاعدة البيانات: pgsql أو mysql',
    'option_skip_ssl' => 'تخطي إعداد SSL (خلف proxy)',

    // Validation messages
    'domain_required' => 'النطاق مطلوب لوضع الإنتاج',
    'email_required' => 'البريد الإلكتروني مطلوب لشهادات SSL',
];
