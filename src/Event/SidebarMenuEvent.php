<?php

namespace Olix\BackOfficeBundle\Event;

use Olix\BackOfficeBundle\Model\MenuItemInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Evènements sur le menu de la barre latérale
 *
 * @package    Olix
 * @subpackage BackOfficeBundle
 * @author     Sabinus52 <sabinus52@gmail.com>
 */
class SidebarMenuEvent extends BackOfficeEvent
{
    /**
     * @var MenuItemInterface[]
     */
    protected $rootItems = [];

    /**
     * @var Request
     */
    protected $request;



    /**
     * @param Request $request
     */
    public function __construct(Request $request = null)
    {
        $this->request = $request;
    }


    /**
     * @return Request
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }


    /**
     * Retourne le menu de la barre latérale
     *
     * @return array
     */
    public function getSidebarMenu(): array
    {
        return $this->rootItems;
    }


    /**
     * Ajoute un nouvel élémént de menu
     *
     * @param MenuItemInterface $item
     * @return SidebarMenuEvent
     */
    public function addItem(MenuItemInterface $item): self
    {
        $this->rootItems[$item->getCode()] = $item;

        return $this;
    }


    /**
     * Enlève un élément au menu
     *
     * @param MenuItemInterface|string $item
     * @return SidebarMenuEvent
     */
    public function removeItem($item): self
    {
        if ($item instanceof MenuItemInterface && isset($this->rootItems[$item->getCode()])) {
            unset($this->rootItems[$item->getCode()]);
        } elseif (is_string($item) && isset($this->rootItems[$item])) {
            unset($this->rootItems[$item]);
        }

        return $this;
    }

    /**
     * @param string $code
     * @return MenuItemInterface|null
     */
    public function getItem($code): ?MenuItemInterface
    {
        return $this->rootItems[$code] ?? null;
    }


    /**
     * Retourne le menu actif du niveau 1
     *
     * @return MenuItemInterface|null
     */
    public function getActive(): ?MenuItemInterface
    {
        foreach ($this->getSidebarMenu() as $item) {
            if ($item->isActive()) {
                return $item;
            }
        }

        return null;
    }
}
