<?php

declare(strict_types=1);

/**
 * This file is part of OlixBackOfficeBundle.
 * (c) Sabinus52 <sabinus52@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Olix\BackOfficeBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension des "filters" et "functions" personnalisés TWIG.
 *
 * @author     Sabinus52 <sabinus52@gmail.com>
 *
 * @see        https://symfony.com/doc/current/templating/twig_extension.html
 */
class BackOfficeExtension extends AbstractExtension
{
    /**
     * Déclaration des fonctions Twig.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('olixbo_class_body', [BackOfficeRuntime::class, 'getClassBody']),
            new TwigFunction('olixbo_class_navbar', [BackOfficeRuntime::class, 'getClassNavbar']),
            new TwigFunction('olixbo_class_sidebar', [BackOfficeRuntime::class, 'getClassSidebar']),
            new TwigFunction('olixbo_class_menu', [BackOfficeRuntime::class, 'getClassMenu']),
            new TwigFunction('olixbo_sidebar_menu', [EventsRuntime::class, 'getSidebarMenu']),
            new TwigFunction('olixbo_breadcrumb', [EventsRuntime::class, 'getBreadcrumb']),
            new TwigFunction('olixbo_notification', [EventsRuntime::class, 'getNotifications']),
        ];
    }
}
