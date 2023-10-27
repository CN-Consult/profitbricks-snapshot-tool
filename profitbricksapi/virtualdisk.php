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

use DateTime;
use DateTimeZone;

/**
 * Class VirtualDisk is a quite simple data container which wrap all available values.
 *
 * The variables can resist and can be used in future developed code even if ProfitBricks changes the api construction.
 * In this case just the variables here have to be modified and the code still works.
 */
class VirtualDisk
{
    public string $id;
    public string $href;
    public DateTime $createdDate;
    public string $createdBy;
    public string $etag;
    public DateTime $lastModifiedDate;
    public string $lastModifiedBy;
    public string $state;
    public string $name;
    public string $type;
    public string $size;
    public string $availabilityZone;
    public string $bus;
    public string $licenceType;
    public string $cpuHotPlug;
    public string $ramHotPlug;
    public string $nicHotPlug;
    public string $nicHotUnplug;
    public string $discVirtioHotPlug;
    public string $discVirtioHotUnplug;
    public string $deviceNumber;
    // custom variables
    /** @var Snapshot[] $snapshots */
    public array $snapshots;
    public int $numberSnapshots;
    public DateTime $lastSnapshotDate;

    public function __construct(object $_virtualDisk)
    {
        $this->id = $_virtualDisk->id;
        $this->href = $_virtualDisk->href;
        $this->createdDate = new DateTime($_virtualDisk->metadata->createdDate);
        $this->createdDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $this->createdBy = $_virtualDisk->metadata->createdBy;
        $this->etag = $_virtualDisk->metadata->etag;
        $this->lastModifiedDate = new DateTime($_virtualDisk->metadata->lastModifiedDate);
        $this->lastModifiedDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $this->lastModifiedBy = $_virtualDisk->metadata->lastModifiedBy;
        $this->state = $_virtualDisk->metadata->state;
        $this->name = $_virtualDisk->properties->name;
        $this->type = $_virtualDisk->properties->type;
        $this->size = $_virtualDisk->properties->size;
        $this->availabilityZone = $_virtualDisk->properties->availabilityZone;
        $this->bus = $_virtualDisk->properties->bus;
        $this->licenceType = $_virtualDisk->properties->licenceType;
        $this->cpuHotPlug = $_virtualDisk->properties->cpuHotPlug;
        $this->ramHotPlug = $_virtualDisk->properties->ramHotPlug;
        $this->nicHotPlug = $_virtualDisk->properties->nicHotPlug;
        $this->nicHotUnplug = $_virtualDisk->properties->nicHotUnplug;
        $this->discVirtioHotPlug = $_virtualDisk->properties->discVirtioHotPlug;
        $this->discVirtioHotUnplug = $_virtualDisk->properties->discVirtioHotUnplug;
        $this->deviceNumber = $_virtualDisk->properties->deviceNumber;
        // custom
        $this->snapshots = array();
        $this->numberSnapshots = 0;
        $this->lastSnapshotDate = new \DateTime("2000-01-01 00:01");
    }
}

/**
 *  OUTDATED API DESCRIPTION !!!
 *
 *  This object class is filled by values submitted by ProfitBricksAPI. Not all values are important.
 *  The original values are:
 *  $disk                                   Array
 *  $disk->id                               id
 *  $disk->type                             mostly 'snapshot'
 *  $disk->href                             link (maybe for download)
 *  $disk->metadata                         object
 *  $disk->metadata->createdDate            date of creation
 *  $disk->metadata->createdBy              user name
 *  $disk->metadata->etag                   cryptic number
 *  $disk->metadata->lastModifiedDate       date of last modification
 *  $disk->metadata->lastModifiedBy         user name
 *  $disk->metadata->state                  availability
 *  $disk->properties                       object
 *  $disk->properties->name                 name
 *  $disk->properties->type                 HDD / SSD
 *  $disk->properties->size                 GB
 *  $disk->properties->availabilityZone     Value AUTO or w/e
 *  $disk->properties->image                null
 *  $disk->properties->imagePassword        null
 *  $disk->properties->sshKeys              SSH keys
 *  $disk->properties->bus                  bus type
 *  $disk->properties->licenseType
 *  $disk->properties->cpuHotPlug
 *  $disk->properties->cpuHotUnplug
 *  $disk->properties->ramHotPlug
 *  $disk->properties->ramHotUnplug
 *  $disk->properties->nicHotPlug
 *  $disk->properties->nicHotUnplug
 *  $disk->properties->discVirtioHotPLug
 *  $disk->properties->discVirtioHotUnplug
 *  $disk->properties->discScsiHotPlug
 *  $disk->properties->discScsiHotUnplug
 *  $disk->properties->deviceNumber
 */
