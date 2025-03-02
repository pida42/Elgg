<?php

namespace Elgg;

use ElggFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @group UploadService
 * @group UnitTests
 */
class UploadServiceUnitTest extends \Elgg\UnitTestCase {

	/**
	 * @var \ElggUser
	 */
	private $user;

	/**
	 * @var int
	 */
	private $owner_guid;

	/**
	 * @var string
	 */
	private $owner_dir_path;

	public function up() {
		$this->user = $this->createUser();
		$this->owner_guid = $this->user->guid;

		$dir = (new EntityDirLocator($this->owner_guid))->getPath();
		$this->owner_dir_path = _elgg_services()->config->dataroot . $dir;

		_elgg_services()->hooks->backup();
		_elgg_services()->events->backup();

		// Events service is trying to connect to the DB
		_elgg_services()->events->unregisterHandler('all', 'all', 'Elgg\SystemLog\Logger::listen');
		_elgg_services()->events->unregisterHandler('log', 'systemlog', 'Elgg\SystemLog\Logger::log');

		$request = $this->prepareHttpRequest();
		_elgg_services()->setValue('request', $request);
		_elgg_services()->setValue('uploads', new UploadService($request));

		$session = \ElggSession::getMock();
		_elgg_services()->setValue('session', $session);
		_elgg_services()->session->start();
	}

	public function down() {
		elgg_delete_directory($this->owner_dir_path);
		
		_elgg_services()->hooks->restore();
		_elgg_services()->events->restore();
	}

	public function testDefaultUploadEmpty() {
		$uploaded_file = _elgg_services()->uploads->getFiles('upload');
		$this->assertEmpty($uploaded_file);
	}

	public function testCanGetUploadedFile() {

		$tmp = new ElggFile();
		$tmp->owner_guid = $this->owner_guid;
		$tmp->setFilename('tmp.gif');
		$tmp->open('write');
		$tmp->write(file_get_contents(_elgg_services()->config->dataroot . '1/1/400x300.gif'));
		$tmp->close();

		$tmp_file = $tmp->getFilenameOnFilestore();

		$upload = new UploadedFile($tmp_file, 'tmp.gif', 'image/gif', UPLOAD_ERR_OK, true);

		_elgg_services()->request->files->set('upload', $upload);

		$uploaded_files = _elgg_services()->uploads->getFiles('upload');
		$uploaded_file = array_shift($uploaded_files);
		
		$this->assertInstanceOf(UploadedFile::class, $uploaded_file);
		$this->assertEquals(pathinfo($tmp_file, PATHINFO_BASENAME), $uploaded_file->getClientOriginalName());
		$this->assertEquals('image/gif', $uploaded_file->getClientMimeType());
		$this->assertTrue($uploaded_file->isValid());
	}

	public function testCanGetUploadedFileByIndex() {

		$tmp = new ElggFile();
		$tmp->owner_guid = $this->owner_guid;
		$tmp->setFilename('tmp.gif');
		$tmp->open('write');
		$tmp->write(file_get_contents(_elgg_services()->config->dataroot . '1/1/400x300.gif'));
		$tmp->close();

		$tmp_gif = $tmp->getFilenameOnFilestore();


		$tmp = new ElggFile();
		$tmp->owner_guid = $this->owner_guid;
		$tmp->setFilename('tmp.png');
		$tmp->open('write');
		$tmp->write(file_get_contents(_elgg_services()->config->dataroot . '1/1/400x300.png'));
		$tmp->close();

		$tmp_png = $tmp->getFilenameOnFilestore();

		$upload = [
			'gif' => new UploadedFile($tmp_gif, 'tmp.gif', 'image/gif', UPLOAD_ERR_OK, true),
			'png' => new UploadedFile($tmp_png, 'tmp.png', 'image/png', UPLOAD_ERR_OK, true),
			'jpg' => new UploadedFile('', '', '', UPLOAD_ERR_NO_FILE, true),
		];

		_elgg_services()->request->files->set('upload', $upload);

		$uploaded_files = _elgg_services()->uploads->getFiles('upload');
		$uploaded_file = $uploaded_files['gif'];
		$this->assertInstanceOf(UploadedFile::class, $uploaded_file);
		$this->assertEquals(pathinfo($tmp_gif, PATHINFO_BASENAME), $uploaded_file->getClientOriginalName());
		$this->assertEquals('image/gif', $uploaded_file->getClientMimeType());
		$this->assertTrue($uploaded_file->isValid());

		$uploaded_file = $uploaded_files['png'];
		$this->assertInstanceOf(UploadedFile::class, $uploaded_file);
		$this->assertEquals(pathinfo($tmp_png, PATHINFO_BASENAME), $uploaded_file->getClientOriginalName());
		$this->assertEquals('image/png', $uploaded_file->getClientMimeType());
		$this->assertTrue($uploaded_file->isValid());

		$uploaded_file = $uploaded_files['jpg'];
		$this->assertInstanceOf(UploadedFile::class, $uploaded_file);
		$this->assertFalse($uploaded_file->isValid());
	}

	public function testCanAcceptUploadedFile() {

		$tmp = new ElggFile();
		$tmp->owner_guid = $this->owner_guid;
		$tmp->setFilename('tmp.gif');
		$tmp->open('write');
		$tmp->write(file_get_contents(_elgg_services()->config->dataroot . '1/1/400x300.gif'));
		$tmp->close();

		$tmp_file = $tmp->getFilenameOnFilestore();
		$filesize = $tmp->getSize();

		$upload = [
			'gif' => new UploadedFile($tmp_file, 'tmp.gif', 'image/gif', UPLOAD_ERR_OK, true),
		];

		_elgg_services()->request->files->set('upload', $upload);

		$file = new ElggFile();
		$file->owner_guid = $this->owner_guid;

		$uploaded_files = _elgg_services()->uploads->getFiles('upload');
		$uploaded_file = array_shift($uploaded_files);

		$this->assertTrue($file->acceptUploadedFile($uploaded_file));

		$this->assertTrue($file->exists());
		$this->assertNotEmpty($file->title);
		$this->assertEquals('image/gif', $file->getMimeType());
		$this->assertEquals('image', $file->getSimpleType());
		$this->assertEquals($filesize, $file->getSize());
		$this->assertEquals('tmp.gif', $file->originalfilename);
	}

	public function testCanFilterUploadAction() {

		$tmp = new ElggFile();
		$tmp->owner_guid = $this->owner_guid;
		$tmp->setFilename('tmp.gif');
		$tmp->open('write');
		$tmp->write(file_get_contents(_elgg_services()->config->dataroot . '1/1/400x300.gif'));
		$tmp->close();

		$tmp_file = $tmp->getFilenameOnFilestore();

		$upload = [
			'gif' => new UploadedFile($tmp_file, 'tmp.gif', 'image/gif', UPLOAD_ERR_OK, true),
		];

		_elgg_services()->request->files->set('upload', $upload);

		$file = new ElggFile();
		$file->owner_guid = $this->owner_guid;

		$uploaded_files = _elgg_services()->uploads->getFiles('upload');
		$uploaded_file = array_shift($uploaded_files);

		$upload_event_calls = 0;
		$upload_hook_calls = 0;

		_elgg_services()->events->registerHandler('upload:after', 'file', function(\Elgg\Event $event) use (&$upload_event_calls) {
			$this->assertEquals('upload:after', $event->getName());
			$this->assertEquals('file', $event->getType());
			$this->assertInstanceOf(\ElggFile::class, $event->getObject());
			$upload_event_calls++;
		});

		_elgg_services()->hooks->registerHandler('upload', 'file', function(\Elgg\Hook $hook) use (&$upload_hook_calls) {
			$this->assertNull($hook->getValue());
			$this->assertEquals('upload', $hook->getName());
			$this->assertEquals('file', $hook->getType());
			$this->assertInstanceOf(\ElggFile::class, $hook->getParam('file'));
			$this->assertInstanceOf(UploadedFile::class, $hook->getParam('upload'));
			$upload_hook_calls++;
			return false;
		});

		$this->assertFalse($file->acceptUploadedFile($uploaded_file));
		$this->assertEquals(0, $upload_event_calls);
		$this->assertEquals(1, $upload_hook_calls);
		$this->assertFalse($file->exists());

		_elgg_services()->hooks->registerHandler('upload', 'file', function() use (&$upload_hook_calls) {
			$upload_hook_calls++;
			return true;
		});

		$this->assertTrue($file->acceptUploadedFile($uploaded_file));
		$this->assertEquals(1, $upload_event_calls);
		$this->assertEquals(3, $upload_hook_calls);
		$this->assertFalse($file->exists());
	}
}
