<?php

declare(strict_types=1);

namespace Decision\Model;

use Application\Model\Traits\TimestampableTrait;
use Decision\Model\Enums\MeetingTypes;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    HasLifecycleCallbacks,
    Id,
    JoinColumn,
    OneToOne};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Meeting minutes.
 */
#[Entity]
#[HasLifecycleCallbacks]
class MeetingMinutes implements ResourceInterface
{
    use TimestampableTrait;

    /**
     * Meeting type.
     */
    #[Id]
    #[Column(
        type: "string",
        enumType: MeetingTypes::class,
    )]
    protected MeetingTypes $meeting_type;

    /**
     * Meeting number.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $meeting_number;

    /**
     * The corresponding meeting for these minutes.
     */
    #[OneToOne(
        targetEntity: Meeting::class,
        inversedBy: "meetingMinutes",
    )]
    #[JoinColumn(
        name: "meeting_type",
        referencedColumnName: "type",
        nullable: false,
    )]
    #[JoinColumn(
        name: "meeting_number",
        referencedColumnName: "number",
        nullable: false,
    )]
    protected Meeting $meeting;

    /**
     * The storage path.
     */
    #[Column(type: "string")]
    protected string $path;

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param Meeting $meeting
     */
    public function setMeeting(Meeting $meeting): void
    {
        $this->meeting = $meeting;
        $this->meeting_type = $meeting->getType();
        $this->meeting_number = $meeting->getNumber();
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'meeting_minutes';
    }
}
