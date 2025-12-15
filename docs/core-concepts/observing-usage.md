# Observing Agent Usage

Prism exposes detailed usage information on every response so you can monitor how your agents consume tokens, attribute costs, and keep an eye on cache hits. Nothing is sent anywhere automatically—the data stays in your application so you can decide how to log or meter it. Usage is surfaced as a `Usage` value object that includes prompt tokens, completion tokens, optional cache read/write tokens, and provider-specific thought tokens where supported.

## Synchronous responses

Every text or structured response carries usage data you can log or persist for analytics:

```php
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

$response = Prism::text()
    ->using(Provider::OpenAI, 'gpt-4o-mini')
    ->withPrompt('Draft a support reply about billing limits.')
    ->asText();

$usage = $response->usage;

Log::info('agent_usage', [
    'prompt_tokens' => $usage->promptTokens,
    'completion_tokens' => $usage->completionTokens,
    'cache_write_tokens' => $usage->cacheWriteInputTokens,
    'cache_read_tokens' => $usage->cacheReadInputTokens,
    'thought_tokens' => $usage->thoughtTokens,
]);
```

Multi-step and tool-driven flows expose per-step usage for deeper visibility:

```php
$firstStepUsage = $response->steps->first()->usage;
```

Embeddings, image generation, and audio responses also expose usage—check the `$response->usage` object (`EmbeddingsUsage` for embeddings) to capture the token counts returned by the provider.

## Streaming responses

When streaming, usage is included on the terminal `StreamEndEvent`. Capture it in your stream callback to record the full agent cost once the stream completes:

```php
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Streaming\Events\StreamEndEvent;
use Prism\Prism\Streaming\Events\StreamEvent;
use Prism\Prism\Text\PendingRequest;

return Prism::text()
    ->using(Provider::Anthropic, 'claude-3-7-sonnet')
    ->withPrompt('Plan a three-step onboarding flow.')
    ->asEventStreamResponse(function (PendingRequest $request, Collection $events): void {
        $usage = $events
            ->first(fn (StreamEvent $event) => $event instanceof StreamEndEvent)
            ?->usage;

        if ($usage) {
            Log::info('agent_usage', [
                'prompt_tokens' => $usage->promptTokens,
                'completion_tokens' => $usage->completionTokens,
            ]);
        }
    });
```

This same pattern works with `asDataStreamResponse()` and `asBroadcast()`, giving you a single place to persist usage for streaming agents.

## Testing usage reporting

Prism fakes ship with sensible default usage values. When testing, override them to assert your logging or billing logic:

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Usage;

Prism::fake([
    TextResponseFake::make()
        ->withUsage(new Usage(promptTokens: 120, completionTokens: 240)),
]);
```

This keeps your analytics code covered without relying on live provider responses.
