PBST (ProfitBricks snapshot tool)
=================================
Synopsis
--------
This script was created to manage automatic snapshot creation from virtual disks connected to
virtual machines at the cloud hosting provider ProfitBricks. It is written in PHP and uses the
symfony command framework.

Motivation
----------------
ProfitBricks provides an API which enables us to create, delete and modify virtual machines,
virtual disks and its snapshots. PBST covers the creation and deletion of snapshots. Which is
with an automatic script much easier than in the web interface.
##### PBST method
In a 1st step PBST is getting all information about data centers, virtual machines and its disks.
Due to this dependencies PBST considers only connected disks for snapshots. Regarding to this
we can make snapshot from commandline for every server at once or for just one. This can be done
by manual initiation or by cronjob.
##### Notification
Having an automatic snapshot creation without monitoring this behaviour is not the best way.
So PBST delivers success messages to a specified email address when the snapshot has been done.
The email address can be configured, which is explained in the chapter configuration.

Installation
------------
- PHP (tested on 5.5.10 successfully)
- Composer (to get symfony command framework)
- finally the here provided files

Reference
---------
#### Configuration
The config file (config.ini) consists of the two fixed chapters api and mail, which configures
the user credentials and the email addresses. The following chapters configures the servers. 

So there has to be a at least a config file which should provide your credentials for ProfitBricks.
If you decide to use automatic creation you have to configure your servers there also, because
not configured machines will not be considered for snapshot creation. Manual snapshot creation
doesn't need a configuration.

There is an self explained example file delivered with this repository.

#### HOW-TO
Command usage is depending on your operating system.

List commands give you a small overview and you can test with it the validity of your configuration.
- php pbst.php servers:list
- php bpst.php disk:list
- php pbst.php snapshot:list

Manual commands:
- php pbst.php snapshot:createFromAttachedDisks server1.name server2.name -d "short text"
- php pbst.php snapshot:delete

Automatic commands:
- php pbst.php snapshot:autoCreate     (should run once per day)
- php pbst.php snapshot:check          (notification should run in 10 min interval after creation)
- php pbst.php snapshot:autoDelete     (should run once per day)

The auto deletion script deletes only snapshots created by auto creation command. The snapshots
are marked and identified with leading "Auto-Script:" text in its description. This behaviour
does not match to the manual deletion.
Multiple runs of auto creation per day can destroy the notification. So don't do so!

Contributors
------------
CN-Consult GmbH

License
-------
DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE  
Read the file LICENSE.