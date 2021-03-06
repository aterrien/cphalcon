<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2012 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the licnse and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  +------------------------------------------------------------------------+
*/

class ModelsMetadataAdaptersTest extends PHPUnit_Framework_TestCase
{

	private $_data = array(
		'robots' => array(
			0 => array(
				0 => 'id',
				1 => 'name',
				2 => 'type',
				3 => 'year',
			),
			1 => array(
				0 => 'id',
			),
			2 => array(
				0 => 'name',
				1 => 'type',
				2 => 'year',
			),
			3 => array(
				0 => 'id',
				1 => 'name',
				2 => 'type',
				3 => 'year',
			),
			4 => array(
				'id' => 0,
				'name' => 2,
				'type' => 2,
				'year' => 0,
			),
			5 => array(
				'id' => true,
				'year' => true,
			),
			8 => 'id',
			9 => array(
				'id' => 1,
				'name' => 2,
				'type' => 2,
				'year' => 1,
			),
			10 => array(),
			11 => array()
		),
		'Robots' => array(
			0 => null,
			1 => null,
		)
	);

	public function __construct()
	{
		spl_autoload_register(array($this, 'modelsAutoloader'));
	}

	public function __destruct()
	{
		spl_autoload_unregister(array($this, 'modelsAutoloader'));
	}

	public function modelsAutoloader($className)
	{
		if (file_exists('unit-tests/models/'.$className.'.php')) {
			require 'unit-tests/models/'.$className.'.php';
		}
	}

	protected function _getDI()
	{

		Phalcon\DI::reset();

		$di = new Phalcon\DI();

		$di->set('modelsManager', function(){
			return new Phalcon\Mvc\Model\Manager();
		});

		$di->set('db', function(){
			require 'unit-tests/config.db.php';
			return new Phalcon\Db\Adapter\Pdo\Mysql($configMysql);
		});

		return $di;
	}

	public function testMetadataMemory()
	{

		$di = $this->_getDI();

		$di->set('modelsMetadata', function(){
			return new Phalcon\Mvc\Model\Metadata\Memory();
		});

		$metaData = $di->getShared('modelsMetadata');

		$metaData->reset();

		$this->assertTrue($metaData->isEmpty());

		Robots::findFirst();

		$this->assertFalse($metaData->isEmpty());

		Robots::findFirst();

	}

	public function testMetadataSession()
	{

		@session_start();

		$di = $this->_getDI();

		$di->set('modelsMetadata', function(){
			return new Phalcon\Mvc\Model\Metadata\Session(array(
				'suffix' => 'my-local-app'
			));
		});

		$metaData = $di->getShared('modelsMetadata');

		$metaData->reset();

		$this->assertTrue($metaData->isEmpty());

		Robots::findFirst();

		$expectedSession = array(
			'$PMM$my-local-app' => $this->_data
		);

		$this->assertEquals($_SESSION, $expectedSession);

		$this->assertFalse($metaData->isEmpty());

		Robots::findFirst();

	}

	public function testMetadataApc()
	{

		if (!extension_loaded('apc')) {
			$this->markTestSkipped('apc extension is not loaded');
			return false;
		}

		apc_delete('$PMM$my-local-app');

		$di = $this->_getDI();

		$di->set('modelsMetadata', function(){
			return new Phalcon\Mvc\Model\Metadata\Apc(array(
				'suffix' => 'my-local-app',
				'lifetime' => 60
			));
		});

		$metaData = $di->getShared('modelsMetadata');

		$metaData->reset();

		$this->assertTrue($metaData->isEmpty());

		Robots::findFirst();

		$this->assertEquals(apc_fetch('$PMM$my-local-approbots'), $this->_data['robots']);
		$this->assertEquals(apc_fetch('$PMM$my-local-appRobots'), $this->_data['Robots']);

		$this->assertFalse($metaData->isEmpty());

		Robots::findFirst();

	}

	public function testMetadataFiles()
	{

		$di = $this->_getDI();

		$di->set('modelsMetadata', function(){
			return new Phalcon\Mvc\Model\Metadata\Files(array(
				'metaDataDir' => 'unit-tests/cache/',
			));
		});

		@unlink('unit-tests/cache/robots.php');
		@unlink('unit-tests/cache/Robots.php');

		$metaData = $di->getShared('modelsMetadata');

		$metaData->reset();

		$this->assertTrue($metaData->isEmpty());

		Robots::findFirst();

		$this->assertEquals(require 'unit-tests/cache/robots.php', $this->_data['robots']);
		$this->assertEquals(require 'unit-tests/cache/Robots.php', $this->_data['Robots']);

		$this->assertFalse($metaData->isEmpty());

		Robots::findFirst();
	}

}
