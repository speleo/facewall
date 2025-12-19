<?php
defined( 'ABSPATH' ) || exit;

/**
 * Load TCPDF from CiviCRM if available.
 *
 * @return bool
 */
function facewall_load_tcpdf() {

    // Already loaded
    if ( class_exists( 'TCPDF' ) ) {
        return true;
    }

    // Only proceed if CiviCRM is present
    if ( ! defined( 'CIVICRM_VERSION' ) ) {
        return false;
    }

    $tcpdf_path = WP_PLUGIN_DIR . '/civicrm/civicrm/vendor/tecnickcom/tcpdf/tcpdf.php';

    if ( file_exists( $tcpdf_path ) ) {
        require_once $tcpdf_path;
    }

    return class_exists( 'TCPDF' );
}

