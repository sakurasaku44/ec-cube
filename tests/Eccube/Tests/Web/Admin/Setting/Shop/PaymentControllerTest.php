<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
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
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Eccube\Tests\Web\Admin\Setting\Shop;

use Eccube\Entity\Payment;
use Eccube\Repository\PaymentRepository;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;

class PaymentControllerTest extends AbstractAdminWebTestCase
{
    public function testRouting()
    {
        $this->client->request('GET', $this->generateUrl('admin_setting_shop_payment'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testRoutingNew()
    {
        $this->client->request('GET', $this->generateUrl('admin_setting_shop_payment_new'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * @param $isSuccess
     * @param $expected
     * @dataProvider dataSubmitProvider
     */
    public function testNew($isSuccess, $expected)
    {
        $formData = $this->createFormData();
        if (!$isSuccess) {
            $formData['method'] = '';
        }

        $crawler = $this->client->request('POST',
            $this->generateUrl('admin_setting_shop_payment_new'),
            array(
                'payment_register' => $formData
            )
        );

        $this->expected = $expected;
        $this->actual = $this->client->getResponse()->isRedirection();
        $this->verify();
    }

    public function testRoutingEdit()
    {
        $Payment = $this->container->get(PaymentRepository::class)->find(1);
        $this->client->request('GET', $this->generateUrl('admin_setting_shop_payment_edit', array('id' => $Payment->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * @param $isSuccess
     * @param $expected
     * @dataProvider dataSubmitProvider
     */
    public function testEdit($isSuccess, $expected)
    {
        $formData = $this->createFormData();
        if (!$isSuccess) {
            $formData['method'] = '';
        }

        $Payment = $this->container->get(PaymentRepository::class)->find(1);

        $this->client->request('POST',
            $this->generateUrl('admin_setting_shop_payment_edit', array('id' => $Payment->getId())),
            array(
                'payment_register' => $formData
            )
        );
        $this->expected = $expected;
        $this->actual = $this->client->getResponse()->isRedirection();
        $this->verify();
    }

    public function testDeleteSuccess()
    {

        $Member = $this->createMember();
        $Payment = new Payment();
        $Payment->setMethod('testDeleteSuccess')
            ->setCharge(0)
            ->setRuleMin(0)
            ->setRuleMax(9999)
            ->setCreator($Member)
            ->setVisible(true);

        $this->entityManager->persist($Payment);
        $this->entityManager->flush();

        $pid = $Payment->getId();
        $this->client->request('DELETE',
            $this->generateUrl('admin_setting_shop_payment_delete', array('id' => $pid))
        );

        $this->assertTrue($this->client->getResponse()->isRedirection());

        $Payment = $this->container->get(PaymentRepository::class)->find($pid);
        $this->assertNull($Payment);
    }

    public function testDeleteFail_NotFound()
    {
        $pid = 9999;
        $this->client->request(
            'DELETE',
            $this->generateUrl('admin_setting_shop_payment_delete', array('id' => $pid))
        );
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());

    }

    public function testUp()
    {
        $pid = 4;
        $Payment = $this->container->get(PaymentRepository::class)->find($pid);
        $before = $Payment->getSortNo();
        $crawler = $this->client->request('PUT',
            $this->generateUrl('admin_setting_shop_payment_up', array('id' => $pid))
        );
        $this->assertTrue($this->client->getResponse()->isRedirection());

        $after = $Payment->getSortNo();
        $this->actual = $after;
        $this->expected = $before + 1;
        $this->verify();
    }

    public function testDown()
    {
        $pid = 1;
        $Payment = $this->container->get(PaymentRepository::class)->find($pid);
        $before = $Payment->getSortNo();
        $this->client->request('PUT',
            $this->generateUrl('admin_setting_shop_payment_down', array('id' => $pid))
        );

        $this->assertTrue($this->client->getResponse()->isRedirection());

        $after = $Payment->getSortNo();
        $this->actual = $after;
        $this->expected = $before - 1;
        $this->verify();
    }

    public function testAddImage()
    {
        $formData = $this->createFormData();

        $this->client->request('POST',
            $this->generateUrl('admin_payment_image_add'),
            array(
                'payment_register' => $formData
            ),
            array(),
            array(
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testAddImage_NotAjax()
    {
        $formData = $this->createFormData();

        $this->client->request('POST',
            $this->generateUrl('admin_payment_image_add'),
            array(
                'payment_register' => $formData
            ),
            array()
        );
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }


    //    public function testAddImage_MineNotSupported()
    //    {
    //        $formData = $this->createFormData();
    //
    //        $formData['payment_image'] = 'abc.avi';
    //        $formData['payment_image_file'] = 'abc.avi';
    //
    //        $this->client->request('POST',
    //            $this->app->url('admin_payment_image_add'),
    //            array(
    //                'payment_register' => $formData
    //            ),
    //            array(),
    //            array(
    //                'HTTP_X-Requested-With' => 'XMLHttpRequest',
    //            )
    //        );
    //    }

    public function createFormData()
    {
        $charge = 10000;
        if (mt_rand(0, 1)) {
            $charge = number_format($charge);
        }

        $rule_max = 10000;
        if (mt_rand(0, 1)) {
            $rule_max = number_format($rule_max);
        }

        $form = array(
            '_token' => 'dummy',
            'method' => 'Test',
            'charge' => $charge,
            'rule_min' => '100',
            'rule_max' => $rule_max,
            'payment_image' => 'abc.png',
            'payment_image_file' => 'abc.png',
            'fixed' => true,
        );

        return $form;
    }

    public function dataSubmitProvider()
    {
        return array(
            array(false, false),
            array(true, true),
            // To do implement
        );
    }
    //    TO DO : implement
}
