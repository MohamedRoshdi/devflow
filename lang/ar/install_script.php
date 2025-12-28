<?php

return [
    // Page titles
    'title' => 'مولد سكربت التثبيت',
    'generate_script' => 'إنشاء سكربت التثبيت',

    // Deployment modes
    'deployment_mode' => 'وضع النشر',
    'development' => 'التطوير',
    'development_desc' => 'تطوير محلي مع أدوات التصحيح',
    'production' => 'الإنتاج',
    'production_desc' => 'آمن ومحسن للخوادم الحية',

    // Production settings
    'production_settings' => 'إعدادات الإنتاج',
    'domain' => 'النطاق',
    'email' => 'البريد الإلكتروني (لـ SSL)',
    'skip_ssl' => 'تخطي إعداد SSL',
    'skip_ssl_desc' => 'استخدم عند وجود reverse proxy',

    // Database
    'database_config' => 'إعدادات قاعدة البيانات',

    // Security & Services
    'security_services' => 'الأمان والخدمات',
    'queue_workers' => 'معالجات الطوابير',

    // Script info
    'estimated_time' => 'الوقت المقدر للتثبيت',
    'minutes' => 'دقائق',
    'lines' => 'سطر',

    // Actions
    'generate' => 'إنشاء السكربت',
    'generating' => 'جاري الإنشاء...',
    'download' => 'تحميل السكربت',
    'copy' => 'نسخ',
    'reconfigure' => 'إعادة التكوين',
    'close' => 'إغلاق',
    'copied_to_clipboard' => 'تم نسخ السكربت إلى الحافظة!',

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

    // Validation messages
    'domain_required' => 'النطاق مطلوب لوضع الإنتاج',
    'email_required' => 'البريد الإلكتروني مطلوب لشهادات SSL',
];
