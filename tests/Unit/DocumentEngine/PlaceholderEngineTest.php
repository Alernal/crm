<?php

namespace Tests\Unit\DocumentEngine;

use App\Models\Client;
use App\Models\User;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\PlaceholderEngine;
use App\Services\DocumentEngine\PlaceholderProvider;
use PHPUnit\Framework\TestCase;

class PlaceholderEngineTest extends TestCase
{
    public function test_resolves_registered_namespace_placeholders(): void
    {
        $engine = new PlaceholderEngine();
        $engine->register($this->fakeProvider('saludo', ['nombre' => 'Andrés']));

        $result = $engine->render('Hola {{saludo.nombre}}, bienvenido.', $this->fakeContext());

        $this->assertSame('Hola Andrés, bienvenido.', $result);
    }

    public function test_leaves_unresolved_placeholder_visible_instead_of_inventing_data(): void
    {
        $engine = new PlaceholderEngine();
        $engine->register($this->fakeProvider('saludo', ['nombre' => 'Andrés']));

        $result = $engine->render('Valor: {{saludo.apellido}}', $this->fakeContext());

        $this->assertSame('Valor: {{saludo.apellido}}', $result);
    }

    public function test_leaves_placeholder_visible_when_namespace_has_no_provider(): void
    {
        $engine = new PlaceholderEngine();

        $result = $engine->render('{{desconocido.clave}}', $this->fakeContext());

        $this->assertSame('{{desconocido.clave}}', $result);
    }

    public function test_resolves_multiple_placeholders_across_namespaces(): void
    {
        $engine = new PlaceholderEngine();
        $engine->register($this->fakeProvider('a', ['x' => '1']));
        $engine->register($this->fakeProvider('b', ['y' => '2']));

        $result = $engine->render('{{a.x}}-{{b.y}}-{{a.x}}', $this->fakeContext());

        $this->assertSame('1-2-1', $result);
    }

    private function fakeProvider(string $namespace, array $values): PlaceholderProvider
    {
        return new class($namespace, $values) implements PlaceholderProvider {
            public function __construct(private string $ns, private array $values)
            {
            }

            public function namespace(): string
            {
                return $this->ns;
            }

            public function resolve(string $key, PlaceholderContext $context): ?string
            {
                return $this->values[$key] ?? null;
            }
        };
    }

    private function fakeContext(): PlaceholderContext
    {
        /** @var User $company */
        $company = new User();
        /** @var Client|null $client */
        $client = null;

        return new PlaceholderContext($client, $company);
    }
}
