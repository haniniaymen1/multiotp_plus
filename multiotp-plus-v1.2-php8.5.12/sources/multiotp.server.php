<?php
/**
 * @file  multiotp.server.php
 * @brief web service for the multiOTP class.
 *
 * multiOTP web service - Strong two-factor authentication PHP class
 * https://www.multiotp.net
 *
 * Visit http://forum.multiotp.net/ for additional support.
 *
 * Donation are always welcome! Please check https://www.multiotp.net
 * and you will find the magic button ;-)
 *
 * The multiOTP web service is simply merged with the multiOTP PHP class
 * in order to provide the server part of the client/server solution.
 *
 * This file can be used with any web server supporting PHP as
 * script language, like the following web servers:
 *  - nginx is a light one under Linux
 *    (http://nginx.org/)
 *  - Mongoose is a light one under Windows
 *    (https://code.google.com/archive/p/mongoose/downloads)
 *  - The Apache HTTP server is a very well known web server running under Linux and Windows
 *    (http://httpd.apache.org/)
 *
 *
 * PHP 7.4 or higher is supported.
 *
 * @author    Andre Liechti, SysCo systemes de communication sa, <info@multiotp.net>
 * @version   5.10.2.2
 * @date      2026-04-03
 * @since     2013-08-06
 * @copyright (c) 2013-2026 SysCo systemes de communication sa
 * @copyright GNU Lesser General Public License
 *
 *//*
 *
 * LICENCE
 *
 *   Copyright (c) 2010-2026 SysCo systemes de communication sa
 *   SysCo (tm) is a trademark of SysCo systemes de communication sa
 *   (http://www.sysco.ch)
 *   All rights reserved.
 * 
 *   This file is part of the multiOTP PHP class
 *
 *   multiOTP PHP class is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the License,
 *   or (at your option) any later version.
 * 
 *   multiOTP PHP class is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Lesser General Public License for more details.
 * 
 *   You should have received a copy of the GNU Lesser General Public
 *   License along with MultiOTP PHP class.
 *   If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * Usage
 *
 *   You will have to send an XML formatted content in the field named data
 *    in a POSTed form.
 *
 *
 * Special issues
 *
 *   If you need specific developments concerning strong authentication,
 *   do not hesitate to contact us per email at info@multiotp.net.
 *
 *
 * Users feedbacks and comments
 *
 * 2017-02-09 Frank van der Aa, Vanboxtel BV (NL)
 *   Thanks for your debug about lockedlistarray[], the new
 *   GetDelayedUsersList() method and the delayed users display on the web GUI.
 *
 * 2013-07-25 Dominik Pretzsch from Last Squirrel IT
 *   After some discussions with Dominik, integration of the
 *    client/server support in the basic library
 *
 *
 * Change Log
 *
 *   2022-05-08 5.8.8.5 SysCo/al Scratchlist can be generated from the Web GUI
 *   2021-03-25 5.8.1.9 SysCo/al Cookie privacy (httponly and secure) are now handled in the application directly
 *                               Weak SSL ciphers disabled
 *   2021-02-12 5.8.1.0 SysCo/al Web GUI update
 *   2019-01-24 5.4.1.5 SysCo/al If any, clean specific NTP DHCP option at every reboot
 *   2019-01-07 5.4.1.1 SysCo/al Raspberry Pi 3B+ support
 *   2018-08-21 5.3.0.0 SysCo/al without2FA algorithm added
 *   2017-07-07 5.0.4.9 SysCo/al Code cleaning, possible web information added
 *   2017-05-29 5.0.4.5 SysCo/al Restore configuration added
 *                               Fixed configuration file directory under Windows
 *                               The file can now be included from another one that have already an instance
 *   2016-11-06 5.0.2.7 SysCo/al Better configuration file detection
 *   2016-11-04 5.0.2.6 SysCo/al Backup configuration added
 *   2016-10-16 5.0.2.5 SysCo/al New methods added for SOAP service
 *   2016-04-18 5.0.0.0 SysCo/al ForceNoDisplayLog() method called to avoid log on display
 *   2015-07-15 4.3.2.5 SysCo/al Admin password update has been fixed
 *   2015-06-10 4.3.2.3 SysCo/al Enhancements for the Dev(Talks): demo
 *   2015-06-09 4.3.2.2 SysCo/al More option available from the GUI, Dev Talks Edition
 *   2014-12-09 4.3.1.0 SysCo/al Speed improvement (apc options have been tuned)
 *   2014-11-04 4.3.0.0 SysCo/al Updated GUI
 *   2014-04-13 4.2.4.2 SysCo/al Version synchronization
 *   2014-03-30 4.2.4   SysCo/al Forum link added on the GUI, minor bug fixes
 *   2014-03-01 4.2.2   SysCo/al More options available from the GUI
 *   2014-01-20 4.1.1   SysCo/al Version synchronization
 *   2013-12-23 4.1.0   SysCo/al Adding basic web functionalities
 *   2013-08-30 4.0.7   SysCo/al Version synchronization
 *   2013-08-25 4.0.6   SysCo/al Enhanced default page
 *   2013-08-20 4.0.4   SysCo/al Initial release
 *
 *********************************************************************/

///////////////////////////////////////////////////////////////////////////
// The multiOTP class is loaded from the separate multiotp.class.php file,
// which must be present in the same folder as this server script.
///////////////////////////////////////////////////////////////////////////
if (!class_exists('Multiotp')) {
  ///////////////////////////////////////////////////////////////////////////
  // The multiOTP class now lives in its own file (multiotp.class.php) and is
  // loaded from here, instead of being embedded directly in this server file.
  ///////////////////////////////////////////////////////////////////////////
  require_once(dirname(__FILE__).'/multiotp.class.php');
}
$multiotp_etc_dir  = '/etc/multiotp';
$config_folder     = $multiotp_etc_dir.'/config';
if (false === mb_strpos(getcwd(), '/')) {
  // if (!@file_exists($config_folder)) {
  $multiotp_etc_dir  = '';
  $config_folder = '';
}

if (!isset($multiotp)) {
  $multiotp = new Multiotp('DefaultCliEncryptionKey', false, '', $config_folder);
  $multiotp->SetHashSalt(_MULTIOTP_DEFAULT_HASH_SALT_); // Shared secret
}
$multiotp->ForceNoDisplayLog(); // No log on display as we are running a web server

if ('' != $multiotp_etc_dir) {
  $multiotp->SetLogFolder('/var/log/multiotp/');
  $multiotp->SetConfigFolder($multiotp_etc_dir.'/config/');
  $multiotp->SetDdnsFolder($multiotp_etc_dir.'/ddns/');
  $multiotp->SetDevicesFolder($multiotp_etc_dir.'/devices/');
  $multiotp->SetGroupsFolder($multiotp_etc_dir.'/groups/');
  $multiotp->SetTokensFolder($multiotp_etc_dir.'/tokens/');
  $multiotp->SetUsersFolder($multiotp_etc_dir.'/users/');
  $multiotp->SetCacheFolder('/tmp/cache/');
  $multiotp->SetLinuxFileMode('0666');
}
$multiotp->ReadConfigData();

/****************************************
 * WE REALLY DO NOT WANT TO BE CACHED !!!
 ****************************************/
header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (isset($_SERVER['HTTP_ORIGIN'])) {
  header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
  header("Access-Control-Allow-Origin: *");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Access-Control headers are received during OPTIONS requests
if ((isset($_SERVER['REQUEST_METHOD'])) && ('OPTIONS' == $_SERVER['REQUEST_METHOD'])) {
  header('Access-Control-Allow-Headers: Content-Type,Prefer,X-Requested-With');
  exit(0);
}

$data = isset($_POST['data'])?$_POST['data']:'';
$method = mb_substr(isset($_GET['method'])?$_GET['method']:(isset($_POST['method'])?$_POST['method']:''),0,255);
$options = isset($_GET['options'])?$_GET['options']:(isset($_POST['options'])?$_POST['options']:'');
$postdata = (('cli' == php_sapi_name()) ? '' : file_get_contents("php://input"));


/**********************************************************************
 **********************************************************************
 *** MULTI-ADMINISTRATOR ACCOUNTS EXTENSION                         ***
 ***                                                                ***
 *** Adds the support of several administrator accounts, stored in  ***
 *** an AES-256 encrypted file (multiotp-admin-accounts.json) in    ***
 *** the config folder. Passwords are never stored in clear text:   ***
 *** the client sends md5(hash_salt + password + hash_salt) and the ***
 *** server keeps a bcrypt hash of it. Accounts can be added,       ***
 *** modified, disabled, enabled, unlocked and deleted from the     ***
 *** web console (methods below), with a super_admin / admin        ***
 *** role hierarchy.                                                ***
 **********************************************************************
 **********************************************************************/
/* The user's refactored server file relies on nullable_trim() without
   defining it; provide it only if the class file did not define it. */
if (!function_exists('nullable_trim')) {
  function nullable_trim($value)
  {
    return trim(strval($value));
  }
}

define('MULTIOTP_ADMINS_FILENAME',     'multiotp-admin-accounts.json');
define('MULTIOTP_ADMINS_MAX_FAILED',   5);      // Lock the account after 5 failed attempts
define('MULTIOTP_ADMINS_LOCK_SECONDS', 600);    // Lock duration: 10 minutes

/**
 * @brief   Full path of the encrypted administrator accounts file.
 *          The config folder is created if it does not exist yet.
 */
function MultiotpAdminAccountsFilePath($multiotp)
{
  return $multiotp->GetConfigFolder(true, false).MULTIOTP_ADMINS_FILENAME;
}

/**
 * @brief   Encrypt the account database (AES-256-CBC, key derived
 *          from the multiOTP encryption key; XOR fallback).
 */
function MultiotpAdminAccountsEncrypt($multiotp, $plaintext)
{
  $encryption_key = $multiotp->GetEncryptionKey();
  if (function_exists('openssl_encrypt') && function_exists('openssl_random_pseudo_bytes')) {
    $key    = hash('sha256', 'multiotp-admin-accounts|'.$encryption_key, true);
    $iv     = openssl_random_pseudo_bytes(16);
    $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if (false !== $cipher) {
      return 'MOA1:'.base64_encode($iv.$cipher);
    }
  }
  return 'MOA0:'.$multiotp->Encrypt('admins', $plaintext, $encryption_key);
}

/**
 * @brief   Decrypt the account database.
 */
function MultiotpAdminAccountsDecrypt($multiotp, $ciphertext)
{
  if (('' == $ciphertext) || ('MOA' !== mb_substr($ciphertext, 0, 3))) {
    return '';
  }
  $format = mb_substr($ciphertext, 0, 5);
  $data   = mb_substr($ciphertext, 5);
  $encryption_key = $multiotp->GetEncryptionKey();
  if ('MOA1:' == $format) {
    if (!function_exists('openssl_decrypt')) {
      return '';
    }
    $raw = base64_decode($data, true);
    if ((false === $raw) || (strlen($raw) < 17)) {
      return '';
    }
    $key    = hash('sha256', 'multiotp-admin-accounts|'.$encryption_key, true);
    $iv     = substr($raw, 0, 16);
    $result = openssl_decrypt(substr($raw, 16), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return (false === $result) ? '' : $result;
  }
  return $multiotp->Decrypt('admins', $data, $encryption_key);
}

/**
 * @brief   Load the administrator accounts array ([] if none).
 */
function MultiotpAdminAccountsLoad($multiotp)
{
  $file = MultiotpAdminAccountsFilePath($multiotp);
  if (!file_exists($file)) {
    return array();
  }
  $content = @file_get_contents($file);
  if ((false === $content) || ('' == nullable_trim($content))) {
    return array();
  }
  $json = MultiotpAdminAccountsDecrypt($multiotp, nullable_trim($content));
  $data = json_decode($json, true);
  return (is_array($data) && isset($data['accounts']) && is_array($data['accounts'])) ? $data['accounts'] : array();
}

/**
 * @brief   Encrypt and save the administrator accounts array.
 */
function MultiotpAdminAccountsSave($multiotp, $accounts)
{
  $file = MultiotpAdminAccountsFilePath($multiotp);
  $json = json_encode(array('accounts' => array_values($accounts)));
  if (false === @file_put_contents($file, MultiotpAdminAccountsEncrypt($multiotp, $json), LOCK_EX)) {
    return false;
  }
  @chmod($file, ('' != $multiotp->GetLinuxFileMode()) ? octdec($multiotp->GetLinuxFileMode()) : 0666);
  return file_exists($file);
}

/**
 * @brief   Return the index of an account (case-insensitive) or -1.
 */
function MultiotpAdminAccountsFindIndex($accounts, $username)
{
  $username = nullable_trim(mb_strtolower($username, 'UTF-8'));
  foreach ($accounts as $index => $one_account) {
    if (isset($one_account['username']) && (nullable_trim(mb_strtolower($one_account['username'], 'UTF-8')) == $username)) {
      return $index;
    }
  }
  return -1;
}

/**
 * @brief   Check the syntax of a new administrator username.
 */
function MultiotpAdminAccountsCheckUsername($username)
{
  return ((strlen($username) >= 2) &&
          (strlen($username) <= 64) &&
          (1 === preg_match('/^[A-Za-z0-9_.@-]+$/', $username)) &&
          (0 !== strcasecmp($username, 'admin')));
}

/**
 * @brief   Normalize the role value ('admin' or 'super_admin').
 */
function MultiotpAdminAccountsNormalizeRole($role)
{
  return ('super_admin' == $role) ? 'super_admin' : 'admin';
}

/**
 * @brief   Count enabled super_admin accounts (JSON accounts only).
 *          The built-in 'admin' account always remains available, but
 *          the console refuses to leave no enabled super_admin account
 *          in the accounts file (lastSuperAdmin protection).
 */
function MultiotpAdminAccountsEnabledSuperAdminCount($accounts)
{
  $count = 0;
  foreach ($accounts as $one_account) {
    if (('super_admin' == $one_account['role']) && (!empty($one_account['enabled']))) {
      $count++;
    }
  }
  return $count;
}

/**
 * @brief   Write an entry in the multiOTP log for admin account actions.
 */
function MultiotpAdminAccountsLog($multiotp, $text, $category = 'Authentication', $user = '')
{
  $multiotp->WriteLog(array('text'       => $text,
                            'error_code' => _MULTIOTP_OPERATION_DONE_,
                            'category'   => $category,
                            'user'       => $user));
}

/**
 * @brief   Role of the current console session:
 *          the built-in 'admin' account and any super_admin
 *          account are allowed to manage administrator accounts.
 */
function MultiotpAdminAccountsCurrentRole()
{
  if (isset($_SESSION['multiotp_admin_role']) && ('' != $_SESSION['multiotp_admin_role'])) {
    return $_SESSION['multiotp_admin_role'];
  }
  return 'super_admin'; // Built-in 'admin' account session
}

/**
 * @brief   Username of the current console session ('admin' for the built-in account).
 */
function MultiotpAdminAccountsCurrentUser()
{
  if (isset($_SESSION['multiotp_admin_user']) && ('' != $_SESSION['multiotp_admin_user'])) {
    return $_SESSION['multiotp_admin_user'];
  }
  return 'admin';
}

/**
 * @brief   Authenticate an administrator account against the encrypted
 *          database. $client_hash is md5(hash_salt + password + hash_salt).
 * @return  array('username'=>..., 'role'=>...) on success, FALSE otherwise.
 */
function MultiotpAdminAccountsAuthenticate($multiotp, $username, $client_hash)
{
  $accounts = MultiotpAdminAccountsLoad($multiotp);
  $index    = MultiotpAdminAccountsFindIndex($accounts, $username);
  $now      = time();
  $source_ip = $multiotp->GetSourceIp();

  if (('' == nullable_trim($username)) || ('' == nullable_trim($client_hash))) {
    // Empty credentials (for example at logout), nothing to check and nothing to log
    return false;
  }

  if (-1 == $index) {
    MultiotpAdminAccountsLog($multiotp, "Warning: unknown administrator account \"$username\" login denied on console from IP $source_ip", 'Authentication', $username);
    return false;
  }

  $account = $accounts[$index];

  if (empty($account['enabled'])) {
    MultiotpAdminAccountsLog($multiotp, "Warning: disabled administrator account \"$username\" login denied on console from IP $source_ip", 'Authentication', $username);
    return false;
  }

  if ((isset($account['locked_until'])) && (intval($account['locked_until']) > $now)) {
    MultiotpAdminAccountsLog($multiotp, "Warning: locked administrator account \"$username\" login denied on console from IP $source_ip", 'Authentication', $username);
    return false;
  }

  if ((false !== $client_hash) &&
      ('' != $client_hash) &&
      (isset($account['hash'])) &&
      (password_verify($client_hash, $account['hash']))) {
    // Successful authentication: reset the failure counter
    $accounts[$index]['failed']      = 0;
    $accounts[$index]['locked_until'] = 0;
    $accounts[$index]['last_login']  = date('Y-m-d H:i:s');
    MultiotpAdminAccountsSave($multiotp, $accounts);
    MultiotpAdminAccountsLog($multiotp, "Info: administrator account \"$username\" successfully logged in on console", 'Authentication', $username);
    return array('username' => $account['username'],
                 'role'     => MultiotpAdminAccountsNormalizeRole($account['role']));
  }

  // Failed authentication: increase the counter and lock if needed
  $accounts[$index]['failed'] = (isset($account['failed']) ? intval($account['failed']) : 0) + 1;
  if ($accounts[$index]['failed'] >= MULTIOTP_ADMINS_MAX_FAILED) {
    $accounts[$index]['locked_until'] = $now + MULTIOTP_ADMINS_LOCK_SECONDS;
    MultiotpAdminAccountsLog($multiotp, "Error: administrator account \"$username\" locked for ".MULTIOTP_ADMINS_LOCK_SECONDS." seconds after ".$accounts[$index]['failed']." failed attempts from IP $source_ip", 'Authentication', $username);
  } else {
    MultiotpAdminAccountsLog($multiotp, "Warning: administrator account \"$username\" login denied on console from IP $source_ip", 'Authentication', $username);
  }
  MultiotpAdminAccountsSave($multiotp, $accounts);
  return false;
}

/**
 * @brief   Create a new administrator account.
 *          $client_hash is md5(hash_salt + password + hash_salt) sent by the console.
 * @return  TRUE on success, FALSE or an error message string otherwise.
 */
function MultiotpAdminAccountsCreate($multiotp, $username, $client_hash, $role, $creator)
{
  $username = nullable_trim($username);
  $role     = MultiotpAdminAccountsNormalizeRole($role);
  $source_ip = $multiotp->GetSourceIp();

  if (!MultiotpAdminAccountsCheckUsername($username)) {
    MultiotpAdminAccountsLog($multiotp, "Warning: administrator account \"$username\" creation refused by \"$creator\" (invalid or reserved username) from IP $source_ip", 'System', $username);
    return 'invalidUsername';
  }
  if ((strlen($client_hash) < 32) || (0 === preg_match('/^[A-Fa-f0-9]+$/', $client_hash))) {
    return 'invalidPassword';
  }
  $accounts = MultiotpAdminAccountsLoad($multiotp);
  if (-1 != MultiotpAdminAccountsFindIndex($accounts, $username)) {
    MultiotpAdminAccountsLog($multiotp, "Warning: administrator account \"$username\" creation refused by \"$creator\" (already exists) from IP $source_ip", 'System', $username);
    return 'alreadyExists';
  }
  $new_id = mb_substr(md5(date('YmdHis').mt_rand(100000, 999999).$username), 0, 12);
  $accounts[] = array('id'          => $new_id,
                      'username'    => $username,
                      'hash'        => password_hash($client_hash, PASSWORD_BCRYPT),
                      'role'        => $role,
                      'enabled'     => true,
                      'created'     => date('Y-m-d H:i:s'),
                      'modified'    => date('Y-m-d H:i:s'),
                      'last_login'  => '',
                      'failed'      => 0,
                      'locked_until' => 0);
  if (!MultiotpAdminAccountsSave($multiotp, $accounts)) {
    MultiotpAdminAccountsLog($multiotp, "Error: unable to save the administrator accounts file (creation of \"$username\" by \"$creator\")", 'System', $username);
    return 'saveError';
  }
  MultiotpAdminAccountsLog($multiotp, "Info: administrator account \"$username\" (role $role) created by \"$creator\" from IP $source_ip", 'System', $username);
  return true;
}

/**
 * @brief   Modify an existing administrator account (password and/or role).
 *          Empty $client_hash means "keep the current password".
 * @return  TRUE on success, FALSE or an error message string otherwise.
 */
function MultiotpAdminAccountsModify($multiotp, $username, $client_hash, $role, $modifier)
{
  $username  = nullable_trim($username);
  $role      = MultiotpAdminAccountsNormalizeRole($role);
  $source_ip = $multiotp->GetSourceIp();

  $accounts = MultiotpAdminAccountsLoad($multiotp);
  $index    = MultiotpAdminAccountsFindIndex($accounts, $username);
  if (-1 == $index) {
    return 'unknownAccount';
  }
  if (0 === strcasecmp($username, 'admin')) {
    return 'builtInAccount';
  }

  $change_password = false;
  if ('' != nullable_trim($client_hash)) {
    $client_hash = nullable_trim($client_hash);
    if ((strlen($client_hash) < 32) || (0 === preg_match('/^[A-Fa-f0-9]+$/', $client_hash))) {
      return 'invalidPassword';
    }
    $change_password = true;
  }

  // Keep at least one enabled super_admin in the accounts file
  $saved_role = $accounts[$index]['role'];
  $accounts[$index]['role'] = $role;
  if (('super_admin' == $saved_role) && ('super_admin' != $role) &&
      (!empty($accounts[$index]['enabled'])) &&
      (0 == MultiotpAdminAccountsEnabledSuperAdminCount($accounts))) {
    $accounts[$index]['role'] = $saved_role;
    return 'lastSuperAdmin';
  }

  if ($change_password) {
    $accounts[$index]['hash'] = password_hash($client_hash, PASSWORD_BCRYPT);
  }
  $accounts[$index]['modified'] = date('Y-m-d H:i:s');
  if (!MultiotpAdminAccountsSave($multiotp, $accounts)) {
    return 'saveError';
  }
  MultiotpAdminAccountsLog($multiotp, "Info: administrator account \"$username\" modified by \"$modifier\" (role $role".($change_password ? ', password changed' : '').") from IP $source_ip", 'System', $username);
  return true;
}

/**
 * @brief   Enable or disable an administrator account.
 * @return  TRUE on success, FALSE or an error message string otherwise.
 */
function MultiotpAdminAccountsSetEnabled($multiotp, $username, $enabled, $modifier)
{
  $username  = nullable_trim($username);
  $source_ip = $multiotp->GetSourceIp();

  if (0 === strcasecmp($username, 'admin')) {
    return 'builtInAccount';
  }
  $accounts = MultiotpAdminAccountsLoad($multiotp);
  $index    = MultiotpAdminAccountsFindIndex($accounts, $username);
  if (-1 == $index) {
    return 'unknownAccount';
  }
  if (0 === strcasecmp($username, $modifier)) {
    return 'selfAccount';
  }
  $previous_state = !empty($accounts[$index]['enabled']);
  if ($previous_state == $enabled) {
    return true; // Nothing to do
  }
  $accounts[$index]['enabled'] = $enabled;
  if (!$enabled && ('super_admin' == $accounts[$index]['role']) &&
      (0 == MultiotpAdminAccountsEnabledSuperAdminCount($accounts))) {
    $accounts[$index]['enabled'] = $previous_state;
    return 'lastSuperAdmin';
  }
  $accounts[$index]['enabled']      = $enabled;
  $accounts[$index]['failed']       = 0;
  $accounts[$index]['locked_until'] = 0;
  $accounts[$index]['modified']     = date('Y-m-d H:i:s');
  if (!MultiotpAdminAccountsSave($multiotp, $accounts)) {
    return 'saveError';
  }
  MultiotpAdminAccountsLog($multiotp, "Info: administrator account \"$username\" ".($enabled ? 'enabled' : 'disabled')." by \"$modifier\" from IP $source_ip", 'System', $username);
  return true;
}

/**
 * @brief   Delete an administrator account.
 * @return  TRUE on success, FALSE or an error message string otherwise.
 */
function MultiotpAdminAccountsDelete($multiotp, $username, $deleter)
{
  $username  = nullable_trim($username);
  $source_ip = $multiotp->GetSourceIp();

  if (0 === strcasecmp($username, 'admin')) {
    return 'builtInAccount';
  }
  $accounts = MultiotpAdminAccountsLoad($multiotp);
  $index    = MultiotpAdminAccountsFindIndex($accounts, $username);
  if (-1 == $index) {
    return 'unknownAccount';
  }
  if (0 === strcasecmp($username, $deleter)) {
    return 'selfAccount';
  }
  $was_enabled_super_admin = (('super_admin' == $accounts[$index]['role']) && (!empty($accounts[$index]['enabled'])));
  array_splice($accounts, $index, 1);
  if ($was_enabled_super_admin &&
      (0 == MultiotpAdminAccountsEnabledSuperAdminCount($accounts))) {
    // Nothing is saved, the accounts file stays unchanged
    return 'lastSuperAdmin';
  }
  if (!MultiotpAdminAccountsSave($multiotp, $accounts)) {
    return 'saveError';
  }
  MultiotpAdminAccountsLog($multiotp, "Info: administrator account \"$username\" deleted by \"$deleter\" from IP $source_ip", 'System', $username);
  return true;
}

/**
 * @brief   Unlock a locked administrator account (reset the failed counter).
 * @return  TRUE on success, FALSE or an error message string otherwise.
 */
function MultiotpAdminAccountsUnlock($multiotp, $username, $unlocker)
{
  $username  = nullable_trim($username);
  $source_ip = $multiotp->GetSourceIp();

  $accounts = MultiotpAdminAccountsLoad($multiotp);
  $index    = MultiotpAdminAccountsFindIndex($accounts, $username);
  if (-1 == $index) {
    return 'unknownAccount';
  }
  $accounts[$index]['failed']       = 0;
  $accounts[$index]['locked_until'] = 0;
  if (!MultiotpAdminAccountsSave($multiotp, $accounts)) {
    return 'saveError';
  }
  MultiotpAdminAccountsLog($multiotp, "Info: administrator account \"$username\" unlocked by \"$unlocker\" from IP $source_ip", 'System', $username);
  return true;
}

/**
 * @brief   Change the password of the CURRENTLY logged administrator account
 *          (multi-admin accounts only; the built-in 'admin' account uses
 *          the classic SetAdminPasswordHash method).
 *          $old_hash and $new_hash are md5(hash_salt + password + hash_salt).
 * @return  TRUE on success, FALSE or an error message string otherwise.
 */
function MultiotpAdminAccountsChangeOwnPassword($multiotp, $old_hash, $new_hash, $username)
{
  $source_ip = $multiotp->GetSourceIp();

  $accounts = MultiotpAdminAccountsLoad($multiotp);
  $index    = MultiotpAdminAccountsFindIndex($accounts, $username);
  if (-1 == $index) {
    return 'unknownAccount';
  }
  $old_hash = nullable_trim($old_hash);
  $new_hash = nullable_trim($new_hash);
  if (('' == $old_hash) || ('' == $new_hash)) {
    return 'invalidPassword';
  }
  if ((strlen($new_hash) < 32) || (0 === preg_match('/^[A-Fa-f0-9]+$/', $new_hash))) {
    return 'invalidPassword';
  }
  if (!password_verify($old_hash, $accounts[$index]['hash'])) {
    MultiotpAdminAccountsLog($multiotp, "Warning: administrator account \"$username\" password change refused (wrong current password) from IP $source_ip", 'System', $username);
    return 'wrongPassword';
  }
  $accounts[$index]['hash']     = password_hash($new_hash, PASSWORD_BCRYPT);
  $accounts[$index]['modified'] = date('Y-m-d H:i:s');
  if (!MultiotpAdminAccountsSave($multiotp, $accounts)) {
    return 'saveError';
  }
  MultiotpAdminAccountsLog($multiotp, "Info: administrator account \"$username\" changed its own password from IP $source_ip", 'System', $username);
  return true;
}

/**
 * @brief   Build the console list of all administrator accounts
 *          (including the built-in 'admin' account as first entry).
 * @return  JSON array (eval-ready) with the non sensitive information.
 */
function MultiotpAdminAccountsListForConsole($multiotp)
{
  $result   = array();
  $now      = time();
  $current  = MultiotpAdminAccountsCurrentUser();

  $result[] = array('username'   => 'admin',
                    'role'       => 'super_admin',
                    'enabled'    => true,
                    'locked'     => false,
                    'last_login' => '',
                    'is_self'    => (0 === strcasecmp($current, 'admin')),
                    'is_builtin' => true);

  $accounts = MultiotpAdminAccountsLoad($multiotp);
  foreach ($accounts as $one_account) {
    $result[] = array('username'   => $one_account['username'],
                      'role'       => MultiotpAdminAccountsNormalizeRole($one_account['role']),
                      'enabled'    => !empty($one_account['enabled']),
                      'locked'     => ((isset($one_account['locked_until'])) && (intval($one_account['locked_until']) > $now)),
                      'last_login' => (isset($one_account['last_login']) ? $one_account['last_login'] : ''),
                      'is_self'    => (0 === strcasecmp($one_account['username'], $current)),
                      'is_builtin' => false);
  }
  return json_encode($result);
}

/** End of the multi-administrator accounts extension **/


/**********************************************************************
 **********************************************************************
 *** LDAP ADMINISTRATOR ACCESS EXTENSION                            ***
 ***                                                                ***
 *** Allows domain accounts (LDAP / Active Directory) to sign in    ***
 *** to this console when the feature is explicitly enabled and     ***
 *** when the user belongs to the authorized organizational unit    ***
 *** (OU) or one of its sub-OUs. Role granted: administrator.       ***
 ***                                                                ***
 *** - Reuses the LDAP settings of the multiOTP configuration       ***
 ***   (domain controllers, port, SSL, base DN, bind DN/password).  ***
 *** - The OU distinguished name is stored encrypted in a dedicated ***
 ***   JSON file (multiotp-ldap-admin.json, AES-256-CBC).           ***
 *** - At sign-in: search the account INSIDE the authorized OU      ***
 ***   subtree, then bind with the user DN to validate the          ***
 ***   password (the console never stores domain passwords).        ***
 *** - Lockout: 5 failed attempts per user+IP, 10 minutes.          ***
 **********************************************************************
 **********************************************************************/

define('MULTIOTP_LDAP_ADMIN_FILENAME', 'multiotp-ldap-admin.json');

/**
 * @brief   Full path of the encrypted LDAP administrator access file.
 */
function MultiotpLdapAdminFilePath($multiotp)
{
  return $multiotp->GetConfigFolder(true, false).MULTIOTP_LDAP_ADMIN_FILENAME;
}

/**
 * @brief   Encrypt the LDAP admin access configuration (AES-256-CBC).
 */
function MultiotpLdapAdminEncrypt($multiotp, $plaintext)
{
  $encryption_key = $multiotp->GetEncryptionKey();
  if (function_exists('openssl_encrypt') && function_exists('openssl_random_pseudo_bytes')) {
    $key    = hash('sha256', 'multiotp-ldap-admin|'.$encryption_key, true);
    $iv     = openssl_random_pseudo_bytes(16);
    $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if (false !== $cipher) {
      return 'MOL1:'.base64_encode($iv.$cipher);
    }
  }
  return 'MOL0:'.$multiotp->Encrypt('ldapadmin', $plaintext, $encryption_key);
}

/**
 * @brief   Decrypt the LDAP admin access configuration.
 */
function MultiotpLdapAdminDecrypt($multiotp, $ciphertext)
{
  if (('' == $ciphertext) || ('MOL' !== mb_substr($ciphertext, 0, 3))) {
    return '';
  }
  $format = mb_substr($ciphertext, 0, 5);
  $data   = mb_substr($ciphertext, 5);
  $encryption_key = $multiotp->GetEncryptionKey();
  if ('MOL1:' == $format) {
    if (!function_exists('openssl_decrypt')) {
      return '';
    }
    $raw = base64_decode($data, true);
    if ((false === $raw) || (strlen($raw) < 17)) {
      return '';
    }
    $key    = hash('sha256', 'multiotp-ldap-admin|'.$encryption_key, true);
    $iv     = substr($raw, 0, 16);
    $result = openssl_decrypt(substr($raw, 16), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return (false === $result) ? '' : $result;
  }
  return $multiotp->Decrypt('ldapadmin', $data, $encryption_key);
}

/**
 * @brief   Default configuration of the LDAP administrator access.
 */
function MultiotpLdapAdminDefaultConfig()
{
  return array('enabled'    => false,
               'ou_dn'      => '',
               'ou_label'   => '',
               'changed_by' => '',
               'changed_at' => 0,
               'fails'      => array());
}

/**
 * @brief   Load the LDAP admin access configuration (defaults merged).
 */
function MultiotpLdapAdminLoad($multiotp)
{
  $config = MultiotpLdapAdminDefaultConfig();
  $file   = MultiotpLdapAdminFilePath($multiotp);
  if (!file_exists($file)) {
    return $config;
  }
  $content = @file_get_contents($file);
  if ((false === $content) || ('' == nullable_trim($content))) {
    return $config;
  }
  $data = json_decode(MultiotpLdapAdminDecrypt($multiotp, nullable_trim($content)), true);
  if (is_array($data)) {
    foreach ($config as $one_key => $one_value) {
      if (isset($data[$one_key])) {
        $config[$one_key] = $data[$one_key];
      }
    }
  }
  if (!is_array($config['fails'])) {
    $config['fails'] = array();
  }
  return $config;
}

/**
 * @brief   Encrypt and save the LDAP admin access configuration.
 */
function MultiotpLdapAdminSave($multiotp, $config)
{
  $file = MultiotpLdapAdminFilePath($multiotp);
  $json = json_encode($config);
  if (false === @file_put_contents($file, MultiotpLdapAdminEncrypt($multiotp, $json), LOCK_EX)) {
    return false;
  }
  @chmod($file, ('' != $multiotp->GetLinuxFileMode()) ? octdec($multiotp->GetLinuxFileMode()) : 0666);
  return file_exists($file);
}

/**
 * @brief   Normalize a distinguished name (single commas, no extra spaces).
 */
function MultiotpLdapAdminNormalizeDn($dn)
{
  return preg_replace('/\s*,\s*/', ',', nullable_trim($dn));
}

/**
 * @brief   Robust check of an OU distinguished name.
 *          Accepts:  OU=test,DC=test,DC=local
 *                    OU=Users,OU=Site,DC=corp,DC=example,DC=com
 *                    OU=My Unit, DC=ad, DC=local   (spaces after commas)
 *                    OU=x,O=y                      (non-AD directory)
 *          The attribute names are case-insensitive; values may contain
 *          letters (accented included), digits, spaces, dots, hyphens,
 *          underscores and parentheses.
 */
function MultiotpLdapAdminIsValidOuDn($dn)
{
  $dn = nullable_trim($dn);
  if ('' == $dn) {
    return false;
  }
  if (strlen($dn) > 512) {
    return false;
  }
  return (1 === preg_match('/^OU\s*=\s*[^,=;\x00-\x1f]+(\s*,\s*(OU|DC|O|CN)\s*=\s*[^,=;\x00-\x1f]+)+$/iu', $dn));
}

/**
 * @brief   Escape a value for an LDAP search filter.
 */
function MultiotpLdapAdminEscapeFilter($value)
{
  if (function_exists('ldap_escape')) {
    return ldap_escape($value, '', LDAP_ESCAPE_FILTER);
  }
  return str_replace(array('\\', '*', '(', ')', "\x00"),
                     array('\5c', '\2a', '\28', '\29', '\00'),
                     $value);
}

/**
 * @brief   Connect and bind to the LDAP directory using the multiOTP
 *          configuration (domain controllers, port, SSL, bind DN/password).
 *          Returns the connection handle or FALSE ($err receives a code).
 */
function MultiotpLdapAdminLdapConnect($multiotp, &$err)
{
  $err = '';
  if (!function_exists('ldap_connect')) {
    $err = 'phpLdapMissing';
    return false;
  }
  $dc_list = nullable_trim($multiotp->GetLdapDomainControllers());
  if ('' == $dc_list) {
    $err = 'ldapNotConfigured';
    return false;
  }
  $dc_array = preg_split('/[\s;,]+/', $dc_list);
  $use_ssl  = $multiotp->IsLdapSsl();
  $port     = intval($multiotp->GetLdapPort());
  if ($port <= 0) {
    $port = $use_ssl ? 636 : 389;
  }
  $timeout = intval($multiotp->GetLdapNetworkTimeout());
  if ($timeout <= 0) {
    $timeout = 10;
  }
  $last_err = '';
  foreach ($dc_array as $one_dc) {
    $one_dc = nullable_trim($one_dc);
    if ('' == $one_dc) {
      continue;
    }
    /* v1.2: a domain controller entry may already carry its own scheme
       and/or port (example written by the multiOTP configuration:
       ldap://192.168.40.145:389). Only prepend the scheme and the port
       when they are missing (fixes the v1.1 double-scheme URI bug).
       The parsing mirrors the official multiotp.class.php behavior. */
    $proto      = $use_ssl ? 'ldaps://' : 'ldap://';
    $host_part  = $one_dc;
    $entry_port = $port;
    $pos_scheme = mb_strpos($host_part, '://');
    if (false !== $pos_scheme) {
      $proto     = mb_substr($host_part, 0, $pos_scheme + 3);
      $host_part = mb_substr($host_part, $pos_scheme + 3);
    }
    $pos_port = mb_strpos($host_part, ':');
    if (false !== $pos_port) {
      $candidate = intval(mb_substr($host_part, $pos_port + 1));
      if ($candidate > 0) {
        $entry_port = $candidate;
      }
      $host_part = mb_substr($host_part, 0, $pos_port);
    }
    $host = $proto.$host_part.':'.$entry_port;
    /* v1.2: TCP pre-check for a precise diagnostic when the domain
       controller is unreachable or the port is closed. */
    $tcp_errno  = 0;
    $tcp_errstr = '';
    $tcp = @fsockopen($host_part, $entry_port, $tcp_errno, $tcp_errstr, $timeout);
    if (false === $tcp) {
      $last_err = 'TCP connection to '.$host_part.':'.$entry_port.' failed (unreachable or port closed: '.$tcp_errstr.')';
      continue;
    }
    fclose($tcp);
    $conn = @ldap_connect($host);
    if (false === $conn) {
      $last_err = 'connect '.$host.' (invalid LDAP URI)';
      continue;
    }
    @ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    @ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
    @ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, $timeout);
    if ($use_ssl && defined('LDAP_OPT_X_TLS_REQUIRE_CERT')) {
      @ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
    }
    $bind_dn = nullable_trim($multiotp->GetLdapBindDn());
    $bind_pw = $multiotp->GetLdapServerPassword();
    $bound   = @ldap_bind($conn, $bind_dn, $bind_pw);
    if ($bound) {
      return $conn;
    }
    $last_err = 'bind '.$host.': '.@ldap_error($conn);
    @ldap_unbind($conn);
  }
  $err = ('' != $last_err) ? $last_err : 'ldapError';
  return false;
}

/**
 * @brief   List the organizational units under the LDAP base DN.
 *          Returns array('ok'=>bool, 'ous'=>array(dn,name), 'error'=>...).
 */
function MultiotpLdapAdminBrowseOusForConsole($multiotp)
{
  $err  = '';
  $conn = MultiotpLdapAdminLdapConnect($multiotp, $err);
  if (false === $conn) {
    $message = ('ldapNotConfigured' == $err) ? 'LDAP is not configured in the multiOTP configuration (no domain controller)'
             : (('phpLdapMissing' == $err) ? 'PHP LDAP extension not installed on this server'
             : 'LDAP connection failed: '.$err);
    return array('ok' => false, 'ous' => array(), 'error' => $message);
  }
  $base_dn = nullable_trim($multiotp->GetLdapBaseDn());
  if ('' == $base_dn) {
    @ldap_unbind($conn);
    return array('ok' => false, 'ous' => array(), 'error' => 'LDAP base DN is empty in the multiOTP configuration');
  }
  $ous    = array();
  $search = @ldap_search($conn, $base_dn, '(objectClass=organizationalUnit)', array('dn', 'name'), 0, 500);
  if (false === $search) {
    $search = @ldap_list($conn, $base_dn, '(objectClass=organizationalUnit)', array('dn', 'name'), 0, 500);
  }
  if (false !== $search) {
    $entries = @ldap_get_entries($conn, $search);
    if (is_array($entries)) {
      for ($i = 0; $i < intval($entries['count']); $i++) {
        $one_dn   = isset($entries[$i]['dn']) ? $entries[$i]['dn'] : '';
        $one_name = (isset($entries[$i]['name']) && isset($entries[$i]['name'][0])) ? $entries[$i]['name'][0] : $one_dn;
        if ('' != $one_dn) {
          $ous[] = array('dn' => $one_dn, 'name' => $one_name);
        }
      }
    }
  }
  @ldap_unbind($conn);
  usort($ous, function ($a, $b) {
    $da = substr_count(mb_strtolower($a['dn'], 'UTF-8'), 'dc=');
    $db = substr_count(mb_strtolower($b['dn'], 'UTF-8'), 'dc=');
    if ($da != $db) {
      return ($da < $db) ? -1 : 1;
    }
    return strcasecmp($a['dn'], $b['dn']);
  });
  return array('ok' => true, 'ous' => $ous, 'error' => '');
}

/**
 * @brief   Register a failed LDAP sign-in attempt (lockout 5 x 10 minutes).
 */
function MultiotpLdapAdminRegisterFail($multiotp, $config, $key)
{
  $now = time();
  foreach ($config['fails'] as $one_key => $one_data) {
    if ((!isset($one_data['until'])) || (intval($one_data['until']) < $now)) {
      unset($config['fails'][$one_key]);
    }
  }
  $count = (isset($config['fails'][$key]['count'])) ? intval($config['fails'][$key]['count']) : 0;
  $count++;
  $config['fails'][$key] = array('count' => $count,
                                 'until' => ($count >= 5) ? ($now + 600) : 0);
  MultiotpLdapAdminSave($multiotp, $config);
}

/**
 * @brief   Try to authenticate a domain account against the authorized OU.
 *          Returns array(username, dn, display) on success, FALSE otherwise
 *          ($err receives a code for the log / console messages).
 */
function MultiotpLdapAdminTryLogin($multiotp, $username, $password, &$err)
{
  $err    = '';
  $config = MultiotpLdapAdminLoad($multiotp);
  if ((empty($config['enabled'])) || ('' == nullable_trim($config['ou_dn']))) {
    $err = 'disabled';
    return false;
  }
  if (('' == nullable_trim($username)) || ('' == $password)) {
    $err = 'badCredentials';
    return false;
  }
  $now  = time();
  $key  = md5(mb_strtolower(nullable_trim($username), 'UTF-8').'|'.(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli'));
  foreach ($config['fails'] as $one_key => $one_data) {
    if (isset($one_data['until']) && (intval($one_data['until']) >= $now) && ($one_key == $key)
        && (isset($one_data['count'])) && (intval($one_data['count']) >= 5)) {
      $err = 'locked';
      MultiotpAdminAccountsLog($multiotp, 'LDAP admin access: account '.$username.' is locked (too many failed attempts)', 'Authentication', $username);
      return false;
    }
  }
  $conn = MultiotpLdapAdminLdapConnect($multiotp, $lerr);
  if (false === $conn) {
    $err = $lerr;
    MultiotpAdminAccountsLog($multiotp, 'LDAP admin access: connection error ('.$lerr.') during sign-in of '.$username, 'Authentication', $username);
    return false;
  }
  $ou_dn  = MultiotpLdapAdminNormalizeDn($config['ou_dn']);
  $filter = '(&(objectClass=user)(!(objectClass=computer))(|(sAMAccountName='.MultiotpLdapAdminEscapeFilter($username)
           .')(userPrincipalName='.MultiotpLdapAdminEscapeFilter($username).')(uid='
           .MultiotpLdapAdminEscapeFilter($username).')(cn='.MultiotpLdapAdminEscapeFilter($username).')))';
  $search = @ldap_search($conn, $ou_dn, $filter, array('dn', 'samaccountname', 'displayname', 'useraccountcontrol'), 0, 6);
  $entry  = ((false !== $search) ? @ldap_first_entry($conn, $search) : false);
  if (false === $entry) {
    @ldap_unbind($conn);
    MultiotpAdminAccountsLog($multiotp, 'LDAP admin access: user '.$username.' not found in the authorized OU ('.$ou_dn.')', 'Authentication', $username);
    $err = 'badCredentials';
    MultiotpLdapAdminRegisterFail($multiotp, $config, $key);
    return false;
  }
  $user_dn  = @ldap_get_dn($conn, $entry);
  $uac      = 0;
  $uac_data = @ldap_get_values_len($conn, $entry, 'useraccountcontrol');
  if (is_array($uac_data) && isset($uac_data[0])) {
    $uac = intval($uac_data[0]);
  }
  if (0 !== ($uac & 2)) { // ACCOUNTDISABLE
    @ldap_unbind($conn);
    MultiotpAdminAccountsLog($multiotp, 'LDAP admin access: account '.$username.' is disabled in the directory', 'Authentication', $username);
    $err = 'badCredentials';
    MultiotpLdapAdminRegisterFail($multiotp, $config, $key);
    return false;
  }
  if (!@ldap_bind($conn, $user_dn, $password)) {
    @ldap_unbind($conn);
    MultiotpAdminAccountsLog($multiotp, 'LDAP admin access: wrong password for '.$username.' ('.$user_dn.')', 'Authentication', $username);
    $err = 'badCredentials';
    MultiotpLdapAdminRegisterFail($multiotp, $config, $key);
    return false;
  }
  // Defense in depth: the user DN must belong to the authorized OU subtree
  $ou_len = mb_strlen($ou_dn, 'UTF-8');
  if (($ou_len <= 0) || (0 !== strcasecmp(mb_substr($user_dn, -$ou_len, $ou_len, 'UTF-8'), $ou_dn))) {
    @ldap_unbind($conn);
    MultiotpAdminAccountsLog($multiotp, 'LDAP admin access: '.$user_dn.' is OUTSIDE the authorized OU ('.$ou_dn.')', 'Authentication', $username);
    $err = 'badCredentials';
    MultiotpLdapAdminRegisterFail($multiotp, $config, $key);
    return false;
  }
  @ldap_unbind($conn);
  if (isset($config['fails'][$key])) {
    unset($config['fails'][$key]);
    MultiotpLdapAdminSave($multiotp, $config);
  }
  $display = $username;
  MultiotpAdminAccountsLog($multiotp, 'LDAP admin access: user '.$username.' authenticated via LDAP ('.$user_dn.')', 'Authentication', $username);
  return array('username' => $username, 'dn' => $user_dn, 'display' => $display);
}

/**
 * @brief   Apply the console configuration (options: enabled \t OU DN \t label).
 */
function MultiotpLdapAdminApplyFromOptions($multiotp, $options_array, $modifier)
{
  $enabled = ('1' == nullable_trim(isset($options_array[0]) ? $options_array[0] : ''));
  $dn      = nullable_trim(isset($options_array[1]) ? $options_array[1] : '');
  $label   = nullable_trim(isset($options_array[2]) ? $options_array[2] : '');
  if ($enabled && ('' == $dn)) {
    return 'ouRequired';
  }
  if ('' != $dn) {
    if (!MultiotpLdapAdminIsValidOuDn($dn)) {
      return 'invalidOuDn';
    }
    $dn = MultiotpLdapAdminNormalizeDn($dn);
  }
  if ('' == $label) {
    $label = $dn;
  }
  $config               = MultiotpLdapAdminLoad($multiotp);
  $config['enabled']    = $enabled;
  $config['ou_dn']      = $dn;
  $config['ou_label']   = $label;
  $config['changed_by'] = $modifier;
  $config['changed_at'] = time();
  if (!MultiotpLdapAdminSave($multiotp, $config)) {
    return 'saveError';
  }
  MultiotpAdminAccountsLog($multiotp, 'LDAP admin access '.($enabled ? 'ENABLED' : 'disabled').(('' != $dn) ? ' - authorized OU: '.$dn : '').' by '.$modifier, 'System', $modifier);
  return 'true';
}

/**
 * @brief   Status of the feature for the console panel.
 */
function MultiotpLdapAdminStatusArray($multiotp)
{
  $config = MultiotpLdapAdminLoad($multiotp);
  return array('enabled'         => !empty($config['enabled']),
               'ou_dn'           => (isset($config['ou_dn']) ? $config['ou_dn'] : ''),
               'ou_label'        => (isset($config['ou_label']) ? $config['ou_label'] : ''),
               'changed_by'      => (isset($config['changed_by']) ? $config['changed_by'] : ''),
               'changed_at'      => (isset($config['changed_at']) ? intval($config['changed_at']) : 0),
               'ldap_configured' => ('' != nullable_trim($multiotp->GetLdapDomainControllers())),
               'php_ldap'        => function_exists('ldap_connect'),
               'can_edit'        => ('super_admin' == MultiotpAdminAccountsCurrentRole()));
}




/**********************************************************************
 **********************************************************************
 *** HIGH AVAILABILITY (MASTER / SLAVE) EXTENSION v3                ***
 ***                                                                ***
 *** Pull model: the SLAVE pulls a backup of the users/tokens flat  ***
 *** files from the MASTER, at a configurable interval in minutes.  ***
 ***                                                                ***
 *** v3 NEW - INSTANT PUSH (event driven synchronization): when the ***
 *** data changes on the MASTER, the slaves are notified at once    ***
 *** and pull immediately (no need to wait for the cron interval): ***
 ***  1) console/API/SOAP request watch: if the request changed the ***
 ***     users/tokens data, the slaves are notified right after the ***
 ***     response is sent (register_shutdown_function);             ***
 ***  2) master watcher task: "php multiotp.server.php              ***
 ***     ha-push-check" every minute covers the changes done        ***
 ***     OUTSIDE the console (multiotp.exe CLI, LDAP sync, ...);    ***
 ***  3) HaNotifyPull (slave, token-checked) answers AT ONCE and    ***
 ***     runs the pull in a DETACHED background job (v3.9 fix: doing***
 ***     the pull inline DEADLOCKS a single-worker master console); ***
 ***     the job checks the revision and pulls ONLY on change;      ***
 ***  4) HaGetRevision (master, token-checked) returns a cheap      ***
 ***     fingerprint of the users/tokens data;                      ***
 ***  5) v3.9 - Send test push verifies the REAL replication: the   ***
 ***     master polls HaSlaveStatus until the revisions match.      ***
 *** The cron interval is KEPT as a periodic full-refresh safety    ***
 *** net (and for the auth-driven internal state updates).          ***
 *** v2 FIX - the "Synchronization interval in minutes (slave       ***
 *** cron)" setting is now REALLY enforced, through 3 paths:        ***
 ***  1) CLI entry point: "php multiotp.server.php ha-cron-pull"    ***
 ***     can be called by a Windows scheduled task / Linux cron     ***
 ***     every minute; the CLI itself checks the stored interval    ***
 ***     and pulls only when due, so changing the interval in the   ***
 ***     console is enough (no task re-creation needed);            ***
 ***  2) "Create/update scheduled task" button: installs the task   ***
 ***     automatically (schtasks on Windows); if exec() is          ***
 ***     disabled, the exact command is returned for manual setup;  ***
 ***  3) lazy safety net: a console page load triggers the pull if  ***
 ***     the interval is already elapsed (best effort).             ***
 ***                                                                ***
 *** Config: encrypted AES-256-CBC (MOH1 format) in the file        ***
 *** multiotp-ha-config.json of the config folder. The transfered   ***
 *** backup payload is encrypted with the shared HA secret.         ***
 ***  v3.10: peer tokens are HMAC-SHA256 (md5 legacy accepted)      ***
 ***  v4.0: CLUSTER multi-master (AD-like): every console accepts   ***
 ***  changes, LWW merge + tombstones, peer list replicates too     ***
 **********************************************************************
 **********************************************************************/
define('MULTIOTP_HA_FILENAME',      'multiotp-ha-config.json');
define('MULTIOTP_HA_TASK_NAME',     'multiOTP HA slave pull');
define('MULTIOTP_HA_TASK_NAME_MASTER', 'multiOTP HA master push');
define('MULTIOTP_HA_TASK_NAME_CLUSTER', 'multiOTP HA cluster sync'); // v4.0
define('MULTIOTP_HA_TOMBSTONE_FILENAME', 'multiotp-ha-tombstones.json'); // v4.0
define('MULTIOTP_HA_TOMBSTONE_LIFETIME', 2592000); // v4.0: 30 days (AD-like tombstone lifetime, shortened)
define('MULTIOTP_HA_TOKEN_WINDOW',  300);  // +/- seconds of token freshness

/**
 * @brief   Full path of the encrypted HA configuration file.
 */
function MultiotpHaFilePath($multiotp)
{
  return $multiotp->GetConfigFolder(true, false).MULTIOTP_HA_FILENAME;
}

/**
 * @brief   Default HA configuration.
 */
function MultiotpHaDefaultConfig()
{
  return array('role'                  => 'off',   // off | master | slave | cluster (v4: multi-master, AD-like)
               'master_url'            => '',      // full console URL of the master (slave role)
               'shared_secret'         => '',      // same secret on master and slave
               'interval_minutes'      => 5,       // slave cron pull interval, in minutes
               'last_pull_ts'          => 0,
               'last_pull_attempt_ts'  => 0,
               'last_pull_result'      => '',
               'last_pull_users'       => 0,
               'last_pull_tokens'      => 0,
               'task_installed'        => false,
               'task_last_command'     => '',
               // v3: instant push (master side)
               'push_enabled'          => true,
               'slave_urls'            => '',    // comma-separated slave console URLs
               'last_push_ts'          => 0,
               'last_push_result'      => '',
               'last_pushed_rev'       => '',    // fingerprint of the last pushed state
               // v3: revision tracking (slave side)
               'last_known_rev'        => '',    // fingerprint of the last applied backup
               'own_url'               => '');   // v4.0: this console's own URL (CLI-safe self-exclusion)
}

/**
 * @brief   Encrypt a local HA configuration (AES-256-CBC, MOH1).
 */
function MultiotpHaConfigEncrypt($multiotp, $plaintext)
{
  $encryption_key = $multiotp->GetEncryptionKey();
  if (function_exists('openssl_encrypt') && function_exists('openssl_random_pseudo_bytes')) {
    $key    = hash('sha256', 'multiotp-ha-config|'.$encryption_key, true);
    $iv     = openssl_random_pseudo_bytes(16);
    $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if (false !== $cipher) {
      return 'MOH1:'.base64_encode($iv.$cipher);
    }
  }
  return 'MOH0:'.$multiotp->Encrypt('multiotp-ha', $plaintext, $encryption_key);
}

/**
 * @brief   Decrypt a local HA configuration (MOH1/MOH0).
 */
function MultiotpHaConfigDecrypt($multiotp, $ciphertext)
{
  if (('' == $ciphertext) || ('MOH' !== mb_substr($ciphertext, 0, 3))) {
    return '';
  }
  $format = mb_substr($ciphertext, 0, 5);
  $data   = mb_substr($ciphertext, 5);
  $encryption_key = $multiotp->GetEncryptionKey();
  if ('MOH1:' == $format) {
    if (!function_exists('openssl_decrypt')) {
      return '';
    }
    $raw = base64_decode($data, true);
    if ((false === $raw) || (strlen($raw) < 17)) {
      return '';
    }
    $key    = hash('sha256', 'multiotp-ha-config|'.$encryption_key, true);
    $iv     = substr($raw, 0, 16);
    $result = openssl_decrypt(substr($raw, 16), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return (false === $result) ? '' : $result;
  }
  return $multiotp->Decrypt('multiotp-ha', $data, $encryption_key);
}

/**
 * @brief   Load the HA configuration (defaults merged with stored values).
 */
function MultiotpHaLoadConfig($multiotp)
{
  $config = MultiotpHaDefaultConfig();
  $file   = MultiotpHaFilePath($multiotp);
  if (@file_exists($file)) {
    $content = @file_get_contents($file);
    if ((false !== $content) && ('' != nullable_trim($content))) {
      $json = MultiotpHaConfigDecrypt($multiotp, nullable_trim($content));
      $data = json_decode($json, true);
      if (is_array($data)) {
        foreach ($config as $one_key => $one_value) {
          if (isset($data[$one_key])) {
            $config[$one_key] = $data[$one_key];
          }
        }
      }
    }
  }
  return $config;
}

/**
 * @brief   Save the HA configuration (encrypted).
 */
function MultiotpHaSaveConfig($multiotp, $config)
{
  $file = MultiotpHaFilePath($multiotp);
  $json = json_encode($config);
  return @file_put_contents($file, MultiotpHaConfigEncrypt($multiotp, $json)."\n");
}

/**
 * @brief   One-time token: md5(action|secret|ts), +/- window seconds.
 *          v3.10: LEGACY format, kept for rolling upgrades (an older
 *          peer that only sends this token is still accepted).
 */
function MultiotpHaMakeToken($secret, $ts, $action)
{
  return md5('multiotp-ha|'.$action.'|'.$secret.'|'.$ts);
}

/**
 * @brief   v3.10: STRONG one-time token: HMAC-SHA256(action|ts, secret).
 *          Keyed with the shared secret (standard construction): a token
 *          captured on plain HTTP can no longer be used to brute-force
 *          the secret offline (the legacy md5 allowed it in theory).
 */
function MultiotpHaMakeTokenStrong($secret, $ts, $action)
{
  return hash_hmac('sha256', 'multiotp-ha|'.$action.'|'.intval($ts), (string) $secret);
}

/**
 * @brief   Check a one-time token with freshness window.
 *          v3.10: the STRONG token ($token2) is verified first; if it is
 *          absent or does not match, the LEGACY md5 token is checked, so
 *          a peer running a previous version keeps working unchanged
 *          (rolling upgrade in any direction).
 */
function MultiotpHaCheckToken($secret, $ts, $token, $action, $token2 = '')
{
  $ts = intval($ts);
  if (($ts < (time() - MULTIOTP_HA_TOKEN_WINDOW)) || ($ts > (time() + MULTIOTP_HA_TOKEN_WINDOW))) {
    return false;
  }
  $token2 = nullable_trim($token2);
  if (('' != $token2) && hash_equals(MultiotpHaMakeTokenStrong($secret, $ts, $action), $token2)) {
    return true;
  }
  return hash_equals(MultiotpHaMakeToken($secret, $ts, $action), nullable_trim($token));
}

/**
 * @brief   Simple HTTP GET (curl if available, then file_get_contents).
 *          v3.2: the transport failure reason is recorded (curl_errno /
 *          curl_error, or the last PHP warning for the stream fallback),
 *          so the master console can display WHY a slave was unreachable
 *          (connection refused / timed out / could not resolve host...).
 */
function MultiotpHaHttpGet($url, $timeout = 10)
{
  $result = false;
  $error  = '';
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true,
                                 CURLOPT_TIMEOUT        => $timeout,
                                 CURLOPT_CONNECTTIMEOUT => $timeout,
                                 CURLOPT_FOLLOWLOCATION => false,
                                 CURLOPT_SSL_VERIFYPEER => false,
                                 CURLOPT_SSL_VERIFYHOST => 0,
                                 CURLOPT_USERAGENT      => 'multiotp-ha'));
    $result = curl_exec($ch);
    if (false === $result) {
      $error = 'curl #'.curl_errno($ch).': '.trim((string) curl_error($ch));
    }
    curl_close($ch);
  }
  if (false === $result) {
    $context = stream_context_create(array('http' => array('timeout'      => $timeout,
                                                           'ignore_errors' => true,
                                                           'header'        => "User-Agent: multiotp-ha\r\n"),
                                           'ssl'  => array('verify_peer'      => false,   // v3.5: SAME policy as
                                                           'verify_peer_name' => false,   // the curl branch
                                                           'allow_self_signed'=> true))); // (console HTTPS =
    $result = @file_get_contents($url, false, $context);   // self-signed certificates)
    if (false === $result) {
      $last = error_get_last();
      if (is_array($last) && isset($last['message']) && ('' != trim((string) $last['message']))) {
        $error = trim((string) $last['message']); // stream fallback is the LAST attempt, its reason wins
      }
    }
  }
  $GLOBALS['multiotp_ha_http_last_error'] = (false === $result) ? $error : '';
  return $result;
}

/**
 * @brief   v3.2: reason of the LAST MultiotpHaHttpGet failure ('' if the
 *          last call succeeded or nothing was recorded yet).
 */
function MultiotpHaHttpGetLastError()
{
  return isset($GLOBALS['multiotp_ha_http_last_error']) ? (string) $GLOBALS['multiotp_ha_http_last_error'] : '';
}

/**
 * @brief   v3.2: human label for a transport failure, e.g.
 *          "unreachable (curl #7: Failed to connect... Connection refused)".
 *          Makes the Send test push result actionable at a glance.
 */
function MultiotpHaUnreachableLabel()
{
  $reason = trim(MultiotpHaHttpGetLastError());
  // v3.4: PHP stream warnings start with "file_get_contents(<long URL>): <REAL REASON>".
  // The URL must not eat the size budget: keep only the part after the last "): ".
  if (false !== ($pos = mb_strrpos($reason, '): '))) {
    $reason = trim(mb_substr($reason, $pos + 3));
  }
  return ('' == $reason) ? 'unreachable' : 'unreachable ('.mb_substr($reason, 0, 200).')';
}

/**
 * @brief   v3.3: TRUE only when the notify attempt was valid (ok flag)
 *          AND every slave actually answered (no 'unreachable' entry).
 *          Used to decide whether the pushed revision may be recorded:
 *          a failed notification must NOT be recorded, otherwise the
 *          per-minute push check would consider the data as already
 *          replicated and would NEVER retry (observed in production as
 *          "the slave only receives the change via its 5-min cron").
 */
function MultiotpHaNotifyAllReached($notify)
{
  if (!is_array($notify) || empty($notify['ok'])
      || empty($notify['results']) || !is_array($notify['results'])) {
    return false;
  }
  foreach ($notify['results'] as $one) {
    if (!is_array($one) || !isset($one['answer'])
        || (0 === mb_strpos((string) $one['answer'], 'unreachable'))) {
      return false;
    }
  }
  return true;
}

/**
 * @brief   Encrypt the backup payload with the SHARED SECRET (both peers).
 *          Requires openssl; without it, HA transfer is refused.
 */
function MultiotpHaPayloadEncrypt($secret, $plaintext)
{
  if (!function_exists('openssl_encrypt') || !function_exists('openssl_random_pseudo_bytes')) {
    return '';
  }
  $key    = hash('sha256', 'multiotp-ha-payload|'.$secret, true);
  $iv     = openssl_random_pseudo_bytes(16);
  $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
  if (false === $cipher) {
    return '';
  }
  return 'MHP1:'.base64_encode($iv.$cipher);
}

/**
 * @brief   Decrypt the backup payload (MHP1) with the shared secret.
 */
function MultiotpHaPayloadDecrypt($secret, $blob)
{
  if (('' == $blob) || ('MHP' !== mb_substr($blob, 0, 3)) || !function_exists('openssl_decrypt')) {
    return '';
  }
  if ('MHP1:' != mb_substr($blob, 0, 5)) {
    return '';
  }
  $raw = base64_decode(mb_substr($blob, 5), true);
  if ((false === $raw) || (strlen($raw) < 17)) {
    return '';
  }
  $key    = hash('sha256', 'multiotp-ha-payload|'.$secret, true);
  $iv     = substr($raw, 0, 16);
  $result = openssl_decrypt(substr($raw, 16), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
  return (false === $result) ? '' : $result;
}

/**
 * @brief   Normalize a folder path with a trailing slash.
 */
function MultiotpHaNormFolder($folder)
{
  return rtrim(nullable_trim($folder), '/\\').'/';
}

/** v4.0 CLUSTER MULTI-MASTER HELPERS *********************************/

/**
 * @brief   v4.0: TRUE for the roles that SERVE the data to their peers
 *          (answer HaPing/HaGetBackup with a valid token).
 */
function MultiotpHaServesData($config)
{
  return in_array(isset($config['role']) ? $config['role'] : '', array('master', 'cluster'));
}

/**
 * @brief   v4.0: TRUE for the roles that NOTIFY their peers on a local
 *          data change (instant push / cluster replication).
 */
function MultiotpHaPushesData($config)
{
  return in_array(isset($config['role']) ? $config['role'] : '', array('master', 'cluster'));
}

/**
 * @brief   v4.0: full path of the tombstone store (deletion journal).
 */
function MultiotpHaTombstonePath($multiotp)
{
  return $multiotp->GetConfigFolder(true, false).MULTIOTP_HA_TOMBSTONE_FILENAME;
}

/**
 * @brief   v4.0: load the tombstone store: array('kind|lowername' => array('ts'=>, 'origin'=>)).
 */
function MultiotpHaTombstoneLoad($multiotp)
{
  $content = @file_get_contents(MultiotpHaTombstonePath($multiotp));
  if ((false === $content) || ('' == nullable_trim($content))) {
    return array();
  }
  $data = json_decode($content, true);
  return (is_array($data) && isset($data['tombstones']) && is_array($data['tombstones']))
         ? $data['tombstones'] : array();
}

/**
 * @brief   v4.0: save the tombstone store (atomic tmp+rename).
 */
function MultiotpHaTombstoneSave($multiotp, $tombstones)
{
  $tmp = MultiotpHaTombstonePath($multiotp).'.tmp-'.getmypid();
  if (false === @file_put_contents($tmp, json_encode(array('ver' => 1, 'tombstones' => $tombstones)))) {
    @unlink($tmp);
    return false;
  }
  @rename($tmp, MultiotpHaTombstonePath($multiotp));
  return true;
}

/**
 * @brief   v4.0: drop the tombstones older than the lifetime (30 days).
 */
function MultiotpHaTombstonePrune($tombstones, $now)
{
  $limit = intval($now) - MULTIOTP_HA_TOMBSTONE_LIFETIME;
  $fresh = array();
  foreach ($tombstones as $one_key => $one_entry) {
    if (is_array($one_entry) && isset($one_entry['ts']) && (intval($one_entry['ts']) >= $limit)) {
      $fresh[$one_key] = $one_entry;
    }
  }
  return $fresh;
}

/**
 * @brief   v4.0: tombstone map key (kind + lowercased name).
 */
function MultiotpHaTombstoneKey($kind, $name)
{
  return $kind.'|'.mb_strtolower((string) $name);
}

/**
 * @brief   v4.0: record one tombstone (keep the NEWEST ts per key).
 */
function MultiotpHaTombstoneRecord($tombstones, $kind, $name, $ts, $origin)
{
  $key = MultiotpHaTombstoneKey($kind, $name);
  if (!isset($tombstones[$key]) || !is_array($tombstones[$key])
      || (intval($tombstones[$key]['ts']) < intval($ts))) {
    $tombstones[$key] = array('ts' => intval($ts), 'origin' => (string) $origin);
  }
  return $tombstones;
}

/**
 * @brief   v4.0: PURE last-writer-wins decision, deterministic AND symmetric
 *          (both peers compute the SAME winner -> guaranteed convergence).
 *          Newer mtime wins; equal mtime: larger content wins (byte compare);
 *          identical bytes: nothing to do.
 * @return  'remote' | 'local' | 'same'
 */
function MultiotpHaLwwDecide($local_mtime, $local_content, $remote_mtime, $remote_content)
{
  $local_mtime  = intval($local_mtime);
  $remote_mtime = intval($remote_mtime);
  if ($remote_mtime > $local_mtime) { return 'remote'; }
  if ($remote_mtime < $local_mtime) { return 'local'; }
  $local_bytes  = (string) $local_content;
  $remote_bytes = (string) $remote_content;
  if ($local_bytes === $remote_bytes) { return 'same'; }
  if (strlen($remote_bytes) != strlen($local_bytes)) {
    return (strlen($remote_bytes) > strlen($local_bytes)) ? 'remote' : 'local';
  }
  return (strcmp($remote_bytes, $local_bytes) > 0) ? 'remote' : 'local';
}

/**
 * @brief   Build the backup payload (MASTER side): users + tokens .db files.
 *          The .db files are transfered as-is (their content stays
 *          encrypted with the local multiOTP encryption key, which must
 *          be the same on both peers - checked with a fingerprint).
 */
function MultiotpHaExportBackup($multiotp)
{
  $config = MultiotpHaLoadConfig($multiotp);
  if (!MultiotpHaServesData($config)) {   // v4.0: master AND cluster serve their peers
    return array('error' => 'not_master');
  }
  $secret = nullable_trim($config['shared_secret']);
  if ('' == $secret) {
    return array('error' => 'no_secret');
  }
  $files  = array('users' => array(), 'tokens' => array());
  $folders = array('users'  => MultiotpHaNormFolder($multiotp->GetUsersFolder()),
                   'tokens' => MultiotpHaNormFolder($multiotp->GetTokensFolder()));
  foreach ($folders as $one_kind => $one_folder) {
    $found = @glob($one_folder.'*.db');
    if (is_array($found)) {
      foreach ($found as $one_file) {
        $name    = basename($one_file);
        $content = @file_get_contents($one_file);
        if ((false !== $content) && preg_match('/^[A-Za-z0-9._\-]+\.db$/', $name)) {
          $files[$one_kind][] = array('name' => $name, 'b64' => base64_encode($content),
                                      'mt' => intval(@filemtime($one_file))); // v4.0: LWW replica clock
        }
      }
    }
  }
  // v4.0: tombstone bookkeeping (multi-master deletions). A file present in
  // the LAST export snapshot but missing NOW has been deleted locally:
  // record a tombstone so the deletion replicates. A file present again with
  // a NEWER-OR-EQUAL mtime clears its tombstone (re-creation wins, AD-like).
  $tombstones = MultiotpHaTombstonePrune(MultiotpHaTombstoneLoad($multiotp), time());
  $snap_path  = MultiotpHaTombstonePath($multiotp).'.snapshot';
  $prev_snap  = array();
  $snap_raw   = @file_get_contents($snap_path);
  if ((false !== $snap_raw) && ('' != nullable_trim($snap_raw))) {
    $prev_dec = json_decode($snap_raw, true);
    if (is_array($prev_dec)) {
      $prev_snap = $prev_dec;
    }
  }
  $now_snap = array();
  foreach ($files as $ha_kind => $ha_list) {
    foreach ($ha_list as $ha_entry) {
      $now_snap[$ha_kind.'|'.$ha_entry['name']] = $ha_entry['mt'];
    }
  }
  foreach ($prev_snap as $ha_key => $ha_mt) {
    if (!isset($now_snap[$ha_key])) {
      $ha_parts = explode('|', (string) $ha_key, 2);
      $tombstones = MultiotpHaTombstoneRecord($tombstones,
                                              isset($ha_parts[0]) ? $ha_parts[0] : 'users',
                                              isset($ha_parts[1]) ? $ha_parts[1] : '',
                                              time(), 'local_delete');
    } elseif (isset($tombstones[$ha_key]) && is_array($tombstones[$ha_key])
              && (intval($tombstones[$ha_key]['ts']) <= intval($now_snap[$ha_key]))) {
      unset($tombstones[$ha_key]);   // re-created after deletion: drop the tombstone
    }
  }
  MultiotpHaTombstoneSave($multiotp, $tombstones);
  @file_put_contents($snap_path, json_encode($now_snap));
  $ha_tomb_list = array();
  foreach ($tombstones as $ha_key => $ha_entry) {
    $ha_parts = explode('|', (string) $ha_key, 2);
    $ha_tomb_list[] = array('kind' => isset($ha_parts[0]) ? $ha_parts[0] : 'users',
                            'name' => isset($ha_parts[1]) ? $ha_parts[1] : '',
                            'ts'   => intval($ha_entry['ts']));
  }
  $payload = array('ver'            => 2,                  // v4.0: mt + tombstones + peers
                   'ts'             => time(),
                   'key_fingerprint' => strtoupper(md5($multiotp->GetEncryptionKey())),
                   'files'          => $files,
                   'tombstones'     => $ha_tomb_list,
                   'peers'          => ('cluster' == $config['role']) ? MultiotpHaSlaveUrlList($config) : array());
  $blob = MultiotpHaPayloadEncrypt($secret, json_encode($payload));
  if ('' == $blob) {
    return array('error' => 'openssl_required_on_master');
  }
  return array('payload' => $blob,
               'ts'      => time(),
               'counts'  => array('users'  => count($files['users']),
                                  'tokens' => count($files['tokens'])));
}

/**
 * @brief   Apply a backup payload (SLAVE side): write the .db files.
 *          File names are strictly validated (no path traversal).
 */
function MultiotpHaApplyBackup($multiotp, $payload_array, $merge = false) // v4.0: merge = cluster LWW mode
{
  $applied = array('users' => 0, 'tokens' => 0, 'skipped' => 0, 'deleted' => 0,
                   'kept' => 0, 'updated' => 0, 'tombstones' => 0, 'peers_added' => 0); // v4.0
  if (!is_array($payload_array) || !isset($payload_array['files']) || !is_array($payload_array['files'])) {
    $applied['error'] = 'invalid_payload';
    return $applied;
  }
  $local_fingerprint = strtoupper(md5($multiotp->GetEncryptionKey()));
  $tombstones = $merge ? MultiotpHaTombstonePrune(MultiotpHaTombstoneLoad($multiotp), time()) : array(); // v4.0
  if (isset($payload_array['key_fingerprint']) && ('' != nullable_trim($payload_array['key_fingerprint']))
      && ($local_fingerprint != $payload_array['key_fingerprint'])) {
    $applied['error'] = 'encryption_key_mismatch';
    return $applied;
  }
  $folders = array('users'  => MultiotpHaNormFolder($multiotp->GetUsersFolder()),
                   'tokens' => MultiotpHaNormFolder($multiotp->GetTokensFolder()));
  foreach ($folders as $one_kind => $one_folder) {
    if (!isset($payload_array['files'][$one_kind]) || !is_array($payload_array['files'][$one_kind])) {
      continue;
    }
    if (!@is_dir($one_folder)) {
      @mkdir($one_folder, 0777, true);
    }
    $master_names = array();                 // v3: full replica semantics
    foreach ($payload_array['files'][$one_kind] as $one_file) {
      $name = isset($one_file['name']) ? $one_file['name'] : '';
      if (!preg_match('/^[A-Za-z0-9._\-]+\.db$/', $name)) {
        $applied['skipped']++;
        continue;
      }
      $master_names[$name] = true;
      $content = base64_decode(isset($one_file['b64']) ? $one_file['b64'] : '', true);
      if (false === $content) {
        $applied['skipped']++;
        continue;
      }
      $target    = $one_folder.$name;
      $remote_mt = isset($one_file['mt']) ? intval($one_file['mt']) : 0;   // v4.0: replica clock
      if ($merge && @file_exists($target)) {                               // v4.0: LWW merge
        $decision = MultiotpHaLwwDecide(@filemtime($target), @file_get_contents($target),
                                        $remote_mt, $content);
        if ('remote' != $decision) {
          $applied['kept']++;          // local copy is newer (or identical): keep it
          continue;
        }
        $applied['updated']++;
      }
      $tmp    = $target.'.ha-tmp-'.getmypid();
      if (false === @file_put_contents($tmp, $content)) {
        $applied['skipped']++;
        continue;
      }
      @rename($tmp, $target);
      if ($remote_mt > 0) { @touch($target, $remote_mt); } // v4.0: propagate the ORIGIN mtime
      $applied[$one_kind]++;
    }
    if (!$merge) {
    // v3: delete the local .db files that the master does not have anymore
    // (the slave is a full replica of the master users/tokens data)
    $locals = @glob($one_folder.'*.db');
    if (is_array($locals)) {
      foreach ($locals as $one_local) {
        $lname = basename($one_local);
        if (preg_match('/^[A-Za-z0-9._\-]+\.db$/', $lname) && !isset($master_names[$lname])) {
          if (@unlink($one_local)) {
            $applied['deleted']++;
          }
        }
      }
    }
    } else {   // v4.0 (cluster): a local file the peer does not have is KEPT,
               // deletion happens ONLY through tombstones
    $locals = @glob($one_folder.'*.db');
    if (is_array($locals)) {
      foreach ($locals as $one_local) {
        $lname = basename($one_local);
        if (preg_match('/^[A-Za-z0-9._\-]+\.db$/', $lname) && !isset($master_names[$lname])) {
          $t_key = MultiotpHaTombstoneKey($one_kind, $lname);
          if (isset($tombstones[$t_key]) && (intval(@filemtime($one_local)) < intval($tombstones[$t_key]['ts']))) {
            if (@unlink($one_local)) {
              $applied['deleted']++;
            }
          }
        }
      }
    }
    }
  }
  // v4.0 (cluster): merge the peer tombstones (deletion journal) + the peer
  // list (topology replication, AD-like full mesh convergence).
  if ($merge) {
    $local_tomb = $tombstones;
    $tomb_list  = (isset($payload_array['tombstones']) && is_array($payload_array['tombstones']))
                  ? $payload_array['tombstones'] : array();
    foreach ($tomb_list as $one_tomb) {
      if (!is_array($one_tomb) || !isset($one_tomb['name'])
          || !preg_match('/^[A-Za-z0-9._\-]+$/', (string) $one_tomb['name'])) {
        continue;
      }
      $t_kind = isset($one_tomb['kind']) ? (string) $one_tomb['kind'] : 'users';
      if (!in_array($t_kind, array('users', 'tokens'))) {
        $t_kind = 'users';
      }
      $t_name = (string) $one_tomb['name'];
      $t_ts   = intval(isset($one_tomb['ts']) ? $one_tomb['ts'] : 0);
      $before = count($local_tomb);
      $local_tomb = MultiotpHaTombstoneRecord($local_tomb, $t_kind, $t_name, $t_ts, 'peer');
      if (count($local_tomb) != $before) {
        $applied['tombstones']++;
      }
      $t_target = MultiotpHaNormFolder(('tokens' == $t_kind) ? $multiotp->GetTokensFolder()
                                                             : $multiotp->GetUsersFolder()).$t_name;
      if (@file_exists($t_target) && (intval(@filemtime($t_target)) < $t_ts)) {
        if (@unlink($t_target)) {
          $applied['deleted']++;
        }
      }
    }
    MultiotpHaTombstoneSave($multiotp, $local_tomb);
    if (isset($payload_array['peers']) && is_array($payload_array['peers'])) {
      $own  = MultiotpHaNormalizeSlaveUrl(MultiotpHaOwnConsoleUrl());
      $ha_config = MultiotpHaLoadConfig($multiotp);
      $own_cfg = MultiotpHaNormalizeSlaveUrl(nullable_trim(isset($ha_config['own_url']) ? $ha_config['own_url'] : '')); // v4.0
      $cur  = MultiotpHaSlaveUrlList($ha_config);
      $ha_changed = false;
      // v4.0.3: identity of SELF and of the already known peers by canonical
      // host:port key - a variant form (https/short path) of SELF or of an
      // already listed peer is never merged again (no self listing, no
      // duplication of the peer list at every sync/save cycle).
      $own_keys = array_unique(array_filter(array(MultiotpHaUrlKey($own),
                                                  MultiotpHaUrlKey($own_cfg))));
      foreach ($payload_array['peers'] as $one_peer) {
        $one_peer = MultiotpHaNormalizeSlaveUrl((string) $one_peer);
        if (('' == $one_peer) || !preg_match('#^https?://#i', $one_peer)
            || MultiotpHaIsLoopbackUrl($one_peer)) {
          continue;
        }
        $one_key = MultiotpHaUrlKey($one_peer);
        if (('' == $one_key) || in_array($one_key, $own_keys)) {   // v4.0.3: never add SELF (any variant form)
          continue;
        }
        $ha_before = count($cur);
        $cur = MultiotpHaDedupeUrls(array_merge($cur, array($one_peer)));
        if ((count($cur) > $ha_before) && (count($cur) <= 16)) {
          $applied['peers_added']++;
          $ha_changed = true;
        } elseif (count($cur) > 16) {
          $cur = array_slice($cur, 0, 16);   // v3.1 cap preserved
          break;
        }
      }
      if ($ha_changed) {
        $ha_config['slave_urls'] = implode(',', $cur);
        MultiotpHaSaveConfig($multiotp, $ha_config);
      }
    }
  }
  return $applied;
}

/**
 * @brief   Full pull cycle (SLAVE side). $force = skip the interval check.
 * @return  array(ok, applied|skipped|error, ...)
 */
function MultiotpHaDoPull($multiotp, $force = false)
{
  $config = MultiotpHaLoadConfig($multiotp);
  $now    = time();
  if ('cluster' == $config['role']) {   // v4.0: multi-master = pull+merge from EVERY peer
    return MultiotpHaDoPullCluster($multiotp, $force);
  }
  if ('slave' != $config['role']) {
    return array('ok' => false, 'error' => 'not_slave');
  }
  $url    = nullable_trim($config['master_url']);
  $secret = nullable_trim($config['shared_secret']);
  if (('' == $url) || ('' == $secret)) {
    return array('ok' => false, 'error' => 'incomplete_config');
  }
  $interval = max(1, intval($config['interval_minutes']));
  if (!$force) {
    if (($now - intval($config['last_pull_ts'])) < ($interval * 60)) {
      return array('ok'    => true,
                   'skipped' => 'not_due',
                   'interval_minutes' => $interval,
                   'next_pull_in'     => ($interval * 60) - ($now - intval($config['last_pull_ts'])));
    }
    if (($now - intval($config['last_pull_attempt_ts'])) < 60) {
      return array('ok' => true, 'skipped' => 'retry_too_soon');
    }
  }
  $config['last_pull_attempt_ts'] = $now;
  MultiotpHaSaveConfig($multiotp, $config);

  $sep    = (false === mb_strpos($url, '?')) ? '?' : '&';
  $ts     = $now;
  $token  = MultiotpHaMakeToken($secret, $ts, 'pull');
  $token2 = MultiotpHaMakeTokenStrong($secret, $ts, 'pull'); // v3.10
  $full   = $url.$sep.'method=HaGetBackup&ha_ts='.$ts.'&ha_token='.$token.'&ha_token2='.$token2;
  $raw   = MultiotpHaHttpGet($full, 10);
  if (false === $raw) {
    $config['last_pull_result'] = 'ERROR: master unreachable ('.$url.')';
    MultiotpHaSaveConfig($multiotp, $config);
    MultiotpHaLog($multiotp, 'HA pull: master unreachable');
    return array('ok' => false, 'error' => 'master_unreachable', 'url' => $url);
  }
  $decoded = json_decode($raw, true);
  if (is_string($decoded)) {
    $config['last_pull_result'] = 'ERROR: master answered: '.mb_substr($decoded, 0, 120);
    MultiotpHaSaveConfig($multiotp, $config);
    return array('ok' => false, 'error' => 'master_answer', 'answer' => mb_substr($decoded, 0, 200));
  }
  if (!is_array($decoded) || !isset($decoded['multiotp_ha'])) {
    $config['last_pull_result'] = 'ERROR: invalid answer from master';
    MultiotpHaSaveConfig($multiotp, $config);
    return array('ok' => false, 'error' => 'invalid_answer');
  }
  if (!MultiotpHaCheckToken($secret, isset($decoded['ts']) ? $decoded['ts'] : 0,
                            isset($decoded['token']) ? $decoded['token'] : '', 'backup',
                            isset($decoded['token2']) ? $decoded['token2'] : '')) { // v3.10
    $config['last_pull_result'] = 'ERROR: backup token rejected (clock drift ?)';
    MultiotpHaSaveConfig($multiotp, $config);
    return array('ok' => false, 'error' => 'bad_token');
  }
  $payload = MultiotpHaPayloadDecrypt($secret, isset($decoded['payload']) ? $decoded['payload'] : '');
  if ('' == $payload) {
    $config['last_pull_result'] = 'ERROR: payload decrypt failed (different shared secret ?)';
    MultiotpHaSaveConfig($multiotp, $config);
    return array('ok' => false, 'error' => 'decrypt_failed');
  }
  $data    = json_decode($payload, true);
  $applied = MultiotpHaApplyBackup($multiotp, is_array($data) ? $data : array());
  if (isset($applied['error'])) {
    $config['last_pull_result'] = 'ERROR: '.$applied['error'];
    MultiotpHaSaveConfig($multiotp, $config);
    MultiotpHaLog($multiotp, 'HA pull failed: '.$applied['error']);
    return array('ok' => false, 'error' => $applied['error']);
  }
  $config['last_pull_ts']      = $now;
  $config['last_pull_users']   = $applied['users'];
  $config['last_pull_tokens']  = $applied['tokens'];
  $config['last_pull_result']  = 'OK (users: '.$applied['users'].', tokens: '.$applied['tokens']
                               .(($applied['deleted'] > 0) ? ', deleted: '.$applied['deleted'] : '').')';
  if (isset($decoded['rev']) && ('' != nullable_trim($decoded['rev']))) {
    $config['last_known_rev'] = nullable_trim($decoded['rev']); // v3: revision tracking
  }
  MultiotpHaSaveConfig($multiotp, $config);
  MultiotpHaLog($multiotp, 'HA pull done from master: '.$applied['users'].' users, '.$applied['tokens'].' tokens');
  return array('ok' => true, 'applied' => $applied, 'interval_minutes' => $interval);
}

/**
 * @brief   v4.0 (cluster): fetch + authenticate ONE peer backup payload.
 * @return  array(ok, data, rev) or array(ok=false, error, url, ...)
 */
function MultiotpHaFetchPeerPayload($multiotp, $config, $url)
{
  $secret = nullable_trim($config['shared_secret']);
  if (('' == $url) || ('' == $secret)) {
    return array('ok' => false, 'error' => 'incomplete_config');
  }
  $sep    = (false === mb_strpos($url, '?')) ? '?' : '&';
  $ts     = time();
  $token  = MultiotpHaMakeToken($secret, $ts, 'pull');
  $token2 = MultiotpHaMakeTokenStrong($secret, $ts, 'pull');
  $raw    = MultiotpHaHttpGet($url.$sep.'method=HaGetBackup&ha_ts='.$ts.'&ha_token='.$token.'&ha_token2='.$token2, 10);
  if (false === $raw) {
    return array('ok' => false, 'error' => 'peer_unreachable', 'url' => $url);
  }
  $decoded = json_decode($raw, true);
  if (is_string($decoded)) {
    return array('ok' => false, 'error' => 'peer_answer', 'answer' => mb_substr($decoded, 0, 200), 'url' => $url);
  }
  if (!is_array($decoded) || !isset($decoded['multiotp_ha'])) {
    return array('ok' => false, 'error' => 'invalid_answer', 'url' => $url);
  }
  if (!MultiotpHaCheckToken($secret, isset($decoded['ts']) ? $decoded['ts'] : 0,
                            isset($decoded['token']) ? $decoded['token'] : '', 'backup',
                            isset($decoded['token2']) ? $decoded['token2'] : '')) {
    return array('ok' => false, 'error' => 'bad_token', 'url' => $url);
  }
  $payload = MultiotpHaPayloadDecrypt($secret, isset($decoded['payload']) ? $decoded['payload'] : '');
  if ('' == $payload) {
    return array('ok' => false, 'error' => 'decrypt_failed', 'url' => $url);
  }
  $data = json_decode($payload, true);
  return array('ok'   => true,
               'data' => (is_array($data) ? $data : array()),
               'rev'  => isset($decoded['rev']) ? nullable_trim($decoded['rev']) : '');
}

/**
 * @brief   v4.0 (cluster, multi-master AD-like): pull + MERGE the data of
 *          EVERY peer. Nothing is blindly overwritten: a change made locally
 *          (or received from another peer) is kept unless the peer copy is
 *          strictly NEWER (LWW). Deletions replicate through tombstones.
 */
function MultiotpHaDoPullCluster($multiotp, $force = false)
{
  $config = MultiotpHaLoadConfig($multiotp);
  $now    = time();
  $urls   = MultiotpHaSlaveUrlList($config);
  if (0 == count($urls)) {
    return array('ok' => false, 'error' => 'no_peer_urls');
  }
  $interval = max(1, intval($config['interval_minutes']));
  if (!$force) {
    if (($now - intval($config['last_pull_ts'])) < ($interval * 60)) {
      return array('ok'    => true,
                   'skipped' => 'not_due',
                   'interval_minutes' => $interval,
                   'next_pull_in'     => ($interval * 60) - ($now - intval($config['last_pull_ts'])));
    }
    if (($now - intval($config['last_pull_attempt_ts'])) < 60) {
      return array('ok' => true, 'skipped' => 'retry_too_soon');
    }
  }
  $config['last_pull_attempt_ts'] = $now;
  MultiotpHaSaveConfig($multiotp, $config);
  $ha_own_cfg  = MultiotpHaNormalizeSlaveUrl(nullable_trim(isset($config['own_url']) ? $config['own_url'] : '')); // v4.0
  $ha_own_web  = MultiotpHaNormalizeSlaveUrl(MultiotpHaOwnConsoleUrl());   // v4.0
  $ha_own_keys = array_unique(array_filter(array(MultiotpHaUrlKey($ha_own_cfg),
                                                 MultiotpHaUrlKey($ha_own_web)))); // v4.0.3
  $agg = array('users' => 0, 'tokens' => 0, 'kept' => 0, 'updated' => 0, 'deleted' => 0,
               'tombstones' => 0, 'peers_added' => 0, 'skipped' => 0);
  $peers_result = array();
  $any_ok = false;
  foreach ($urls as $one_url) {
    $ha_one_key = MultiotpHaUrlKey($one_url);   // v4.0.3
    if ((('' != $ha_own_cfg) && (0 === strcasecmp($one_url, $ha_own_cfg)))   // v4.0: never pull from SELF
        || (('' != $ha_own_web) && (0 === strcasecmp($one_url, $ha_own_web)))
        || (('' != $ha_one_key) && in_array($ha_one_key, $ha_own_keys))) {   // v4.0.3: any variant form of SELF
      continue;
    }
    $fetched = MultiotpHaFetchPeerPayload($multiotp, $config, $one_url);
    if (empty($fetched['ok'])) {
      // v4.0.2: phantom-https rescue. A peer announced/saved with an "https://"
      // scheme while it actually serves plain HTTP (stray "fastcgi_param HTTPS
      // on" on the peer) makes this pull die in TLS (peer_unreachable) even
      // though the peer is healthy. One evidenced retry in plain http restores
      // the sync, and the merge records the URL that really works.
      $ha_alt_url     = (0 === mb_stripos($one_url, 'https://')) ? ('http://'.mb_substr($one_url, 8)) : '';
      $ha_alt_fetched = ('' != $ha_alt_url) ? MultiotpHaFetchPeerPayload($multiotp, $config, $ha_alt_url) : array();
      if (!empty($ha_alt_fetched['ok'])) {
        $one_url = $ha_alt_url;
        $fetched = $ha_alt_fetched;
        MultiotpHaLog($multiotp, 'HA pull recovered with plain http (phantom https announce): '.$ha_alt_url);
      } else {
        $peers_result[] = array('url' => $one_url, 'error' => isset($fetched['error']) ? $fetched['error'] : 'error');
        continue;
      }
    }
    $applied = MultiotpHaApplyBackup($multiotp, $fetched['data'], true);
    if (isset($applied['error'])) {
      $peers_result[] = array('url' => $one_url, 'error' => $applied['error']);
      continue;
    }
    $any_ok = true;
    foreach ($agg as $ha_k => $ha_v) {
      $agg[$ha_k] = $ha_v + intval(isset($applied[$ha_k]) ? $applied[$ha_k] : 0);
    }
    $peers_result[] = array('url'         => $one_url,
                            'ok'          => true,
                            'written'     => intval($applied['users'] + $applied['tokens']),
                            'kept'        => intval($applied['kept']),
                            'deleted'     => intval($applied['deleted']),
                            'peers_added' => intval($applied['peers_added']));
  }
  if (!$any_ok) {
    $config['last_pull_result'] = 'ERROR: every peer unreachable or rejected (cluster)';
    MultiotpHaSaveConfig($multiotp, $config);
    MultiotpHaLog($multiotp, 'HA cluster pull FAILED: '.json_encode($peers_result));
    return array('ok' => false, 'error' => 'peers_unreachable', 'peers' => $peers_result);
  }
  $fresh = MultiotpHaLoadConfig($multiotp);   // ApplyBackup may have merged the peer list
  $fresh['last_pull_ts']         = $now;
  $fresh['last_pull_attempt_ts'] = $now;
  $fresh['last_pull_users']      = $agg['users'];
  $fresh['last_pull_tokens']     = $agg['tokens'];
  $fresh['last_pull_result']     = 'OK cluster merge (new: '.$agg['users'].' users, '.$agg['tokens'].' tokens'
                                 .', kept: '.$agg['kept'].', deleted: '.$agg['deleted'].')';
  $fresh['last_known_rev'] = MultiotpHaFingerprint($multiotp); // v4.0: REAL convergence status for the peers' verification
  MultiotpHaSaveConfig($multiotp, $fresh);
  MultiotpHaLog($multiotp, 'HA cluster merge done with '.count($urls).' peer(s): '.json_encode($peers_result));
  return array('ok' => true, 'applied' => $agg, 'peers' => $peers_result, 'interval_minutes' => $interval);
}

/**
 * @brief   v4.0 (cluster): pull + merge ONE peer (the notification origin).
 */
function MultiotpHaPullPeerMerge($multiotp, $config, $url)
{
  $fetched = MultiotpHaFetchPeerPayload($multiotp, $config, $url);
  if (empty($fetched['ok'])) {
    // v4.0.2: phantom-https rescue (same evidence rule as the cluster merge).
    $ha_alt_url     = (0 === mb_stripos($url, 'https://')) ? ('http://'.mb_substr($url, 8)) : '';
    $ha_alt_fetched = ('' != $ha_alt_url) ? MultiotpHaFetchPeerPayload($multiotp, $config, $ha_alt_url) : array();
    if (!empty($ha_alt_fetched['ok'])) {
      $url     = $ha_alt_url;
      $fetched = $ha_alt_fetched;
      MultiotpHaLog($multiotp, 'HA pull recovered with plain http (phantom https announce): '.$ha_alt_url);
    } else {
      return array('ok' => false, 'error' => isset($fetched['error']) ? $fetched['error'] : 'error', 'url' => $url);
    }
  }
  $applied = MultiotpHaApplyBackup($multiotp, $fetched['data'], true);
  if (isset($applied['error'])) {
    return array('ok' => false, 'error' => $applied['error'], 'url' => $url);
  }
  $config['last_pull_ts']     = time();
  $config['last_pull_result'] = 'OK cluster merge from '.$url
                              .' (new: '.$applied['users'].' users, '.$applied['tokens'].' tokens, kept: '.$applied['kept'].')';
  $config['last_known_rev'] = MultiotpHaFingerprint($multiotp); // v4.0: convergence status
  MultiotpHaSaveConfig($multiotp, $config);
  return array('ok' => true, 'applied' => $applied, 'rev' => isset($fetched['rev']) ? $fetched['rev'] : '');
}

/**
 * @brief   Lazy safety net: pull if the interval is elapsed (best effort).
 *          Called once per request, only for web page loads.
 */
function MultiotpHaMaybeAutoPull($multiotp)
{
  static $ha_done = false;
  if ($ha_done) {
    return;
  }
  $ha_done = true;
  $config  = MultiotpHaLoadConfig($multiotp);
  if (('slave' != $config['role']) && ('cluster' != $config['role'])) { // v4.0: cluster too
    return;
  }
  $now      = time();
  $interval = max(1, intval($config['interval_minutes']));
  if (($now - intval($config['last_pull_ts'])) < ($interval * 60)) {
    return;
  }
  if (($now - intval($config['last_pull_attempt_ts'])) < 60) {
    return;
  }
  if (function_exists('set_time_limit')) {
    @set_time_limit(30);
  }
  MultiotpHaDoPull($multiotp, false);
}

/**
 * @brief   Detect the PHP binary usable by the scheduled task.
 */
function MultiotpHaDetectPhpBinary()
{
  $candidates = array();
  $is_windows = (false !== mb_strpos(mb_strtolower(PHP_OS), 'win'));
  $exe        = $is_windows ? 'php.exe' : 'php';
  if (defined('PHP_BINARY') && ('' != @constant('PHP_BINARY'))) {
    $candidates[] = PHP_BINARY;
    $candidates[] = dirname(PHP_BINARY).'/'.$exe;
  }
  $candidates[] = __DIR__.'/php/'.$exe;                 // multiOTP all-in-one layout
  if ($is_windows) {
    $candidates[] = 'C:/multiotp/php/php.exe';
  }
  foreach ($candidates as $one_candidate) {
    if (('' != $one_candidate) && @file_exists($one_candidate)) {
      return $one_candidate;
    }
  }
  return $exe; // hope it is in the PATH
}

/**
 * @brief   Build the scheduled task command (interval is NOT baked in:
 *          the task runs every minute and the CLI checks the interval).
 */
function MultiotpHaBuildTaskCommand($multiotp = null)
{
  $php    = MultiotpHaDetectPhpBinary();
  $script = __FILE__;
  $task   = MULTIOTP_HA_TASK_NAME;
  $arg    = 'ha-cron-pull';                 // slave role: periodic pull
  if (null !== $multiotp) {
    $ha_config = MultiotpHaLoadConfig($multiotp);
    if ('master' == $ha_config['role']) {
      $task = MULTIOTP_HA_TASK_NAME_MASTER; // master role: push watcher
      $arg  = 'ha-push-check';
    }
    if ('cluster' == $ha_config['role']) { // v4.0: bidirectional watcher (push + merge pull)
      $task = MULTIOTP_HA_TASK_NAME_CLUSTER;
      $arg  = 'ha-cluster-check';
    }
  }
  if (false !== mb_strpos(mb_strtolower(PHP_OS), 'win')) {
    $tr = '\\"'.$php.'\\" \\"'.$script.'\\" '.$arg;
    return 'schtasks /Create /F /TN "'.$task.'" /SC MINUTE /MO 1 /TR "'.$tr.'"';
  }
  $cron_line = '* * * * * root '.escapeshellarg($php).' '.escapeshellarg($script).' '.$arg.' >/dev/null 2>&1';
  return 'echo "'.$cron_line.'" > /etc/cron.d/multiotp-ha';
}

/**
 * @brief   Install/update the scheduled task (exec if allowed, else manual).
 */
function MultiotpHaInstallTask($multiotp)
{
  $command  = MultiotpHaBuildTaskCommand($multiotp);
  $output   = array();
  $code     = -1;
  $disabled = array_map('trim', explode(',', (string) @ini_get('disable_functions')));
  $can_exec = function_exists('exec') && !in_array('exec', $disabled);
  $installed = false;
  if ($can_exec) {
    @exec($command.' 2>&1', $output, $code);
    $installed = (0 == $code);
  }
  $config = MultiotpHaLoadConfig($multiotp);
  $config['task_installed']    = $installed;
  $config['task_last_command'] = $command;
  MultiotpHaSaveConfig($multiotp, $config);
  MultiotpHaLog($multiotp, 'HA scheduled task: '.($installed ? 'installed' : 'manual setup required'));
  return array('installed'  => $installed,
               'command'    => $command,
               'exec_output' => implode("\n", (array) $output),
               'can_exec'   => $can_exec);
}

/**
 * @brief   Full status array (shared secret never returned in clear).
 */
function MultiotpHaStatusArray($multiotp)
{
  $config = MultiotpHaLoadConfig($multiotp);
  $status = $config;
  $status['shared_secret'] = '';                        // masked
  $status['secret_set']    = ('' != nullable_trim($config['shared_secret']));
  $status['is_cluster']    = ('cluster' == $config['role']); // v4.0
  $status['slave_count']   = count(MultiotpHaSlaveUrlList($config));
  // v3.4: show the EFFECTIVE push targets (normalized, script path appended)
  $status['slave_urls_effective'] = implode(',', MultiotpHaSlaveUrlList($config));
  // v3.5: a 127.0.0.1/localhost target is only valid if the slave console runs
  // on THIS same machine (it would otherwise point the push to itself).
  $status['slave_urls_loopback'] = (bool) preg_match('#//(127\.0\.0\.1|localhost|\[::1\])([:/]|$)#i',
                                                    (string) $status['slave_urls_effective']);
  $status['push_ready']    = (in_array($config['role'], array('master', 'cluster')) // v4.0: cluster too
                              && !empty($config['push_enabled'])
                              && ($status['slave_count'] > 0) && ('' != nullable_trim($config['shared_secret'])));
  // v3.3: data changed but the last notify did not reach every slave ->
  // the per-minute push check is (or will be) retrying. Make it visible.
  $status['pending_push']  = ($status['push_ready']
                              && (MultiotpHaFingerprint($multiotp) != nullable_trim($config['last_pushed_rev'])));
  $users = nullable_trim($multiotp->GetUsersList());
  $status['users_count'] = ('' == $users) ? 0 : count(explode("\t", $users));
  $status['php_binary']  = MultiotpHaDetectPhpBinary();
  $status['script_path'] = __FILE__;
  return $status;
}

/**
 * @brief   Test the master from the slave (ping with token).
 */
function MultiotpHaTestPeer($multiotp)
{
  $config = MultiotpHaLoadConfig($multiotp);
  if ('cluster' == $config['role']) {   // v4.0: ping the FIRST peer instead of a master
    $peers = MultiotpHaSlaveUrlList($config);
    if (0 == count($peers)) {
      return array('ok' => false, 'error' => 'no_peer_urls');
    }
    $config['master_url'] = $peers[0];   // reuse the same ping path
  }
  if (!in_array($config['role'], array('slave', 'cluster'))) { // v4.0: cluster too
    return array('ok' => false, 'error' => 'not_slave');
  }
  $url    = nullable_trim($config['master_url']);
  $secret = nullable_trim($config['shared_secret']);
  if (('' == $url) || ('' == $secret)) {
    return array('ok' => false, 'error' => 'incomplete_config');
  }
  $ts     = time();
  $token  = MultiotpHaMakeToken($secret, $ts, 'ping');
  $token2 = MultiotpHaMakeTokenStrong($secret, $ts, 'ping'); // v3.10
  $sep    = (false === mb_strpos($url, '?')) ? '?' : '&';
  $start  = microtime(true);
  $raw    = MultiotpHaHttpGet($url.$sep.'method=HaPing&ha_ts='.$ts.'&ha_token='.$token.'&ha_token2='.$token2, 8);
  $ms    = intval((microtime(true) - $start) * 1000);
  if (false === $raw) {
    return array('ok' => false, 'error' => 'master_unreachable', 'ms' => $ms);
  }
  $decoded = json_decode($raw, true);
  if (is_string($decoded) && (0 === mb_strpos($decoded, 'pong|'))) {
    $parts = explode('|', $decoded);
    return array('ok'      => true,
                 'ms'      => $ms,
                 'users'   => intval(isset($parts[1]) ? $parts[1] : 0),
                 'version' => isset($parts[2]) ? $parts[2] : '');
  }
  if (is_string($decoded)) {
    return array('ok' => false, 'error' => 'master_answer', 'answer' => mb_substr($decoded, 0, 200), 'ms' => $ms);
  }
  return array('ok' => false, 'error' => 'unexpected_answer', 'ms' => $ms);
}

/**
 * @brief   Save HA configuration from AJAX options.
 *          options: role \t master_url \t shared_secret(empty=keep) \t interval
 *                   \t slave_urls (master, v3) \t push_enabled 1|0 (v3)
 */
function MultiotpHaSetConfigFromOptions($multiotp, $options_array)
{
  $config = MultiotpHaLoadConfig($multiotp);
  $role   = mb_strtolower(nullable_trim(isset($options_array[0]) ? $options_array[0] : 'off'));
  if (!in_array($role, array('off', 'master', 'slave', 'cluster'))) { // v4.0
    $role = 'off';
  }
  if ($role != $config['role']) {
    $config['task_installed'] = false; // the task must be re-created for the new role
  }
  $config['role'] = $role;
  if (isset($options_array[1])) {
    $config['master_url'] = nullable_trim($options_array[1]);
  }
  $secret = nullable_trim(isset($options_array[2]) ? $options_array[2] : '');
  if ('' != $secret) {
    $config['shared_secret'] = $secret;
  }
  $interval = intval(isset($options_array[3]) ? $options_array[3] : 0);
  if ($interval > 0) {
    $config['interval_minutes'] = min(1440, $interval);
  }
  if (isset($options_array[4])) {
    // v4.0.3: the Save button REPLACES the whole peer list (never appends).
    // The submitted value is normalized and de-duplicated by canonical
    // host:port key before storage, so re-saving the field (or saving a
    // field that still shows old duplicated entries) always cleans it up.
    $config['slave_urls'] = implode(',', MultiotpHaDedupeUrls(
        preg_split('#[\s,;]+#', nullable_trim($options_array[4]))));
  }
  if (isset($options_array[5])) {
    $flag = mb_strtolower(nullable_trim($options_array[5]));  // v3: instant push on/off
    $config['push_enabled'] = in_array($flag, array('1', 'true', 'on', 'yes'));
  }
  $ha_own_now = MultiotpHaOwnConsoleUrl();   // v4.0: remember the own console URL (web context)
  // v4.0.1: NEVER record a loopback own_url (console browsed via http://localhost/
  // or http://127.0.0.1/): peers reject loopback self-announcements with
  // {"error":"loopback_url"} and the CLI self-exclusion never matches. Keep any
  // previously recorded LAN URL instead; re-save while browsing via the LAN IP.
  // v4.0.2: the candidate own_url must also ANSWER a real request (evidence,
  // not guess). A stray "fastcgi_param HTTPS on" on a plain-HTTP console makes
  // PHP capture a phantom "https://..." own_url: every peer then queues a
  // doomed TLS pull and stores "every peer unreachable or rejected". Any
  // answer (even bad_token) proves reachability. If the https candidate stays
  // silent while the SAME host/port answers in plain http, record the http
  // form; if nothing answers at all, keep the previously recorded URL.
  $ha_own_new = (('' != $ha_own_now) && !MultiotpHaIsLoopbackUrl(MultiotpHaNormalizeSlaveUrl($ha_own_now)))
                ? MultiotpHaNormalizeSlaveUrl($ha_own_now) : '';
  if ('' != $ha_own_new) {
    $ha_probe_ts  = time();
    $ha_probe_raw = MultiotpHaHttpGet($ha_own_new.'?method=HaPing&ha_ts='.$ha_probe_ts.'&ha_token=selfcheck', 4);
    if ((false === $ha_probe_raw) && (0 === mb_stripos($ha_own_new, 'https://'))) {
      $ha_probe_raw = MultiotpHaHttpGet('http://'.mb_substr($ha_own_new, 8).'?method=HaPing&ha_ts='.$ha_probe_ts.'&ha_token=selfcheck', 4);
      if (false !== $ha_probe_raw) {
        $ha_own_new = 'http://'.mb_substr($ha_own_new, 8);   // evidenced scheme fix
      }
    }
    if (false !== $ha_probe_raw) {
      $config['own_url'] = $ha_own_new;
    } else {
      MultiotpHaLog($multiotp, 'HA own_url candidate did NOT answer a self-probe, keeping previous value: '.$ha_own_new);
    }
  }
  MultiotpHaSaveConfig($multiotp, $config);
  $registration = array();
  if ('slave' == $role) {
    // v3.1: announce this console URL to the master (best effort) so
    // the instant push targets are complete without manual typing.
    $registration = MultiotpHaRegisterSelf($multiotp, $config);
    MultiotpHaLog($multiotp, 'HA slave self-registration attempt: '.json_encode($registration));
  }
  if ('cluster' == $role) {   // v4.0: announce THIS console to every configured peer
    foreach (MultiotpHaSlaveUrlList($config) as $ha_peer) {
      $ha_reg_config = $config;
      $ha_reg_config['master_url'] = $ha_peer;   // RegisterSelf announces to master_url
      $ha_one = MultiotpHaRegisterSelf($multiotp, $ha_reg_config);
      $registration[] = array('peer' => $ha_peer, 'result' => $ha_one);
      MultiotpHaLog($multiotp, 'HA cluster self-announcement to peer '.$ha_peer.': '.json_encode($ha_one));
    }
  }
  MultiotpHaLog($multiotp, 'HA configuration saved (role: '.$role.', interval: '.$config['interval_minutes'].' min, push: '.($config['push_enabled'] ? 'on' : 'off').')');
  return array('ok' => true, 'status' => MultiotpHaStatusArray($multiotp), 'slave_registration' => $registration);
}

/**
 * @brief   MASTER side: answer a peer request (ping or backup), token-checked.
 * @return  array to be JSON-encoded by the dispatcher, or plain string.
 */
function MultiotpHaServePeer($multiotp, $ts, $token, $mode, $token2 = '') // v3.10
{
  $config = MultiotpHaLoadConfig($multiotp);
  if (!MultiotpHaServesData($config)) {   // v4.0: master AND cluster serve their peers
    return 'error: not_master';
  }
  $secret = nullable_trim($config['shared_secret']);
  if ('' == $secret) {
    return 'error: no_secret';
  }
  $action = ('ping' == $mode) ? 'ping' : 'pull';
  if (!MultiotpHaCheckToken($secret, $ts, $token, $action, $token2)) {
    return 'error: bad_token';
  }
  if ('ping' == $mode) {
    $users = nullable_trim($multiotp->GetUsersList());
    return 'pong|'.(('' == $users) ? 0 : count(explode("\t", $users))).'|'.$multiotp->GetFullVersionInfo();
  }
  $backup = MultiotpHaExportBackup($multiotp);
  if (isset($backup['error'])) {
    return 'error: '.$backup['error'];
  }
  return array('multiotp_ha' => 1,
               'ts'          => time(),
               'token'       => MultiotpHaMakeToken($secret, time(), 'backup'),
               'token2'      => MultiotpHaMakeTokenStrong($secret, time(), 'backup'), // v3.10
               'payload'     => $backup['payload'],
               'counts'      => $backup['counts'],
               'rev'         => MultiotpHaFingerprint($multiotp));
}

/**
 * @brief   SLAVE side: HTTP entry point for an external scheduler
 *          (token-protected with action 'local-cron'). Forces the pull.
 */
function MultiotpHaServePull($multiotp, $ts, $token, $token2 = '') // v3.10
{
  $config = MultiotpHaLoadConfig($multiotp);
  $secret = nullable_trim($config['shared_secret']);
  if ((('slave' != $config['role']) && ('cluster' != $config['role'])) || ('' == $secret)) { // v4.0
    return array('ok' => false, 'error' => 'not_slave');
  }
  if (!MultiotpHaCheckToken($secret, $ts, $token, 'local-cron', $token2)) {
    return array('ok' => false, 'error' => 'bad_token');
  }
  return MultiotpHaDoPull($multiotp, true);
}

/**
 * @brief   Log HA events in the multiOTP log.
 */
function MultiotpHaLog($multiotp, $text)
{
  $multiotp->WriteLog(array('text'       => $text,
                            'error_code' => _MULTIOTP_OPERATION_DONE_,
                            'category'   => 'HA',
                            'user'       => ''));
}

/**
 * @brief   Data fingerprint (v3): every users/*.db and tokens/*.db file
 *          contributes its name:size:mtime. Any create/modify/delete of a
 *          user or a token changes this value; it is cheap (a few stats).
 */
function MultiotpHaFingerprint($multiotp)
{
  if (function_exists('clearstatcache')) {
    clearstatcache(true); // v3.1: fresh file stats, mandatory for the shutdown watch in the SAME request
  }
  $parts   = array();
  $folders = array(MultiotpHaNormFolder($multiotp->GetUsersFolder()),
                   MultiotpHaNormFolder($multiotp->GetTokensFolder()));
  foreach ($folders as $one_folder) {
    $found = @glob($one_folder.'*.db');
    if (is_array($found)) {
      sort($found);
      foreach ($found as $one_file) {
        $stats = @stat($one_file);
        if (is_array($stats)) {
          $parts[] = basename($one_file).':'.$stats['size'].':'.$stats['mtime'];
        }
      }
    }
  }
  return md5(implode('|', $parts));
}

/**
 * @brief   v3.4: normalize a slave console URL. A URL WITHOUT a path
 *          (e.g. "http://192.168.40.144:8112") never reaches the console
 *          endpoint: the notify must target the console script. Appending
 *          "/multiotp.server.php" is safe in every serving mode (docroot
 *          or router) of the PHP built-in server.
 */
function MultiotpHaNormalizeSlaveUrl($url)
{
  $url   = nullable_trim($url);
  $parts = parse_url($url);
  if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
    return $url;
  }
  if (!in_array(mb_strtolower((string) $parts['scheme']), array('http', 'https'))) {
    return $url;
  }
  $path = isset($parts['path']) ? trim((string) $parts['path']) : '';
  if (('' == $path) || ('/' == $path)) {
    $url = rtrim($url, '/').'/multiotp.server.php';
  }
  return $url;
}

/**
 * @brief   v4.0.3: canonical identity key of a peer console URL (lowercase
 *          host + effective port, implicit 80/443 when absent). Two URLs
 *          sharing the same key designate the SAME console whatever the
 *          scheme (http vs phantom https announce) or the written path
 *          (with/without /multiotp.server.php). Comparing raw strings let a
 *          variant of an already known peer be appended again at every
 *          configuration save, duplicating the "Peer console URLs" field.
 * @return  string "host:port", or '' when the URL is not a usable http(s) URL.
 */
function MultiotpHaUrlKey($url)
{
  $parts = parse_url(nullable_trim($url));
  if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])
      || !in_array(mb_strtolower((string) $parts['scheme']), array('http', 'https'))) {
    return '';
  }
  $scheme = mb_strtolower((string) $parts['scheme']);
  $port   = isset($parts['port']) ? intval($parts['port'])
          : (('https' == $scheme) ? 443 : 80);
  return mb_strtolower((string) $parts['host']).':'.$port;
}

/**
 * @brief   v4.0.3: keep ONE entry per peer console (canonical host:port key).
 *          The first written form wins, the original order is preserved.
 * @return  array of normalized, de-duplicated peer console URLs.
 */
function MultiotpHaDedupeUrls($urls)
{
  $out  = array();
  $keys = array();
  foreach ((array) $urls as $one_url) {
    $one_url = MultiotpHaNormalizeSlaveUrl((string) $one_url);
    if ('' == $one_url) {
      continue;
    }
    $one_key = MultiotpHaUrlKey($one_url);
    if ('' == $one_key) {
      continue;
    }
    if (in_array($one_key, $keys)) {   // same host:port: variant of an already listed peer
      continue;
    }
    $keys[] = $one_key;
    $out[]  = $one_url;
  }
  return $out;
}

function MultiotpHaSlaveUrlList($config)
{
  $raw0 = isset($config['slave_urls']) ? (string) $config['slave_urls'] : '';
  // v4.0.1: rescue URLs glued together without any separator (a paste into the
  // single-line peers input loses the line break: "...phphttp://..." became one
  // unusable entry). Split every chunk at each http(s):// boundary when needed.
  $raw_parts = preg_split('#[\s,;]+#', $raw0);
  $raw_fixed = array();
  foreach ($raw_parts as $ha_one_part) {
    if ('' == $ha_one_part) { continue; }
    $ha_http_pos = mb_stripos($ha_one_part, 'http', 1);
    if (false !== $ha_http_pos) {
      foreach (preg_split('#(?=https?://)#i', $ha_one_part) as $ha_one_sub) {
        if ('' != $ha_one_sub) { $raw_fixed[] = $ha_one_sub; }
      }
    } else {
      $raw_fixed[] = $ha_one_part;
    }
  }
  $raw  = str_replace(array(';', "\r", "\n"), ',', implode(',', $raw_fixed));
  $urls = array();
  foreach (explode(',', $raw) as $one_url) {
    $one_url = MultiotpHaNormalizeSlaveUrl($one_url);   // v3.4: always push to the console endpoint
    if (('' != $one_url) && (false !== mb_strpos($one_url, '://'))
        && preg_match('#^https?://#i', $one_url)) {
      $urls[] = $one_url;
    }
  }
  return MultiotpHaDedupeUrls($urls);   // v4.0.3: one entry per console (host:port), no variant duplicates
}

/**
 * @brief   SLAVE side (v3): ask the master for its current data revision.
 * @return  the revision string, or array('ok'=>false,'error'=>...).
 */
function MultiotpHaGetMasterRevision($multiotp, $config = null)
{
  if (!is_array($config)) {
    $config = MultiotpHaLoadConfig($multiotp);
  }
  $url    = nullable_trim($config['master_url']);
  $secret = nullable_trim($config['shared_secret']);
  if (('' == $url) || ('' == $secret)) {
    return array('ok' => false, 'error' => 'incomplete_config');
  }
  $ts     = time();
  $token  = MultiotpHaMakeToken($secret, $ts, 'revision');
  $token2 = MultiotpHaMakeTokenStrong($secret, $ts, 'revision'); // v3.10
  $sep    = (false === mb_strpos($url, '?')) ? '?' : '&';
  $raw    = MultiotpHaHttpGet($url.$sep.'method=HaGetRevision&ha_ts='.$ts.'&ha_token='.$token.'&ha_token2='.$token2, 5);
  if (false === $raw) {
    return array('ok' => false, 'error' => 'master_unreachable');
  }
  $decoded = json_decode($raw, true);
  if (is_string($decoded) && (0 === mb_strpos($decoded, 'rev|'))) {
    return mb_substr($decoded, 4);
  }
  if (is_string($decoded) && (0 === mb_strpos($decoded, 'error:'))) {
    return array('ok' => false, 'error' => mb_substr($decoded, 7));
  }
  return array('ok' => false, 'error' => 'unexpected_answer');
}

/**
 * @brief   MASTER side (v3): answer a revision request (token-checked).
 */
function MultiotpHaServeRevision($multiotp, $ts, $token, $token2 = '') // v3.10
{
  $config = MultiotpHaLoadConfig($multiotp);
  if (!MultiotpHaServesData($config)) {   // v4.0: master AND cluster
    return 'error: not_master';
  }
  $secret = nullable_trim($config['shared_secret']);
  if ('' == $secret) {
    return 'error: no_secret';
  }
  if (!MultiotpHaCheckToken($secret, $ts, $token, 'revision', $token2)) {
    return 'error: bad_token';
  }
  return 'rev|'.MultiotpHaFingerprint($multiotp);
}

/**
 * @brief   v3.1: best-effort console URL of THIS server, built from the
 *          current web request (scheme + host + script name).
 *          Empty string in CLI context (no request available).
 */
function MultiotpHaOwnConsoleUrl()
{
  if ('cli' == php_sapi_name()) {
    return '';
  }
  $https = (!empty($_SERVER['HTTPS']) && ('off' != mb_strtolower((string) $_SERVER['HTTPS'])))
           || ('on' == mb_strtolower(nullable_trim(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : '')));
  $host   = isset($_SERVER['HTTP_HOST']) ? nullable_trim($_SERVER['HTTP_HOST']) : '';
  $script = isset($_SERVER['SCRIPT_NAME']) ? nullable_trim($_SERVER['SCRIPT_NAME']) : '';
  if (('' == $host) || ('' == $script)) {
    return '';
  }
  return (($https ? 'https' : 'http').'://'.$host.$script);
}

/**
 * @brief   SLAVE side (v3.1): register THIS console URL on the master
 *          (token-checked, action 'register', best effort, never blocks
 *          the configuration save). This fills the master "slave URLs"
 *          list automatically, the #1 cause of a silent instant push.
 */
function MultiotpHaRegisterSelf($multiotp, $config)
{
  $own_url = MultiotpHaOwnConsoleUrl();
  $master  = nullable_trim($config['master_url']);
  $secret  = nullable_trim($config['shared_secret']);
  if (('' == $own_url) || ('' == $master) || ('' == $secret)) {
    return array('ok' => false, 'error' => 'incomplete_config');
  }
  $sep     = (false === mb_strpos($master, '?')) ? '?' : '&';
  $reg_ts  = time();
  $token   = MultiotpHaMakeToken($secret, $reg_ts, 'register');
  $token2  = MultiotpHaMakeTokenStrong($secret, $reg_ts, 'register'); // v3.10
  $raw     = MultiotpHaHttpGet($master.$sep.'method=HaRegisterSlave&ha_ts='.$reg_ts.'&ha_token='.$token.'&ha_token2='.$token2.'&ha_url='.rawurlencode($own_url), 6);
  $answer = json_decode((string) $raw, true);
  return is_array($answer) ? $answer : array('ok' => false, 'error' => 'master_unreachable');
}

/**
 * @brief   MASTER side (v3.1): answer a slave self-registration
 *          (token-checked, action 'register'). Only http(s) URLs are
 *          accepted, the target list is capped to 16 entries.
 */
function MultiotpHaServeRegister($multiotp, $ts, $token, $slave_url, $token2 = '') // v3.10
{
  $config = MultiotpHaLoadConfig($multiotp);
  if (!MultiotpHaServesData($config)) {   // v4.0: a new cluster peer can announce itself too
    return array('ok' => false, 'error' => 'not_master');
  }
  $secret = nullable_trim($config['shared_secret']);
  if ('' == $secret) {
    return array('ok' => false, 'error' => 'no_secret');
  }
  if (!MultiotpHaCheckToken($secret, $ts, $token, 'register', $token2)) {
    return array('ok' => false, 'error' => 'bad_token');
  }
  $slave_url = nullable_trim($slave_url);
  if (('' == $slave_url) || (0 !== mb_strpos($slave_url, 'http'))) {
    return array('ok' => false, 'error' => 'invalid_url');
  }
  $current = MultiotpHaSlaveUrlList($config);
  $slave_url = MultiotpHaNormalizeSlaveUrl($slave_url);   // v3.4: store the effective endpoint
  if (MultiotpHaIsLoopbackUrl($slave_url)) {   // v3.8: a loopback target is accepted only if a
    // REAL non-master console answers at that URL on this machine (a peer announcing
    // the master's own console or a dead address is refused instead of poisoning the list).
    $loop_check = MultiotpHaLoopbackCheck($multiotp, $slave_url);
    if ('slave_ok' != $loop_check) {
      MultiotpHaLog($multiotp, 'HA slave self-registration REJECTED (loopback URL, check: '.$loop_check.'): '.$slave_url);
      return array('ok' => false, 'error' => 'loopback_url');
    }
  }
  $changed = false;
  // v4.0.3: a peer console is identified by its canonical host:port key.
  // A variant announce (http/https, short/long path) of an ALREADY KNOWN
  // peer must be reported as "already known" instead of being appended
  // again (this was duplicating the peer list at every console save).
  $merged = MultiotpHaDedupeUrls(array_merge($current, array($slave_url)));
  if (count($merged) != count($current)) {
    if (count($merged) > 16) {
      return array('ok' => false, 'error' => 'too_many_slaves');
    }
    $config['slave_urls'] = implode(',', $merged);
    MultiotpHaSaveConfig($multiotp, $config);
    $changed = true;
  }
  MultiotpHaLog($multiotp, 'HA slave self-registration'.($changed ? '' : ' (already known)').': '.$slave_url);
  return array('ok' => true, 'registered' => $changed, 'slaves' => count($merged));
}

/**
 * @brief   MASTER side (v3): notify every configured slave that the data
 *          changed, so each slave pulls IMMEDIATELY (event driven push).
 *          Fire and forget: a slave that cannot be reached is reported
 *          but never blocks the console.
 *          v3.9: slaves ACK at once and pull in background; with
 *          $verify = true (Send test push), the master polls each
 *          slave status and reports the REAL end of the replication.
 */
function MultiotpHaMasterNotify($multiotp, $reason = '', $verify = false)
{
  $config = MultiotpHaLoadConfig($multiotp);
  if (!MultiotpHaPushesData($config)) {   // v4.0: master AND cluster notify their peers
    return array('ok' => false, 'error' => 'not_master');
  }
  if (empty($config['push_enabled'])) {
    return array('ok' => false, 'error' => 'push_disabled');
  }
  $urls = MultiotpHaSlaveUrlList($config);
  if (0 == count($urls)) {
    return array('ok' => false, 'error' => ('cluster' == $config['role']) ? 'no_peer_urls' : 'no_slave_urls');
  }
  $secret = nullable_trim($config['shared_secret']);
  if ('' == $secret) {
    return array('ok' => false, 'error' => 'no_secret');
  }
  $ts      = time();
  $token   = MultiotpHaMakeToken($secret, $ts, 'notify');
  $token2  = MultiotpHaMakeTokenStrong($secret, $ts, 'notify'); // v3.10
  $ha_own_cfg = MultiotpHaNormalizeSlaveUrl(nullable_trim(isset($config['own_url']) ? $config['own_url'] : '')); // v4.0
  $ha_own_web = MultiotpHaNormalizeSlaveUrl(MultiotpHaOwnConsoleUrl());   // v4.0
  $ha_own_keys = array_unique(array_filter(array(MultiotpHaUrlKey($ha_own_cfg),
                                                 MultiotpHaUrlKey($ha_own_web)))); // v4.0.3
  $results = array();
  foreach ($urls as $one_url) {
    $ha_one_key = MultiotpHaUrlKey($one_url);   // v4.0.3
    if ((('' != $ha_own_cfg) && (0 === strcasecmp($one_url, $ha_own_cfg)))   // v4.0: never notify SELF
        || (('' != $ha_own_web) && (0 === strcasecmp($one_url, $ha_own_web)))
        || (('' != $ha_one_key) && in_array($ha_one_key, $ha_own_keys))) {   // v4.0.3: any variant form of SELF
      continue;
    }
    $sep = (false === mb_strpos($one_url, '?')) ? '?' : '&';
    $ha_from = MultiotpHaOwnConsoleUrl();   // v4.0: the peer pulls FROM the announcing console
    $raw = MultiotpHaHttpGet($one_url.$sep.'method=HaNotifyPull&ha_ts='.$ts.'&ha_token='.$token.'&ha_token2='.$token2
                             .(('' != $ha_from) ? '&ha_from='.rawurlencode($ha_from) : ''), 8);
    $results[] = array('url'    => $one_url,
                       'answer' => (false === $raw) ? MultiotpHaUnreachableLabel() : mb_substr((string) $raw, 0, 200));
  }
  // v3.9: slaves answer AT ONCE (queued) and pull in background. The queued
  // flag is the transport+acceptation verdict; when $verify is requested
  // (Send test push), the master ALSO polls each slave replication status
  // for a few seconds to report the REAL end of the synchronization.
  foreach ($results as $ha_idx => $ha_one) {
    $ha_dec = json_decode((string) $ha_one['answer'], true);
    $results[$ha_idx]['queued'] = (is_array($ha_dec) && !empty($ha_dec['ok']) && !empty($ha_dec['queued']));
  }
  if ($verify) {
    $rev_expected = MultiotpHaFingerprint($multiotp);
    $ha_t0 = microtime(true);
    for ($ha_try = 0; $ha_try < 6; $ha_try++) {
      $ha_pending = false;
      foreach ($results as $ha_idx => $ha_one) {
        if (empty($results[$ha_idx]['queued']) || !empty($results[$ha_idx]['synced'])) {
          continue;
        }
        $ha_st = MultiotpHaFetchSlaveStatus($ha_one['url'], $secret);
        if (!empty($ha_st['ok'])) {
          $results[$ha_idx]['last_pull_result'] = nullable_trim($ha_st['last_pull_result']);
          if (nullable_trim($ha_st['last_known_rev']) == $rev_expected) {
            $results[$ha_idx]['synced']   = true;
            $results[$ha_idx]['after_ms'] = intval((microtime(true) - $ha_t0) * 1000);
          }
        }
        if (empty($results[$ha_idx]['synced'])) {
          $ha_pending = true;
        }
      }
      if (!$ha_pending) {
        break;
      }
      if ($ha_try < 5) {
        sleep(1);
      }
    }
  }
  $config['last_push_ts']     = time();

  $config['last_push_result'] = json_encode($results);
  MultiotpHaSaveConfig($multiotp, $config);
  MultiotpHaLog($multiotp, 'HA push to slaves ('.$reason.'): '.json_encode($results));
  return array('ok' => true, 'reason' => $reason, 'results' => $results);
}

/**
 * @brief   v3.8: a loopback URL (127.0.0.1 / localhost / [::1]) is only valid
 *          if the target console runs on THIS machine - used by the panel
 *          warning, by the probe suggestions and to refuse self-registrations.
 */
function MultiotpHaIsLoopbackUrl($url)
{
  return (bool) preg_match('#//(127\.0\.0\.1|localhost|\[::1\])([:/]|$)#i', (string) $url);
}

/**
 * @brief   v3.8: pure helper - suggested action for one probed URL.
 *          Returns the suggested replacement URL, 'REMOVE' (loopback target)
 *          or '' (nothing to fix automatically).
 */
function MultiotpHaProbeSuggestion($norm_url, $tcp, $tls, $http_ok, $is_loopback)
{
  if ($is_loopback) {
    return 'REMOVE';
  }
  if ((0 === mb_strpos(mb_strtolower((string) $norm_url), 'https://'))
      && is_string($tcp) && (0 === mb_strpos($tcp, 'ok'))
      && is_string($tls) && (0 === mb_strpos($tls, 'FAILED'))
      && $http_ok) {
    return 'http://'.mb_substr((string) $norm_url, 8);   // TLS refused, plain HTTP proven
  }
  return '';
}

/**
 * @brief   v3.8: pure helper - one-line human interpretation of a probe HTTP
 *          answer (what the PEER console meant).
 */
function MultiotpHaProbeHint($http_answer)
{
  $a = (string) $http_answer;
  if ('' == $a) {
    return '';
  }
  if (false !== mb_strpos($a, 'not_master')) {
    return 'console reachable and answering, but it is NOT a master (normal if this target is a slave or a cluster peer) - now run Send test push to check the shared secret';
  }
  if (false !== mb_strpos($a, 'pong|')) {
    return 'a MASTER answered the ping: this URL points to a master console, not a slave';
  }
  if (false !== mb_strpos($a, 'bad_token')) {
    return 'shared secret DIFFERS on this target: set the SAME secret on both consoles';
  }
  if (false !== mb_strpos($a, 'not_slave')) {
    return 'console reachable but its HA role is not slave (or no secret set): configure this console once as slave';
  }
  return '';
}

/**
 * @brief   v3.8: loopback self-registration check - ping the announced URL
 *          from THIS machine. 'slave_ok' = a non-master console answered (a
 *          real slave lives here); 'master_self' = the announced URL is a
 *          MASTER console (the peer advertised the master itself);
 *          'unreachable' = nothing answers there (bogus entry).
 */
function MultiotpHaLoopbackCheck($multiotp, $url)
{
  $config = MultiotpHaLoadConfig($multiotp);
  $secret = nullable_trim($config['shared_secret']);
  $ts     = time();
  $token  = ('' != $secret) ? MultiotpHaMakeToken($secret, $ts, 'ping') : 'no_secret';
  $sep    = (false === mb_strpos($url, '?')) ? '?' : '&';
  $raw    = MultiotpHaHttpGet($url.$sep.'method=HaPing&ha_ts='.$ts.'&ha_token='.$token, 4);
  if (false === $raw) {
    return 'unreachable';
  }
  $raw = trim((string) $raw);        // the console JSON-encodes string answers: "pong|..."
  if (false !== mb_strpos($raw, 'pong|')) {
    return 'master_self';
  }
  return 'slave_ok';   // not_master / not_slave / bad_token = a console exists there
}

/**

 * @brief   v3.6: ADMIN diagnostic probe of one slave URL, run INSIDE the
 *          master PHP runtime (same transports as the real push):
 *          1) raw TCP connect, 2) TLS handshake, 3) real production GET.
 *          Explains precisely WHERE the chain breaks (TCP / TLS / HTTP)
 *          even when php_curl is missing and stream errors are cryptic.
 */
function MultiotpHaProbeUrl($multiotp, $url)
{
  $out = array('url' => nullable_trim($url));
  $out['env'] = array('curl'      => function_exists('curl_init'),
                      'url_fopen' => (bool) ini_get('allow_url_fopen'),
                      'openssl'   => extension_loaded('openssl'));
  $norm  = MultiotpHaNormalizeSlaveUrl($out['url']);
  $parts = parse_url($norm);
  $scheme = (is_array($parts) && isset($parts['scheme'])) ? mb_strtolower((string) $parts['scheme']) : '';
  $host   = (is_array($parts) && isset($parts['host']))   ? (string) $parts['host'] : '';
  $port   = (is_array($parts) && isset($parts['port']))   ? intval($parts['port']) : ('https' == $scheme ? 443 : 80);
  if (('' == $host) || !in_array($scheme, array('http', 'https'))) {
    $out['error'] = 'invalid_url';
    return $out;
  }
  $out['normalized'] = $norm;
  $t0 = microtime(true);
  $fp = @fsockopen('tcp://'.$host, $port, $errno, $errstr, 5);
  $out['tcp'] = is_resource($fp) ? 'ok ('.intval((microtime(true) - $t0) * 1000).' ms)'
                                 : 'FAILED #'.$errno.': '.$errstr;
  if (is_resource($fp)) { fclose($fp); }
  $t0 = microtime(true);
  $fp = @fsockopen('tls://'.$host, $port, $errno, $errstr, 5);
  $out['tls'] = is_resource($fp) ? 'ok ('.intval((microtime(true) - $t0) * 1000).' ms) -> HTTPS service'
                                 : 'FAILED #'.$errno.': '.$errstr.' -> not a TLS port (or TLS refused)';
  if (is_resource($fp)) { fclose($fp); }
  $config = MultiotpHaLoadConfig($multiotp);
  $secret = nullable_trim($config['shared_secret']);
  $ts     = time();
  $token  = ('' != $secret) ? MultiotpHaMakeToken($secret, $ts, 'ping') : 'no_secret';
  $t0  = microtime(true);
  $raw = MultiotpHaHttpGet($norm.'?method=HaPing&ha_ts='.$ts.'&ha_token='.$token, 8);
  $out['http'] = (false === $raw) ? MultiotpHaUnreachableLabel()
                                  : 'ok ('.intval((microtime(true) - $t0) * 1000).' ms): '.mb_substr(trim((string) $raw), 0, 120);
  // v3.8: when the https GET fails while TLS is refused, retry once in plain
  // HTTP - a working alt request PROVES the scheme switch (not a guess).
  $raw_used = $raw;
  if ((false === $raw) && ('https' == $scheme) && is_string($out['tls'])
      && (0 === mb_strpos($out['tls'], 'FAILED'))) {
    $alt   = 'http://'.mb_substr($norm, 8);
    $t0    = microtime(true);
    $raw2  = MultiotpHaHttpGet($alt.'?method=HaPing&ha_ts='.$ts.'&ha_token='.$token, 8);
    $out['http_alt'] = (false === $raw2) ? MultiotpHaUnreachableLabel()
                                         : 'ok ('.intval((microtime(true) - $t0) * 1000).' ms): '.mb_substr(trim((string) $raw2), 0, 120);
    if (false !== $raw2) { $raw_used = $raw2; }
  }
  $out['hint']    = MultiotpHaProbeHint((false === $raw_used) ? '' : trim((string) $raw_used));
  $out['suggest'] = MultiotpHaProbeSuggestion($norm, $out['tcp'], $out['tls'], (false !== $raw_used),
                                              MultiotpHaIsLoopbackUrl($norm));
  return $out;
}

/**
 * @brief   v3.9: PURE helper - quote a path for a Windows "start" command.
 */
function MultiotpHaWinQuote($path)
{
  return '"'.str_replace('"', '', (string) $path).'"';
}

/**
 * @brief   v3.9: PURE helper - command that runs the background pull job
 *          DETACHED (returns at once, all output discarded). The job is
 *          'ha-notify-pull': cheap revision check first, full pull ONLY
 *          when the master data really changed.
 *          $is_windows = null means auto-detect (unit tests pass a value).
 */
function MultiotpHaBuildBackgroundPullCommand($php, $script, $is_windows = null, $peer_url = '') // v4.0: optional origin peer
{
  if (null === $is_windows) {
    $is_windows = (false !== mb_strpos(mb_strtolower(PHP_OS), 'win'));
  }
  if ($is_windows) {
    return 'start /B "" '.MultiotpHaWinQuote($php).' '.MultiotpHaWinQuote($script)
         .' ha-notify-pull quiet'.(('' != nullable_trim($peer_url)) ? ' '.MultiotpHaWinQuote(nullable_trim($peer_url)) : '')
         .' > NUL 2>&1';
  }
  return '(nohup '.escapeshellarg($php).' '.escapeshellarg($script)
       .' ha-notify-pull quiet'.(('' != nullable_trim($peer_url)) ? ' '.escapeshellarg(nullable_trim($peer_url)) : '')
       .' >/dev/null 2>&1 &)';
}

/**
 * @brief   v3.9: the background pull JOB (CLI 'ha-notify-pull' or the
 *          inline fallback): cheap revision check first, full pull ONLY
 *          when the master data really changed (de-duplication kept).
 */
function MultiotpHaNotifyPullJob($multiotp, $peer_url = '') // v4.0: optional announcing peer
{
  $config = MultiotpHaLoadConfig($multiotp);
  if ('cluster' == $config['role']) {   // v4.0: multi-master notification job
    $peer_url = nullable_trim($peer_url);
    if (('' != $peer_url) && preg_match('#^https?://#i', $peer_url)) {
      $peer_url = MultiotpHaNormalizeSlaveUrl($peer_url);
      $result   = MultiotpHaPullPeerMerge($multiotp, $config, $peer_url);
    } else {
      $result   = MultiotpHaDoPullCluster($multiotp, true); // origin unknown: merge from every peer
    }
    MultiotpHaLog($multiotp, 'HA cluster notify-pull: '.(!empty($result['ok']) ? 'ok' : 'failure '
                  .(isset($result['error']) ? '('.$result['error'].')' : '')));
    return $result;
  }
  $revision = MultiotpHaGetMasterRevision($multiotp, $config);
  if (is_array($revision) && ('' != nullable_trim($revision['error']))
      && ('master_unreachable' != $revision['error'])) {
    return $revision; // incomplete config or rejected request
  }
  // The notification itself is the proof of a change: if the cheap revision
  // check cannot be answered (busy/unreachable master), we pull anyway.
  if (('' == $revision) || !is_string($revision)
      || ($revision != nullable_trim($config['last_known_rev']))) {
    $result = MultiotpHaDoPull($multiotp, true);
    if (!empty($result['ok']) && isset($result['applied'])) {
      $fresh = MultiotpHaLoadConfig($multiotp);
      $result['rev'] = nullable_trim($fresh['last_known_rev']);
    }
    return $result;
  }
  return array('ok' => true, 'skipped' => 'up_to_date', 'rev' => $revision);
}

/**
 * @brief   SLAVE side (v3): HTTP entry point for the master push
 *          notification (token-checked, action 'notify').
 *          v3.9 FIX: the pull used to run BEFORE answering, which
 *          DEADLOCKS a single-worker master console (its only worker
 *          waits for THIS answer while the pull calls the master back)
 *          and can exceed the push timeout on big datasets. The answer
 *          is now sent AT ONCE and the pull runs in a DETACHED background
 *          process ('ha-notify-pull quiet'); if no process can be
 *          spawned (popen disabled), the job runs inline (best effort).
 */
function MultiotpHaServeNotify($multiotp, $ts, $token, $token2 = '', $ha_from = '') // v4.0: announces the origin peer
{
  $config = MultiotpHaLoadConfig($multiotp);
  $secret = nullable_trim($config['shared_secret']);
  if ((('slave' != $config['role']) && ('cluster' != $config['role'])) || ('' == $secret)) { // v4.0
    return array('ok' => false, 'error' => 'not_slave');
  }
  if (!MultiotpHaCheckToken($secret, $ts, $token, 'notify', $token2)) {
    return array('ok' => false, 'error' => 'bad_token');
  }
  $disabled  = array_map('trim', explode(',', (string) @ini_get('disable_functions')));
  $can_spawn = function_exists('popen') && function_exists('pclose')
               && !in_array('popen', $disabled) && !in_array('pclose', $disabled);
  if ($can_spawn) {
    $cmd = MultiotpHaBuildBackgroundPullCommand(MultiotpHaDetectPhpBinary(), __FILE__, null, $ha_from); // v4.0: origin peer
    $ph  = @popen($cmd, 'r');
    if (is_resource($ph)) {
      @pclose($ph);
      MultiotpHaLog($multiotp, 'HA push notification: background pull queued (ha-notify-pull)');
      return array('ok' => true, 'queued' => true, 'mode' => 'background');
    }
  }
  $result = MultiotpHaNotifyPullJob($multiotp);   // fallback: inline, better late than never
  MultiotpHaLog($multiotp, 'HA push notification processed inline: '.(!empty($result['ok']) ? 'ok' : 'failure'));
  return $result;
}

/**
 * @brief   SLAVE side (v3.9): replication status for the master push
 *          verification (token-checked, action 'status'). Lets the
 *          master confirm the REAL end of the replication.
 */
function MultiotpHaServeSlaveStatus($multiotp, $ts, $token, $token2 = '') // v3.10
{
  $config = MultiotpHaLoadConfig($multiotp);
  $secret = nullable_trim($config['shared_secret']);
  if ((('slave' != $config['role']) && ('cluster' != $config['role'])) || ('' == $secret)) { // v4.0
    return array('ok' => false, 'error' => 'not_slave');
  }
  if (!MultiotpHaCheckToken($secret, $ts, $token, 'status', $token2)) {
    return array('ok' => false, 'error' => 'bad_token');
  }
  return array('ok'               => true,
               'role'             => 'slave',
               'last_known_rev'   => nullable_trim($config['last_known_rev']),
               'last_pull_ts'     => intval($config['last_pull_ts']),
               'last_pull_result' => nullable_trim($config['last_pull_result']));
}

/**
 * @brief   MASTER side (v3.9): fetch one slave replication status
 *          (token-checked, action 'status', short timeout).
 */
function MultiotpHaFetchSlaveStatus($url, $secret)
{
  $ts      = time();
  $token   = MultiotpHaMakeToken($secret, $ts, 'status');
  $token2  = MultiotpHaMakeTokenStrong($secret, $ts, 'status'); // v3.10
  $sep     = (false === mb_strpos($url, '?')) ? '?' : '&';
  $raw     = MultiotpHaHttpGet($url.$sep.'method=HaSlaveStatus&ha_ts='.$ts.'&ha_token='.$token.'&ha_token2='.$token2, 4);
  $decoded = json_decode((string) $raw, true);
  return is_array($decoded) ? $decoded : array('ok' => false, 'error' => 'unreachable');
}

/**
 * @brief   MASTER side (v3): watcher for the scheduled task (CLI command
 *          ha-push-check, every minute). Notifies the slaves when the data
 *          fingerprint changed since the last push. This covers every
 *          change done OUTSIDE the console (multiotp.exe CLI, LDAP sync
 *          script, file import, ...); console changes are already pushed
 *          instantly by the request watch below.
 */
function MultiotpHaPushCheck($multiotp)
{
  $config = MultiotpHaLoadConfig($multiotp);
  if (!MultiotpHaPushesData($config)) {   // v4.0: master AND cluster
    return array('ok' => false, 'error' => 'not_master');
  }
  if (empty($config['push_enabled'])) {
    return array('ok' => false, 'skipped' => 'push_disabled');
  }
  if (0 == count(MultiotpHaSlaveUrlList($config))) {
    return array('ok' => false, 'skipped' => 'no_slave_urls');
  }
  $revision = MultiotpHaFingerprint($multiotp);
  if ($revision == nullable_trim($config['last_pushed_rev'])) {
    // v3.9: the notification was acked, but the background pull of a slave
    // may have FAILED: verify the replication REALLY happened on each slave
    // (one cheap status GET per slave) and re-notify the late ones.
    $ha_secret = nullable_trim($config['shared_secret']);
    $ha_late   = array();
    foreach (MultiotpHaSlaveUrlList($config) as $ha_url) {
      $ha_st = MultiotpHaFetchSlaveStatus($ha_url, $ha_secret);
      if (empty($ha_st['ok']) || (nullable_trim($ha_st['last_known_rev']) != $revision)) {
        $ha_late[] = $ha_url;
      }
    }
    if (0 == count($ha_late)) {
      return array('ok' => true, 'skipped' => 'no_change', 'rev' => $revision);
    }
    $notify = MultiotpHaMasterNotify($multiotp, 'push check: '.count($ha_late).' slave(s) late, re-notify');
    if (MultiotpHaNotifyAllReached($notify)) {   // v3.3: only on FULL success
      $config = MultiotpHaLoadConfig($multiotp);
      $config['last_pushed_rev'] = $revision;
      MultiotpHaSaveConfig($multiotp, $config);
    }
    return array('ok' => true, 'pushed' => true, 'renotify' => $ha_late,
                 'rev' => $revision, 'notify' => $notify);
  }
  $notify = MultiotpHaMasterNotify($multiotp, 'push check detected a data change');
  if (MultiotpHaNotifyAllReached($notify)) {   // v3.3: only on FULL success
    $config = MultiotpHaLoadConfig($multiotp);
    $config['last_pushed_rev'] = $revision;
    MultiotpHaSaveConfig($multiotp, $config);
  }
  return array('ok' => true, 'pushed' => true, 'rev' => $revision, 'notify' => $notify);
}

/**
 * @brief   Console request watch (v3, MASTER only): remembers the data
 *          fingerprint when the request starts and compares it when the
 *          script ends. If the request changed the users/tokens data, the
 *          slaves are notified IMMEDIATELY (right after the response).
 */
function MultiotpHaPushWatchStart($multiotp)
{
  if (isset($GLOBALS['multiotp_ha_watch_done'])) {
    return;
  }
  $GLOBALS['multiotp_ha_watch_done'] = true;
  $config = MultiotpHaLoadConfig($multiotp);
  if ((!MultiotpHaPushesData($config)) || empty($config['push_enabled']) // v4.0: cluster too
      || (0 == count(MultiotpHaSlaveUrlList($config)))) {
    return;
  }
  $GLOBALS['multiotp_ha_watch_fp']  = MultiotpHaFingerprint($multiotp);
  $GLOBALS['multiotp_ha_watch_obj'] = $multiotp;
  register_shutdown_function('MultiotpHaPushWatchEnd');
}

/**
 * @brief   End of the console request watch (v3): push if data changed.
 */
function MultiotpHaPushWatchEnd()
{
  if (!isset($GLOBALS['multiotp_ha_watch_fp']) || !isset($GLOBALS['multiotp_ha_watch_obj'])) {
    return;
  }
  $multiotp  = $GLOBALS['multiotp_ha_watch_obj'];
  $rev_start = $GLOBALS['multiotp_ha_watch_fp'];
  unset($GLOBALS['multiotp_ha_watch_fp']);
  $rev_end = MultiotpHaFingerprint($multiotp);
  if ($rev_end == $rev_start) {
    return;
  }
  $notify = MultiotpHaMasterNotify($multiotp, 'console change');
  if (MultiotpHaNotifyAllReached($notify)) {   // v3.3: on failure do NOT record,
    $config = MultiotpHaLoadConfig($multiotp); // the 1-min push check will retry
    $config['last_pushed_rev'] = $rev_end;
    MultiotpHaSaveConfig($multiotp, $config);
  }
}

/** End of the high availability extension functions **/

/**********************************************************************
 * HA CLI ENTRY POINT - the scheduled task / cron calls THIS file from
 * the command line, so the slave pull works without any web session:
 *   php multiotp.server.php ha-cron-pull     slave: pull only if due
 *   php multiotp.server.php ha-push-check    master: notify slaves on change
 *   php multiotp.server.php ha-sync-now      slave: forced pull
 *   php multiotp.server.php ha-notify-pull   slave: background pull job (v3.9)
 *   php multiotp.server.php ha-cluster-check cluster: push+pull both ways (v4.0)
 *   php multiotp.server.php ha-status        status as JSON
 *   php multiotp.server.php ha-install-task  install/print the task cmd
 **********************************************************************/
if (('cli' == php_sapi_name()) && isset($argv[1]) && ('ha-' == mb_substr($argv[1], 0, 3))) {
  $ha_command = mb_strtolower(mb_substr($argv[1], 3));
  $ha_result  = array('ok' => false);
  switch ($ha_command) {
    case 'cron-pull':
      $ha_result = MultiotpHaDoPull($multiotp, false);
      break;
    case 'push-check':
      $ha_result = MultiotpHaPushCheck($multiotp);
      break;
    case 'cluster-check':   // v4.0: notify peers if local change THEN periodic merge pull
      $ha_result = MultiotpHaPushCheck($multiotp);
      if (isset($ha_result['ok'])) {
        $ha_result['cluster_pull'] = MultiotpHaDoPull($multiotp, false);
      }
      break;
    case 'sync-now':
      $ha_result = MultiotpHaDoPull($multiotp, true);
      break;
    case 'notify-pull':
      $ha_result = MultiotpHaNotifyPullJob($multiotp, isset($argv[2]) ? nullable_trim($argv[2]) : ''); // v4.0
      break;
    case 'status':
      $ha_result = array('ok' => true, 'status' => MultiotpHaStatusArray($multiotp));
      break;
    case 'install-task':
      $ha_result = array('ok' => true, 'task' => MultiotpHaInstallTask($multiotp));
      break;
    default:
      $ha_result = array('ok' => false, 'error' => 'unknown_ha_command');
      break;
  }
  $ha_quiet = (isset($argv[2]) && ('quiet' == mb_strtolower(trim((string) $argv[2]))));
  if (!$ha_quiet) {   // v3.9: the DETACHED background pull must stay silent
    echo json_encode($ha_result)."\n";
  }
  exit(0);
}

// HA lazy safety net for web page loads (best effort, cheap when not due)
if (('cli' != php_sapi_name()) && ('' == $method)) {
  MultiotpHaMaybeAutoPull($multiotp);
}

// HA v3 instant push watch: if THIS web request changes the users/tokens
// data, the configured slaves are notified right after the response is
// sent (so they pull at once, without waiting for the cron interval).
if (('cli' != php_sapi_name())) {
  MultiotpHaPushWatchStart($multiotp);
}
$headers = getallheaders();
$prefer = (!empty($headers['Prefer']) ? $headers['Prefer'] : '');

if ($prefer == "api=vuejs") {
 /*****************
  *****************
  *** API/VUEJS ***
  *****************
  *****************/
  $multiotp->ApiVueJsPreAuth();
  $multiotp->ApiVueJs($postdata);
exit();
} elseif (FALSE !== mb_strpos($data,'<multiOTP')) {
  $multiotp->XmlServer($data);
  exit();
} elseif ((FALSE !== mb_strpos($postdata,'<SOAP-ENV')) || (isset($_GET['soap'])) || (isset($_GET['wsdl']))) {
    /*******************
     *******************
     *** SOAP SERVER ***
     *******************
     ******************/
     
    // Instantiate the SOAP server
    $soap_server = new soap_server();

    $soap_service_name = "multiotp";
    $soap_tns_namespace = 'https://www.multiotp.net/wsdl/multiotp/';
    $soap_endpoint_url = false;
    $soap_schema_target_namespace = 'https://www.multiotp.net/wsdl/multiotp/';
    $soap_openotp_namespace = 'urn:openotp'; // urn:openotp

    // Create the WSDL 
    $soap_server->configureWSDL($soap_service_name, $soap_tns_namespace, $soap_endpoint_url);

    // Set the schema target namespace
    // $soap_server->wsdl->schemaTargetNamespace = $soap_schema_target_namespace;


    //Register openotpNormalLogin method
    $soap_server->register(
        'openotpNormalLogin', // method name
        array('username'     => 'xsd:string', // input parameters
              'domain'       => 'xsd:string',
              'ldapPassword' => 'xsd:string',
              'otpPassword'  => 'xsd:string',
              'client'       => 'xsd:string',
              'source'       => 'xsd:string',
              'settings'     => 'xsd:string',
              'options'      => 'xsd:string'),
        array('code'         => 'xsd:integer', // return value(s)
              'message'      => 'xsd:string',
              'session'      => 'xsd:string',
              'data'         => 'xsd:string',
              'timeout'      => 'xsd:integer',
              'otpChallenge' => 'xsd:string',
              'u2fChallenge' => 'xsd:string'),
        $soap_openotp_namespace, // namespace
        false, // soapaction: (use default)
        'rpc', // style: rpc or document
        'encoded', // use: encoded or literal
        'This method is used to send an authentication request.'); // description: documentation for the method
        
    // Defined method
    function openotpNormalLogin($username, $domain, $ldapPassword, $otpPassword, $client, $source, $settings, $options) {
        global $multiotp;
        return $multiotp->SoapOpenotpNormalLogin($username, $domain, $ldapPassword, $otpPassword, $client, $source, $settings, $options);
    }

    //Register openotpSimpleLogin method
    $soap_server->register(
        'openotpSimpleLogin',
        array('username'     => 'xsd:string',
              'domain'       => 'xsd:string',
              'anyPassword'  => 'xsd:string',
              'client'       => 'xsd:string',
              'source'       => 'xsd:string',
              'settings'     => 'xsd:string'),
        array('code'         => 'xsd:integer',
              'message'      => 'xsd:string',
              'session'      => 'xsd:string',
              'data'         => 'xsd:string',
              'timeout'      => 'xsd:integer'),
        $soap_openotp_namespace,
        false,
        'rpc',
        'encoded',
        'This method is similar to openotpNormalLogin with only one generic password attribute.');
        
    // Defined method
    function openotpSimpleLogin($username, $domain, $anyPassword, $client, $source, $settings) {
        global $multiotp;
        return $multiotp->SoapOpenotpSimpleLogin($username, $domain, $anyPassword, $client, $source, $settings);
    }


    //Register openotpLogin method
    $soap_server->register(
        'openotpLogin',
        array('username'     => 'xsd:string', // input parameters
              'domain'       => 'xsd:string',
              'ldapPassword' => 'xsd:string',
              'otpPassword'  => 'xsd:string',
              'client'       => 'xsd:string',
              'source'       => 'xsd:string',
              'settings'     => 'xsd:string',
              'options'      => 'xsd:string'),
        array('code'         => 'xsd:integer',
              'message'      => 'xsd:string',
              'session'      => 'xsd:string',
              'data'         => 'xsd:string',
              'timeout'      => 'xsd:integer'),
        $soap_openotp_namespace,
        false,
        'rpc',
        'encoded',
        'This method is an alias of openotpNormalLogin. It ensures backward compatibility.');
        
    // Defined method
    function openotpLogin($username, $domain, $ldapPassword, $otpPassword, $client, $source, $settings, $options) {
        global $multiotp;
        return $multiotp->SoapOpenotpNormalLogin($username, $domain, $ldapPassword, $otpPassword, $client, $source, $settings, $options);
    }


    //Register openotpChallenge method
    $soap_server->register(
        'openotpChallenge',
        array('username'     => 'xsd:string',
              'domain'       => 'xsd:string',
              'session'      => 'xsd:string',
              'otpPassword'  => 'xsd:string',
              'u2fResponse'  => 'xsd:string'),
        array('code'         => 'xsd:integer',
              'message'      => 'xsd:string',
              'data'         => 'xsd:string'),
        $soap_openotp_namespace,
        false,
        'rpc',
        'encoded',
        'This method is used when the openotpLogin returned a challenge (code 2). This is the second request to be sent containing the user one-time password.');
        
    // Defined method
    function openotpChallenge($username, $domain, $session, $otpPassword) {
        global $multiotp;
        return $multiotp->SoapOpenotpChallenge($username, $domain, $session, $otpPassword);
    }


    //Register openotpStatus method
    $soap_server->register(
        'openotpStatus',
        array(),
        array('status'  => 'xsd:boolean',
              'message' => 'xsd:string'),
        $soap_openotp_namespace,
        false,
        'rpc',
        'encoded',
        'openotpStatus call');


    // Defined method
    function openotpStatus() {
        global $multiotp;
        return $multiotp->SoapOpenotpStatus();
    }

    $soap_server->service($postdata);
    exit();
} else {
    // Secure and httponly cookie parameters forced in the application
    $params = session_get_cookie_params();
    $cookie_secure = (!$multiotp->IsDebugOption()) && (!$multiotp->IsDeveloperMode());
    $cookie_httponly = true;
    session_set_cookie_params($params["lifetime"],
              $params["path"], $params["domain"],
              $cookie_secure, $cookie_httponly
             );
    session_start();
    $hash_salt = $multiotp->GetHashSalt();
    $ldap_admin_login_enabled = 'false';
    $ldap_admin_cfg = MultiotpLdapAdminLoad($multiotp);
    if ((!empty($ldap_admin_cfg['enabled'])) && ('' != nullable_trim($ldap_admin_cfg['ou_dn']))) {
        $ldap_admin_login_enabled = 'true';
    }

    /****************************************
     * WE REALLY DO NOT WANT TO BE CACHED !!!
     ****************************************/
    header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    $multiotp->SendWeeklyAnonymousStat();

    if (isset($_FILES['upgrade_file']['tmp_name'])) {
      if ((isset($_SESSION['logged']) && $_SESSION['logged'])) {
        if (file_exists($_FILES['upgrade_file']['tmp_name']) && (UPLOAD_ERR_OK == $_FILES["upgrade_file"]["error"])) {
          $upgrade_file = $multiotp->ConvertToWindowsPathIfNeeded(sys_get_temp_dir()."/".date("YmdHis")."-".md5($_FILES['upgrade_file']['tmp_name']).".cfg");
          move_uploaded_file($_FILES['upgrade_file']['tmp_name'], $upgrade_file);
        }
      }
    }
    
    if (isset($_FILES['config_file']['tmp_name'])) {
        if ((isset($_SESSION['logged']) && $_SESSION['logged'])) {
            if (file_exists($_FILES['config_file']['tmp_name']) && (UPLOAD_ERR_OK == $_FILES["config_file"]["error"])) {
              $config_file = $multiotp->GetConfigFolder().date("YmdHis")."-".md5($_FILES['config_file']['tmp_name']).".cfg";
              if (move_uploaded_file($_FILES['config_file']['tmp_name'], $config_file)) {

                if ($multiotp->RestoreConfiguration(array('backup_file' => $config_file, 'restore_key' => (isset($_POST['restore_config_password'])?nullable_trim($_POST['restore_config_password']):'')))) {

                  // Clean Devices
                  foreach (explode("\t", $multiotp->GetDevicesList()) as $one_device) {
                    if ('' != nullable_trim($one_device)) {
                      $multiotp->DeleteDevice($one_device);
                    }
                  }
                  $actual_folder = $multiotp->GetDevicesFolder();
                  $actual_filter = "*.db";
                  if (($actual_dir = opendir($actual_folder)) !== FALSE) {
                    while(($actual_file_name = readdir($actual_dir)) !== FALSE) {
                      if (fnmatch($actual_filter, $actual_file_name)) {
                        $actual_file = $actual_folder.$actual_file_name;
                        unlink($actual_file);
                      }
                    }
                  }

                  // Clean Groups
                  foreach (explode("\t", $multiotp->GetGroupsList()) as $one_group) {
                    if ('' != nullable_trim($one_group)) {
                      $multiotp->DeleteGroup($one_group);
                    }
                  }
                  $actual_folder = $multiotp->GetGroupsFolder();
                  $actual_filter = "*.db";
                  if (($actual_dir = opendir($actual_folder)) !== FALSE) {
                    while(($actual_file_name = readdir($actual_dir)) !== FALSE) {
                      if (fnmatch($actual_filter, $actual_file_name)) {
                        $actual_file = $actual_folder.$actual_file_name;
                        unlink($actual_file);
                      }
                    }
                  }

                  // Clean Tokens
                  foreach (explode("\t", $multiotp->GetTokensList()) as $one_token) {
                    if ('' != nullable_trim($one_token)) {
                      $multiotp->DeleteToken($one_token);
                    }
                  }
                  $actual_folder = $multiotp->GetTokensFolder();
                  $actual_filter = "*.db";
                  if (($actual_dir = opendir($actual_folder)) !== FALSE) {
                    while(($actual_file_name = readdir($actual_dir)) !== FALSE) {
                      if (fnmatch($actual_filter, $actual_file_name)) {
                        $actual_file = $actual_folder.$actual_file_name;
                        unlink($actual_file);
                      }
                    }
                  }

                  // Clean Users
                  $user_array = $multiotp->GetNextUserArray(TRUE);
                  while (FALSE !== $user_array) {
                    if (isset($user_array['user'])) {
                      $multiotp->DeleteUser($user_array['user']);
                    }
                    $user_array = $multiotp->GetNextUserArray();
                  }
                  $actual_folder = $multiotp->GetUsersFolder();
                  $actual_filter = "*.db";
                  if (($actual_dir = opendir($actual_folder)) !== FALSE) {
                    while(($actual_file_name = readdir($actual_dir)) !== FALSE) {
                      if (fnmatch($actual_filter, $actual_file_name)) {
                        $actual_file = $actual_folder.$actual_file_name;
                        unlink($actual_file);
                      }
                    }
                  }
                  
                  $multiotp->RestoreConfiguration(array('backup_file' => $config_file, 'restore_key' => (isset($_POST['restore_config_password'])?nullable_trim($_POST['restore_config_password']):'')));
                  
                  $_SESSION = array();
                  $_SESSION['logged'] = FALSE;
                  $ajax_result = "false";
                  // $multiotp->ReadConfigData();
                  header('Location: '.(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
                  exit();
                } // End of the restore is ok
              } // if move_uploaded_file
              if (file_exists($config_file)) {
                unlink($config_file);
              }
            }
        }
    }

    if (isset($_FILES['token_file']['tmp_name'])) {
        if ((isset($_SESSION['logged']) && $_SESSION['logged'])) {
            if (file_exists($_FILES['token_file']['tmp_name']) && (UPLOAD_ERR_OK == $_FILES["token_file"]["error"])) {
                $multiotp->ImportTokensFile($_FILES['token_file']['tmp_name'], $_FILES['token_file']['name'], isset($_POST['token_password'])?nullable_trim($_POST['token_password']):'');
            }
        }
        echo "DONE";
    } elseif ('' == $method) {
        /*********************
         * Basic web server *
         *********************/
         
        $actual_date     = date('Y-m-d H:i:s');
        $class_name      = $multiotp->GetClassName();
        $class_version   = $multiotp->GetVersion();
        $class_date      = $multiotp->GetDate();
        $rpi_serial      = $multiotp->GetRaspberryPiSerialNumber();
        $rpi_info        = (('' != $rpi_serial)?"<br />\n        Raspberry Pi serial number: ".$rpi_serial."\n        ":'');
        $server_software = (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'].', ' : '')."PHP/".phpversion();

        $prefix_required0_checked = '';
        $prefix_required1_checked = '';
        
        if ($multiotp->IsDefaultRequestPrefixPin())
        {
            $prefix_required1_checked = ' checked="checked" ';
        }
        else
        {
            $prefix_required0_checked = ' checked="checked" ';
        }

        if ($multiotp->CheckAdminPassword("1234")) {
          $default_user_info = "(default is admin)";
          $default_password_info = "(default is 1234)";
        } else {
          $default_user_info = "";
          $default_password_info = "";
        }

        $webpage = <<<EOWEBPAGE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title>
            multiOTP web administration console
        </title>
        <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <style>
            :root {
                --bg1: #1e1b4b;
                --bg2: #4c1d95;
                --bg3: #831843;
                --panel: rgba(255, 255, 255, 0.06);
                --panel-border: rgba(255, 255, 255, 0.14);
                --panel-hover: rgba(255, 255, 255, 0.10);
                --text: #f3f4f6;
                --text-soft: #cbd5e1;
                --text-dim: #94a3b8;
                --indigo: #818cf8;
                --pink: #f472b6;
                --amber: #fbbf24;
                --emerald: #34d399;
                --sky: #38bdf8;
                --rose: #fb7185;
                --violet: #a78bfa;
                --teal: #2dd4bf;
                --danger: #f87171;
                --radius: 16px;
                --ease: cubic-bezier(.4,0,.2,1);
            }

            * { box-sizing: border-box; }

            html, body {
                margin: 0;
                padding: 0;
                min-height: 100%;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                color: var(--text);
                font-size: 14px;
                line-height: 1.5;
                background: linear-gradient(135deg, var(--bg1) 0%, var(--bg2) 45%, var(--bg3) 100%) fixed;
                background-attachment: fixed;
                min-height: 100vh;
                padding: 32px 16px 64px 16px;
            }

            h2 { font-size: 15px; font-weight: 700; margin: 0; }
            h3 { font-size: 13px; font-weight: 700; margin: .5em 0 .1em 0; }
            p { margin: 0; }
            a { color: var(--sky); font-weight: 600; text-decoration: none; transition: color .2s var(--ease); }
            a:hover { color: var(--pink); }
            hr { border: none; border-top: 1px solid var(--panel-border); margin: 18px 0; }

            .app {
                max-width: 760px;
                margin: 0 auto;
            }

            /* ---------- Hero header ---------- */
            .hero {
                background: linear-gradient(120deg, rgba(129,140,248,.25), rgba(244,114,182,.20));
                border: 1px solid var(--panel-border);
                border-radius: var(--radius);
                padding: 22px 24px;
                margin-bottom: 20px;
                backdrop-filter: blur(10px);
                box-shadow: 0 10px 30px rgba(0,0,0,.25);
                position: relative;
                overflow: hidden;
            }
            .hero::after {
                content: "";
                position: absolute;
                top: -60%; right: -20%;
                width: 260px; height: 260px;
                background: radial-gradient(circle, rgba(255,255,255,.18), transparent 60%);
                pointer-events: none;
            }
            .hero-title {
                font-size: 22px;
                font-weight: 800;
                letter-spacing: .2px;
                margin: 0 0 4px 0;
                background: linear-gradient(90deg, #ffffff, #e0e7ff 60%, #fbcfe8);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .hero-sub { color: var(--text-soft); font-size: 12.5px; margin-bottom: 10px; }
            .meta-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 10px;
            }
            .badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 5px 11px;
                border-radius: 999px;
                font-size: 11.5px;
                font-weight: 600;
                background: rgba(255,255,255,.08);
                border: 1px solid var(--panel-border);
                color: var(--text-soft);
                white-space: nowrap;
            }
            .badge.dot::before {
                content: "";
                width: 7px; height: 7px;
                border-radius: 50%;
                background: var(--emerald);
                box-shadow: 0 0 6px var(--emerald);
                display: inline-block;
            }

            /* ---------- Info / donation card ---------- */
            .info-card {
                background: var(--panel);
                border: 1px solid var(--panel-border);
                border-left: 3px solid var(--amber);
                border-radius: var(--radius);
                padding: 14px 16px;
                margin-bottom: 20px;
                font-size: 12.5px;
                color: var(--text-soft);
            }
            .info-card i { color: var(--text-soft); font-style: italic; }

            /* ---------- Generic panel/card ---------- */
            .panel {
                background: var(--panel);
                border: 1px solid var(--panel-border);
                border-radius: var(--radius);
                margin-bottom: 14px;
                overflow: hidden;
                backdrop-filter: blur(10px);
                transition: box-shadow .25s var(--ease), border-color .25s var(--ease), transform .2s var(--ease);
            }
            .panel:hover {
                border-color: rgba(255,255,255,.28);
                box-shadow: 0 10px 26px rgba(0,0,0,.28);
            }

            .panel-head {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 14px 18px;
                cursor: pointer;
                user-select: none;
            }
            .panel-head:hover { background: var(--panel-hover); }
            .panel-head.static { cursor: default; }
            .panel-head.static:hover { background: transparent; }

            .panel-icon {
                width: 34px; height: 34px;
                min-width: 34px;
                border-radius: 10px;
                display: flex; align-items: center; justify-content: center;
                font-size: 16px;
                color: #fff;
                box-shadow: 0 4px 10px rgba(0,0,0,.25);
            }
            .icon-indigo { background: linear-gradient(135deg, var(--indigo), #4f46e5); }
            .icon-pink   { background: linear-gradient(135deg, var(--pink), #db2777); }
            .icon-amber  { background: linear-gradient(135deg, var(--amber), #d97706); }
            .icon-emerald{ background: linear-gradient(135deg, var(--emerald), #059669); }
            .icon-sky    { background: linear-gradient(135deg, var(--sky), #0284c7); }
            .icon-rose   { background: linear-gradient(135deg, var(--rose), #e11d48); }
            .icon-violet { background: linear-gradient(135deg, var(--violet), #7c3aed); }
            .icon-teal   { background: linear-gradient(135deg, var(--teal), #0d9488); }

            .panel-title-wrap { flex: 1; min-width: 0; }
            .panel-title { font-weight: 700; font-size: 13.5px; color: var(--text); transition: color .2s var(--ease); }
            .panel-head.active .panel-title { color: var(--pink); }
            .panel-count { color: var(--text-dim); font-size: 11.5px; font-weight: 500; }

            /* Users search extension - begin */
            .panel-search { position: relative; display: flex; align-items: center; }
            .panel-search input[type="text"] {
                background: rgba(0,0,0,.25);
                border: 1px solid var(--panel-border);
                color: var(--text);
                border-radius: 10px;
                padding: 7px 30px 7px 32px;
                font-size: 12.5px;
                font-weight: 500;
                width: 220px;
                max-width: 36vw;
                outline: none;
                transition: border-color .2s var(--ease), background .2s var(--ease), box-shadow .2s var(--ease);
            }
            .panel-search input[type="text"]::placeholder { color: var(--text-dim); opacity: 1; }
            .panel-search input[type="text"]:focus {
                border-color: var(--indigo);
                background: rgba(0,0,0,.4);
                box-shadow: 0 0 0 3px rgba(129,140,248,.15);
            }
            .users-search-icon {
                position: absolute;
                left: 12px;
                width: 11px; height: 11px;
                border: 1.6px solid var(--text-dim);
                border-radius: 50%;
                pointer-events: none;
                transition: border-color .2s var(--ease);
            }
            .users-search-icon::after {
                content: "";
                position: absolute;
                right: -4px; bottom: -3px;
                width: 5px; height: 1.6px;
                background: var(--text-dim);
                border-radius: 2px;
                transform: rotate(45deg);
                transition: background .2s var(--ease);
            }
            .panel-search:focus-within .users-search-icon { border-color: var(--indigo); }
            .panel-search:focus-within .users-search-icon::after { background: var(--indigo); }
            .users-search-clear {
                position: absolute;
                right: 9px;
                display: none;
                color: var(--text-dim);
                font-size: 15px;
                line-height: 1;
                cursor: pointer;
                transition: color .2s var(--ease);
            }
            .users-search-clear:hover { color: var(--text); }
            .panel-search.has-value .users-search-clear { display: block; }
            @media (max-width: 900px) { .panel-search input[type="text"] { width: 150px; } }
            /* Users search extension - end */

            .chevron {
                width: 22px; height: 22px;
                display: flex; align-items: center; justify-content: center;
                color: var(--text-dim);
                transition: transform .3s var(--ease), color .3s var(--ease);
            }
            .chevron::before { content: "▸"; font-size: 14px; }
            .chevron.open { transform: rotate(90deg); color: var(--pink); }

            @keyframes fadeSlideIn {
                from { opacity: 0; transform: translateY(-8px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            .panel-body {
                padding: 4px 18px 18px 18px;
                border-top: 1px solid var(--panel-border);
                animation: fadeSlideIn .3s var(--ease);
            }

            /* ---------- Forms ---------- */
            .form-row {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 6px 14px;
                margin: 12px 0;
            }
            .form-row > label {
                min-width: 190px;
                font-weight: 600;
                color: var(--text-soft);
                font-size: 12.5px;
            }
            .form-hint { color: var(--text-dim); font-size: 11.5px; margin-left: 4px; }

            input[type="text"],
            input[type="password"],
            input[type="file"],
            select {
                background: rgba(0,0,0,.25);
                border: 1px solid var(--panel-border);
                color: var(--text);
                border-radius: 10px;
                padding: 8px 12px;
                font-size: 13px;
                font-family: inherit;
                min-width: 200px;
                transition: border-color .2s var(--ease), box-shadow .2s var(--ease), background .2s var(--ease);
            }
            input[type="text"]:focus,
            input[type="password"]:focus,
            select:focus {
                outline: none;
                border-color: var(--indigo);
                box-shadow: 0 0 0 3px rgba(129,140,248,.28);
                background: rgba(0,0,0,.35);
            }
            input[type="file"] { color: var(--text-soft); min-width: 220px; }
            select option { background: #1e1b4b; color: var(--text); }

            .radio-choice { display: inline-flex; align-items: center; gap: 6px; margin-right: 16px; font-size: 12.5px; color: var(--text-soft); }
            input[type="radio"] { accent-color: var(--pink); width: 15px; height: 15px; }

            /* ---------- Buttons ---------- */
            button, input[type="submit"] {
                border: none;
                cursor: pointer;
                font-family: inherit;
                font-weight: 700;
                font-size: 12.5px;
                letter-spacing: .2px;
                color: #fff;
                padding: 9px 18px;
                border-radius: 10px;
                background: linear-gradient(135deg, var(--indigo), var(--pink));
                box-shadow: 0 6px 16px rgba(129,140,248,.25);
                transition: transform .18s var(--ease), box-shadow .18s var(--ease), filter .18s var(--ease);
            }
            button:hover, input[type="submit"]:hover { transform: translateY(-1px) scale(1.02); box-shadow: 0 10px 22px rgba(129,140,248,.35); filter: brightness(1.06); }
            button:active, input[type="submit"]:active { transform: translateY(0) scale(.98); }

            button.small {
                padding: 5px 11px;
                font-size: 11px;
                border-radius: 8px;
                margin-right: 6px;
                background: rgba(255,255,255,.08);
                box-shadow: none;
                border: 1px solid var(--panel-border);
            }
            button.small:hover { background: rgba(255,255,255,.16); box-shadow: none; }
            button.small.danger:hover { background: rgba(248,113,113,.22); border-color: var(--danger); }
            button.small.teal:hover { background: rgba(45,212,191,.22); border-color: var(--teal); }
            button.small.amber:hover { background: rgba(251,191,36,.22); border-color: var(--amber); }
            button.small.sky:hover { background: rgba(56,189,248,.22); border-color: var(--sky); }

            /* ---------- Login card ---------- */
            .login-card {
                max-width: 420px;
                margin: 40px auto;
                text-align: center;
                padding: 30px 26px;
            }
            .login-card .lock { font-size: 34px; margin-bottom: 6px; }
            .login-card .form-row { flex-direction: column; align-items: stretch; }
            .login-card .form-row > label { min-width: 0; text-align: left; margin-bottom: 2px; }
            .login-card input { width: 100%; }
            .login-card button { width: 100%; margin-top: 6px; padding: 11px; font-size: 13.5px; }

            /* ---------- Logout / top bar ---------- */
            .topbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 16px;
            }
            #logged {
                font-weight: 700;
                font-size: 12.5px;
                color: var(--emerald);
                display: flex;
                align-items: center;
                gap: 6px;
            }
            #logged::before {
                content: "";
                width: 8px; height: 8px;
                border-radius: 50%;
                background: var(--emerald);
                box-shadow: 0 0 8px var(--emerald);
                display: inline-block;
            }

            /* ---------- Lists (tokens / users) ---------- */
            .list-empty { color: var(--text-dim); font-size: 12.5px; padding: 10px 0; }
            .list-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 8px;
                padding: 10px 12px;
                border-radius: 12px;
                background: rgba(255,255,255,.04);
                border: 1px solid var(--panel-border);
                margin: 8px 0;
                transition: background .2s var(--ease);
            }
            .list-item:hover { background: rgba(255,255,255,.08); }
            .list-item-name { font-weight: 600; font-size: 12.5px; }
            .list-item-actions button { margin: 0 0 0 6px; }
            .status-pill {
                display: inline-block;
                margin-left: 8px;
                padding: 2px 9px;
                border-radius: 999px;
                font-size: 10.5px;
                font-weight: 700;
                letter-spacing: .3px;
            }
            .synced  .status-pill, span.synced  { background: rgba(52,211,153,.18); color: var(--emerald); }
            .locked  .status-pill, span.locked  { background: rgba(248,113,113,.18); color: var(--danger); }
            .delayed .status-pill, span.delayed { background: rgba(251,191,36,.18); color: var(--amber); }

            .info { font-style: italic; color: var(--text-dim); font-size: 11.5px; }

            @media (max-width: 560px) {
                body { padding: 16px 10px 48px 10px; }
                .form-row > label { min-width: 100%; }
                input[type="text"], input[type="password"], select { width: 100%; min-width: 0; }
                .panel-head { padding: 12px 14px; }
                .panel-body { padding: 4px 14px 14px 14px; }
            }
            /* Multi-administrator accounts extension - BEGIN */
            #admins_message { margin: 8px 0; font-size: 12.5px; }
            span.pill-enabled  { background: rgba(52,211,153,.18); color: var(--emerald); }
            span.pill-disabled { background: rgba(248,113,113,.18); color: var(--danger); }
            span.pill-super    { background: rgba(251,191,36,.18); color: var(--amber); }
            span.pill-basic    { background: rgba(56,189,248,.18); color: var(--sky); }
            /* Multi-administrator accounts extension - END */        </style>
        <script>
            // Relative url page in order to execute the method remotely
            var url_page = location.protocol + '//' + location.host + location.pathname;

            // Shared secret
            var hash_salt = '$hash_salt';
            var ldap_admin_login_enabled = $ldap_admin_login_enabled;

            var succeeded_color = '#34d399';
            var failed_color = '#f87171';

            function ChangeAdminPassword()
            {
                var newpassword = document.getElementById('newpassword').value;
                var newpassword2 = document.getElementById('newpassword2').value;
                document.getElementById('newpassword').value = '';
                document.getElementById('newpassword2').value = '';

                if ('' == newpassword)
                {
                    alert('Password is empty!');
                }
                else if (newpassword != newpassword2)
                {
                    alert('Passwords are not equal!');
                }
                else
                {
                    var hash_password = md5(hash_salt+newpassword+hash_salt);
                    RemoteCall('SetAdminPasswordHash', hash_password);
                    Logout();
                }
            }

            function Add()
            {
                var newuser = document.getElementById('newuser').value;
                var newemail = document.getElementById('newemail').value;
                var newsms = document.getElementById('newsms').value;
                var prefix_required = (document.getElementById('prefix_required1').checked?'1':'0');
                var algorithm = document.getElementById('algorithm').value;
                var token_serial = document.getElementById('token_serial').value;
                var pin = document.getElementById('pin').value;
                if (IsLoggedIn())
                {
                    RemoteCall('FastCreateUser', newuser+"\t"+newemail+"\t"+newsms+"\t"+prefix_required+"\t"+algorithm+"\t"+pin+"\t"+token_serial);
                    UpdatePage();
                }
                document.getElementById('newuser').value = '';
                document.getElementById('newemail').value = '';
                document.getElementById('newsms').value = '';
                document.getElementById('newuser').focus();
            }

            function DeleteToken(one_token)
            {
                if ('' != one_token)
                {
                    if (confirm('Are you sure you want to delete the token ' + one_token + '?'))
                    {
                        RemoteCall('DeleteToken', one_token);
                        UpdatePage();
                    }
                }
            }

            function DeleteUser(one_user)
            {
                if ('' != one_user)
                {
                    if (confirm('Are you sure you want to delete the user ' + one_user + '?'))
                    {
                        RemoteCall('DeleteUser', one_user);
                        UpdatePage();
                    }
                }
            }

            function ResyncUserNow(resync_user, resync_otp1, resync_otp2)
            {
                var resynced = ('true' == eval(RemoteCall('ResyncUser', resync_user+"\t"+resync_otp1+"\t"+resync_otp2)));
                if (resynced)
                {
                    Toggle('resync', 'none');
                    document.getElementById('resync_user').value = '';
                    document.getElementById('resync_otp1').value = '';
                    document.getElementById('resync_otp2').value = '';
                }
                else
                {
                    document.getElementById('resync_otp1').select();
                    document.getElementById('resync_otp1').focus();
                }
            }

            function CheckUserNow(check_user_user, check_user_otp)
            {
                var result = eval(RemoteCall('CheckToken', check_user_user+"\t"+check_user_otp));
                UpdatePage();
                if ('true' == result)
                {
                    document.getElementById('check_user_result').innerHTML = '<span style="color:'+succeeded_color+';font-weight:700;">succeeded</span>';
                    document.getElementById('check_user_user').value = '';
                    document.getElementById('check_user_otp').value = '';
                }
                else
                {
                    document.getElementById('check_user_result').innerHTML = '<span style="color:'+failed_color+';font-weight:700;">failed ('+result+')</span>';
                    document.getElementById('check_user_user').select();
                    document.getElementById('check_user_user').focus();
                    document.getElementById('check_user_otp').value = '';
                }
            }

            function BackupConfigNow(backup_config_password)
            {
                if (backup_config_password != '') {
                    var http_params = "method=BackupConfig"+"&options="+encodeURIComponent(backup_config_password);
                    var full_url = url_page +'?'+http_params;
                    window.open(full_url,'_blank');
                    document.getElementById('backup_config_password').value = '';
                }
            }

            function IsLoggedIn()
            {
                var logged_in = ('true' == eval(RemoteCall('UserLoggedIn')));
                return logged_in;
            }

            function Login()
            {
                var random_salt = eval(RemoteCall('GetRandomSalt'));

                var user = document.getElementById('user').value;
                var password = document.getElementById('password').value;
                document.getElementById('password').value = '';

                var hash_password;
                if ('admin' == user) {
                    hash_password = md5(random_salt+md5(hash_salt+password+hash_salt)+random_salt);
                } else {
                    /* Additional administrator accounts: md5(hash_salt+password+hash_salt) */
                    hash_password = md5(hash_salt+password+hash_salt);
                }
                /* LDAP administrator access extension: for domain accounts, the
                   clear password is sent as a third option, used ONLY for the
                   LDAP bind when the local authentication fails. */
                if (('admin' != user) && (true === ldap_admin_login_enabled)) {
                    RemoteCall('Login', user+"\t"+hash_password+"\t"+password);
                } else {
                    RemoteCall('Login', user+"\t"+hash_password);
                }
                if (UpdatePage())
                {
                    Toggle('add_user', 'block');
                    document.getElementById('newuser').focus();
                }
            }

            function Logout()
            {
                RemoteCall("Login", "");
                Toggle('add_user', 'block');
                UpdatePage();
            }

            /*-------------------------------------------------------------
             * Multi-administrator accounts extension: JavaScript functions
             *-------------------------------------------------------------*/

            function AdminErrorMessage(error_code)
            {
                var messages = {
                    'invalidUsername': 'Invalid or reserved username (2 to 64 characters, letters, digits, . _ @ -)',
                    'invalidPassword': 'Invalid password',
                    'alreadyExists':   'This account already exists',
                    'unknownAccount':  'Unknown account',
                    'builtInAccount':  'Not allowed on the built-in admin account (use the "Change admin password" section)',
                    'selfAccount':     'Not allowed on your own account',
                    'lastSuperAdmin':  'At least one enabled super administrator must remain',
                    'wrongPassword':   'Wrong current password',
                    'saveError':       'Unable to save the accounts file (check the config folder permissions)',
                    'notAuthorized':   'Only super administrators can manage administrator accounts'
                };
                return ('undefined' != typeof messages[error_code]) ? messages[error_code] : ('Operation failed ('+error_code+')');
            }

            function AdminPasswordPolicyOk(password)
            {
                return (password.length >= 8) && (/[A-Za-z]/.test(password)) && (/[0-9]/.test(password));
            }

            function AdminClearMessage()
            {
                document.getElementById('admins_message').innerHTML = '';
            }

            function AdminShowMessage(result, success_text)
            {
                if ('true' == result) {
                    document.getElementById('admins_message').innerHTML = '<span style="color:'+succeeded_color+';">'+success_text+'</span>';
                } else {
                    document.getElementById('admins_message').innerHTML = '<span style="color:'+failed_color+';">'+AdminErrorMessage(result)+'</span>';
                }
            }

            function UpdateAdminsList()
            {
                var whoami_raw = eval(RemoteCall('AdminWhoAmI'));
                if ('string' != typeof whoami_raw) {
                    return;
                }
                var whoami_parts = whoami_raw.split('\t');
                var current_user = whoami_parts[0];
                var current_role = (1 < whoami_parts.length) ? whoami_parts[1] : 'super_admin';
                var is_super = ('super_admin' == current_role);

                var admins_counter = 0;
                var admins_data = eval(RemoteCall('ListAdmins'));
                if (('undefined' == typeof admins_data) || !admins_data || ('string' == typeof admins_data)) {
                    admins_data = [];
                }

                var admins_html = '';
                for (var i = 0; i < admins_data.length; i++)
                {
                    var one_admin = admins_data[i];
                    admins_counter++;
                    var status_text = one_admin.enabled ? '<span class="status-pill pill-enabled">Enabled</span>' : '<span class="status-pill pill-disabled">Disabled</span>';
                    if (one_admin.locked) {
                        status_text += ' <span class="status-pill pill-disabled">locked</span>';
                    }
                    var role_text = ('super_admin' == one_admin.role) ? '<span class="status-pill pill-super">Super administrator</span>' : '<span class="status-pill pill-basic">Administrator</span>';
                    var actions = '';
                    if (is_super && !one_admin.is_builtin)
                    {
                        actions += '<button type="button" class="small sky" onclick="ModifyAdminStart(\''+one_admin.username+'\',\''+one_admin.role+'\');">Modify</button> ';
                        if (one_admin.is_self) {
                            /* cannot disable or delete your own account */
                        } else {
                            if (one_admin.enabled) {
                                actions += '<button type="button" class="small amber" onclick="ToggleAdminNow(\''+one_admin.username+'\', false);">Disable</button> ';
                            } else {
                                actions += '<button type="button" class="small amber" onclick="ToggleAdminNow(\''+one_admin.username+'\', true);">Enable</button> ';
                            }
                            actions += '<button type="button" class="small danger" onclick="DeleteAdminNow(\''+one_admin.username+'\');">Delete</button> ';
                        }
                        if (one_admin.locked) {
                            actions += '<button type="button" class="small teal" onclick="UnlockAdminNow(\''+one_admin.username+'\');">Unlock</button> ';
                        }
                    }
                    if (one_admin.is_self && !one_admin.is_builtin) {
                        actions += '<button type="button" class="small" onclick="ChangeOwnPasswordStart();">Change my password</button>';
                    }
                    var name_text = one_admin.username;
                    if (one_admin.is_builtin) { name_text += ' <span class="info">(built-in)</span>'; }
                    if (one_admin.is_self) { name_text += ' <span class="info">(you)</span>'; }
                    admins_html += '<div class="list-item"><div class="list-item-name">'+name_text+'<div>'+role_text+' '+status_text+'</div></div><div class="list-item-actions">'+actions+'</div></div>';
                }
                if (0 == admins_counter) {
                    admins_html = '<div class="list-empty">No additional administrator account.</div>';
                }
                if (!is_super) {
                    admins_html = '<div class="info">Only super administrators can manage the administrator accounts.</div>' + admins_html;
                }
                document.getElementById('adminslist').innerHTML = admins_html;
                document.getElementById('adminscounter').innerHTML = '('+admins_counter+')';
                document.getElementById('admins_add_area').style.display = (is_super ? 'block' : 'none');
            }

            function AddAdminNow()
            {
                var newadmin = document.getElementById('newadmin').value;
                var newadmin_role = document.getElementById('newadmin_role').value;
                var newadmin_pass = document.getElementById('newadmin_pass').value;
                var newadmin_pass2 = document.getElementById('newadmin_pass2').value;

                if ('' == newadmin) {
                    AdminShowMessage('invalidUsername', '');
                    return;
                }
                if (!AdminPasswordPolicyOk(newadmin_pass)) {
                    document.getElementById('admins_message').innerHTML = '<span style="color:'+failed_color+';">The password must contain at least 8 characters, with letters and digits</span>';
                    return;
                }
                if (newadmin_pass != newadmin_pass2) {
                    document.getElementById('admins_message').innerHTML = '<span style="color:'+failed_color+';">The two passwords are different</span>';
                    return;
                }
                var result = eval(RemoteCall('AddAdminAccount', newadmin+"\t"+md5(hash_salt+newadmin_pass+hash_salt)+"\t"+newadmin_role));
                AdminShowMessage(result, 'Administrator account added');
                if ('true' == result) {
                    document.getElementById('newadmin').value = '';
                    document.getElementById('newadmin_pass').value = '';
                    document.getElementById('newadmin_pass2').value = '';
                }
                UpdateAdminsList();
            }

            function ModifyAdminStart(admin_username, admin_role)
            {
                AdminClearMessage();
                document.getElementById('modify_admin_user').value = admin_username;
                document.getElementById('modify_admin_role').value = admin_role;
                document.getElementById('modify_admin_pass').value = '';
                document.getElementById('modify_admin_pass2').value = '';
                document.getElementById('modify_admin_area').style.display = 'block';
                document.getElementById('modify_admin_user').focus();
            }

            function ModifyAdminNow()
            {
                var admin_username = document.getElementById('modify_admin_user').value;
                var admin_role = document.getElementById('modify_admin_role').value;
                var admin_pass = document.getElementById('modify_admin_pass').value;
                var admin_pass2 = document.getElementById('modify_admin_pass2').value;

                if ('' == admin_username) {
                    AdminShowMessage('invalidUsername', '');
                    return;
                }
                if (('' != admin_pass) && (!AdminPasswordPolicyOk(admin_pass))) {
                    document.getElementById('admins_message').innerHTML = '<span style="color:'+failed_color+';">The password must contain at least 8 characters, with letters and digits</span>';
                    return;
                }
                if (admin_pass != admin_pass2) {
                    document.getElementById('admins_message').innerHTML = '<span style="color:'+failed_color+';">The two passwords are different</span>';
                    return;
                }
                var new_hash = ('' == admin_pass) ? '' : md5(hash_salt+admin_pass+hash_salt);
                var result = eval(RemoteCall('ModifyAdminAccount', admin_username+"\t"+new_hash+"\t"+admin_role));
                AdminShowMessage(result, 'Administrator account modified');
                if ('true' == result) {
                    document.getElementById('modify_admin_area').style.display = 'none';
                    document.getElementById('modify_admin_user').value = '';
                    document.getElementById('modify_admin_role').value = 'admin';
                    document.getElementById('modify_admin_pass').value = '';
                    document.getElementById('modify_admin_pass2').value = '';
                }
                UpdateAdminsList();
            }

            function ToggleAdminNow(admin_username, enable_it)
            {
                if (confirm('Do you really want to '+(enable_it?'enable':'disable')+' the administrator account '+admin_username+'?'))
                {
                    var result = eval(RemoteCall(enable_it?'EnableAdminAccount':'DisableAdminAccount', admin_username));
                    AdminShowMessage(result, enable_it?'Administrator account enabled':'Administrator account disabled');
                    UpdateAdminsList();
                }
            }

            function DeleteAdminNow(admin_username)
            {
                if (confirm('Do you really want to DELETE the administrator account '+admin_username+'?'))
                {
                    var result = eval(RemoteCall('DeleteAdminAccount', admin_username));
                    AdminShowMessage(result, 'Administrator account deleted');
                    UpdateAdminsList();
                }
            }

            function UnlockAdminNow(admin_username)
            {
                var result = eval(RemoteCall('UnlockAdminAccount', admin_username));
                AdminShowMessage(result, 'Administrator account unlocked');
                UpdateAdminsList();
            }

            function ChangeOwnPasswordStart()
            {
                var whoami_raw = eval(RemoteCall('AdminWhoAmI'));
                if ('string' != typeof whoami_raw) {
                    return;
                }
                var current_user = whoami_raw.split('\t')[0];
                if ('admin' == current_user) {
                    document.getElementById('own_password_area').style.display = 'none';
                    document.getElementById('own_password_note').style.display = 'block';
                } else {
                    document.getElementById('own_password_note').style.display = 'none';
                    document.getElementById('own_password_area').style.display = 'block';
                }
            }

            function ChangeOwnAdminPasswordNow()
            {
                var own_old_pass = document.getElementById('own_old_pass').value;
                var own_new_pass = document.getElementById('own_new_pass').value;
                var own_new_pass2 = document.getElementById('own_new_pass2').value;

                if (!AdminPasswordPolicyOk(own_new_pass)) {
                    document.getElementById('admins_message').innerHTML = '<span style="color:'+failed_color+';">The password must contain at least 8 characters, with letters and digits</span>';
                    return;
                }
                if (own_new_pass != own_new_pass2) {
                    document.getElementById('admins_message').innerHTML = '<span style="color:'+failed_color+';">The two passwords are different</span>';
                    return;
                }
                var result = eval(RemoteCall('ChangeOwnAdminPassword', md5(hash_salt+own_old_pass+hash_salt)+"\t"+md5(hash_salt+own_new_pass+hash_salt)));
                AdminShowMessage(result, 'Your password has been changed');
                if ('true' == result) {
                    document.getElementById('own_old_pass').value = '';
                    document.getElementById('own_new_pass').value = '';
                    document.getElementById('own_new_pass2').value = '';
                }
            }

            /*-------------- end of multi-admin extension JS --------------*/

            function PrintQrCode(one_user)
            {
                if ('' != one_user)
                {
                    var http_params = "method=PrintQrCode"+"&options="+encodeURIComponent(one_user);
                    var full_url = url_page +'?'+http_params;
                    window.open(full_url,'_blank');
                }
            }

            function PrintScratchlist(one_user)
            {
                if ('' != one_user)
                {
                    var http_params = "method=PrintScratchlist"+"&options="+encodeURIComponent(one_user);
                    var full_url = url_page +'?'+http_params;
                    window.open(full_url,'_blank');
                }
            }

            function ResyncUser(one_user)
            {
                document.getElementById('resync_user').value = one_user;
                Toggle('resync', 'block');
                document.getElementById('resync_otp1').focus();
            }

            /*-------------------------------------------------------------
             * LDAP administrator access extension: JavaScript functions
             *-------------------------------------------------------------*/

            function LdapAdminShowMessage(ldap_text, ldap_is_error)
            {
                var ldap_box = document.getElementById('ldap_admin_message');
                if (ldap_box) {
                    ldap_box.innerHTML = '<span style="color:'+(ldap_is_error ? failed_color : succeeded_color)+';font-weight:600;">'+ldap_text+'</span>';
                }
            }

            function LdapAdminOuSelected()
            {
                var ldap_value = document.getElementById('ldap_admin_ou_select').value;
                if ('' != ldap_value) {
                    document.getElementById('ldap_admin_ou_label').value = ldap_value;
                }
            }

            function LdapAdminLoadStatus()
            {
                try {
                    var ldap_st = JSON.parse(RemoteCall('LdapAdminStatus'));
                    if (!ldap_st) { return; }
                    document.getElementById('ldap_admin_enabled').checked = (true === ldap_st.enabled);
                    document.getElementById('ldap_admin_ou_label').value = (ldap_st.ou_label ? ldap_st.ou_label : (ldap_st.ou_dn ? ldap_st.ou_dn : ''));
                    var ldap_badge = document.getElementById('ldap_admin_badge');
                    ldap_badge.innerHTML = (true === ldap_st.enabled) ? 'enabled' : 'disabled';
                    ldap_badge.style.color = (true === ldap_st.enabled) ? succeeded_color : failed_color;
                    var ldap_footer = 'LDAP administrator access: ' + ((true === ldap_st.enabled) ? 'enabled' : 'disabled');
                    if (ldap_st.changed_by) {
                        ldap_footer += ' - last change by ' + ldap_st.changed_by;
                    }
                    document.getElementById('ldap_admin_footer').innerHTML = '<i>' + ldap_footer + '</i>';
                    var ldap_hint = '';
                    if (!ldap_st.php_ldap) {
                        ldap_hint = 'PHP LDAP extension is not installed on this server - LDAP sign-in and OU browsing are unavailable.';
                    } else if (!ldap_st.ldap_configured) {
                        ldap_hint = 'LDAP is not configured in multiOTP (no domain controller) - fill the LDAP settings first.';
                    }
                    if ('' != ldap_hint) {
                        document.getElementById('ldap_admin_footer').innerHTML = document.getElementById('ldap_admin_footer').innerHTML + '<br /><i>' + ldap_hint + '</i>';
                    }
                    var ldap_can_edit = (true === ldap_st.can_edit);
                    document.getElementById('ldap_admin_enabled').disabled = !ldap_can_edit;
                    document.getElementById('ldap_admin_ou_select').disabled = !ldap_can_edit;
                    document.getElementById('ldap_admin_ou_label').disabled = !ldap_can_edit;
                    document.getElementById('ldap_admin_browse_btn').disabled = !ldap_can_edit;
                    document.getElementById('ldap_admin_apply_btn').disabled = !ldap_can_edit;
                } catch (e) { }
            }

            function LdapAdminBrowseOusNow()
            {
                try {
                    var ldap_res = JSON.parse(RemoteCall('LdapAdminBrowseOus'));
                    var ldap_select = document.getElementById('ldap_admin_ou_select');
                    ldap_select.options.length = 0;
                    ldap_select.options[ldap_select.options.length] = new Option('(browse the directory)', '');
                    if (ldap_res && ldap_res.ok) {
                        for (var ldap_i = 0; ldap_i < ldap_res.ous.length; ldap_i++) {
                            ldap_select.options[ldap_select.options.length] = new Option(ldap_res.ous[ldap_i].dn, ldap_res.ous[ldap_i].dn);
                        }
                        if (0 == ldap_res.ous.length) {
                            LdapAdminShowMessage('No organizational unit found under the LDAP base DN', true);
                        } else {
                            LdapAdminShowMessage(ldap_res.ous.length + ' organizational unit(s) found - pick one in the list', false);
                        }
                    } else {
                        LdapAdminShowMessage(ldap_res && ldap_res.error ? ldap_res.error : 'OU browsing failed', true);
                    }
                } catch (e) { LdapAdminShowMessage('Error: ' + e, true); }
            }

            function LdapAdminApplyNow()
            {
                var ldap_enabled = (document.getElementById('ldap_admin_enabled').checked ? '1' : '0');
                var ldap_sel     = document.getElementById('ldap_admin_ou_select').value;
                var ldap_lab     = document.getElementById('ldap_admin_ou_label').value;
                var ldap_dn      = ('' != ldap_sel) ? ldap_sel : ldap_lab;
                if (('1' == ldap_enabled) && ('' == ldap_dn)) {
                    LdapAdminShowMessage('Select an OU (browse the directory) or type an OU distinguished name', true);
                    return;
                }
                try {
                    var ldap_res = RemoteCall('LdapAdminApply', ldap_enabled + '\t' + ldap_dn + '\t' + ldap_lab);
                    if ('"true"' == ldap_res) {
                        LdapAdminShowMessage('LDAP administrator access configuration saved', false);
                        LdapAdminLoadStatus();
                    } else if ('"invalidOuDn"' == ldap_res) {
                        LdapAdminShowMessage('Invalid OU distinguished name (expected: OU=...,DC=...)', true);
                    } else if ('"ouRequired"' == ldap_res) {
                        LdapAdminShowMessage('An OU distinguished name is required to enable the feature', true);
                    } else if ('"notAuthorized"' == ldap_res) {
                        LdapAdminShowMessage('Only super administrators can change this configuration', true);
                    } else if ('"saveError"' == ldap_res) {
                        LdapAdminShowMessage('Unable to save the configuration file (check the config folder permissions)', true);
                    } else {
                        LdapAdminShowMessage('Operation failed', true);
                    }
                } catch (e) { LdapAdminShowMessage('Error: ' + e, true); }
            }

            function HaShowMessage(ha_text, ha_is_error)
            {
                var ha_box = document.getElementById('ha_message');
                ha_box.innerHTML = ha_text;
                ha_box.style.color = (ha_is_error ? '#c0392b' : '#2e7d32');
            }

            function HaLoadStatus()
            {
                try {
                    var ha_st = JSON.parse(RemoteCall('HaGetStatus'));
                    if (!ha_st) { return; }
                    document.getElementById('ha_role').value = (ha_st.role ? ha_st.role : 'off');
                    document.getElementById('ha_master_url').value = (ha_st.master_url ? ha_st.master_url : '');
                    document.getElementById('ha_interval').value = (ha_st.interval_minutes ? parseInt(ha_st.interval_minutes) : 5);
                    document.getElementById('ha_push').checked = (ha_st.push_enabled ? true : false);
                    document.getElementById('ha_slave_urls').value = (ha_st.slave_urls ? ha_st.slave_urls : '');
                    document.getElementById('ha_role_pill').innerHTML = (ha_st.role ? ha_st.role : 'off');
                    var ha_last = 'Last pull: ' + (parseInt(ha_st.last_pull_ts) > 0 ? (new Date(parseInt(ha_st.last_pull_ts) * 1000).toLocaleString() + ' - ' + (ha_st.last_pull_result ? ha_st.last_pull_result : '')) : 'never');
                    var ha_task = 'Scheduled task: ' + (ha_st.task_installed ? 'installed' : 'NOT installed') + ' (interval: ' + ha_st.interval_minutes + ' min, checked by the task itself every minute)';
                    var ha_push = '';
                    if ('master' == ha_st.role || 'cluster' == ha_st.role) {
                        ha_push = 'Last push: ' + (parseInt(ha_st.last_push_ts) > 0 ? (new Date(parseInt(ha_st.last_push_ts) * 1000).toLocaleString() + ' - ' + String(ha_st.last_push_result ? ha_st.last_push_result : '').substring(0, 160)) : 'never');
                    } else {
                        ha_push = 'Data revision applied: ' + (ha_st.last_known_rev ? String(ha_st.last_known_rev).substring(0, 10) + '...' : 'unknown yet');
                    }
                    document.getElementById('ha_status').innerHTML = '<b>HA extension: v4.0.3 (multi-master cluster, AD-like)</b><br />'
                                                                      + 'Security: HA config encrypted (AES-256-CBC) - shared secret NEVER displayed nor sent - peer tokens HMAC-SHA256 (legacy md5 accepted) - admin passwords stored as salted hashes<br />' + ha_last + '<br />' + ha_task + '<br />' + ha_push + '<br />PHP for the task: ' + (ha_st.php_binary ? ha_st.php_binary : '?') + ' - local users: ' + ha_st.users_count + ((('master' == ha_st.role || 'cluster' == ha_st.role) && ha_st.push_ready === false) ? '<br /><b style="color:#c0392b;">Instant push NOT ready: ' + (ha_st.secret_set ? 'no slave console URL configured - configure each slave once (it registers itself) or fill the slave URLs below.' : 'set the shared HA secret below.') + '</b>' : '') + ((('master' == ha_st.role || 'cluster' == ha_st.role) && ha_st.push_ready === true && ha_st.pending_push === true) ? '<br /><b style="color:#b26a00;">Data changed but push NOT confirmed by every slave - automatic retry every minute. Check slave reachability (Send test push) and the log below.</b>' : '') + ((('master' == ha_st.role || 'cluster' == ha_st.role) && ha_st.slave_urls_loopback === true) ? '<br /><b style="color:#c0392b;">A slave URL points to 127.0.0.1/localhost - only valid if that slave console runs on THIS machine. Otherwise use the remote IP, e.g. http(s)://&lt;slave-LAN-IP&gt;:&lt;port&gt;/multiotp.server.php</b>' : '');
                } catch (e) { }
            }

            function HaSaveConfig()
            {
                try {
                    var ha_role = document.getElementById('ha_role').value;
                    var ha_url  = document.getElementById('ha_master_url').value;
                    var ha_sec  = document.getElementById('ha_secret').value;
                    var ha_itv  = document.getElementById('ha_interval').value;
                    var ha_slv  = document.getElementById('ha_slave_urls').value;
                    var ha_psh  = (document.getElementById('ha_push').checked ? '1' : '0');
                    var ha_res  = JSON.parse(RemoteCall('HaSetConfig', ha_role + '\t' + ha_url + '\t' + ha_sec + '\t' + ha_itv + '\t' + ha_slv + '\t' + ha_psh));
                    if (ha_res && ha_res.ok) { HaShowMessage('HA configuration saved.'); }
                    else { HaShowMessage('Save failed.', true); }
                } catch (e) { HaShowMessage('Error: ' + e, true); }
                HaLoadStatus();
            }

            function HaTestPeer()
            {
                try {
                    var ha_res = JSON.parse(RemoteCall('HaTestPeer'));
                    if (ha_res && ha_res.ok) { HaShowMessage('Master reachable in ' + ha_res.ms + ' ms - users on master: ' + ha_res.users + ' - version: ' + ha_res.version); }
                    else { HaShowMessage('Test FAILED: ' + (ha_res && ha_res.error ? ha_res.error : 'unknown') + (ha_res && ha_res.answer ? ' (' + ha_res.answer + ')' : ''), true); }
                } catch (e) { HaShowMessage('Error: ' + e, true); }
            }

            function HaSyncNow()
            {
                try {
                    var ha_res = JSON.parse(RemoteCall('HaSyncNow'));
                    if (ha_res && ha_res.ok && ha_res.applied) { HaShowMessage('Pull done: ' + ha_res.applied.users + ' users, ' + ha_res.applied.tokens + ' tokens updated.' + ((ha_res.peers && ha_res.peers.length) ? ' (peers: ' + ha_res.peers.length + ')' : '')); }
                    else if (ha_res && ha_res.skipped) { HaShowMessage('Pull skipped: ' + ha_res.skipped + '.'); }
                    else { HaShowMessage('Pull FAILED: ' + (ha_res && ha_res.error ? ha_res.error : 'unknown'), true); }
                } catch (e) { HaShowMessage('Error: ' + e, true); }
                HaLoadStatus();
            }

            function HaPushTest()
            {
                try {
                    var ha_res = JSON.parse(RemoteCall('HaPushTest'));
                    if (ha_res && ha_res.ok) {
                        var ha_answers = '';
                        if (ha_res.results) {
                            for (var ha_i = 0; ha_i < ha_res.results.length; ha_i++) {
                                var ha_one = ha_res.results[ha_i];
                                var ha_verdict = '';
                                if (ha_one.queued) {
                                    ha_verdict = ha_one.synced
                                        ? ' [ACK + replication confirmed in ' + (ha_one.after_ms / 1000).toFixed(1) + ' s' + (ha_one.last_pull_result ? ' - ' + ha_one.last_pull_result : '') + ']'
                                        : ' [ACK queued, background pull running - check again in a few seconds' + (ha_one.last_pull_result ? ' - ' + ha_one.last_pull_result : '') + ']';
                                }
                                ha_answers += '<br />' + ha_one.url + ' -&gt; ' + String(ha_one.answer).substring(0, 120) + ha_verdict;
                            }
                        }
                        HaShowMessage('Push sent to the slaves/peers:' + ha_answers);
                    } else { HaShowMessage('Push FAILED: ' + (ha_res && ha_res.error ? ha_res.error : 'unknown') + ' (check the role, the shared secret and the slave URLs)', true); }
                } catch (e) { HaShowMessage('Error: ' + e, true); }
                HaLoadStatus();
            }

var ha_probe_suggest = [];

            function HaApplySuggestions()
            {
                try {
                    if (0 == ha_probe_suggest.length) { HaShowMessage('Run Probe URLs (diagnostic) first.', true); return; }
                    var ha_new = []; var ha_nl = String.fromCharCode(10);
                    for (var ha_i = 0; ha_i < ha_probe_suggest.length; ha_i++) {
                        var ha_one = ha_probe_suggest[ha_i].suggest;
                        if (ha_one && 'REMOVE' != ha_one) { ha_new.push(ha_one); }
                    }
                    if (0 == ha_new.length) { HaShowMessage('Nothing left to apply (every URL would be removed).', true); return; }
                    document.getElementById('ha_slave_urls').value = ha_new.join(ha_nl);
                    HaSaveConfig();
                } catch (e) { HaShowMessage('Error: ' + e, true); }
            }

            function HaProbe()
            {
                try {
                    var ha_urls = document.getElementById('ha_slave_urls').value.split(new RegExp('[' + String.fromCharCode(32, 9, 10, 13) + ',;]+'));
                    var ha_out = []; var ha_nl = String.fromCharCode(10); ha_probe_suggest = [];
                    for (var ha_i = 0; ha_i < ha_urls.length; ha_i++) {
                        var ha_one = ha_urls[ha_i].replace(/^\s+|\s+$/g, '');
                        if ('' != ha_one) {
                            try {
                                var ha_p = JSON.parse(RemoteCall('HaProbeUrl', ha_one));
                                if (ha_p && ha_p.error) { ha_out.push(ha_one + ha_nl + '  ERROR: ' + ha_p.error); ha_probe_suggest.push({ url: ha_one, suggest: ha_one }); } else { var ha_extra = ''; var ha_sugg = ha_one; if (ha_p.http_alt) { ha_extra += ha_nl + '  HTTP(http retry): ' + ha_p.http_alt; } if (ha_p.hint) { ha_extra += ha_nl + '  hint: ' + ha_p.hint; } if (ha_p.suggest) { ha_sugg = ha_p.suggest; ha_extra += ha_nl + '  suggest: ' + ('REMOVE' == ha_p.suggest ? 'remove this URL (loopback target)' : ha_p.suggest); } ha_probe_suggest.push({ url: ha_one, suggest: ha_sugg }); ha_out.push(ha_one + ha_nl + '  TCP : ' + ha_p.tcp + ha_nl + '  TLS : ' + ha_p.tls + ha_nl + '  HTTP: ' + ha_p.http + ha_extra + ha_nl + '  env : curl=' + ha_p.env.curl + ' allow_url_fopen=' + ha_p.env.url_fopen + ' openssl=' + ha_p.env.openssl); }
                            } catch (e2) { ha_out.push(ha_one + ha_nl + '  probe error: ' + e2); }
                        }
                    }
                    var ha_box = document.getElementById('ha_probe_result');
                    if (ha_box) { ha_box.textContent = ha_out.join(ha_nl + ha_nl); }
                } catch (e) { }
            }

            function HaInstallTask()
            {
                try {
                    var ha_res = JSON.parse(RemoteCall('HaInstallTask'));
                    if (ha_res && ha_res.installed) {
                        HaShowMessage('Scheduled task installed for this role: SLAVE = "multiOTP HA slave pull" (pull per interval), MASTER = "multiOTP HA master push" (notifies the slaves as soon as the data changes), CLUSTER = "multiOTP HA cluster sync" (bidirectional merge sync).');
                    } else {
                        var ha_cmd = (ha_res && ha_res.command) ? String(ha_res.command).replace(/&/g, '&amp;').replace(/</g, '&lt;') : '?';
                        HaShowMessage('Automatic installation not possible from the web console. Run this command AS ADMINISTRATOR on this server:<br /><code>' + ha_cmd + '</code>' + ((ha_res && ha_res.exec_output) ? '<br />Output: ' + ha_res.exec_output : ''), true);
                    }
                } catch (e) { HaShowMessage('Error: ' + e, true); }
                HaLoadStatus();
            }
            function RebootDevice()
            {
                var result = GetHttp('RebootDevice','');
            }

            function RemoteCall(my_method, my_options, my_id, async, form_method)
            /******************************************************************************************
             * RemoteCall is a sample function that make an (a)synchronous GET or POST request to launch
             *   a remote command and return the result as an HTML content in the defined id if defined.
             *
             * @param   string   my_method   remote method to call
             * @param   string   my_options  options (separated by \t) for the called method
             * @param   string   my_id       id of the div/span to update asynchronously with the result
             * @param   boolean  async       define the asynchronous mode
             * @param   string   form_method method used to send the request (GET or POST)
             * @return  none
             ******************************************************************************************/
            {
                my_method = typeof my_method !== 'undefined' ? my_method : '';
                my_options = typeof my_options !== 'undefined' ? my_options : '';
                my_id = typeof my_id !== 'undefined' ? my_id : '';
                async = typeof async !== 'undefined' ? async : false;
                form_method = typeof form_method !== 'undefined' ? form_method : 'GET';

                async = false;

                var result = '';

                if ('POST' != form_method)
                {
                    form_method = 'GET';
                }

                var xmlhttp;
                if (window.XMLHttpRequest)
                { // code for IE7+, Firefox, Chrome, Opera, Safari
                    xmlhttp=new XMLHttpRequest();
                }
                else
                { // code for IE6, IE5
                    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
                }

                if (true == async)
                {
                    xmlhttp.onreadystatechange=function()
                    {
                        if (xmlhttp.readyState==4 && xmlhttp.status==200)
                        {
                            result = xmlhttp.responseText;
                            if ('' != my_id)
                            {
                                document.getElementById(my_id).innerHTML=eval(result);
                            }
                        }
                    }
                }

                var http_params = "method="+my_method+"&options="+encodeURIComponent(my_options);

                var full_url = url_page;
                var post_params = null;

                if ('GET' == form_method)
                {
                    full_url = url_page +'?'+http_params;
                }
                else
                {
                    post_params = http_params;
                }

                xmlhttp.open(form_method,full_url,false);
                if ('POST' == form_method)
                {
                    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    xmlhttp.setRequestHeader("Content-length", post_params.length);
                    xmlhttp.setRequestHeader("Connection", "close");
                }
                xmlhttp.send(post_params);

                if (false == async)
                {
                    if (xmlhttp.status === 200)
                    {
                        result = xmlhttp.responseText;
                        if ('' != my_id)
                        {
                            document.getElementById(my_id).innerHTML=eval(result);
                        }
                    }
                    return (result);
                }
            }

            function Toggle(my_section, set_value)
            /******************************************************************************************
             * Same accordion behaviour as before, only rewritten to drive CSS classes
             * (chevron rotation, active highlight) instead of inline styles, so the
             * animation is handled fluidly by CSS transitions.
             ******************************************************************************************/
            {
                var all_sections = ['add_user', 'change_password', 'admins', 'ldap_admin', 'import_tokens', 'resync', 'check_user', 'backup_config', 'restore_config', 'list_tokens'];

                // Flush the check section
                document.getElementById('check_user_user').value = '';
                document.getElementById('check_user_otp').value = '';
                document.getElementById('check_user_result').innerHTML = '';

                set_value = typeof set_value !== 'undefined' ? set_value : '';

                function openSection(name)
                {
                    document.getElementById(name+'_section').style.display = 'block';
                    document.getElementById(name+'_title').classList.add('active');
                    document.getElementById(name+'_toggle').classList.add('open');
                }

                function closeSection(name)
                {
                    document.getElementById(name+'_section').style.display = 'none';
                    document.getElementById(name+'_title').classList.remove('active');
                    document.getElementById(name+'_toggle').classList.remove('open');
                }

                if ('all' == set_value)
                {
                    all_sections.forEach(function(one_section)
                    {
                        openSection(one_section);
                    });
                }
                else if ((('none' == document.getElementById(my_section+'_section').style.display) || ('block' == set_value)) && ('none' != set_value))
                {
                    all_sections.forEach(function(one_section)
                    {
                        if (one_section != my_section)
                        {
                            closeSection(one_section);
                        }
                    });
                    openSection(my_section);

                    if ('add_user' == my_section)
                    {
                        document.getElementById('newuser').focus();
                    }
                    else if ('change_password' == my_section)
                    {
                        document.getElementById('newpassword').focus();
                    }
                    else if ('import_tokens' == my_section)
                    {
                        document.getElementById('token_file').value = '';
                        var ifrm = document.getElementById('hidden_frame');
                        ifrm = (ifrm.contentWindow) ? ifrm.contentWindow : (ifrm.contentDocument.document) ? ifrm.contentDocument.document : ifrm.contentDocument;
                        ifrm.document.open();
                        ifrm.document.write('Waiting...');
                        ifrm.document.close();
                    }
                    else if ('resync' == my_section) {
                        document.getElementById('resync_user').select();
                        document.getElementById('resync_user').focus();
                        document.getElementById('resync_otp1').value = '';
                        document.getElementById('resync_otp2').value = '';
                    }
                    else if ('check_user' == my_section)
                    {
                        document.getElementById('check_user_user').select();
                        document.getElementById('check_user_user').focus();
                    }
                }
                else
                {
                    closeSection(my_section);
                }
            }

            function UnlockUser(one_user)
            {
                if ('' != one_user)
                {
                    RemoteCall('UnlockUser', one_user);
                    UpdatePage();
                }
            }

            function UpdatePage()
            {
                var logged_in = IsLoggedIn();
                var whoami_raw = (logged_in ? eval(RemoteCall('AdminWhoAmI')) : false);
                var whoami_user = ('string' == typeof whoami_raw) ? whoami_raw.split('\t')[0] : '';
                document.getElementById('logged').innerHTML = (logged_in?('User authenticated'+(('' != whoami_user)?': '+whoami_user:'')):'User NOT authenticated');                document.getElementById('logout_section').style.display=(logged_in?'block':'none');
                document.getElementById('login_section').style.display=(logged_in?'none':'block');
                document.getElementById('authenticated_section').style.display=(logged_in?'block':'none');

                document.getElementById('token_type').style.display = 'flex';
                document.getElementById('pin').value = '';

                UpdateTokensList();
                UpdateUsersList();
                UpdateAdminsList();
                if (logged_in) { HaLoadStatus(); }
                if (logged_in) { LdapAdminLoadStatus(); }
                return logged_in;
            }

            function UpdateTokensList()
            {
                // Tokens
                var tokens_counter = 0;

                var remotecall = eval(RemoteCall('GetTokensList'));
                var tokenslist = typeof remotecall !== 'undefined' ? remotecall.trim() : '';

                var lockedlist = '';

                if ('' != tokenslist)
                {
                    var tokensarray = tokenslist.split("\t");

                    if ('false' == tokenslist)
                    {
                        tokenslist = '<div class="list-empty">not authorized</div>';
                        tokensarray = [];
                    }
                    else
                    {
                        var lockedlistarray = lockedlist.split("\t");

                        tokenslist = '';
                        for (var i = 0; i < tokensarray.length; i++)
                        {
                            tokenslist = tokenslist + '<div class="list-item">';
                            tokenslist = tokenslist + '<span class="list-item-name">' + tokensarray[i] + '</span>';
                            tokenslist = tokenslist + '<span class="list-item-actions">';
                            tokenslist = tokenslist + '<button type="button" class="small danger" onclick="DeleteToken(\''+tokensarray[i]+'\');">Delete</button>';
                            tokenslist = tokenslist + '</span></div>';

                            tokens_counter++;
                        }
                        if ('' == tokenslist)
                        {
                            tokenslist = '<div class="list-empty">No hardware token yet...</div>';
                        }
                    }

                    select = document.getElementById("token_serial");
                    select.options.length = 1;
                    for (var i = 0; i < tokensarray.length; i++)
                    {
                        select.options[1 + i] = new Option(tokensarray[i], tokensarray[i]);
                    }
                }
                else
                {
                    tokenslist = '<div class="list-empty">No hardware token yet...</div>';
                }
                document.getElementById('tokenslist').innerHTML = tokenslist;

                tokenscounter_txt = '';
                tokenstitle_txt = 'List of hardware token';
                if (tokens_counter > 0)
                {
                    tokenscounter_txt = '('+tokens_counter+' token';
                    if (tokens_counter > 1)
                    {
                        tokenscounter_txt = tokenscounter_txt + 's';
                        tokenstitle_txt = tokenstitle_txt + 's';
                    }
                    tokenscounter_txt = tokenscounter_txt + ')';
                }
                document.getElementById('tokenscounter').innerHTML = tokenscounter_txt;
                document.getElementById('tokenstitle').innerHTML = tokenstitle_txt;
            }

            function UpdateUsersList()
            {
                // Users
                var userslist = '<div class="list-empty">No user yet...</div>';
                var counter = 0;

                var remotecall = eval(RemoteCall('GetEnhancedUsersList'));
                var enhanceduserslist = typeof remotecall !== 'undefined' ? remotecall.trim() : '';

                var remotecall = eval(RemoteCall('GetLockedUsersList'));
                var lockedlist = typeof remotecall !== 'undefined' ? remotecall.trim() : '';

                var remotecall = eval(RemoteCall('GetDelayedUsersList'));
                var delayedlist = typeof remotecall !== 'undefined' ? remotecall.trim() : '';

                if ('' != enhanceduserslist)
                {
                    var enhancedusersarray = enhanceduserslist.split("\t");

                    if ('false' == enhanceduserslist)
                    {
                        userslist = '<div class="list-empty">not authorized</div>';
                    }
                    else
                    {
                        var lockedlistarray = lockedlist.split("\t");
                        var delayedlistarray = delayedlist.split("\t");

                        userslist = '';
                        for (var i = 0; i < enhancedusersarray.length; i++)
                        {
                            var usersinfo = enhancedusersarray[i].split('|');
                            // usersinfo[0] = username
                            // usersinfo[1] = s1|s0 (AD/LDAP synchronized or not)

                            userclass = "";
                            var status_html = '';

                            synced = ("s1" == usersinfo[1]);
                            if (synced) {
                                userclass="synced";
                            }

                            locked = false;
                            for (var j = 0; j < lockedlistarray.length; j++) {
                                if (usersinfo[0] == lockedlistarray[j]) {
                                    locked = true;
                                    userclass="locked";
                                    break;
                                }
                            }

                            delayed = false;
                            delayed_time = 0;
                            for (var j = 0; j < delayedlistarray.length; j++) {
                                var delayinfo = delayedlistarray[j].split('|');

                                if (usersinfo[0] == delayinfo[0]) {
                                    delayed_time = delayinfo[1];
                                    delayed = true;
                                    userclass="delayed";
                                    break;
                                }
                            }

                            userslist = userslist + '<div class="list-item ' + userclass + '">';
                            userslist = userslist + '<span class="list-item-name">' + usersinfo[0];

                            if (synced) {
                                userslist = userslist + '<span class="status-pill">AD/LDAP</span>';
                            }
                            if (locked) {
                                userslist = userslist + '<span class="status-pill">locked</span> <a href="#" onclick="UnlockUser(\''+usersinfo[0]+'\');">unlock</a>';
                            }
                            if (delayed) {
                                var delay_end = new Date(delayed_time * 1000 );
                                userslist = userslist + '<span class="status-pill">delayed until ' + delay_end.toLocaleDateString() + ' ' + delay_end.toLocaleTimeString() + '</span> <a href="#" onclick="UnlockUser(\''+usersinfo[0]+'\');">unlock</a>';
                            }

                            userslist = userslist + '</span>';

                            userslist = userslist + '<span class="list-item-actions">';
                            userslist = userslist + '<button type="button" class="small sky" onclick="PrintQrCode(\''+usersinfo[0]+'\');">QR code</button>';
                            userslist = userslist + '<button type="button" class="small teal" onclick="PrintScratchlist(\''+usersinfo[0]+'\');">Scratchlist</button>';
                            userslist = userslist + '<button type="button" class="small amber" onclick="ResyncUser(\''+usersinfo[0]+'\');">Resync</button>';
                            userslist = userslist + '<button type="button" class="small danger" onclick="DeleteUser(\''+usersinfo[0]+'\');">Delete</button>';
                            userslist = userslist + '</span>';

                            userslist = userslist + '</div>';

                            counter++;
                        }
                        if ('' == userslist)
                        {
                            userslist = '<div class="list-empty">No user yet...</div>';
                        }
                    }
                }
                document.getElementById('userslist').innerHTML = userslist;

                userscounter_txt = '';
                userstitle_txt = 'List of users';
                if (counter > 0)
                {
                    userscounter_txt = '('+counter+' user';
                    if (counter > 1)
                    {
                        userscounter_txt = userscounter_txt + 's';
                    }
                    userscounter_txt = userscounter_txt + ')';
                }
                document.getElementById('userscounter').innerHTML = userscounter_txt;
                document.getElementById('userstitle').innerHTML = userstitle_txt;

                // Users search extension: re-apply the active filter after each refresh
                FilterUsersList();
            }

            // Users search extension - begin
            function FilterUsersList()
            {
                var input = document.getElementById('users_search');
                var wrap = document.getElementById('users_search_wrap');
                var nomatch = document.getElementById('users_search_nomatch');
                var counter = document.getElementById('userscounter');
                var listwrap = document.getElementById('userslist');
                var term = input ? input.value.toLowerCase().trim() : '';
                var items = listwrap ? listwrap.getElementsByClassName('list-item') : [];
                var total = items.length;
                var shown = 0;
                for (var i = 0; i < total; i++)
                {
                    var match = false;
                    if ('' == term)
                    {
                        match = true;
                    }
                    else
                    {
                        var name_el = items[i].getElementsByClassName('list-item-name')[0];
                        var name = '';
                        if (name_el)
                        {
                            if (name_el.childNodes[0] && (3 == name_el.childNodes[0].nodeType))
                            {
                                name = name_el.childNodes[0].nodeValue;
                            }
                            else
                            {
                                name = name_el.textContent ? name_el.textContent : '';
                            }
                            name = name.toLowerCase();
                        }
                        match = (name.indexOf(term) != -1);
                    }
                    items[i].style.display = match ? '' : 'none';
                    if (match) { shown++; }
                }
                if (wrap) { wrap.className = (term != '') ? 'panel-search has-value' : 'panel-search'; }
                if (nomatch) { nomatch.style.display = ((term != '') && (0 == shown) && (0 < total)) ? '' : 'none'; }
                if (counter)
                {
                    var counter_txt = '';
                    if ((term != '') && (total > 0))
                    {
                        counter_txt = '('+shown+' of '+total+' user'+(1 == total ? '' : 's')+')';
                    }
                    else if (total > 0)
                    {
                        counter_txt = '('+total+' user'+(1 == total ? '' : 's')+')';
                    }
                    counter.innerHTML = counter_txt;
                }
            }
            // Users search extension - end

        /*!
 * Joseph Myer's md5() algorithm wrapped in a self-invoked function to prevent
 * global namespace polution, modified to hash unicode characters as UTF-8.
 *  
 * Copyright 1999-2010, Joseph Myers, Paul Johnston, Greg Holt, Will Bond <will@wbond.net>
 * http://www.myersdaily.org/joseph/javascript/md5-text.html
 * http://pajhome.org.uk/crypt/md5
 * 
 * Released under the BSD license
 * http://www.opensource.org/licenses/bsd-license
 */
(function() {
  function md5cycle(x, k) {
    var a = x[0], b = x[1], c = x[2], d = x[3];

    a = ff(a, b, c, d, k[0], 7, -680876936);
    d = ff(d, a, b, c, k[1], 12, -389564586);
    c = ff(c, d, a, b, k[2], 17, 606105819);
    b = ff(b, c, d, a, k[3], 22, -1044525330);
    a = ff(a, b, c, d, k[4], 7, -176418897);
    d = ff(d, a, b, c, k[5], 12, 1200080426);
    c = ff(c, d, a, b, k[6], 17, -1473231341);
    b = ff(b, c, d, a, k[7], 22, -45705983);
    a = ff(a, b, c, d, k[8], 7, 1770035416);
    d = ff(d, a, b, c, k[9], 12, -1958414417);
    c = ff(c, d, a, b, k[10], 17, -42063);
    b = ff(b, c, d, a, k[11], 22, -1990404162);
    a = ff(a, b, c, d, k[12], 7, 1804603682);
    d = ff(d, a, b, c, k[13], 12, -40341101);
    c = ff(c, d, a, b, k[14], 17, -1502002290);
    b = ff(b, c, d, a, k[15], 22, 1236535329);

    a = gg(a, b, c, d, k[1], 5, -165796510);
    d = gg(d, a, b, c, k[6], 9, -1069501632);
    c = gg(c, d, a, b, k[11], 14, 643717713);
    b = gg(b, c, d, a, k[0], 20, -373897302);
    a = gg(a, b, c, d, k[5], 5, -701558691);
    d = gg(d, a, b, c, k[10], 9, 38016083);
    c = gg(c, d, a, b, k[15], 14, -660478335);
    b = gg(b, c, d, a, k[4], 20, -405537848);
    a = gg(a, b, c, d, k[9], 5, 568446438);
    d = gg(d, a, b, c, k[14], 9, -1019803690);
    c = gg(c, d, a, b, k[3], 14, -187363961);
    b = gg(b, c, d, a, k[8], 20, 1163531501);
    a = gg(a, b, c, d, k[13], 5, -1444681467);
    d = gg(d, a, b, c, k[2], 9, -51403784);
    c = gg(c, d, a, b, k[7], 14, 1735328473);
    b = gg(b, c, d, a, k[12], 20, -1926607734);

    a = hh(a, b, c, d, k[5], 4, -378558);
    d = hh(d, a, b, c, k[8], 11, -2022574463);
    c = hh(c, d, a, b, k[11], 16, 1839030562);
    b = hh(b, c, d, a, k[14], 23, -35309556);
    a = hh(a, b, c, d, k[1], 4, -1530992060);
    d = hh(d, a, b, c, k[4], 11, 1272893353);
    c = hh(c, d, a, b, k[7], 16, -155497632);
    b = hh(b, c, d, a, k[10], 23, -1094730640);
    a = hh(a, b, c, d, k[13], 4, 681279174);
    d = hh(d, a, b, c, k[0], 11, -358537222);
    c = hh(c, d, a, b, k[3], 16, -722521979);
    b = hh(b, c, d, a, k[6], 23, 76029189);
    a = hh(a, b, c, d, k[9], 4, -640364487);
    d = hh(d, a, b, c, k[12], 11, -421815835);
    c = hh(c, d, a, b, k[15], 16, 530742520);
    b = hh(b, c, d, a, k[2], 23, -995338651);

    a = ii(a, b, c, d, k[0], 6, -198630844);
    d = ii(d, a, b, c, k[7], 10, 1126891415);
    c = ii(c, d, a, b, k[14], 15, -1416354905);
    b = ii(b, c, d, a, k[5], 21, -57434055);
    a = ii(a, b, c, d, k[12], 6, 1700485571);
    d = ii(d, a, b, c, k[3], 10, -1894986606);
    c = ii(c, d, a, b, k[10], 15, -1051523);
    b = ii(b, c, d, a, k[1], 21, -2054922799);
    a = ii(a, b, c, d, k[8], 6, 1873313359);
    d = ii(d, a, b, c, k[15], 10, -30611744);
    c = ii(c, d, a, b, k[6], 15, -1560198380);
    b = ii(b, c, d, a, k[13], 21, 1309151649);
    a = ii(a, b, c, d, k[4], 6, -145523070);
    d = ii(d, a, b, c, k[11], 10, -1120210379);
    c = ii(c, d, a, b, k[2], 15, 718787259);
    b = ii(b, c, d, a, k[9], 21, -343485551);

    x[0] = add32(a, x[0]);
    x[1] = add32(b, x[1]);
    x[2] = add32(c, x[2]);
    x[3] = add32(d, x[3]);
  }

  function cmn(q, a, b, x, s, t) {
    a = add32(add32(a, q), add32(x, t));
    return add32((a << s) | (a >>> (32 - s)), b);
  }

  function ff(a, b, c, d, x, s, t) {
    return cmn((b & c) | ((~b) & d), a, b, x, s, t);
  }

  function gg(a, b, c, d, x, s, t) {
    return cmn((b & d) | (c & (~d)), a, b, x, s, t);
  }

  function hh(a, b, c, d, x, s, t) {
    return cmn(b ^ c ^ d, a, b, x, s, t);
  }

  function ii(a, b, c, d, x, s, t) {
    return cmn(c ^ (b | (~d)), a, b, x, s, t);
  }

  function md51(s) {
    // Converts the string to UTF-8 "bytes" when necessary
    if (/[\x80-\xFF]/.test(s)) {
      s = unescape(encodeURI(s));
    }
    txt = '';
    var n = s.length, state = [1732584193, -271733879, -1732584194, 271733878], i;
    for (i = 64; i <= s.length; i += 64) {
      md5cycle(state, md5blk(s.substring(i - 64, i)));
    }
    s = s.substring(i - 64);
    var tail = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    for (i = 0; i < s.length; i++)
    tail[i >> 2] |= s.charCodeAt(i) << ((i % 4) << 3);
    tail[i >> 2] |= 0x80 << ((i % 4) << 3);
    if (i > 55) {
      md5cycle(state, tail);
      for (i = 0; i < 16; i++) tail[i] = 0;
    }
    tail[14] = n * 8;
    md5cycle(state, tail);
    return state;
  }

  function md5blk(s) { /* I figured global was faster.   */
    var md5blks = [], i; /* Andy King said do it this way. */
    for (i = 0; i < 64; i += 4) {
      md5blks[i >> 2] = s.charCodeAt(i) +
                        (s.charCodeAt(i + 1) << 8) +
                        (s.charCodeAt(i + 2) << 16) +
                        (s.charCodeAt(i + 3) << 24);
    }
    return md5blks;
  }

  var hex_chr = '0123456789abcdef'.split('');

  function rhex(n) {
    var s = '', j = 0;
    for (; j < 4; j++)
    s += hex_chr[(n >> (j * 8 + 4)) & 0x0F] +
         hex_chr[(n >> (j * 8)) & 0x0F];
    return s;
  }

  function hex(x) {
    for (var i = 0; i < x.length; i++)
    x[i] = rhex(x[i]);
    return x.join('');
  }

  md5 = function (s) {
    return hex(md51(s));
  }

  /* this function is much faster, so if possible we use it. Some IEs are the
  only ones I know of that need the idiotic second function, generated by an
  if clause.  */
  function add32(a, b) {
    return (a + b) & 0xFFFFFFFF;
  }

  if (md5('hello') != '5d41402abc4b2a76b9719d911017c592') {
    function add32(x, y) {
      var lsw = (x & 0xFFFF) + (y & 0xFFFF),
          msw = (x >> 16) + (y >> 16) + (lsw >> 16);
      return (msw << 16) | (lsw & 0xFFFF);
    }
  }
})();</script>
    </head>
    <body onload=" if (UpdatePage()) { Toggle('add_user', 'block'); document.getElementById('newuser').focus(); }">
        <div class="app">

            <div class="hero">
                <h2 class="hero-title">multi<i>OTP</i> web administration console</h2>
                <div class="hero-sub">the open source strong authentication library</div>
                <div class="meta-row">
                    <span class="badge">$class_name $class_version</span>
                    <span class="badge">$class_date</span>
                    <span class="badge dot">Ready $actual_date</span>
                    <span class="badge">$server_software</span>
                </div>
                $rpi_info
            </div>

            <div class="info-card">
                This package is the result of a *bunch* of work. If you find this package useful, <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=B78FJAH6RBNZ2">[Donation]</a> are always welcome to support this project.
                <br />
                Please check <a target="_blank" href="https://www.multiotp.net/">https://www.multiotp.net/</a> and you will find the magic button ;-)
                <br />
                Visit <a target="_blank" href="http://forum.multiotp.net/">http://forum.multiotp.net/</a> for additional support.
                <hr />
                <i>\-_infoweb_-/</i>
            </div>

            <div id="login_section">
                <div class="panel login-card">
                    <div class="lock">&#128274;</div>
                    <h3 style="margin-bottom:14px;">Sign in</h3>
                    <div class="form-row">
                        <label for="user">Username</label>
                        <input type="text" name="user" id="user" length="20" value="" />
                        <span class="form-hint">$default_user_info</span>
                    </div>
                    <div class="form-row">
                        <label for="password">Password</label>
                        <input type="password" onfocus="this.value='';" name="password" id="password" length="20" value="" />
                        <span class="form-hint">$default_password_info</span>
                    </div>
                    <span id="login"><button type="button" onclick="Login();">Login</button></span>
                </div>
            </div>

            <div id="logout_section">
                <div class="topbar">
                    <div id="log_info"><span id="logged"></span></div>
                    <span id="logout"><button type="button" class="small" onclick="Logout();">Logout</button></span>
                </div>
            </div>

            <div id="authenticated_section">
                <iframe name="hidden_frame" id="hidden_frame" style="display:none;"></iframe>

                <div class="panel">
                    <div class="panel-head" id="change_password_title" onclick="Toggle('change_password');">
                        <div class="panel-icon icon-violet">&#128272;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="change_password_text">Change admin password</span>
                        </div>
                        <span class="chevron" id="change_password_toggle"></span>
                    </div>
                    <div id="change_password_section" class="panel-body" style="display:none;">
                        <div class="form-row">
                            <label for="newpassword">New admin password</label>
                            <input type="password" name="newpassword" id="newpassword" length="20" value="" />
                        </div>
                        <div class="form-row">
                            <label for="newpassword2">Retype new password</label>
                            <input type="password" name="newpassword2" id="newpassword2" length="20" value="" />
                        </div>
                        <div class="form-row">
                            <button type="button" onclick="ChangeAdminPassword();">Apply</button>
                        </div>
                    </div>
                </div>

                <div class="panel" id="ha_panel">
                    <div class="panel-head" id="ha_title" onclick="Toggle('ha');">
                        <div class="panel-icon icon-indigo">&#128256;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="ha_text">High availability (master / slave / cluster)</span>
                            <span class="panel-count info" id="ha_role_pill"></span>
                        </div>
                        <span class="chevron" id="ha_toggle"></span>
                    </div>
                    <div id="ha_section" class="panel-body" style="display:none;">
                        <div id="ha_message"></div>
                        <div class="form-row">
                            <label for="ha_role">Role of this server</label>
                            <select name="ha_role" id="ha_role">
                                <option value="off">Disabled</option>
                                <option value="master">Master</option>
                                <option value="slave">Slave</option>
                                <option value="cluster">Cluster (multi-master, AD-like)</option>
                            </select>
                            <span class="form-hint">(slave pulls from the master; cluster = EVERY console accepts changes and replicates to all peers, like AD domain controllers)</span>
                        </div>
                        <div class="form-row">
                            <label for="ha_master_url">Master console URL (slave only)</label>
                            <input type="text" name="ha_master_url" id="ha_master_url" length="255" value="" placeholder="http://master-ip:8112/multiotp.server.php" />
                            <span class="form-hint">(full URL of the master web console)</span>
                        </div>
                        <div class="form-row">
                            <label for="ha_secret">HA shared secret</label>
                            <input type="password" name="ha_secret" id="ha_secret" length="64" value="" autocomplete="new-password" />
                            <span class="form-hint">(same secret on master and slave - leave empty to keep the current one)</span>
                        </div>
                        <div class="form-row">
                            <label for="ha_interval">Synchronization interval in minutes (slave cron)</label>
                            <input type="number" name="ha_interval" id="ha_interval" min="1" max="1440" value="5" />
                            <span class="form-hint">(the scheduled task runs every minute and pulls ONLY when this interval is elapsed - with the instant push below, this interval is just a safety net)</span>
                        </div>
                        <div class="form-row">
                            <label for="ha_push">Instant push on data change (master)</label>
                            <input type="checkbox" name="ha_push" id="ha_push" value="1" checked="checked" />
                            <span class="form-hint">(when the users/tokens data change on the master, the slaves are notified at once and pull immediately)</span>
                        </div>
                        <div class="form-row">
                            <label for="ha_slave_urls">Peer console URLs (master slaves / cluster peers, comma-separated)</label>
                            <input type="text" name="ha_slave_urls" id="ha_slave_urls" length="1000" value="" placeholder="http://slave1:8112/multiotp.server.php,http://slave2:8112/multiotp.server.php" />
                            <span class="form-hint">(targets of the instant push; a watcher task on the master also covers the changes done outside the console)</span>
                        </div>
                        <div class="form-row">
                            <button type="button" onclick="HaSaveConfig();">Save</button>
                            <button type="button" onclick="HaTestPeer();">Test peer</button>
                            <button type="button" onclick="HaSyncNow();">Sync now</button>
                            <button type="button" onclick="HaPushTest();">Send test push</button>
                            <button type="button" onclick="HaProbe();">Probe URLs (diagnostic)</button>
                            <button type="button" onclick="HaApplySuggestions();">Apply suggested URLs</button>
                            <button type="button" onclick="HaInstallTask();">Create/update scheduled task</button>
                        </div>
                        <div id="ha_status" class="info"></div>
                        <pre id="ha_probe_result" style="white-space:pre-wrap;font-size:12px;"></pre>
                    </div>
                </div>
                <div class="panel" id="admins_panel">
                    <div class="panel-head" id="admins_title" onclick="Toggle('admins');">
                        <div class="panel-icon icon-indigo">&#128101;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="admins_text">Administrator accounts</span>
                            <span class="panel-count info" id="adminscounter"></span>
                        </div>
                        <span class="chevron" id="admins_toggle"></span>
                    </div>
                    <div id="admins_section" class="panel-body" style="display:none;">
                        <div id="admins_message"></div>
                        <div id="adminslist"></div>
                        <div id="modify_admin_area" style="display:none;">
                            <h3>Modify an administrator account</h3>
                            <div class="form-row">
                                <label for="modify_admin_user">Username</label>
                                <input type="text" name="modify_admin_user" id="modify_admin_user" length="64" value="" readonly />
                            </div>
                            <div class="form-row">
                                <label for="modify_admin_role">Role</label>
                                <select name="modify_admin_role" id="modify_admin_role">
                                    <option value="admin">Administrator</option>
                                    <option value="super_admin">Super administrator</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="modify_admin_pass">New password</label>
                                <input type="password" name="modify_admin_pass" id="modify_admin_pass" length="20" value="" autocomplete="new-password" />
                                <span class="form-hint">(leave empty to keep the current password)</span>
                            </div>
                            <div class="form-row">
                                <label for="modify_admin_pass2">Retype new password</label>
                                <input type="password" name="modify_admin_pass2" id="modify_admin_pass2" length="20" value="" autocomplete="new-password" />
                            </div>
                            <div class="form-row">
                                <button type="button" onclick="ModifyAdminNow();">Apply</button>
                            </div>
                        </div>
                        <div id="admins_add_area">
                            <h3>Add a new administrator account</h3>
                            <div class="form-row">
                                <label for="newadmin">Username</label>
                                <input type="text" name="newadmin" id="newadmin" length="64" value="" />
                            </div>
                            <div class="form-row">
                                <label for="newadmin_role">Role</label>
                                <select name="newadmin_role" id="newadmin_role">
                                    <option value="admin">Administrator</option>
                                    <option value="super_admin">Super administrator</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="newadmin_pass">Password</label>
                                <input type="password" name="newadmin_pass" id="newadmin_pass" length="20" value="" autocomplete="new-password" />
                                <span class="form-hint">(8 characters minimum, letters and digits)</span>
                            </div>
                            <div class="form-row">
                                <label for="newadmin_pass2">Retype password</label>
                                <input type="password" name="newadmin_pass2" id="newadmin_pass2" length="20" value="" autocomplete="new-password" />
                            </div>
                            <div class="form-row">
                                <button type="button" onclick="AddAdminNow();">Add</button>
                            </div>
                        </div>
                        <div id="own_password_note" class="info" style="display:none;">
                            The built-in admin account password is changed in the "Change admin password" section above.
                        </div>
                        <div id="own_password_area" style="display:none;">
                            <h3>Change my own password</h3>
                            <div class="form-row">
                                <label for="own_old_pass">Current password</label>
                                <input type="password" name="own_old_pass" id="own_old_pass" length="20" value="" autocomplete="current-password" />
                            </div>
                            <div class="form-row">
                                <label for="own_new_pass">New password</label>
                                <input type="password" name="own_new_pass" id="own_new_pass" length="20" value="" autocomplete="new-password" />
                                <span class="form-hint">(8 characters minimum, letters and digits)</span>
                            </div>
                            <div class="form-row">
                                <label for="own_new_pass2">Retype new password</label>
                                <input type="password" name="own_new_pass2" id="own_new_pass2" length="20" value="" autocomplete="new-password" />
                            </div>
                            <div class="form-row">
                                <button type="button" onclick="ChangeOwnAdminPasswordNow();">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel" id="ldap_admin_panel">
                    <div class="panel-head" id="ldap_admin_title" onclick="Toggle('ldap_admin');">
                        <div class="panel-icon icon-pink">&#128273;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="ldap_admin_text">LDAP administrator access</span>
                            <span class="panel-count" id="ldap_admin_badge" style="color:var(--pink);">disabled</span>
                        </div>
                        <span class="chevron" id="ldap_admin_toggle"></span>
                    </div>
                    <div id="ldap_admin_section" class="panel-body" style="display:none;">
                        <div id="ldap_admin_message"></div>
                        <div class="form-row" style="align-items:center;">
                            <input type="checkbox" name="ldap_admin_enabled" id="ldap_admin_enabled" value="1" style="width:auto;min-width:0;" />
                            <label for="ldap_admin_enabled" style="min-width:0;width:auto;font-weight:600;">Allow domain accounts (LDAP) to sign in to this console</label>
                        </div>
                        <div class="form-row">
                            <label for="ldap_admin_ou_select">Authorized organizational unit (OU)</label>
                            <select name="ldap_admin_ou_select" id="ldap_admin_ou_select" onchange="LdapAdminOuSelected();">
                                <option value="">(browse the directory)</option>
                            </select>
                            <span class="form-hint">Users of this OU and of its sub-OUs are authorized (role: administrator)</span>
                        </div>
                        <div class="form-row">
                            <label for="ldap_admin_ou_label">OU label (display)</label>
                            <input type="text" name="ldap_admin_ou_label" id="ldap_admin_ou_label" maxlength="255" value="" />
                            <span class="form-hint">Example: OU=test,DC=test,DC=local</span>
                        </div>
                        <div class="form-row">
                            <button type="button" id="ldap_admin_browse_btn" onclick="LdapAdminBrowseOusNow();">Browse OUs</button>
                            <button type="button" id="ldap_admin_apply_btn" onclick="LdapAdminApplyNow();">Apply</button>
                        </div>
                        <div id="ldap_admin_footer"></div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head" id="import_tokens_title" onclick="Toggle('import_tokens');">
                        <div class="panel-icon icon-amber">&#128229;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="import_tokens_text">Import new hardware tokens</span>
                        </div>
                        <span class="chevron" id="import_tokens_toggle"></span>
                    </div>
                    <div id="import_tokens_section" class="panel-body" style="display:none;">
                        <form target="hidden_frame" enctype="multipart/form-data" method="post" action="">
                            <div class="form-row">
                                <label for="token_file">Token definition file</label>
                                <input type="file" name="token_file" id="token_file" />
                                <span class="form-hint">OATH PSKC, Yubico, etc.</span>
                            </div>
                            <div class="form-row">
                                <label for="token_password">Password (if any)</label>
                                <input type="password" name="token_password" id="token_password" />
                            </div>
                            <div class="form-row">
                                <input type="submit" value="Import" onClick="Toggle('import_tokens', 'none'); setTimeout(function(){UpdateTokensList()},2500);" />
                            </div>
                        </form>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head" id="list_tokens_title" onclick="Toggle('list_tokens');">
                        <div class="panel-icon icon-sky">&#128273;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="list_tokens_text"><span id="tokenstitle">List of hardware tokens</span></span>
                            <span class="panel-count info" id="tokenscounter"></span>
                        </div>
                        <span class="chevron" id="list_tokens_toggle"></span>
                    </div>
                    <div id="list_tokens_section" class="panel-body" style="display:none;">
                        <div id="tokenslist"></div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head active" id="add_user_title" onclick="Toggle('add_user');">
                        <div class="panel-icon icon-emerald">&#10133;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="add_user_text">Add a new user</span>
                        </div>
                        <span class="chevron open" id="add_user_toggle"></span>
                    </div>
                    <div id="add_user_section" class="panel-body" style="display:block;">
                        <div class="form-row">
                            <label for="newuser">Username</label>
                            <input type="text" name="newuser" id="newuser" length="20" value="" />
                        </div>
                        <div class="form-row">
                            <label for="newemail">Email address</label>
                            <input type="text" name="newemail" id="newemail" length="30" value="" />
                        </div>
                        <div class="form-row">
                            <label for="newsms">Mobile phone (SMS)</label>
                            <input type="text" name="newsms" id="newsms" length="30" value="" />
                        </div>
                        <div class="form-row">
                            <label>With prefix PIN</label>
                            <span class="radio-choice"><input type="radio" name="prefix_required" id="prefix_required1" $prefix_required1_checked value="1" /> yes</span>
                            <span class="radio-choice"><input type="radio" name="prefix_required" id="prefix_required0" $prefix_required0_checked value="0" /> no</span>
                        </div>
                        <div class="form-row">
                            <label for="pin">Specific prefix PIN</label>
                            <input type="text" name="pin" id="pin" length="30" value="" />
                        </div>
                        <div class="form-row">
                            <label for="token_serial">Select a token</label>
                            <select name="token_serial" id="token_serial" onchange="if ('' != this.value) { document.getElementById('token_type').style.display = 'none' } else { document.getElementById('token_type').style.display = 'flex' };">
                                <option value="" selected="selected">software</option>
                            </select>
                        </div>
                        <div class="form-row" id="token_type" style="display:flex;">
                            <label for="algorithm">Token type</label>
                            <select name="algorithm" id="algorithm">
                                <option value="TOTP" selected="selected">TOTP</option>
                                <option value="HOTP">HOTP</option>
                                <option value="MOTP">MOTP</option>
                                <option value="without2FA">without2FA</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <button type="button" onclick="Add();">Add this user</button>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head" id="resync_title" onclick="Toggle('resync');">
                        <div class="panel-icon icon-teal">&#128260;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="resync_text">Resync a user</span>
                        </div>
                        <span class="chevron" id="resync_toggle"></span>
                    </div>
                    <div id="resync_section" class="panel-body" style="display:none;">
                        <div class="form-row">
                            <label for="resync_user">User to resync</label>
                            <input type="text" name="resync_user" id="resync_user" length="20" value="" />
                        </div>
                        <div class="form-row">
                            <label for="resync_otp1">First OTP</label>
                            <input type="text" name="resync_otp1" id="resync_otp1" length="20" value="" />
                        </div>
                        <div class="form-row">
                            <label for="resync_otp2">Second OTP</label>
                            <input type="text" name="resync_otp2" id="resync_otp2" length="20" value="" />
                        </div>
                        <div class="form-row">
                            <button type="button" onclick="ResyncUserNow(document.getElementById('resync_user').value, document.getElementById('resync_otp1').value, document.getElementById('resync_otp2').value);">Resync now</button>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head" id="check_user_title" onclick="Toggle('check_user');">
                        <div class="panel-icon icon-indigo">&#9989;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="check_user_text">Check a user</span>
                        </div>
                        <span class="chevron" id="check_user_toggle"></span>
                    </div>
                    <div id="check_user_section" class="panel-body" style="display:none;">
                        <div class="form-row">
                            <label for="check_user_user">User to check</label>
                            <input type="text" name="check_user_user" id="check_user_user" length="50" value="" />
                        </div>
                        <div class="form-row">
                            <label for="check_user_otp">OTP (with prefix if needed)</label>
                            <input type="text" name="check_user_otp" id="check_user_otp" length="50" value="" />
                        </div>
                        <div class="form-row">
                            <button type="button" onclick="CheckUserNow(document.getElementById('check_user_user').value, document.getElementById('check_user_otp').value);">Check now</button>
                        </div>
                        <div class="form-row">
                            <label>Test result</label>
                            <span id="check_user_result"></span>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head" id="backup_config_title" onclick="Toggle('backup_config');">
                        <div class="panel-icon icon-rose">&#128190;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="backup_config_text">Backup the configuration</span>
                        </div>
                        <span class="chevron" id="backup_config_toggle"></span>
                    </div>
                    <div id="backup_config_section" class="panel-body" style="display:none;">
                        <div class="form-row">
                            <label for="backup_config_password">Backup password</label>
                            <input type="password" name="backup_config_password" id="backup_config_password" length="50" value="" />
                            <span class="form-hint">cannot be empty</span>
                        </div>
                        <div class="form-row">
                            <button type="button" onclick="BackupConfigNow(document.getElementById('backup_config_password').value);">Backup configuration now</button>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head" id="restore_config_title" onclick="Toggle('restore_config');">
                        <div class="panel-icon icon-pink">&#9851;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="restore_config_text">Restore the configuration</span>
                        </div>
                        <span class="chevron" id="restore_config_toggle"></span>
                    </div>
                    <div id="restore_config_section" class="panel-body" style="display:none;">
                        <form target="_self" enctype="multipart/form-data" method="post" action="">
                            <div class="form-row">
                                <label for="config_file">Configuration file (*.cfg)</label>
                                <input type="file" name="config_file" id="config_file" />
                            </div>
                            <div class="form-row">
                                <label for="restore_config_password">Restore password</label>
                                <input type="password" name="restore_config_password" id="restore_config_password" />
                                <span class="form-hint">cannot be empty</span>
                            </div>
                            <div class="form-row">
                                <input type="submit" value="Restore configuration now" />
                            </div>
                        </form>
                        <iframe name="upload_frame" id="upload_frame" style="display:none;"></iframe>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head static" id="list_users_title">
                        <div class="panel-icon icon-indigo">&#128100;</div>
                        <div class="panel-title-wrap">
                            <span class="panel-title" id="list_users_text"><span id="userstitle">List of users</span></span>
                            <span class="panel-count info" id="userscounter"></span>
                        </div>
                        <div class="panel-search" id="users_search_wrap" onclick="event.stopPropagation();">
                            <span class="users-search-icon"></span>
                            <input type="text" id="users_search" autocomplete="off" spellcheck="false" placeholder="Search a user..." oninput="FilterUsersList();" onkeydown="if (('Escape' == event.key) || ('Esc' == event.key)) { this.value = ''; FilterUsersList(); }" />
                            <span class="users-search-clear" title="Clear the search" onclick="document.getElementById('users_search').value=''; FilterUsersList(); document.getElementById('users_search').focus(); event.stopPropagation();">&times;</span>
                        </div>
                        <span id="list_users_toggle"></span>
                    </div>
                    <div id="list_users_section" class="panel-body" style="display:block;">
                        <div id="userslist"></div>
                        <div id="users_search_nomatch" class="list-empty" style="display:none;">No user matches the search...</div>
                    </div>
                </div>

            </div>
        </div>
    </body>
</html>
EOWEBPAGE;
        $infoweb = "";
        $infoweb_filename = "infoweb.html";
        if (file_exists($multiotp->GetConfigFolder().$infoweb_filename)) {
            if ($infoweb_handler = @fopen($multiotp->GetConfigFolder().$infoweb_filename, "rt")) {
                $infoweb = nullable_trim(fgets($infoweb_handler));
                fclose($infoweb_handler);
            }
        }
        if (nullable_trim($infoweb == "")) {
            $infoweb = <<<EOI
<i>
    Are you interested in additional features like automatic syncronization of AD/LDAP users, provisioning PDF automatic email distribution, API automation, HA in master-slave mode and many others, everything through an easy and fast web interface ?
    <br />
    Check out our commercial editions here: <a target="_blank" href="https://www.multiOTP.com/">https://www.multiOTP.com/</a>
</i>
EOI;
        }
        $webpage = str_replace('\-_infoweb_-/', $infoweb, $webpage);
        echo $webpage;
    } else {
        /*********************
         * Basic Ajax server *
         *********************/
        $method = preg_replace('/[^(x20-\x7F)]*/','', $method);

        // This filtering is not working for full UTF-8 support
        // $options = preg_replace('/[^(\x09\x20-\x7E\xA0-\xFF)]*/','', $options);


        $options = preg_replace('/[^(\x09\x20-\xFF)]*/','', $options);
        $options_array = explode("\t",$options);
    
        // Set the default password to 1234 if no password is set
        if ('' == $multiotp->GetConfigAttribute('admin_password_hash'))
        {
            $multiotp->SetAdminPassword('1234');
            $multiotp->WriteConfigData();
        }
    
        if (!isset($_SESSION['logged']))
        {
            $_SESSION['logged'] = FALSE;
        }

        if (!isset($_SESSION['random_salt']))
        {
            $random_salt = mb_substr(md5(time()."@".rand(100000,999999)),0,12);
            $_SESSION['random_salt'] = $random_salt;
        }
        $multiotp->SetRandomSalt($_SESSION['random_salt']);
        
        $ajax_result = "false";

        switch (mb_strtoupper($method,'UTF-8'))
        {
            case mb_strtoupper("GetRandomSalt",'UTF-8'):
                $ajax_result = $multiotp->GetRandomSalt();
                break;
case mb_strtoupper("Login",'UTF-8'):
                $result = FALSE;
                $username = mb_substr(isset($options_array[0])?$options_array[0]:'',0,255);
                $password = mb_substr(isset($options_array[1])?$options_array[1]:'',0,255);
                $admin_role = '';
                $admin_user = '';
                if ('admin' == $username) {
                    $result = $multiotp->CheckAdminPasswordHashWithRandomSalt($password);
                    if ($result) {
                        $admin_user = 'admin';
                        $admin_role = 'super_admin';
                    }
                } else {
                    /* Multi-administrator accounts extension: for the additional
                       accounts, the client sends md5(hash_salt + password + hash_salt) */
                    $admin_account = MultiotpAdminAccountsAuthenticate($multiotp, $username, $password);
                    if (is_array($admin_account)) {
                        $result = TRUE;
                        $admin_user = $admin_account['username'];
                        $admin_role = $admin_account['role'];
                    }
                    /* LDAP administrator access extension: domain accounts may
                       sign in when the feature is enabled. The third option
                       carries the clear password; it is used ONLY for the LDAP
                       bind and ONLY when the local authentication failed. */
                    if ((!$result) && isset($options_array[2]) && ('' != nullable_trim($options_array[2]))) {
                        $ldap_err = '';
                        $ldap_user = MultiotpLdapAdminTryLogin($multiotp, $username, $options_array[2], $ldap_err);
                        if (is_array($ldap_user)) {
                            $result = TRUE;
                            $admin_user = $ldap_user['username'];
                            $admin_role = 'admin';
                        }
                    }
                }

                if ($result) {
                    $_SESSION['logged'] = TRUE;
                    $_SESSION['multiotp_admin_user'] = $admin_user;
                    $_SESSION['multiotp_admin_role'] = $admin_role;
                    /* And we change the random_salt to avoid a second login with the same previous hash */
                    $random_salt = mb_substr(md5(time()."@".rand(100000,999999)),0,12);
                    $_SESSION['random_salt'] = $random_salt;
                    $multiotp->SetRandomSalt($random_salt);
                    $ajax_result = "true";
                } else {
                    $_SESSION = array();
                    $_SESSION['logged'] = FALSE;
                    $ajax_result = "false";
                }
                break;
                        /*------------------------------------------------------------
                         * High availability extension: peer/scheduler methods,
                         * callable WITHOUT a console session (token-checked).
                         * Options may come from the options parameter (tab
                         * separated) OR from ha_ts / ha_token GET parameters
                         * (the peer builds the URL itself); empty options
                         * must NOT shadow the GET parameters.
                         *------------------------------------------------------------*/
                        case mb_strtoupper("HaPing",'UTF-8'):
                        case mb_strtoupper("HaGetBackup",'UTF-8'):
                            $ha_opt_ts    = nullable_trim(isset($options_array[0]) ? $options_array[0] : '');
                            $ha_opt_token = nullable_trim(isset($options_array[1]) ? $options_array[1] : '');
                            $ha_ts    = intval(('' != $ha_opt_ts) ? $ha_opt_ts : (isset($_GET['ha_ts']) ? $_GET['ha_ts'] : 0));
                            $ha_token = ('' != $ha_opt_token) ? $ha_opt_token : (isset($_GET['ha_token']) ? $_GET['ha_token'] : '');
                            $ha_token2 = (isset($options_array[2]) && ('' != nullable_trim($options_array[2])))
                                        ? nullable_trim($options_array[2])
                                        : (isset($_GET['ha_token2']) ? $_GET['ha_token2'] : ''); // v3.10
                            $ha_mode  = ('HAPING' == mb_strtoupper($method, 'UTF-8')) ? 'ping' : 'backup';
                            $ajax_result = MultiotpHaServePeer($multiotp, $ha_ts, $ha_token, $ha_mode, $ha_token2);
                            break;
                        case mb_strtoupper("HaCronPull",'UTF-8'):
                            $ha_opt_ts    = nullable_trim(isset($options_array[0]) ? $options_array[0] : '');
                            $ha_opt_token = nullable_trim(isset($options_array[1]) ? $options_array[1] : '');
                            $ha_ts    = intval(('' != $ha_opt_ts) ? $ha_opt_ts : (isset($_GET['ha_ts']) ? $_GET['ha_ts'] : 0));
                            $ha_token = ('' != $ha_opt_token) ? $ha_opt_token : (isset($_GET['ha_token']) ? $_GET['ha_token'] : '');
                            $ha_token2 = (isset($options_array[2]) && ('' != nullable_trim($options_array[2])))
                                        ? nullable_trim($options_array[2])
                                        : (isset($_GET['ha_token2']) ? $_GET['ha_token2'] : ''); // v3.10
                            $ajax_result = MultiotpHaServePull($multiotp, $ha_ts, $ha_token, $ha_token2);
                            break;
                        case mb_strtoupper("HaGetRevision",'UTF-8'):
                            $ha_opt_ts    = nullable_trim(isset($options_array[0]) ? $options_array[0] : '');
                            $ha_opt_token = nullable_trim(isset($options_array[1]) ? $options_array[1] : '');
                            $ha_ts    = intval(('' != $ha_opt_ts) ? $ha_opt_ts : (isset($_GET['ha_ts']) ? $_GET['ha_ts'] : 0));
                            $ha_token = ('' != $ha_opt_token) ? $ha_opt_token : (isset($_GET['ha_token']) ? $_GET['ha_token'] : '');
                            $ha_token2 = (isset($options_array[2]) && ('' != nullable_trim($options_array[2])))
                                        ? nullable_trim($options_array[2])
                                        : (isset($_GET['ha_token2']) ? $_GET['ha_token2'] : ''); // v3.10
                            $ajax_result = MultiotpHaServeRevision($multiotp, $ha_ts, $ha_token, $ha_token2);
                            break;
                        case mb_strtoupper("HaNotifyPull",'UTF-8'):
                            $ha_opt_ts    = nullable_trim(isset($options_array[0]) ? $options_array[0] : '');
                            $ha_opt_token = nullable_trim(isset($options_array[1]) ? $options_array[1] : '');
                            $ha_ts    = intval(('' != $ha_opt_ts) ? $ha_opt_ts : (isset($_GET['ha_ts']) ? $_GET['ha_ts'] : 0));
                            $ha_token = ('' != $ha_opt_token) ? $ha_opt_token : (isset($_GET['ha_token']) ? $_GET['ha_token'] : '');
                            $ha_token2 = (isset($options_array[2]) && ('' != nullable_trim($options_array[2])))
                                        ? nullable_trim($options_array[2])
                                        : (isset($_GET['ha_token2']) ? $_GET['ha_token2'] : ''); // v3.10
                            $ha_from = (isset($_GET['ha_from']) ? nullable_trim($_GET['ha_from']) : ''); // v4.0
                            $ajax_result = MultiotpHaServeNotify($multiotp, $ha_ts, $ha_token, $ha_token2, $ha_from);
                            break;
                        case mb_strtoupper("HaSlaveStatus",'UTF-8'):
                            $ha_opt_ts    = nullable_trim(isset($options_array[0]) ? $options_array[0] : '');
                            $ha_opt_token = nullable_trim(isset($options_array[1]) ? $options_array[1] : '');
                            $ha_ts    = intval(('' != $ha_opt_ts) ? $ha_opt_ts : (isset($_GET['ha_ts']) ? $_GET['ha_ts'] : 0));
                            $ha_token = ('' != $ha_opt_token) ? $ha_opt_token : (isset($_GET['ha_token']) ? $_GET['ha_token'] : '');
                            $ha_token2 = (isset($options_array[2]) && ('' != nullable_trim($options_array[2])))
                                        ? nullable_trim($options_array[2])
                                        : (isset($_GET['ha_token2']) ? $_GET['ha_token2'] : ''); // v3.10
                            $ajax_result = MultiotpHaServeSlaveStatus($multiotp, $ha_ts, $ha_token, $ha_token2);
                            break;
                        case mb_strtoupper("HaRegisterSlave",'UTF-8'):
                            $ha_opt_ts    = nullable_trim(isset($options_array[0]) ? $options_array[0] : '');
                            $ha_opt_token = nullable_trim(isset($options_array[1]) ? $options_array[1] : '');
                            $ha_opt_url   = nullable_trim(isset($options_array[2]) ? $options_array[2] : '');
                            $ha_ts    = intval(('' != $ha_opt_ts) ? $ha_opt_ts : (isset($_GET['ha_ts']) ? $_GET['ha_ts'] : 0));
                            $ha_token = ('' != $ha_opt_token) ? $ha_opt_token : (isset($_GET['ha_token']) ? $_GET['ha_token'] : '');
                            $ha_url   = ('' != $ha_opt_url) ? $ha_opt_url : (isset($_GET['ha_url']) ? $_GET['ha_url'] : '');
                            $ha_token2 = (isset($options_array[3]) && ('' != nullable_trim($options_array[3])))
                                        ? nullable_trim($options_array[3])
                                        : (isset($_GET['ha_token2']) ? $_GET['ha_token2'] : ''); // v3.10
                            $ajax_result = MultiotpHaServeRegister($multiotp, $ha_ts, $ha_token, $ha_url, $ha_token2);
                            break;
                        /*----------------- end of HA peer methods ----------------*/
            default:
                if ((isset($_SESSION['logged']) && $_SESSION['logged']))
                {
                    /*******************************************************
                     * The next methods are allowed only if we are logged in
                     *******************************************************/
                    switch (mb_strtoupper($method,'UTF-8'))
                    {
                        case mb_strtoupper("DeleteUser",'UTF-8'):
                            $ajax_result = $multiotp->DeleteUser($options_array[0]);
                            break;
                        case mb_strtoupper("DeleteToken",'UTF-8'):
                            $ajax_result = $multiotp->DeleteToken($options_array[0]);
                            break;
                        case mb_strtoupper("FastCreateUser",'UTF-8'):
                            $user              = nullable_trim((isset($options_array[0])?$options_array[0]:''));
                            $email             = nullable_trim((isset($options_array[1])?$options_array[1]:''));
                            $sms               = nullable_trim((isset($options_array[2])?$options_array[2]:''));
                            $prefix_pin_needed = intval(isset($options_array[3])?$options_array[3]:$multiotp->GetDefaultRequestPrefixPin());
                            $algorithm         = (isset($options_array[4])?$options_array[4]:"totp");
                            $pin               = (isset($options_array[5])?$options_array[5]:'');
                            $token_serial      = nullable_trim((isset($options_array[6])?$options_array[6]:''));
                            if ('' != $token_serial) {
                                $ajax_result = $multiotp->CreateUserFromToken($user, $token_serial, $email, $sms, $pin, $prefix_pin_needed);
                            }
                            else {
                                $ajax_result = $multiotp->FastCreateUser($user, $email, $sms, $prefix_pin_needed, $algorithm, 1, '', '*DEFAULT*', 0, $pin);
                            }
                            if (isset($options_array[3])) {
                                $multiotp->SetDefaultRequestPrefixPin(intval($options_array[3]));
                                $multiotp->WriteConfigData();
                            }
                            break;
                        case mb_strtoupper("GetFullVersionInfo",'UTF-8'):
                            $ajax_result = $multiotp->GetFullVersionInfo();
                            break;
                        case mb_strtoupper("GetTokensList",'UTF-8'):
                            $ajax_result = $multiotp->GetTokensList();
                            break;
                        case mb_strtoupper("GetUsersList",'UTF-8'):
                            $ajax_result = $multiotp->GetUsersList();
                            break;
                        case mb_strtoupper("GetDelayedUsersList",'UTF-8'):
                            $ajax_result = $multiotp->GetDelayedUsersList();
                            break;
                        case mb_strtoupper("GetLockedUsersList",'UTF-8'):
                            $ajax_result = $multiotp->GetLockedUsersList();
                            break;
                        case mb_strtoupper("GetEnhancedUsersList",'UTF-8'):
                            $ajax_result = $multiotp->GetEnhancedUsersList();
                            break;
                        case mb_strtoupper("PrintQrCode",'UTF-8'):
                            echo $multiotp->GenerateHtmlQrCode($options_array[0]);
                            $ajax_result = '';
                            break;
                        case mb_strtoupper("PrintScratchlist",'UTF-8'):
                            echo $multiotp->GenerateHtmlScratchlist($options_array[0]);
                            $ajax_result = '';
                            break;
                        case mb_strtoupper("ResyncUser",'UTF-8'):
                            $ajax_result = "false";
                            if ($multiotp->ReadUserData($options_array[0])) {
                                $result = $multiotp->CheckToken($options_array[1], $options_array[2]);
                                if (14 == $result) { // 14 Token has been resynchronized successfully
                                    $ajax_result = "true";
                                }
                            }
                            break;
                        case mb_strtoupper("CheckToken",'UTF-8'):
                            $ajax_result = '21 '.$multiotp->GetErrorText(21);
                            if ($multiotp->ReadUserData($options_array[0]))
                            {
                                $result = $multiotp->CheckToken($options_array[1]);
                                if (0 == $result)
                                {
                                    $ajax_result = "true";
                                }
                                else
                                {
                                    $ajax_result = $result.' '.$multiotp->GetErrorText($result);
                                }
                            }
                            break;
                        case mb_strtoupper("BackupConfig",'UTF-8'):
                            $tmp  = '/tmp';
                            if (!file_exists($tmp)) {
                              $tmp = $multiotp->ConvertToWindowsPathIfNeeded(sys_get_temp_dir());
                            }
                            $backup_file_name = "multiotp-".date('Y-m-d-His').".cfg";
                            $backup_config_file = "$tmp/$backup_file_name";
                            if (file_exists($backup_config_file)) {
                                unlink($backup_config_file);
                            }

                            $result = $multiotp->BackupConfiguration(array("backup_file"       => $backup_config_file,
                                                                           "encryption_key"    => $options_array[0],
                                                                           "flush_attributes"  => array("admin_password_hash"),
                                                                           "return_content"    => FALSE));
                            if ($result) {
                                // $size = filesize($backup_config_file);
                                header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
                                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                                header("Cache-Control: no-store, no-cache, must-revalidate");
                                header("Cache-Control: post-check=0, pre-check=0", false);
                                header("Pragma: no-cache");
                                header("Content-Type: application/force-download");
                                header("Content-Type: application/octet-stream");
                                header("Content-Type: application/download\n"); 
                                header("Content-Disposition: attachment; filename=\"".basename($backup_config_file)."\""); 
                                header("Content-Transfer-Encoding: binary");
                                // header("Content-Length: $size");
                                $fn=fopen($backup_config_file, "rb");
                                while(!feof($fn)) {
                                    @set_time_limit(0);
                                    echo fread($fn, 1024*8);
                                    flush();
                                }
                                fclose($fn);
                                $ajax_result = '';
                            } else {
                                echo "*** BACKUP ERROR ***";
                            }
                            break;
                        case mb_strtoupper("SetAdminPasswordHash",'UTF-8'):
                            if ($multiotp->IsDemoMode())
                            {
                                $result = "false";
                            }
                            else
                            {
                                $ajax_result = $multiotp->SetAdminPasswordHash($options_array[0]);
                                $multiotp->WriteConfigData();
                            }
                            break;
                        case mb_strtoupper("UnlockUser",'UTF-8'):
                            $ajax_result = $multiotp->UnlockUser($options_array[0]);
                            break;
                        case mb_strtoupper("UserLoggedIn",'UTF-8'):
                            $ajax_result = "true"; //User is logged if code arrives here!
                            break;
                        /*------------------------------------------------------------
                         * Multi-administrator accounts extension: methods below
                         *------------------------------------------------------------*/
                        case mb_strtoupper("AdminWhoAmI",'UTF-8'):
                            $ajax_result = MultiotpAdminAccountsCurrentUser()."\t".MultiotpAdminAccountsCurrentRole();
                            break;
                        case mb_strtoupper("ListAdmins",'UTF-8'):
                            if ('super_admin' == MultiotpAdminAccountsCurrentRole()) {
                                $admin_list = json_decode(MultiotpAdminAccountsListForConsole($multiotp), true);
                                $ajax_result = is_array($admin_list) ? $admin_list : array();
                            } else {
                                $ajax_result = "notAuthorized";
                            }
                            break;
                        case mb_strtoupper("AddAdminAccount",'UTF-8'):
                            if ('super_admin' == MultiotpAdminAccountsCurrentRole()) {
                                $op_result = MultiotpAdminAccountsCreate($multiotp,
                                                                         (isset($options_array[0])?$options_array[0]:''),
                                                                         (isset($options_array[1])?nullable_trim($options_array[1]):''),
                                                                         (isset($options_array[2])?$options_array[2]:'admin'),
                                                                         MultiotpAdminAccountsCurrentUser());
                                $ajax_result = (true === $op_result) ? "true" : ((false === $op_result) ? "false" : $op_result);
                            } else {
                                $ajax_result = "notAuthorized";
                            }
                            break;
                        case mb_strtoupper("ModifyAdminAccount",'UTF-8'):
                            if ('super_admin' == MultiotpAdminAccountsCurrentRole()) {
                                $op_result = MultiotpAdminAccountsModify($multiotp,
                                                                         (isset($options_array[0])?$options_array[0]:''),
                                                                         (isset($options_array[1])?nullable_trim($options_array[1]):''),
                                                                         (isset($options_array[2])?$options_array[2]:'admin'),
                                                                         MultiotpAdminAccountsCurrentUser());
                                $ajax_result = (true === $op_result) ? "true" : ((false === $op_result) ? "false" : $op_result);
                            } else {
                                $ajax_result = "notAuthorized";
                            }
                            break;
                        case mb_strtoupper("EnableAdminAccount",'UTF-8'):
                            if ('super_admin' == MultiotpAdminAccountsCurrentRole()) {
                                $op_result = MultiotpAdminAccountsSetEnabled($multiotp,
                                                                             (isset($options_array[0])?$options_array[0]:''),
                                                                             true,
                                                                             MultiotpAdminAccountsCurrentUser());
                                $ajax_result = (true === $op_result) ? "true" : ((false === $op_result) ? "false" : $op_result);
                            } else {
                                $ajax_result = "notAuthorized";
                            }
                            break;
                        case mb_strtoupper("DisableAdminAccount",'UTF-8'):
                            if ('super_admin' == MultiotpAdminAccountsCurrentRole()) {
                                $op_result = MultiotpAdminAccountsSetEnabled($multiotp,
                                                                             (isset($options_array[0])?$options_array[0]:''),
                                                                             false,
                                                                             MultiotpAdminAccountsCurrentUser());
                                $ajax_result = (true === $op_result) ? "true" : ((false === $op_result) ? "false" : $op_result);
                            } else {
                                $ajax_result = "notAuthorized";
                            }
                            break;
                        case mb_strtoupper("UnlockAdminAccount",'UTF-8'):
                            if ('super_admin' == MultiotpAdminAccountsCurrentRole()) {
                                $op_result = MultiotpAdminAccountsUnlock($multiotp,
                                                                         (isset($options_array[0])?$options_array[0]:''),
                                                                         MultiotpAdminAccountsCurrentUser());
                                $ajax_result = (true === $op_result) ? "true" : ((false === $op_result) ? "false" : $op_result);
                            } else {
                                $ajax_result = "notAuthorized";
                            }
                            break;
                        case mb_strtoupper("DeleteAdminAccount",'UTF-8'):
                            if ('super_admin' == MultiotpAdminAccountsCurrentRole()) {
                                $op_result = MultiotpAdminAccountsDelete($multiotp,
                                                                         (isset($options_array[0])?$options_array[0]:''),
                                                                         MultiotpAdminAccountsCurrentUser());
                                $ajax_result = (true === $op_result) ? "true" : ((false === $op_result) ? "false" : $op_result);
                            } else {
                                $ajax_result = "notAuthorized";
                            }
                            break;
                        case mb_strtoupper("ChangeOwnAdminPassword",'UTF-8'):
                            $op_result = MultiotpAdminAccountsChangeOwnPassword($multiotp,
                                                                                (isset($options_array[0])?nullable_trim($options_array[0]):''),
                                                                                (isset($options_array[1])?nullable_trim($options_array[1]):''),
                                                                                MultiotpAdminAccountsCurrentUser());
                            $ajax_result = (true === $op_result) ? "true" : ((false === $op_result) ? "false" : $op_result);
                            break;
                        /*------------------------------------------------------------
                         * High availability extension: admin methods (session).
                         * The dispatcher json_encode()s $ajax_result, so we
                         * return the raw ARRAY here (single encoding).
                         *------------------------------------------------------------*/
                        case mb_strtoupper("HaSetConfig",'UTF-8'):
                            $ajax_result = MultiotpHaSetConfigFromOptions($multiotp, $options_array);
                            break;
                        case mb_strtoupper("HaTestPeer",'UTF-8'):
                            $ajax_result = MultiotpHaTestPeer($multiotp);
                            break;
                        case mb_strtoupper("HaSyncNow",'UTF-8'):
                            $ha_result = MultiotpHaDoPull($multiotp, true);
                            MultiotpHaLog($multiotp, 'HA manual pull requested: '.($ha_result['ok'] ? 'done' : 'failed ('.(isset($ha_result['error']) ? $ha_result['error'] : '?').')'));
                            $ajax_result = $ha_result;
                            break;
                        case mb_strtoupper("HaGetStatus",'UTF-8'):
                            $ajax_result = MultiotpHaStatusArray($multiotp);
                            break;
                        case mb_strtoupper("HaInstallTask",'UTF-8'):
                            $ajax_result = MultiotpHaInstallTask($multiotp);
                            break;
                        case mb_strtoupper("HaPushTest",'UTF-8'):
                            $ajax_result = MultiotpHaMasterNotify($multiotp, 'manual test from console', true);
                            break;
                        case mb_strtoupper("HaProbeUrl",'UTF-8'):
                            $ajax_result = MultiotpHaProbeUrl($multiotp,
                                                              isset($options_array[0]) ? $options_array[0] : '');
                            break;
                        /*------------------------------------------------------------
                         * LDAP administrator access extension: methods (session).
                         * Status = raw ARRAY (single json_encode by the dispatcher).
                         *------------------------------------------------------------*/
                        case mb_strtoupper("LdapAdminStatus",'UTF-8'):
                            $ajax_result = MultiotpLdapAdminStatusArray($multiotp);
                            break;
                        case mb_strtoupper("LdapAdminApply",'UTF-8'):
                            if ('super_admin' == MultiotpAdminAccountsCurrentRole()) {
                                $ajax_result = MultiotpLdapAdminApplyFromOptions($multiotp,
                                                                                 $options_array,
                                                                                 MultiotpAdminAccountsCurrentUser());
                            } else {
                                $ajax_result = "notAuthorized";
                            }
                            break;
                        case mb_strtoupper("LdapAdminBrowseOus",'UTF-8'):
                            if ('super_admin' == MultiotpAdminAccountsCurrentRole()) {
                                $ajax_result = MultiotpLdapAdminBrowseOusForConsole($multiotp);
                            } else {
                                $ajax_result = "notAuthorized";
                            }
                            break;
                        /*----------------- end of HA admin methods ----------------*/
                        /*----------------- end of multi-admin extension ----------------*/

                        default:
                            $ajax_result = "false";
                            break;
                    }
                }
                else
                {
                    $ajax_result = "false";
                }
                break;
        }
        if ('' != $ajax_result)
        {
            echo json_encode($ajax_result);
        }
        @ob_flush();
        flush(); 
    }
}

