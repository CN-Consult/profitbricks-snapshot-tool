<?php
/**
 * @file
 * @version 0.1
 * @copyright 2017 CN-Consult GmbH
 * @author Jens Stahl <jens.stahl@cn-consult.eu>
 *
 * License: Please check the LICENSE file for more information.
 */

namespace ProfitBricksApi;

/**
 * Class VirtualDisk describes the properties of a VD at ProfitBricks.
 */
class VirtualDisk
{
    public $id;
    //public $type;         //no need for this variable & it would exist twice
    public $href;
    public $createdDate;
    public $createdBy;
    public $etag;
    public $lastModifiedDate;
    public $lastModifiedBy;
    public $state;
    public $name;
    public $type;
    public $size;
    public $availabilityZone;
    public $image;
    public $imagePassword;
    public $sshKeys;
    public $bus;
    public $licenceType;
    public $cpuHotPlug;
    public $cpuHotUnplug;
    public $ramHotPlug;
    public $ramHotUnplug;
    public $nicHotPlug;
    public $nicHotUnplug;
    public $discVirtioHotPlug;
    public $discVirtioHotUnplug;
    public $discScsiHotPlug;
    public $discScsiHotUnplug;
    public $deviceNumber;
    // custom variables
    /** @var Snapshot[] $snapshots */
    public $snapshots;
    public $numberSnapshots;
    /** @var \DateTime $lastBackup */
    public $lastSnapshotDate;

    public function __construct($_virtualDisk)
    {
        $this->id = $_virtualDisk->id;
        $this->href = $_virtualDisk->href;
        $this->createdDate = new \DateTime($_virtualDisk->metadata->createdDate);
        $this->createdDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->createdBy = $_virtualDisk->metadata->createdBy;
        $this->etag = $_virtualDisk->metadata->etag;
        $this->lastModifiedDate = new \DateTime($_virtualDisk->metadata->lastModifiedDate);
        $this->lastModifiedDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $this->lastModifiedBy = $_virtualDisk->metadata->lastModifiedBy;
        $this->state = $_virtualDisk->metadata->state;
        $this->name = $_virtualDisk->properties->name;
        $this->type = $_virtualDisk->properties->type;
        $this->size = $_virtualDisk->properties->size;
        $this->availabilityZone = $_virtualDisk->properties->availabilityZone;
        $this->image = $_virtualDisk->properties->image;
        $this->imagePassword = $_virtualDisk->properties->imagePassword;
        $this->sshKeys = $_virtualDisk->properties->sshKeys;
        $this->bus = $_virtualDisk->properties->bus;
        $this->licenceType = $_virtualDisk->properties->licenceType;
        $this->cpuHotPlug = $_virtualDisk->properties->cpuHotPlug;
        $this->cpuHotUnplug = $_virtualDisk->properties->cpuHotUnplug;
        $this->ramHotPlug = $_virtualDisk->properties->ramHotPlug;
        $this->ramHotUnplug = $_virtualDisk->properties->ramHotUnplug;
        $this->nicHotPlug = $_virtualDisk->properties->nicHotPlug;
        $this->nicHotUnplug = $_virtualDisk->properties->nicHotUnplug;
        $this->discVirtioHotPlug = $_virtualDisk->properties->discVirtioHotPlug;
        $this->discVirtioHotUnplug = $_virtualDisk->properties->discVirtioHotUnplug;
        $this->discScsiHotPlug = $_virtualDisk->properties->discScsiHotPlug;
        $this->discScsiHotUnplug = $_virtualDisk->properties->discScsiHotUnplug;
        $this->deviceNumber = $_virtualDisk->properties->deviceNumber;
        // custom
        $this->snapshots = array();
        $this->numberSnapshots = 0;
        $this->lastSnapshotDate = new \DateTime("2000-01-01 00:01");
    }
}

/**
 *  This object class is filled by values submitted by ProfitBricksAPI. Not all values are important.
 *  The original values are:
 *  $disk                             Array
 *  $disk->id                             id
 *  $disk->type                           mostly 'snapshot'
 *  $disk->href                           link (maybe for download)
 *  $disk->metadata                   object
 *  $disk->metadata->createdDate          date of creation
 *  $disk->metadata->createdBy            user name
 *  $disk->metadata->etag                 cryptic number
 *  $disk->metadata->lastModifiedDate     date of last modification
 *  $disk->metadata->lastModifiedBy       user name
 *  $disk->metadata->state                availability
 *  $disk->properties                 object
 *  $disk->properties->name               name
 *  $disk->properties->type               HDD / SSD
 *  $disk->properties->size               GB
 *  $disk->properties->availabilityZone   Value AUTO or w/e
 *  $disk->properties->image              null
 *  $disk->properties->imagePassword      null
 *  $disk->properties->sshKeys            SSH keys
 *  $disk->properties->bus                bus type
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