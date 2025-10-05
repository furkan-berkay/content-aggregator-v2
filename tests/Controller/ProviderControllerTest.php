<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProviderControllerTest extends WebTestCase
{
    public function testProviderList()
    {
        $client = static::createClient();
        $client->request('GET', '/api/providers');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testAddProvider()
    {
        $client = static::createClient();
        $client->request('POST', '/api/providers', [
            'name' => 'Test Provider',
            'url' => 'https://example.com',
            'format' => 'json',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('ok', $data['status']);
    }

    public function testRateLimit()
    {
        $client = static::createClient();

        // 10 defa GET isteği → rate limit tetiklenecek
        for ($i = 0; $i < 10; $i++) {
            $client->request('GET', '/api/providers');
        }

        $this->assertEquals(429, $client->getResponse()->getStatusCode());
    }
}
