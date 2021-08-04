<?php

namespace Decision\Model;

use DateTime;
use Decision\Model\SubDecision\Foundation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Organ entity.
 *
 * Note that this entity is derived from the decisions themself.
 *
 * @ORM\Entity
 */
class Organ
{
    public const ORGAN_TYPE_COMMITTEE = 'committee';
    public const ORGAN_TYPE_AVC = 'avc';
    public const ORGAN_TYPE_FRATERNITY = 'fraternity';
    public const ORGAN_TYPE_AVW = 'avw';
    public const ORGAN_TYPE_KKK = 'kkk';
    public const ORGAN_TYPE_RVA = 'rva';

    /**
     * Id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Abbreviation (only for when organs are created).
     *
     * @ORM\Column(type="string")
     */
    protected $abbr;

    /**
     * Name (only for when organs are created).
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Type of the organ.
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * Reference to foundation of organ.
     *
     * @ORM\OneToOne(targetEntity="Decision\Model\SubDecision\Foundation", inversedBy="organ")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *     @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *     @ORM\JoinColumn(name="r_decision_point", referencedColumnName="decision_point"),
     *     @ORM\JoinColumn(name="r_decision_number", referencedColumnName="decision_number"),
     *     @ORM\JoinColumn(name="r_number", referencedColumnName="number")
     * })
     */
    protected $foundation;

    /**
     * Foundation date.
     *
     * @ORM\Column(type="date")
     */
    protected $foundationDate;

    /**
     * Abrogation date.
     *
     * @ORM\Column(type="date", nullable=true)
     */
    protected $abrogationDate;

    /**
     * Reference to members.
     *
     * @ORM\OneToMany(targetEntity="OrganMember", mappedBy="organ")
     */
    protected $members;

    /**
     * Reference to subdecisions.
     *
     * @ORM\ManyToMany(targetEntity="SubDecision")
     * @ORM\JoinTable(name="organs_subdecisions",
     *     joinColumns={@ORM\JoinColumn(name="organ_id", referencedColumnName="id")},
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="meeting_type", referencedColumnName="meeting_type"),
     *         @ORM\JoinColumn(name="meeting_number", referencedColumnName="meeting_number"),
     *         @ORM\JoinColumn(name="decision_point", referencedColumnName="decision_point"),
     *         @ORM\JoinColumn(name="decision_number", referencedColumnName="decision_number"),
     *         @ORM\JoinColumn(name="subdecision_number", referencedColumnName="number")
     *     })
     */
    protected $subdecisions;

    /**
     * All organInformation for this organ.
     *
     * @ORM\OneToMany(targetEntity="OrganInformation", mappedBy="organ", cascade={"persist", "remove"})
     */
    protected $organInformation;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->subdecisions = new ArrayCollection();
    }

    /**
     * Get the ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the ID.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the abbreviation.
     *
     * @return string
     */
    public function getAbbr()
    {
        return $this->abbr;
    }

    /**
     * Set the abbreviation.
     *
     * @param string $abbr
     */
    public function setAbbr($abbr)
    {
        $this->abbr = $abbr;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the foundation.
     *
     * @return Foundation
     */
    public function getFoundation()
    {
        return $this->foundation;
    }

    /**
     * Set the foundation.
     *
     * @param Foundation $foundation
     */
    public function setFoundation($foundation)
    {
        $this->foundation = $foundation;
    }

    /**
     * Get the foundation date.
     *
     * @return DateTime
     */
    public function getFoundationDate()
    {
        return $this->foundationDate;
    }

    /**
     * Set the foundation date.
     */
    public function setFoundationDate(DateTime $foundationDate)
    {
        $this->foundationDate = $foundationDate;
    }

    /**
     * Get the abrogation date.
     *
     * @return DateTime
     */
    public function getAbrogationDate()
    {
        return $this->abrogationDate;
    }

    /**
     * Set the abrogation date.
     */
    public function setAbrogationDate(DateTime $abrogationDate)
    {
        $this->abrogationDate = $abrogationDate;
    }

    /**
     * Get the members.
     *
     * @return Collection of OrganMember
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * Add multiple subdecisions.
     *
     * @param array $subdecisions
     */
    public function addSubdecisions($subdecisions)
    {
        foreach ($subdecisions as $subdecision) {
            $this->addSubdecision($subdecision);
        }
    }

    /**
     * Add a subdecision.
     */
    public function addSubdecision(SubDecision $subdecision)
    {
        if (!$this->subdecisions->contains($subdecision)) {
            $this->subdecisions[] = $subdecision;
        }
    }

    /**
     * Get all subdecisions.of this organ.
     *
     * @return ArrayCollection
     */
    public function getSubdecisions()
    {
        return $this->subdecisions;
    }

    /**
     * Get all subdecisions of this organ ordered by upload order.
     *
     * @return array subdecisions[0]->getDate < subdecision[1]->getDate
     */
    public function getSubDecisionsUploadOrdered()
    {
        $array = $this->subdecisions->toArray();
        usort($array, function ($dA, $dB) {
            return ($dA->getDecision()->getMeeting()->getDate() > $dB->getDecision()->getMeeting()->getDate() ? -1 : 1);
        });

        return $array;
    }

    /**
     * Returns all organ information.
     *
     * @return ArrayCollection of OrganInformation
     */
    public function getOrganInformation()
    {
        return $this->organInformation;
    }

    /**
     * Returns the approved information for an organ.
     *
     * @return OrganInformation|null
     */
    public function getApprovedOrganInformation()
    {
        foreach ($this->organInformation as $information) {
            if (!is_null($information->getApprover())) {
                return $information;
            }
        }

        return null;
    }
}
