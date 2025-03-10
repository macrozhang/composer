<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Test\Repository\Vcs;

use Composer\Repository\Vcs\SvnDriver;
use Composer\Config;
use Composer\Test\TestCase;
use Composer\Util\Filesystem;
use Composer\Test\Mock\ProcessExecutorMock;

class SvnDriverTest extends TestCase
{
    /**
     * @var string
     */
    protected $home;
    /**
     * @var Config
     */
    protected $config;

    public function setUp()
    {
        $this->home = $this->getUniqueTmpDirectory();
        $this->config = new Config();
        $this->config->merge(array(
            'config' => array(
                'home' => $this->home,
            ),
        ));
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->removeDirectory($this->home);
    }

    public function testWrongCredentialsInUrl()
    {
        $this->setExpectedException('RuntimeException');

        $console = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $httpDownloader = $this->getMockBuilder('Composer\Util\HttpDownloader')->disableOriginalConstructor()->getMock();

        $output = "svn: OPTIONS of 'https://corp.svn.local/repo':";
        $output .= " authorization failed: Could not authenticate to server:";
        $output .= " rejected Basic challenge (https://corp.svn.local/)";

        $process = new ProcessExecutorMock;
        $process->expects(array(
            'svn --version',
            array('cmd' => '', 'return' => 1, 'stderr' => $output),
        ), true);

        $repoConfig = array(
            'url' => 'https://till:secret@corp.svn.local/repo',
        );

        $svn = new SvnDriver($repoConfig, $console, $this->config, $httpDownloader, $process);
        $svn->initialize();

        $process->assertComplete($this);
    }

    public static function supportProvider()
    {
        return array(
            array('http://svn.apache.org', true),
            array('https://svn.sf.net', true),
            array('svn://example.org', true),
            array('svn+ssh://example.org', true),
        );
    }

    /**
     * @dataProvider supportProvider
     *
     * @param string $url
     * @param bool   $assertion
     */
    public function testSupport($url, $assertion)
    {
        $config = new Config();
        $result = SvnDriver::supports($this->getMockBuilder('Composer\IO\IOInterface')->getMock(), $config, $url);
        $this->assertEquals($assertion, $result);
    }
}
