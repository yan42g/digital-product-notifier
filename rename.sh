#!/bin/bash

echo "🔄 Renommage minimal du plugin..."

# 1. Renommer le fichier principal
mv digital-planner-update-manager.php digital-product-notifier.php

# 2. Remplacer seulement les text-domains dans les fichiers PHP et templates
find . -name "*.php" -type f -exec sed -i '' \
    -e "s/'digital-planner-update-manager'/'digital-product-notifier'/g" \
    {} +

# 3. Mettre à jour les URLs de pages admin (dans includes/admin.php)
sed -i '' \
    -e 's/digital-planner-update-manager-debug/digital-product-notifier-files/g' \
    -e 's/digital-planner-update-manager/digital-product-notifier/g' \
    includes/admin.php

echo "✅ Modifications minimales terminées !"
echo "📝 Vérifiez le fichier principal et les titres des menus"