<?php
/**
 * arsagen.net framework
 * Copyright Alexander René Sagen, No Rights Reserved.
 *
 * Website: http://arsagen.net
 * License: http://www.gnu.org/copyleft/gpl.html
 */

define('ROOT_FOLDER', dirname(dirname(__FILE__)).'/');
define("INSTALL_ROOT", dirname(__FILE__).'/');

date_default_timezone_set('UTC');

require_once ROOT_FOLDER.'inc/functions.php';

require_once ROOT_FOLDER.'inc/db_mysqli.php';

$db = new db_mysqli;

$var['lock'] = file_exists('lock');

$var['step'] = (isset($_POST['step']) && is_numeric($_POST['step']) ? $_POST['step'] : 0);

$var['skip'] = false;
$var['error'] = false;

if(!extension_loaded('mysqli'))
{
  $var['error'] = true;
  $messages['error'][] = 'MySQLi is not loaded. Please run "sudo apt-get install php5-mysql" to fix.';
}

if(!extension_loaded('json'))
{
  $var['error'] = true;
  $messages['error'][] = 'JSON is not loaded. Please run "sudo apt-get install php5-json" to fix.';
}

if(isset($_GET['action']) && $_GET['action'] == 'install')
{
  if(isset($_POST['next'])) $var['step']++;
  if(isset($_POST['back'])) $var['step'] = $var['step']-1;

  if($var['step'] == 1 && !isset($_POST['submit']))
  {
    if(file_exists(ROOT_FOLDER.'inc/config.php'))
    {
      include_once ROOT_FOLDER.'inc/config.php';

      if(!$db->connect($config['database']))
      {
        $messages['warning'][] = 'Config file exists, but is invalid. Please re-enter database information.';
      }
      else
      {
        $messages['info'][] = 'A valid config file already exists, you can skip this step.';
        $var['skip'] = true;
      }
    }
  }
  elseif($var['step'] == 1 && isset($_POST['submit']))
  {
    $db_port = (isset($_POST['db_port']) && !empty($_POST['db_port']) ? ':'.$_POST['db_port'] : '');

    $db_config = array(
      'hostname' => $_POST['db_hostname'].$db_port, 
      'username' => $_POST['db_username'], 
      'password' => $_POST['db_password'], 
      'database' => $_POST['db_database']
    );

    if(!$db->connect($db_config))
    {
      $var['step'] = 1;
      $messages['error'][] = 'Unable to connect to database.';
    }
    else
    {
      $messages['success'][] = 'Connected to database successfully.';

      $config_handle = fopen(ROOT_FOLDER.'inc/config.php.tmp', 'w');
      $config_write = fwrite($config_handle, '<?php
/**
* arsagen.net framework
* Copyright Alexander René Sagen, No Rights Reserved.
*
* Website: http://arsagen.net
* License: http://www.gnu.org/copyleft/gpl.html
*/

// MySQL database configuration
$config["database"]["hostname"] = "'.$db_config['hostname'].'";
$config["database"]["username"] = "'.$db_config['username'].'";
$config["database"]["password"] = "'.$db_config['password'].'";
$config["database"]["database"] = "'.$db_config['database'].'";

?>');
      if(!$config_write)
      {
        $var['step'] = 1;
        $messages['error'][] = 'Unable to write to config file. Give your webserver write access to the "inc" directory and try again.';
      }
      else
      {
        fclose($config_handle);
        rename(ROOT_FOLDER.'inc/config.php.tmp', ROOT_FOLDER.'inc/config.php');
        $var['step'] = 2;
      }
    }
  }
  elseif($var['step'] == 3)
  {
    require_once INSTALL_ROOT.'res/mysql_tables.php';
    require_once ROOT_FOLDER.'inc/config.php';

    if(!$db->connect($config['database']))
    {
      $messages['error'][] = 'Unable to connect to database';
    }

    foreach($tables as $table)
    {
      if(!$db->query($table['sql']))
      {
        $messages['error'][] = "Unable to create table &#96;{$table['name']}&#96;.";
      }
      else
      {
        $messages['success'][] = "Table &#96;{$table['name']}&#96; created successfully.";
      }
    }
  }
  elseif($var['step'] == 5)
  {
    require_once INSTALL_ROOT.'res/mysql_inserts.php';
    require_once ROOT_FOLDER.'inc/config.php';

    if(!$db->connect($config['database']))
    {
      $messages['error'][] = 'Unable to connect to database.';
    }

    foreach($inserts as $insert)
    {
      if(!$db->query($insert['sql']))
      {
        $messages['error'][] = "Unable to insert into table &#96;{$insert['name']}&#96;.";
      }
      else
      {
        $messages['success'][] = "Data inserted into table &#96;{$insert['name']}&#96; successfully.";
      }
    }
  }
  elseif($var['step'] == 6 && !isset($_POST['submit']))
  {
    require_once ROOT_FOLDER.'inc/config.php';
    if(!$db->connect($config['database']))
    {
      $messages['error'][] = 'Cannot connect to database.';
    }

    if($db->simple_select("website_users", "*", "id = '1' AND usergroup = 'root'") !== false)
    {
      $messages['info'][] = "A valid admin user already exists, you can skip this step.";
      $var['skip'] = true;
    }
  }
  elseif($var['step'] == 6 && isset($_POST['submit']))
  {
    require_once ROOT_FOLDER.'inc/config.php';
    require_once ROOT_FOLDER.'inc/functions.php';
    require_once ROOT_FOLDER.'inc/class_session.php';
    $session = new sessionClass;

    if(!$db->connect($config['database']))
    {
      $messages['error'][] = 'Cannot connect to database.';
    }

    unset($config);

    $admin_user = array(
      'id' => 1,
      'username' => $db->escape_string($_POST['admin_username']),
      'hash' => password_hash($_POST['admin_password'], PASSWORD_BCRYPT),
      'usergroup' => 'root',
      'email' => $db->escape_string($_POST['admin_email']),
      'lastip' => getvisitorip()
    );

    if(!$db->insert_query('website_users', $admin_user))
    {
      $var['step'] = 6;
      $messages['error'][] = 'Unable to create admin user.';
    }
    else
    {
      $messages['success'][] = 'Successfully created admin user.';

      $var['step'] = 7;
    }
  }
  elseif($var['step'] == 7 && !isset($_POST['submit']))
  {
    $lock_handle = fopen(ROOT_FOLDER.'install/lock', 'w');
    $lock_write = fwrite($lock_handle, '1');
    if(!$lock_write)
    {
      $var['step'] = 6;
      $messages['error'][] = 'Unable to write to lock file. Give write access to the "install" directory to your web server and try again.';
    }
    else
    {
      fclose($lock_handle);
    }
  }
}

$messages['all'] = "";
foreach($messages as $type=>$array)
{
  switch($type)
  {
    case 'error':
      foreach($array as $message)
      {
        $messages['all'] .= "<div class=\"alert alert-danger alert-dismissable\">
                <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>
                {$message}
              </div>";
      }
      break;
    case 'warning':
      foreach($array as $message)
      {
        $messages['all'] .= "<div class=\"alert alert-warning alert-dismissable\">
                <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>
                {$message}
              </div>";
      }
      break;
    case 'info':
      foreach($array as $message)
      {
        $messages['all'] .= "<div class=\"alert alert-info alert-dismissable\">
                <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>
                {$message}
              </div>";
      }
      break;
    case 'success':
      foreach($array as $message)
      {
        $messages['all'] .= "<div class=\"alert alert-success alert-dismissable\">
                <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>
                {$message}
              </div>";
      }
      break;
  }
}
?>
<!DOCTYPE html>
<html>

    <head>
      <title>Install</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
      <link href="../install/res/style.css" rel="stylesheet">
    </head>

    <body>

      <nav class="navbar navbar-default" id="navbar" role="navigation">
        <div class="container">
          <div class="navbar-header">
            <a href="#top" class="navbar-brand">Install</a>
          </div>
          <ul class="nav navbar-nav">
          </ul>
        </div>
      </nav>

      <div class="container">

        <?php if($var['error']): ?>

        <div class="page-header" id="setup_db">
          <h3>Errors occurred!</h3>
        </div>
        <p>The following errors need to be corrected before starting the installation:</p><br>
        <?php echo $messages['all']; ?>

        <?php elseif(!$var['error'] && $var['lock']): ?>

          <?php echo $messages['all']; ?>

          <div class="page-header" id="setup_db">
            <h3>Installation locked.</h3>
          </div>
          <p>This installation is locked. This means that it has most likely already been installed. (If not, just remove the "lock" file in the "install" directory.</p><br>

        <?php elseif(!$var['error'] && !$var['lock'] && $var['step'] == 0): ?>

          <?php echo $messages['all']; ?>

          <form class="form-horizontal" action="index.php?action=install" method="post" role="form">

            <div class="page-header" id="setup_db">
              <h1>Welcome!</h1>
            </div>
            <p>To the installation of web-sublime. Here you'll be setting up the bare neccesseties of the editor.</p><br>

            <div class="form-group">
              <div class="col-sm-10">
                <button type="submit" name="next" class="btn btn-default">Begin</button>
              </div>
            </div>

            <input type="hidden" name="step" value="0">

          </form>

        <?php elseif(!$var['error'] && !$var['lock'] && $var['step'] == 1): ?>

          <?php echo $messages['all']; ?>
          
          <form class="form-horizontal" action="index.php?action=install" method="post" role="form">

            <div class="page-header" id="setup_db">
              <h3>Set up your database</h3>
            </div>
            <p>You need to set up an empty MySQL database for sublime-web to function.</p><br>
            <h4>MySQL</h4>
            <div class="form-group">
              <div class="col-sm-1 pull-right" style="padding-right: 0;">
                <input type="text" name="db_port" pattern="\d{1,5}" maxlength="5" class="form-control" id="inputPort1" placeholder="Port">
              </div>
              <label for="inputHostname1" class="col-sm-2 control-label">Hostname and Port</label>
              <div class="input-group col-sm-9">
                <span class="input-group-addon"><span class="glyphicon glyphicon-globe"></span></span>
                <input type="text" name="db_hostname" maxlength="255" required class="form-control" id="inputHostname1" placeholder="Hostname">
              </div>
            </div>
            <div class="form-group">
              <label for="inputUsername1" class="col-sm-2 control-label">Username</label>
              <div class="input-group col-sm-10">
                <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
                <input type="text" name="db_username" required class="form-control" id="inputUsername1" placeholder="Username">
              </div>
            </div>
            <div class="form-group">
              <label for="inputPassword1" class="col-sm-2 control-label">Password</label>
              <div class="input-group col-sm-10">
                <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
                <input type="password" name="db_password" class="form-control" id="inputPassword1" placeholder="Password">
              </div>
            </div>
            <div class="form-group">
              <label for="inputDatabase1" class="col-sm-2 control-label">Database</label>
              <div class="input-group col-sm-10">
                <span class="input-group-addon"><span class="glyphicon glyphicon-hdd"></span></span>
                <input type="text" name="db_database" required class="form-control" id="inputDatabase1" placeholder="Database">
              </div>
            </div>
            
            <div class="form-group">
              <div class="input-group col-sm-2 col-sm-offset-2 collapse in">
                <button type="submit" name="submit" class="btn btn-default">Next</button>
                <?php if($var['skip']) echo '<button type="submit" name="next" formnovalidate class="btn btn-default">Skip</button>'; ?>
              </div>
            </div>

            <input type="hidden" name="step" value="1">

          </form>

        <?php elseif(!$var['error'] && !$var['lock'] && $var['step'] == 2): ?>

          <?php echo $messages['all']; ?>
          
          <form class="form-horizontal" action="index.php?action=install" method="post" role="form">

            <div class="page-header" id="setup_db">
              <h3>Create the tables</h3>
            </div>
            <p>Now we'll create the tables in the database.</p><br>

            <div class="form-group">
              <div class="col-sm-10">
                <button type="submit" name="back" class="btn btn-default">Back</button>
                <button type="submit" name="next" class="btn btn-default">Next</button>
              </div>
            </div>

            <input type="hidden" name="step" value="2">

          </form>

        <?php elseif(!$var['error'] && !$var['lock'] && $var['step'] == 3): ?>

          <form class="form-horizontal" action="index.php?action=install" method="post" role="form">

            <div class="page-header" id="setup_db">
              <h3>Creating tables...</h3>
            </div>

            <?php echo $messages['all']; ?>

            <div class="form-group">
              <div class="col-sm-12">
                <button type="submit" name="next" class="btn btn-default">Next</button>
              </div>
            </div>

            <input type="hidden" name="step" value="3">

          </form>

        <?php elseif(!$var['error'] && !$var['lock'] && $var['step'] == 4): ?>

          <?php echo $messages['all']; ?>
          
          <form class="form-horizontal" action="index.php?action=install" method="post" role="form">

            <div class="page-header" id="setup_db">
              <h3>Insert some data</h3>
            </div>
            <p>This step will insert some data into the tables we just created.</p><br>

            <div class="form-group">
              <div class="col-sm-10">
                <button type="submit" name="next" class="btn btn-default">Next</button>
              </div>
            </div>

            <input type="hidden" name="step" value="4">

          </form>

        <?php elseif(!$var['error'] && !$var['lock'] && $var['step'] == 5): ?>

          <form class="form-horizontal" action="index.php?action=install" method="post" role="form">

            <div class="page-header" id="setup_db">
              <h3>Inserting Data...</h3>
            </div>

            <?php echo $messages['all']; ?>

            <div class="form-group">
              <div class="col-sm-12">
                <button type="submit" name="next" class="btn btn-default">Next</button>
              </div>
            </div>

            <input type="hidden" name="step" value="5">

          </form>

        <?php elseif(!$var['error'] && !$var['lock'] && $var['step'] == 6): ?>

          <form class="form-horizontal" action="index.php?action=install" method="post" role="form">

            <?php echo $messages['all']; ?>
            <div class="page-header" id="setup_db">
              <h3>Set up your admin user</h3>
            </div>
            <p>You need to set up an admin user in order to administrate the framework.</p><br>
            <div class="form-group">
              <label for="inputHostname1" class="col-sm-2 control-label">Username</label>
              <div class="input-group col-sm-10">
                <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
                <input type="text" name="admin_username" required maxlength="45" class="form-control" id="inputUsername3" placeholder="Username">
              </div>
            </div>
            <div class="form-group">
              <label for="inputPassword1" class="col-sm-2 control-label">Password</label>
              <div class="input-group col-sm-10">
                <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
                <input type="password" name="admin_password" required class="form-control" id="inputPassword3" placeholder="Password">
              </div>
            </div>
            <div class="form-group">
              <label for="inputEmail1" class="col-sm-2 control-label">Email</label>
              <div class="input-group col-sm-10">
                <span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
                <input type="text" name="admin_email" pattern=".*@.*\..*" class="form-control" id="inputEmail1" placeholder="E-mail">
              </div>
            </div>
            <div class="form-group">
              <div class="input-group col-sm-10 col-sm-offset-2 collapse in">
                <button type="submit" name="submit" class="btn btn-default">Next</button>
                <?php if($var['skip']) echo '<button type="submit" name="next" formnovalidate class="btn btn-default">Skip</button>'; ?>
              </div>
            </div>

            <input type="hidden" name="step" value="6">

          </form>

        <?php elseif(!$var['error'] && !$var['lock'] && $var['step'] == 7): ?>

        <div class="page-header" id="setup_db">
          <h3>All done!</h3>
        </div>
        <p>Click this button to go to the front page.</p><br>

        <div class="input-group col-sm-10">
          <a href="../" class="btn btn-default">Go</a>
        </div>

        <?php endif; ?>

      </div>

      <!-- Javascript -->
      <script src="../assets/js/jquery-1.10.2.min.js"></script>
      <script src="../assets/js/bootstrap.min.js"></script>

    </body>

</html>
