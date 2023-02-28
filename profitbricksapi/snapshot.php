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
 * Class Snapshot is a quite simple data container which wrap all available values.
 *
 * The variables can resist and can be used in future developed code even if ProfitBricks changes the api construction.
 * In this case just the variables here have to be modified and the code still works.
 */

class Snapshot
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
    public string $description;
    public string $location;
    public int $size;
    public string $cpuHotPlug;
    public string $cpuHotUnplug;
    public string $ramHotPlug;
    public string $ramHotUnplug;
    public string $nicHotPlug;
    public string $nicHotUnplug;
    public string $discVirtioHotPlug;
    public string $discVirtioHotUnplug;
    public string $discScsiHotPlug;
    public string $discScsiHotUnplug;
    public string $licenceType;
    // custom variables
    public string $autoScriptCreated;

    public function __construct(object $_snapshot)
    {
        $this->id = $_snapshot->id;
        $this->type = $_snapshot->type;
        $this->href = $_snapshot->href;
        $this->createdDate = new DateTime($_snapshot->metadata->createdDate);
        $this->createdDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $this->createdBy = $_snapshot->metadata->createdBy;
        $this->etag = $_snapshot->metadata->etag;
        $this->lastModifiedDate = new DateTime($_snapshot->metadata->lastModifiedDate);
        $this->lastModifiedDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $this->lastModifiedBy = $_snapshot->metadata->lastModifiedBy;
        $this->state = $_snapshot->metadata->state;
        $this->name = $_snapshot->properties->name;
        $this->description = $_snapshot->properties->description;
        $this->location = $_snapshot->properties->location;
        $this->size = (int) $_snapshot->properties->size;
        $this->cpuHotPlug = $_snapshot->properties->cpuHotPlug;
        $this->cpuHotUnplug = $_snapshot->properties->cpuHotUnplug;
        $this->ramHotPlug = $_snapshot->properties->ramHotPlug;
        $this->ramHotUnplug = $_snapshot->properties->ramHotUnplug;
        $this->nicHotPlug = $_snapshot->properties->nicHotPlug;
        $this->nicHotUnplug = $_snapshot->properties->nicHotUnplug;
        $this->discVirtioHotPlug = $_snapshot->properties->discVirtioHotPlug;
        $this->discVirtioHotUnplug = $_snapshot->properties->discVirtioHotUnplug;
        $this->discScsiHotPlug = $_snapshot->properties->discScsiHotPlug;
        $this->discScsiHotUnplug = $_snapshot->properties->discScsiHotUnplug;
        $this->licenceType = $_snapshot->properties->licenceType;
        if (str_contains($this->description, "Auto-Script:")) $this->autoScriptCreated = true;
        else $this->autoScriptCreated = false;
    }
}

/** Object description
 *  $snapShot                               Array
 *  $snapShot->id                           cryptic number
 *  $snapShot->type                         mostly 'snapshot'
 *  $snapShot->href                         link (maybe for download)
 *  $snapShot->metadata                     object
 *  $snapShot->metadata->createdDate        date of creation
 *  $snapShot->metadata->createdBy          user name
 *  $snapShot->metadata->etag               cryptic number
 *  $snapShot->metadata->lastModifiedDate   date of last modification
 *  $snapShot->metadata->lastModifiedBy     user name
 *  $snapShot->metadata->state              availability
 *  $snapShot->properties                   object
 *  $snapShot->properties->name             name
 *  $snapShot->properties->description      description
 *  $snapShot->properties->location         location
 *  $snapShot->properties->size             size (in GB, I guess)
 *  $snapShot->properties->cpuHotPlug
 *  $snapShot->properties->cpuHotUnplug
 *  $snapShot->properties->ramHotPlug
 *  $snapShot->properties->ramHotUnplug
 *  $snapShot->properties->nicHotPlug
 *  $snapShot->properties->nicHotUnplug
 *  $snapShot->properties->discVirtioHotPlug
 *  $snapShot->properties->discVirtioHotUnplug
 *  $snapShot->properties->discScsiHotPlug
 *  $snapShot->properties->discScsiHotUnplug
 *  $snapShot->properties->licenseType
 */
