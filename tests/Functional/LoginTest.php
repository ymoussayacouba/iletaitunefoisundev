<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class LoginTest extends WebTestCase
{
    public function testIfLoginIsSuccessful(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get("router");

        $crawler = $client->request(Request::METHOD_GET, $router->generate("security_login"));

        $form = $crawler->filter("form[name=login]")->form([
            "email" => "admin@email.com",
            "password" => "password"
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();

        $this->assertRouteSame('index');
    }

    /**
     * @dataProvider provideInvalidCredentials
     */
    public function testIfCredentialsAreInvalid(string $email, string $password, string $errorMessage): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get("router");

        $crawler = $client->request(Request::METHOD_GET, $router->generate("security_login"));

        $form = $crawler->filter("form[name=login]")->form([
            "email" => $email,
            "password" => $password
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();

        $this->assertSelectorTextContains("form[name=login] > div.alert", $errorMessage);
    }

    public function provideInvalidCredentials(): iterable
    {
        yield ["fail@email.com", "password", "Le nom d'utilisateur n'a pas pu être trouvé."];
        yield ["admin@email.com", "fail", "Identifiants invalides."];
    }

    public function testIfCsrfTokenIsInvalid(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get("router");

        $crawler = $client->request(Request::METHOD_GET, $router->generate("security_login"));

        $form = $crawler->filter("form[name=login]")->form([
            "_csrf_token" => "fail",
            "email" => "admin@email.com",
            "password" => "password"
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();

        $this->assertSelectorTextContains("form[name=login] > div.alert", "Jeton CSRF invalide.");
    }
}
