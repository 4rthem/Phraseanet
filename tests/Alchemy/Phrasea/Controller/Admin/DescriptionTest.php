<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DescriptionTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteDescription()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $databox = array_shift($appbox->get_databoxes());
        $name = "testtest" . uniqid();
        $field = \databox_field::create(self::$DI['app'], $databox, $name, false);
        $id = $field->get_id();
        self::$DI['client']->request("POST", "/admin/description/" . $databox->get_sbas_id() . "/", array(
            'field_ids' => array($id)
            , 'name_' . $id       => $name
            , 'multi_' . $id      => 1
            , 'indexable_' . $id  => 1
            , 'src_' . $id        => '/rdf:RDF/rdf:Description/IPTC:SupplementalCategories'
            , 'required_' . $id   => 0
            , 'readonly_' . $id   => 0
            , 'type_' . $id       => 'string'
            , 'vocabulary_' . $id => 'User'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $field->delete();
    }

    public function testPostDelete()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $databox = array_shift($appbox->get_databoxes());
        $name = "test" . uniqid();
        $field = \databox_field::create(self::$DI['app'], $databox, $name, false);
        $id = $field->get_id();

        self::$DI['client']->request("POST", "/admin/description/" . $databox->get_sbas_id() . "/", array(
            'todelete_ids' => array($id)
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());

        try {
            $field = \databox_field::get_instance(self::$DI['app'], $databox, $id);
            $field->delete();
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testPostCreate()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $databox = array_shift($appbox->get_databoxes());

        $name = 'test' . uniqid();

        self::$DI['client']->request("POST", "/admin/description/" . $databox->get_sbas_id() . "/", array(
            'newfield' => $name
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());

        $fields = $databox->get_meta_structure();
        $find = false;

        foreach ($fields as $field) {
            if ($field->get_name() === databox_field::generateName($name)) {
                $field->delete();
                $find = true;
            }
        }

        if ( ! $find) {
            $this->fail("should have create a new field");
        }
    }

    public function testPostDescriptionException()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $databox = array_shift($appbox->get_databoxes());

        self::$DI['client']->request("POST", "/admin/description/" . $databox->get_sbas_id() . "/", array(
            'todelete_ids' => array('unknow_id')
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());

        $name = "test" . uniqid();
        $field = \databox_field::create(self::$DI['app'], $databox, $name, false);
        $id = $field->get_id();
        self::$DI['client']->request("POST", "/admin/description/" . $databox->get_sbas_id() . "/", array(
            'field_ids' => array($id)
            , 'name_' . $id       => $name
            , 'multi_' . $id      => 1
            , 'indexable_' . $id  => 1
            , 'src_' . $id        => '/rdf:RDF/rdf:Description/IPTC:SupplementalCategories'
            , 'required_' . $id   => 0
            , 'readonly_' . $id   => 0
            , 'type_' . $id       => 'string'
            , 'vocabulary_' . $id => 'Unknow_Vocabulary'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $field->delete();

        $name = "test" . uniqid();
        $field = \databox_field::create(self::$DI['app'], $databox, $name, false);
        $id = $field->get_id();
        self::$DI['client']->request("POST", "/admin/description/" . $databox->get_sbas_id() . "/", array(
            'field_ids' => array($id)
            , 'multi_' . $id      => 1
            , 'indexable_' . $id  => 1
            , 'src_' . $id        => '/rdf:RDF/rdf:Description/IPTC:SupplementalCategories'
            , 'required_' . $id   => 0
            , 'readonly_' . $id   => 0
            , 'type_' . $id       => 'string'
            , 'vocabulary_' . $id => 'Unknow_Vocabulary'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $field->delete();

        $name = "test" . uniqid();
        $field = \databox_field::create(self::$DI['app'], $databox, $name, false);
        $field->set_indexable(false);
        $field->set_required(true);
        $field->set_readonly(true);
        $id = $field->get_id();
        self::$DI['client']->request("POST", "/admin/description/" . $databox->get_sbas_id() . "/", array(
            'field_ids' => array($id)
            , 'name_' . $id       => $name
            , 'multi_' . $id      => 1
            , 'indexable_' . $id  => 1
            , 'src_' . $id        => 'unknow_Source'
            , 'required_' . $id   => 0
            , 'readonly_' . $id   => 0
            , 'type_' . $id       => 'string'
            , 'vocabulary_' . $id => 'Unknow_Vocabulary'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertTrue($field->is_readonly());
        $this->assertTrue($field->is_required());
        $this->assertFalse($field->is_multi());
        $this->assertFalse($field->is_indexable());
        $field->delete();


        $name = "test" . uniqid();
        $field = \databox_field::create(self::$DI['app'], $databox, $name, false);
        $id = $field->get_id();
        self::$DI['client']->request("POST", "/admin/description/" . $databox->get_sbas_id() . "/", array(
            'field_ids' => array('unknow_id')
            , 'name_' . $id       => $name
            , 'multi_' . $id      => 1
            , 'indexable_' . $id  => 1
            , 'src_' . $id        => '/rdf:RDF/rdf:Description/IPTC:SupplementalCategories'
            , 'required_' . $id   => 0
            , 'readonly_' . $id   => 0
            , 'type_' . $id       => 'string'
            , 'vocabulary_' . $id => 'Unknow_Vocabulary'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $field->delete();
    }

    public function testPostDescriptionRights()
    {
        $this->setAdmin(false);

        $databox = array_shift(self::$DI['app']['phraseanet.appbox']->get_databoxes());
        $name = "test" . uniqid();
        $field = \databox_field::create(self::$DI['app'], $databox, $name, false);
        $id = $field->get_id();

        try {
            self::$DI['client']->request("POST", "/admin/description/" . $databox->get_sbas_id() . "/", array(
                'field_ids' => array($id)
                , 'name_' . $id       => $name
                , 'multi_' . $id      => 1
                , 'indexable_' . $id  => 1
                , 'src_' . $id        => '/rdf:RDF/rdf:Description/IPTC:SupplementalCategories'
                , 'required_' . $id   => 0
                , 'readonly_' . $id   => 0
                , 'type_' . $id       => 'string'
                , 'vocabulary_' . $id => 'User'
            ));
            print(self::$DI['client']->getResponse()->getContent());
            $this->fail('Should throw an AccessDeniedException');
        } catch (AccessDeniedHttpException $e) {

        }

        $field->delete();
    }

    public function testGetDescriptionException()
    {
        $this->setAdmin(false);
        $appbox = self::$DI['app']['phraseanet.appbox'];

        $databox = array_shift($appbox->get_databoxes());

        try {
            self::$DI['client']->request("GET", "/admin/description/" . $databox->get_sbas_id() . "/");
            $this->fail('Should throw an AccessDeniedException');
        } catch (AccessDeniedHttpException $e) {

        }
    }

    public function testGetDescription()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $databox = array_shift($appbox->get_databoxes());

        self::$DI['client']->request("GET", "/admin/description/" . $databox->get_sbas_id() . "/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testGetMetadatas()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $databox = array_shift($appbox->get_databoxes());

        self::$DI['client']->request("GET", "/admin/description/metadatas/search/", array('term' => ''));
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        $datas = json_decode(self::$DI['client']->getResponse()->getContent(), true);
        $this->assertEquals(array(), $datas);

        self::$DI['client']->request("GET", "/admin/description/metadatas/search/", array('term' => 'xmp'));
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        $datas = json_decode(self::$DI['client']->getResponse()->getContent(), true);
        $this->assertTrue(is_array($datas));
        $this->assertGreaterThan(0, count($datas));
    }
}
