#!/bin/bash

echo "🔍 Verifying Inline Help System Components..."
echo ""

# Check PHP files
echo "📦 Checking PHP Components..."
if [ -f "app/Services/HelpContentService.php" ]; then
    echo "✅ HelpContentService.php exists"
    php -l app/Services/HelpContentService.php > /dev/null 2>&1 && echo "   ✓ Syntax OK"
else
    echo "❌ HelpContentService.php missing"
fi

if [ -f "app/Livewire/Components/InlineHelp.php" ]; then
    echo "✅ InlineHelp.php exists"
    php -l app/Livewire/Components/InlineHelp.php > /dev/null 2>&1 && echo "   ✓ Syntax OK"
else
    echo "❌ InlineHelp.php missing"
fi

echo ""
echo "📄 Checking Blade Views..."
if [ -f "resources/views/livewire/components/inline-help.blade.php" ]; then
    echo "✅ livewire/components/inline-help.blade.php exists"
else
    echo "❌ livewire/components/inline-help.blade.php missing"
fi

if [ -f "resources/views/components/inline-help.blade.php" ]; then
    echo "✅ components/inline-help.blade.php exists"
else
    echo "❌ components/inline-help.blade.php missing"
fi

if [ -f "resources/views/components/help-details.blade.php" ]; then
    echo "✅ components/help-details.blade.php exists"
else
    echo "❌ components/help-details.blade.php missing"
fi

echo ""
echo "🎨 Checking CSS..."
if [ -f "resources/css/inline-help.css" ]; then
    echo "✅ inline-help.css exists"
    SIZE=$(wc -l < resources/css/inline-help.css)
    echo "   ✓ Lines: $SIZE"
else
    echo "❌ inline-help.css missing"
fi

if grep -q "inline-help.css" resources/css/app.css; then
    echo "✅ CSS imported in app.css"
else
    echo "❌ CSS not imported in app.css"
fi

echo ""
echo "🏗️  Checking Build..."
if [ -f "public/build/manifest.json" ]; then
    echo "✅ Build manifest exists"
else
    echo "❌ Build manifest missing (run npm run build)"
fi

echo ""
echo "📚 Checking Documentation..."
if [ -f "storage/testing/inline-help-components-created.md" ]; then
    echo "✅ Component documentation exists"
else
    echo "❌ Component documentation missing"
fi

if [ -f "storage/testing/inline-help-quick-start.md" ]; then
    echo "✅ Quick start guide exists"
else
    echo "❌ Quick start guide missing"
fi

echo ""
echo "✨ Verification Complete!"
