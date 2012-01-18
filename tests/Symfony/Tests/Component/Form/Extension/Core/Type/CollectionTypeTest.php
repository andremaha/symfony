<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\Form\Extension\Core\Type;

use Symfony\Component\Form\Form;

class CollectionFormTest extends TypeTestCase
{
    public function testContainsNoFieldByDefault()
    {
        $form = $this->factory->create('collection', null, array(
            'type' => 'field',
        ));

        $this->assertCount(0, $form);
    }

    public function testSetDataAdjustsSize()
    {
        $form = $this->factory->create('collection', null, array(
            'type' => 'field',
            'options' => array(
                'max_length' => 20,
            ),
        ));
        $form->setData(array('foo@foo.com', 'foo@bar.com'));

        $this->assertInstanceOf('Symfony\Component\Form\Form', $form[0]);
        $this->assertInstanceOf('Symfony\Component\Form\Form', $form[1]);
        $this->assertCount(2, $form);
        $this->assertEquals('foo@foo.com', $form[0]->getData());
        $this->assertEquals('foo@bar.com', $form[1]->getData());
        $this->assertEquals(20, $form[0]->getAttribute('max_length'));
        $this->assertEquals(20, $form[1]->getAttribute('max_length'));

        $form->setData(array('foo@baz.com'));
        $this->assertInstanceOf('Symfony\Component\Form\Form', $form[0]);
        $this->assertFalse(isset($form[1]));
        $this->assertCount(1, $form);
        $this->assertEquals('foo@baz.com', $form[0]->getData());
        $this->assertEquals(20, $form[0]->getAttribute('max_length'));
    }

    public function testThrowsExceptionIfObjectIsNotTraversable()
    {
        $form = $this->factory->create('collection', null, array(
            'type' => 'field',
        ));
        $this->setExpectedException('Symfony\Component\Form\Exception\UnexpectedTypeException');
        $form->setData(new \stdClass());
    }

    public function testNotResizedIfBoundWithMissingData()
    {
        $form = $this->factory->create('collection', null, array(
            'type' => 'field',
        ));
        $form->setData(array('foo@foo.com', 'bar@bar.com'));
        $form->bind(array('foo@bar.com'));

        $this->assertTrue($form->has('0'));
        $this->assertTrue($form->has('1'));
        $this->assertEquals('foo@bar.com', $form[0]->getData());
        $this->assertNull($form[1]->getData());
    }

    public function testResizedDownIfBoundWithMissingDataAndAllowDelete()
    {
        $form = $this->factory->create('collection', null, array(
            'type' => 'field',
            'allow_delete' => true,
        ));
        $form->setData(array('foo@foo.com', 'bar@bar.com'));
        $form->bind(array('foo@bar.com'));

        $this->assertTrue($form->has('0'));
        $this->assertFalse($form->has('1'));
        $this->assertEquals('foo@bar.com', $form[0]->getData());
        $this->assertEquals(array('foo@bar.com'), $form->getData());
    }

    public function testNotResizedIfBoundWithExtraData()
    {
        $form = $this->factory->create('collection', null, array(
            'type' => 'field',
        ));
        $form->setData(array('foo@bar.com'));
        $form->bind(array('foo@foo.com', 'bar@bar.com'));

        $this->assertTrue($form->has('0'));
        $this->assertFalse($form->has('1'));
        $this->assertEquals('foo@foo.com', $form[0]->getData());
    }

    public function testResizedUpIfBoundWithExtraDataAndAllowAdd()
    {
        $form = $this->factory->create('collection', null, array(
            'type' => 'field',
            'allow_add' => true,
        ));
        $form->setData(array('foo@bar.com'));
        $form->bind(array('foo@foo.com', 'bar@bar.com'));

        $this->assertTrue($form->has('0'));
        $this->assertTrue($form->has('1'));
        $this->assertEquals('foo@foo.com', $form[0]->getData());
        $this->assertEquals('bar@bar.com', $form[1]->getData());
        $this->assertEquals(array('foo@foo.com', 'bar@bar.com'), $form->getData());
    }

    public function testAllowAddButNoPrototype()
    {
        $form = $this->factory->create('collection', null, array(
            'type'      => 'field',
            'allow_add' => true,
            'prototype' => false,
        ));

        $this->assertFalse($form->has('$$name$$'));
    }

    public function testPrototypeMultipartPropagation()
    {
        $form = $this->factory
            ->create('collection', null, array(
                'type'      => 'file',
                'allow_add' => true,
                'prototype' => true,
            ))
        ;

        $this->assertTrue($form->createView()->get('multipart'));
    }

    public function testGetDataDoesNotContainsProtypeNameBeforeDataAreSet()
    {
        $form = $this->factory->create('collection', array(), array(
            'type'      => 'file',
            'prototype' => true,
            'allow_add' => true,
        ));

        $data = $form->getData();
        $this->assertFalse(isset($data['$$name$$']));
    }

    public function testGetDataDoesNotContainsPrototypeNameAfterDataAreSet()
    {
        $form = $this->factory->create('collection', array(), array(
            'type'      => 'file',
            'allow_add' => true,
            'prototype' => true,
        ));

        $form->setData(array('foobar.png'));
        $data = $form->getData();
        $this->assertFalse(isset($data['$$name$$']));
    }

    public function testPrototypeNameOption()
    {
        $form = $this->factory->create('collection', null, array(
            'type'      => 'field',
            'prototype' => true,
            'allow_add' => true,
        ));

        $this->assertSame('$$name$$', $form->getAttribute('prototype')->getName(), '$$name$$ is the default');

        $form = $this->factory->create('collection', null, array(
            'type'           => 'field',
            'prototype'      => true,
            'allow_add'      => true,
            'prototype_name' => 'test',
        ));

        $this->assertSame('$$test$$', $form->getAttribute('prototype')->getName());
    }
}
