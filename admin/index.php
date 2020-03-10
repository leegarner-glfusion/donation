<?php
/**
 * Administrative entry point for the Donation plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2019 Lee Garner <lee@leegarner.com>
 * @package     donation
 * @version     v0.0.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Import core glFusion functions */
require_once('../../../lib-common.php');

/**
 * MAIN
 */
// If plugin is installed but not enabled, display an error and exit gracefully
if (!in_array('donation', $_PLUGINS)) {
    COM_404();
    exit;
}

// Only let admin users access this page
if (!SEC_hasRights('donation.admin')) {
    COM_errorLog("Attempted unauthorized access the Donation Admin page." .
        " User id: {$_USER['uid']}, Username: {$_USER['username']}, " .
        " IP: $REMOTE_ADDR", 1);
    COM_404();
    exit;
}

$action = '';
$expected = array(
    // Actions to perform
    'savecampaign', 'deletecampaign', 'savedonation', 'deletedonation',
    'resetbuttons',
    // Views to display
    'campaigns', 'editcampaign', 'editdonation', 'donations', 'campaigns',
);
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}

// Get the campaign and donation IDs, if any
$camp_id = isset($_REQUEST['camp_id']) ?
        COM_sanitizeID($_REQUEST['camp_id'], false) : '';
$don_id = isset($_REQUEST['don_id']) ? (int)$_REQUEST['don_id'] : 0;
$content = '';      // initialize variable for page content

switch ($action) {
case 'savecampaign':
    $old_camp_id = isset($_POST['old_camp_id']) ? $_POST['old_camp_id'] : '';
    $C = Donation\Campaign::getInstance($old_camp_id);
    $C->Save($_POST);
    $view = 'campaigns';
    break;

case 'deletecampaign':
    Donation\Campaign::Delete($camp_id);
    $view = 'campaigns';
    break;

case 'savedonation':
    $D = new Donation\Donation($don_id);
    $D->Save($_POST);
    $view = 'donations';
    break;

case 'deletedonation':
    $D = new Donation\Donation($don_id);
    // Set camp_id to stay on the donations page for the campaign
    $camp_id = $D->getCampaiginID();
    Donation\Donation::Delete($don_id);
    $view = 'donations';
    break;

case 'resetbuttons':
    $sql = "SELECT * FROM {$_TABLES['don_campaigns']}";
    $res = DB_query($sql);
    while ($A = DB_fetchArray($res, false)) {
        $P = Donation\Campaign::getInstance($A);
        $P->Save();
    }
    $view = 'campaigne';
    break;

default:
    $view = $action;
    break;
}

// Display the correct page content
switch ($view) {
case 'editcampaign':
    $C = Donation\Campaign::getInstance($camp_id);
    $content .= $C->Edit();
    break;

case 'editdonation':
    $don_id = (int)$actionval;
    $D = new Donation\Donation($don_id);
    $content .= $D->Edit();
    break;

case 'donations':
    $content .= Donation\Donation::adminList($camp_id);
    break;

case 'campaigns':
default:
    $view = 'campaigns';
    $content .= Donation\Campaign::adminList();
    break;
}

$display = COM_siteHeader();
$display .= Donation\Menu::Admin($view);
$display .= $content;
$display .= COM_siteFooter();
echo $display;

?>
