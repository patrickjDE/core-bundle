<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\Config;
use Contao\Environment;
use Contao\CoreBundle\EventListener\InitializeSystemListener;
use Contao\CoreBundle\HttpKernel\ContaoKernelInterface;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Tests the BootstrapLegacyListener class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class InitializeSystemListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new InitializeSystemListener(
            $this->getMock('Symfony\Component\Routing\RouterInterface'),
            $this->getRootDir()
        );

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\InitializeSystemListener', $listener);
    }

    // FIXME: add phpDoc comments
    public function testOnBootLegacyForRequestFrontend()
    {
        global $kernel;

        /** @var ContaoKernelInterface $kernel */
        $kernel = $this->mockKernel();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->getRootDir() . '/app'
        );

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'frontend');

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener->onKernelRequest($event);

        $this->assertEquals('FE', TL_MODE);
        $this->assertEquals('/index.html', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    public function testOnBootLegacyForRequestBackend()
    {
        global $kernel;

        /** @var ContaoKernelInterface $kernel */
        $kernel = $this->mockKernel();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/contao/install'),
            $this->getRootDir() . '/app'
        );

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'backend');

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener->onKernelRequest($event);

        $this->assertEquals('BE', TL_MODE);
        $this->assertEquals('/contao/install', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    public function testOnBootLegacyForRequestWithoutRoute()
    {
        global $kernel;

        /** @var ContaoKernelInterface $kernel */
        $kernel = $this->mockKernel();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->getRootDir() . '/app'
        );

        $this->setExpectedException('\Symfony\Component\Routing\Exception\RouteNotFoundException');

        $request = new Request();

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener->onKernelRequest($event);
    }

    public function testOnBootLegacyForRequestFrontendWithoutScope()
    {
        global $kernel;

        /** @var ContaoKernelInterface $kernel */
        $kernel = $this->mockKernel();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->getRootDir() . '/app'
        );

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener->onKernelRequest($event);

        $this->assertEquals('FE', TL_MODE);
        $this->assertEquals('/index.html', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    public function testOnBootLegacyForConsole()
    {
        global $kernel;

        /** @var ContaoKernelInterface $kernel */
        $kernel = $this->mockKernel();

        $listener = new InitializeSystemListener(
            $this->getMock('Symfony\Component\Routing\RouterInterface'),
            $this->getRootDir() . '/app'
        );

        $listener->onConsoleCommand();

        $this->assertEquals('FE', TL_MODE);
        $this->assertEquals('console', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    /**
     * Mocks a Contao kernel.
     *
     * @return ContaoKernelInterface
     */
    private function mockKernel()
    {
        Config::set('bypassCache', true);
        Environment::set('httpAcceptLanguage', []);

        $kernel = $this->getMock(
            'Contao\CoreBundle\HttpKernel\ContaoKernelInterface',
            [
                // ContaoKernelInterface
                'addAutoloadBundles',
                'writeBundleCache',
                'loadBundleCache',
                'getContaoBundles',

                // KernelInterface
                'registerBundles',
                'registerContainerConfiguration',
                'boot',
                'shutdown',
                'getBundles',
                'isClassInActiveBundle',
                'getBundle',
                'locateResource',
                'getName',
                'getEnvironment',
                'isDebug',
                'getRootDir',
                'getContainer',
                'getStartTime',
                'getCacheDir',
                'getLogDir',
                'getCharset',

                // HttpKernelInterface
                'handle',

                // Serializable
                'serialize',
                'unserialize',
            ]
        );

        $kernel
            ->expects($this->any())
            ->method('getContaoBundles')
            ->willReturn([]);

        return $kernel;
    }

    /**
     * Mocks a router returning the given URL.
     *
     * @param string $url The URL to return
     *
     * @return RouterInterface The router object
     */
    private function mockRouter($url)
    {
        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $router
            ->expects($this->any())
            ->method('generate')
            ->willReturn($url);

        return $router;
    }
}