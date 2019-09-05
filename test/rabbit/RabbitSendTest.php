<?php

namespace test\rabbit;

use app\Rabbit;
use PHPUnit\Framework\TestCase;

class RabbitSendTest extends TestCase
{
    function test()
    {
        Rabbit::send('test');
        Rabbit::send(json_encode(['hello world']));
        $this->assertTrue(true);
    }

    function testWholesale()
    {
        Rabbit::send(json_encode([
            'task' => 'wholesale',
            'email' => 'jibo@outlook.com',
        ]));
        $this->assertTrue(true);
    }
}
