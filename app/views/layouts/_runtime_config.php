<div id="meetspaceRuntimeConfig"
     class="hidden"
     data-admin-user-id="<?php echo is_logged_in() && is_admin() ? (int) $_SESSION['user_id'] : 0; ?>"
     data-csrf-token="<?php echo htmlspecialchars(is_logged_in() ? csrf_token() : '', ENT_QUOTES, 'UTF-8'); ?>"></div>
