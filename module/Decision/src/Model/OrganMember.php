<?php

namespace Decision\Model;

use DateTime;
use Decision\Model\SubDecision\Installation;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
    OneToOne,
};

/**
 * Organ member entity.
 *
 * Note that this entity is derived from the decisions themself.
 */
#[Entity]
class OrganMember
{
    /**
     * Id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * Organ.
     */
    #[ManyToOne(
        targetEntity: "Decision\Model\Organ",
        inversedBy: "members",
    )]
    protected Organ $organ;

    /**
     * Member.
     */
    #[ManyToOne(
        targetEntity: "Decision\Model\Member",
        inversedBy: "organInstallations",
    )]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected Member $member;

    /**
     * Function.
     */
    #[Column(type: "string")]
    protected string $function;

    /**
     * Installation date.
     */
    #[Column(type: "date")]
    protected DateTime $installDate;

    /**
     * Installation.
     */
    #[OneToOne(
        targetEntity: "Decision\Model\SubDecision\Installation",
        inversedBy: "organMember",
    )]
    #[JoinColumn(
        name: "r_meeting_type",
        referencedColumnName: "meeting_type",
        nullable: false,
    )]
    #[JoinColumn(
        name: "r_meeting_number",
        referencedColumnName: "meeting_number",
        nullable: false,
    )]
    #[JoinColumn(
        name: "r_decision_point",
        referencedColumnName: "decision_point",
        nullable: false,
    )]
    #[JoinColumn(
        name: "r_decision_number",
        referencedColumnName: "decision_number",
        nullable: false,
    )]
    #[JoinColumn(
        name: "r_number",
        referencedColumnName: "number",
        nullable: false,
    )]
    protected Installation $installation;

    /**
     * Discharge date.
     */
    #[Column(
        type: "date",
        nullable: true,
    )]
    protected ?DateTime $dischargeDate;

    /**
     * Set the organ.
     *
     * @param Organ $organ
     */
    public function setOrgan(Organ $organ): void
    {
        $this->organ = $organ;
    }

    /**
     * Get the organ.
     *
     * @return Organ
     */
    public function getOrgan(): Organ
    {
        return $this->organ;
    }

    /**
     * Set the member.
     *
     * @param Member $member
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the member.
     *
     * @return Member
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Set the function.
     *
     * @param string $function
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the function.
     *
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Set the installation date.
     *
     * @param DateTime $installDate
     */
    public function setInstallDate(DateTime $installDate): void
    {
        $this->installDate = $installDate;
    }

    /**
     * Get the installation date.
     *
     * @return DateTime
     */
    public function getInstallDate(): DateTime
    {
        return $this->installDate;
    }

    /**
     * Set the installation.
     *
     * @param Installation $installation
     */
    public function setInstallation(Installation $installation): void
    {
        $this->installation = $installation;
    }

    /**
     * Get the installation.
     *
     * @return Installation
     */
    public function getInstallation(): Installation
    {
        return $this->installation;
    }

    /**
     * Set the discharge date.
     *
     * @param DateTime $dischargeDate
     */
    public function setDischargeDate(DateTime $dischargeDate): void
    {
        $this->dischargeDate = $dischargeDate;
    }

    /**
     * Get the discharge date.
     *
     * @return DateTime|null
     */
    public function getDischargeDate(): ?DateTime
    {
        return $this->dischargeDate;
    }
}
