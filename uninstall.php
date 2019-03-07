<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
delete_metadata ($meta_type, -1, 'cp_Index', '', true);

?>
