<?php

namespace Concrete\Package\LgtToolkit\Entity\Attribute\Value\Value;

use Doctrine\ORM\Mapping as ORM;
use Concrete\Core\Entity\Attribute\Value\Value\AbstractValue;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="atLgtRedirectValue"
 * )
 */
class RedirectValue extends AbstractValue
{
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected string $redirectType;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected int $redirectMethod;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected string $value;

    /**
     * Get the value of redirectType
     */
    public function getRedirectType(): string
    {
        return $this->redirectType;
    }

    /**
     * Set the value of redirectType
     *
     * @return self
     */
    public function setRedirectType(string $redirectType): self
    {
        $this->redirectType = $redirectType;

        return $this;
    }

    /**
     * Get the value of redirectMethod
     */
    public function getRedirectMethod(): int
    {
        return $this->redirectMethod;
    }

    /**
     * Set the value of redirectMethod
     *
     * @return self
     */
    public function setRedirectMethod(int $redirectMethod): self
    {
        $this->redirectMethod = $redirectMethod;

        return $this;
    }

    /**
     * Get the value of value
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Set the value of value
     *
     * @return self
     */
    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
