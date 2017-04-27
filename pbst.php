<?php
/**
 * @file
 * @version 0.1
 * @copyright 2017 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

require_once ("vendor/autoload.php");
use Symfony\Component\Console\Application;

/**
 * Custom auto loader for own files.
 * @param string $_className The name of the class that gets sourced.
 */
function customAutoLoader($_className)
{
    $strippedClassName = strtolower(substr($_className, strrpos($_className, '\\') + 1));
    foreach (array(".", "commands", "profitbricksapi") as $directory)
    {
        $filepath = __DIR__."/".$directory."/".$strippedClassName.'.php';
        if (file_exists($filepath))
        {
            require_once $filepath;
            break;
        }
    }
}
spl_autoload_register("customAutoLoader");

/**

foreach (glob(getcwd()."/commands/*command.php") as $filename)
{
    require_once ($filename);
}
require_once ('profitbricksapi/profitbricksapi.php');
require_once ('profitbricksapi/datacenter.php');
require_once ('profitbricksapi/virtualmachine.php');
require_once ('profitbricksapi/virtualdisk.php');
require_once ('profitbricksapi/snapshot.php');
*/

$application = new Application();
$application = new Application();
foreach(array("SnapshotListCommand", "SnapshotCreateFromAttachedDisksCommand", "SnapshotDeleteCommand", "SnapshotAutoCreateCommand",
            "SnapshotAutoDeleteCommand", "SnapshotCheckerCommand", "ServerListCommand", "DiskListCommand") as $command)
{
    $command = "\\PBST\\Commands\\".$command;
    $application->add(new $command());
}
/*
$application->add(new \PBST\Commands\SnapshotListCommand());
$application->add(new \PBST\Commands\SnapshotCreateFromAttachedDisksCommand());
$application->add(new \PBST\Commands\SnapshotDeleteCommand());
$application->add(new \PBST\Commands\SnapshotAutoCreateCommand());
$application->add(new \PBST\Commands\SnapshotAutoDeleteCommand());
$application->add(new \PBST\Commands\SnapshotCheckerCommand());
$application->add(new \PBST\Commands\ServerListCommand());
$application->add(new \PBST\Commands\DiskListCommand());
*/
$application->run();