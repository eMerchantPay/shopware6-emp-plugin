<?php declare(strict_types=1);
/*
 * Copyright (C) 2024 emerchantpay Ltd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      emerchantpay
 * @copyright   2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Emerchantpay\Genesis\Service\Framework\Routing;

use Shopware\Core\Framework\Routing\CoreSubscriber;
use Shopware\Core\PlatformRequest;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class CustomCoreSubscriber
 *
 * Decorates CoreSubscriber to help override X-Frame-Options option
 *
 * @package Emerchantpay\Genesis\Service\Framework\Routing
 */
class CustomCoreSubscriber extends CoreSubscriber
{
    /**
     * @var array
     */
    private array $cspTemplates;

    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    /**
     * @param array        $cspTemplates
     * @param RequestStack $requestStack
     */
    public function __construct(array $cspTemplates, RequestStack $requestStack)
    {
        $this->cspTemplates = $cspTemplates;
        $this->requestStack = $requestStack;
        parent::__construct($this->cspTemplates);
    }

    /**
     * Override X-Frame-Options for versions before 6.6
     *
     * @param ResponseEvent $event
     *
     * @return void
     */
    public function setSecurityHeaders(ResponseEvent $event): void
    {
        parent::setSecurityHeaders($event);
        if ($request = $this->requestStack->getCurrentRequest()) {
            $currentRoute = $request->attributes->get('_route');
            if ($currentRoute === 'frontend.emerchantpay.return_iframe') {
                $response = $event->getResponse();
                $response->headers->set(PlatformRequest::HEADER_FRAME_OPTIONS, 'ALLOWALL');
            }
        }
    }
}
