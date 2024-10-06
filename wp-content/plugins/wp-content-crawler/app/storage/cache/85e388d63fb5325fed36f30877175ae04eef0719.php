

<?php

use WPCCrawler\Services\UserPrefsService;

$prefs = (new UserPrefsService())->getUserPreferences($pageType ?? null, $siteId ?? null) ?? '{}';

?>

<div id="user-prefs" class="hidden" data-prefs='<?php echo $prefs; ?>'></div><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/partials/user-preferences.blade.php ENDPATH**/ ?>