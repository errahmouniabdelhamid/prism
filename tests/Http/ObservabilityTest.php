<?php

use Illuminate\Testing\TestResponse;
use Mockery;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\PrismServer;
use Prism\Prism\Models\PrismUsage;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Text\Response;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

use function Pest\Laravel\artisan;
use function Pest\Laravel\get;

it('records usage when observability is enabled', function (): void {
    artisan('migrate');

    $generator = Mockery::mock(PendingRequest::class);

    $generator->expects('withMessages')
        ->withArgs(fn ($messages): bool => $messages[0] instanceof UserMessage
            && $messages[0]->text() === 'Who are you?')
        ->andReturnSelf();

    $textResponse = new Response(
        steps: collect(),
        text: "I'm Nyx!",
        finishReason: FinishReason::Stop,
        toolCalls: [],
        toolResults: [],
        usage: new Usage(10, 10),
        meta: new Meta('cmp_asdf123', 'gpt-4'),
        messages: collect(),
    );

    $generator->expects('asText')
        ->andReturn($textResponse);

    PrismServer::register(
        'nyx',
        fn () => $generator
    );

    /** @var TestResponse */
    $response = $this->postJson('prism/openai/v1/chat/completions', [
        'model' => 'nyx',
        'messages' => [[
            'role' => 'user',
            'content' => 'Who are you?',
        ]],
    ]);

    $response->assertOk();

    expect(PrismUsage::count())->toBe(1);

    $usage = PrismUsage::first();

    expect($usage->model)->toBe('gpt-4');
    expect(data_get($usage->response, 'text'))->toBe("I'm Nyx!");
    expect(data_get($usage->messages, '0.role'))->toBe('user');
});

it('renders the observability dashboard', function (): void {
    artisan('migrate');

    PrismUsage::create([
        'model' => 'gpt-4',
        'provider' => 'openai',
        'messages' => [['role' => 'user', 'content' => 'Hello!']],
        'response' => ['text' => 'Hi there!'],
        'tool_calls' => [],
        'tool_results' => [],
        'prompt_tokens' => 5,
        'completion_tokens' => 7,
        'total_tokens' => 12,
    ]);

    /** @var TestResponse */
    $response = get('prism/observability');

    $response->assertOk();
    $response->assertSee('Prism Observability');
    $response->assertSee('gpt-4');
    $response->assertSee('Hi there!');
});
