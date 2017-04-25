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
foreach (glob(getcwd()."/commands/*command.php") as $filename)
{
    require_once ($filename);
}
require_once ('profitbricksapi/profitbricksapi.php');
require_once ('profitbricksapi/datacenter.php');
require_once ('profitbricksapi/virtualmachine.php');
require_once ('profitbricksapi/virtualdisk.php');
require_once ('profitbricksapi/snapshot.php');

use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new \PBST\Command\SnapshotListCommand());
$application->add(new \PBST\Command\SnapshotCreateFromAttachedDisksCommand());
$application->add(new \PBST\Command\SnapshotDeleteCommand());
$application->add(new \PBST\Command\SnapshotAutoCreateCommand());
$application->add(new \PBST\Command\SnapshotAutoDeleteCommand());
$application->add(new \PBST\Command\SnapshotCheckerCommand());
$application->add(new \PBST\Command\ServerListCommand());
$application->add(new \PBST\Command\DiskListCommand());
$application->run();