<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use CRM_Mosaicomsgtpl_ExtensionUtil as E;

/**
 * Job.mosaico_msg_sync API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v3_Job_MosaicoMsgSyncTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   * See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install(array('uk.co.vedaconsulting.mosaico', 'org.civicrm.mosaicomsgtpl'))
      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Synchronize all msg templates.
   */
  public function testUpdateAll() {
    $this->assertEquals('MosaicoTemplate', CRM_Core_DAO_AllCoreTables::getBriefName('CRM_Mosaico_DAO_MosaicoTemplate'));

    $myHtml = '<p>placeholder</p>';
    $first = $this->createMosaicoTemplate(array('title' => 'First example', 'html' => $myHtml));
    $second = $this->createMosaicoTemplate(array('title' => 'Second example', 'html' => $myHtml));

    $this->assertEquals(NULL, $first['msg_tpl_id']);
    $this->assertEquals(NULL, $second['msg_tpl_id']);
    $oldCount = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_msg_template WHERE msg_html = %1', array(1 => array($myHtml, 'String')));

    $result = civicrm_api3('Job', 'mosaico_msg_sync', array());

    $newCount = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_msg_template WHERE msg_html = %1', array(1 => array($myHtml, 'String')));
    $this->assertEquals(2, $result['values']['processed']);
    $this->assertEquals(2 + $oldCount, $newCount);

    $getResult = civicrm_api3('MosaicoTemplate', 'get', array());
    $this->assertEquals(2, count($getResult['values']));
    foreach ($getResult['values'] as $value) {
      $this->assertTrue(is_numeric($value['msg_tpl_id']));
      $msgTpl = civicrm_api3('MessageTemplate', 'getsingle', array('id' => $value['msg_tpl_id']));
      $this->assertEquals($myHtml, $msgTpl['msg_html']);
    }
  }

  /**
   * Synchronize one msg templates.
   */
  public function testUpdateOne() {
    $this->assertEquals('MosaicoTemplate', CRM_Core_DAO_AllCoreTables::getBriefName('CRM_Mosaico_DAO_MosaicoTemplate'));

    $first = $this->createMosaicoTemplate(array('title' => 'First example'));
    $second = $this->createMosaicoTemplate(array('title' => 'Second example'));

    $this->assertEquals(NULL, $first['msg_tpl_id']);
    $this->assertEquals(NULL, $second['msg_tpl_id']);
    $oldCount = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_msg_template');

    $result = civicrm_api3('Job', 'mosaico_msg_sync', array(
      'id' => $second['id'],
    ));

    $newCount = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_msg_template');
    $this->assertEquals(1, $result['values']['processed']);
    $this->assertEquals(1 + $oldCount, $newCount);
  }

  protected function createMosaicoTemplate($params = array()) {
    $defaults = array(
      'title' => 'The Title',
      'base' => 'versafix-1',
      'html' => '<p>placeholder</p>',
      'metadata' => json_encode(array('template' => 'placeholder')),
      'content' => json_encode(array('template' => 'placeholder')),
    );
    $msgTpl = civicrm_api3('MosaicoTemplate', 'create', array_merge($defaults, $params));
    return $msgTpl['values'][$msgTpl['id']];
  }

}
