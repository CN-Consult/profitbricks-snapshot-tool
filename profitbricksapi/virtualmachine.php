<?php
/**
 * @file
 * @version 0.2
 * @copyright 2023 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace PBST\ProfitBricksApi;

use stdClass;
use DateTime;

/**
 * Class VirtualMachine is a quite simple data container which wrap all available values.
 *
 * The variables can resist and can be used in future developed code even if ProfitBricks changes the api construction.
 * In this case just the variables here have to be modified and the code still works.
 */
class VirtualMachine
{
    public string $id;
    public string $type;
    public string $href;
    public DateTime $createdDate;
    public string $createdBy;
    public string $etag;
    public DateTime $lastModifiedDate;
    public string $lastModifiedBy;
    public string $state;
    public string $name;
    public string $cores;
    public string $ram;
    public string $availabilityZone;
    public string $vmState;
    public stdClass $bootCdrom;
    public array $virtualDisks;
    // custom variables
    public array $remainingSnapshots;
    public int $snapshotInterval;
    public DateTime $latestFullSnapshot;

    public function __construct($_profitBricksVM)
    {
        $this->id = $_profitBricksVM->id;
        $this->type = $_profitBricksVM->type;
        $this->href = $_profitBricksVM->href;
        $this->createdDate = new DateTime($_profitBricksVM->metadata->createdDate);
        $this->createdDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->createdBy = $_profitBricksVM->metadata->createdBy;
        $this->etag = $_profitBricksVM->metadata->etag;
        $this->lastModifiedDate = new DateTime($_profitBricksVM->metadata->lastModifiedDate);
        $this->lastModifiedDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->lastModifiedBy = $_profitBricksVM->metadata->lastModifiedBy;
        $this->state = $_profitBricksVM->metadata->state;
        $this->name = $_profitBricksVM->properties->name;
        $this->cores = $_profitBricksVM->properties->cores;
        $this->ram = $_profitBricksVM->properties->ram;
        $this->availabilityZone = $_profitBricksVM->properties->availabilityZone;
        $this->vmState = $_profitBricksVM->properties->vmState;
        if ($_profitBricksVM->properties->bootCdrom)
            $this->bootCdrom = $_profitBricksVM->properties->bootCdrom;
        $this->virtualDisks = array();
    }
}

/**
 *  This object class is filled by values submitted by ProfitBricksAPI. Not all values are important.
 *  The depths of this objects are depending on the "?depths=x" value, submitted by GET to the API. (1-5)
 *  The original values are:
 * 
 *  $virtualMachine                                                             Array
 *  $virtualMachine->id                                                         id
 *  $virtualMachine->type                                                       mostly 'server'
 *  $virtualMachine->href                                                       link (maybe for download)
 *  $virtualMachine->metadata                                                   object
 *  $virtualMachine->metadata->createdDate                                      date of creation
 *  $virtualMachine->metadata->createdBy                                        user name
 *  $virtualMachine->metadata->etag                                             cryptic number
 *  $virtualMachine->metadata->lastModifiedDate                                 date of last modification
 *  $virtualMachine->metadata->lastModifiedBy                                   user name
 *  $virtualMachine->metadata->state                                            availability
 *  $virtualMachine->properties                                                 object
 *  $virtualMachine->properties->name                                           name
 *  $virtualMachine->properties->cores                                          amount of cores
 *  $virtualMachine->properties->ram                                            RAM in MB
 *  $virtualMachine->properties->availabilityZone                               AUTO or w/e
 *  $virtualMachine->properties->vmState                                        machine running state
 *  $virtualMachine->properties->bootCdrom     e->properties->bootVolume                                 object
 *  $virtualMachine->properties->bootVolume->                                   booting state
 *  $virtualMachinid                                 id
 *  $virtualMachine->properties->bootVolume->type                               "volume" in this case
 *  $virtualMachine->properties->bootVolume->href                               link
 *  $virtualMachine->properties->bootVolume->metadata                           object
 *  $virtualMachine->properties->bootVolume->metadata->createdDate
 *  $virtualMachine->properties->bootVolume->metadata->createdBy
 *  $virtualMachine->properties->bootVolume->metadata->etag
 *  $virtualMachine->properties->bootVolume->metadata->lastModifiedDate
 *  $virtualMachine->properties->bootVolume->metadata->lastModifiedBy
 *  $virtualMachine->properties->bootVolume->metadata->state
 *  $virtualMachine->properties->bootVolume->properties                         object
 *  $virtualMachine->properties->bootVolume->properties->name
 *  $virtualMachine->properties->bootVolume->properties->type
 *  $virtualMachine->properties->bootVolume->properties->size
 *  $virtualMachine->properties->bootVolume->properties->image
 *  $virtualMachine->properties->bootVolume->properties->imagePassword
 *  $virtualMachine->properties->bootVolume->properties->bus
 *  $virtualMachine->properties->bootVolume->properties->licenseType
 *  $virtualMachine->properties->bootVolume->properties->cpuHotPlug
 *  $virtualMachine->properties->bootVolume->properties->cpuHotUnplug
 *  $virtualMachine->properties->bootVolume->properties->ramHotPlug
 *  $virtualMachine->properties->bootVolume->properties->ramHotUnplug
 *  $virtualMachine->properties->bootVolume->properties->nicHotPlug
 *  $virtualMachine->properties->bootVolume->properties->nicHotUnplug
 *  $virtualMachine->properties->bootVolume->properties->discVirtioHotPlug
 *  $virtualMachine->properties->bootVolume->properties->discVirtioHotUnplug
 *  $virtualMachine->properties->bootVolume->properties->discScsiHotPlug
 *  $virtualMachine->properties->bootVolume->properties->discScsiHotUnplug
 *  $virtualMachine->properties->bootVolume->properties->deviceNumber
 *  $virtualMachine->properties->cpuFamily
 *  $virtualMachine->entities                                                   object
 *  $virtualMachine->entities->cdroms                                           object
 *  $virtualMachine->entities->cdroms->id
 *  $virtualMachine->entities->cdroms->type                                     usually "collection"
 *  $virtualMachine->entities->cdroms->href                                     link
 *  $virtualMachine->entities->cdroms->items                                    array
 *  $virtualMachine->entities->cdroms->items->id                                id
 *  $virtualMachine->entities->cdroms->items->type                              usually "image"
 *  $virtualMachine->entities->cdroms->items->href                              link
 *  $virtualMachine->entities->cdroms->items->metadata                          object
 *  $virtualMachine->entities->cdroms->items->metadata->createdDate
 *  $virtualMachine->entities->cdroms->items->metadata->createdBy
 *  $virtualMachine->entities->cdroms->items->metadata->etag
 *  $virtualMachine->entities->cdroms->items->metadata->lastModifiedDate
 *  $virtualMachine->entities->cdroms->items->metadata->lastModifiedBy
 *  $virtualMachine->entities->cdroms->items->metadata->state
 *  $virtualMachine->entities->cdroms->items->properties                        object
 *  $virtualMachine->entities->cdroms->items->properties->name
 *  $virtualMachine->entities->cdroms->items->properties->description
 *  $virtualMachine->entities->cdroms->items->properties->location
 *  $virtualMachine->entities->cdroms->items->properties->size
 *  $virtualMachine->entities->cdroms->items->properties->cpuHotPlug
 *  $virtualMachine->entities->cdroms->items->properties->cpuHotUnplug
 *  $virtualMachine->entities->cdroms->items->properties->ramHotPlug
 *  $virtualMachine->entities->cdroms->items->properties->ramHotUnplug
 *  $virtualMachine->entities->cdroms->items->properties->nicHotPLug
 *  $virtualMachine->entities->cdroms->items->properties->nicHotUnplug
 *  $virtualMachine->entities->cdroms->items->properties->discVirtioHotPLug
 *  $virtualMachine->entities->cdroms->items->properties->discVirtioHotUnplug
 *  $virtualMachine->entities->cdroms->items->properties->discScsiHotPlug
 *  $virtualMachine->entities->cdroms->items->properties->discScsiHotUnplug
 *  $virtualMachine->entities->cdroms->items->properties->licenseType
 *  $virtualMachine->entities->cdroms->items->properties->imageType
 *  $virtualMachine->entities->cdroms->items->properties->public
 *  $virtualMachine->entities->volumes                                          object
 *  $virtualMachine->entities->volumes->id
 *  $virtualMachine->entities->volumes->type                                    usually "collection"
 *  $virtualMachine->entities->volumes->href                                    link
 *  $virtualMachine->entities->volumes->items                                   array
 *  $virtualMachine->entities->volumes->items->id                               id
 *  $virtualMachine->entities->volumes->items->type                             usually "image"
 *  $virtualMachine->entities->volumes->items->href                             link
 *  $virtualMachine->entities->volumes->items->metadata                         object
 *  $virtualMachine->entities->volumes->items->metadata->createdDate
 *  $virtualMachine->entities->volumes->items->metadata->createdBy
 *  $virtualMachine->entities->volumes->items->metadata->etag
 *  $virtualMachine->entities->volumes->items->metadata->lastModifiedDate
 *  $virtualMachine->entities->volumes->items->metadata->lastModifiedBy
 *  $virtualMachine->entities->volumes->items->metadata->state
 *  $virtualMachine->entities->volumes->items->properties                       object
 *  $virtualMachine->entities->volumes->items->properties->name
 *  $virtualMachine->entities->volumes->items->properties->type
 *  $virtualMachine->entities->volumes->items->properties->size
 *  $virtualMachine->entities->volumes->items->properties->availabilityZone
 *  $virtualMachine->entities->volumes->items->properties->image
 *  $virtualMachine->entities->volumes->items->properties->imagePassword
 *  $virtualMachine->entities->volumes->items->properties->sshKeys
 *  $virtualMachine->entities->volumes->items->properties->bus
 *  $virtualMachine->entities->volumes->items->properties->licenseType
 *  $virtualMachine->entities->volumes->items->properties->cpuHotPlug
 *  $virtualMachine->entities->volumes->items->properties->cpuHotUnplug
 *  $virtualMachine->entities->volumes->items->properties->ramHotPlug
 *  $virtualMachine->entities->volumes->items->properties->ramHotUnplug
 *  $virtualMachine->entities->volumes->items->properties->nicHotPLug
 *  $virtualMachine->entities->volumes->items->properties->nicHotUnplug
 *  $virtualMachine->entities->volumes->items->properties->discVirtioHotPLug
 *  $virtualMachine->entities->volumes->items->properties->discVirtioHotUnplug
 *  $virtualMachine->entities->volumes->items->properties->discScsiHotPlug
 *  $virtualMachine->entities->volumes->items->properties->discScsiHotUnplug
 *  $virtualMachine->entities->volumes->items->properties->deviceNumber
 *  $virtualMachine->entities->loadbalancers                                    object
 *  $virtualMachine->entities->loadbalancers->id                                id
 *  $virtualMachine->entities->loadbalancers->type                              mostly just "collection"
 *  $virtualMachine->entities->loadbalancers->href                              href
 *  $virtualMachine->entities->nics                                             object
 *  $virtualMachine->entities->nics->id                                         id
 *  $virtualMachine->entities->nics->type                                       mostly just "collection"
 *  $virtualMachine->entities->nics->href                                       href
 *  $virtualMachine->entities->nics->items                                      array
 *  ...
 */
