<?php

declare(strict_types=1);

namespace Prism\Prism\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Prism\Prism\Models\PrismUsage;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\ToolCall;
use Prism\Prism\ValueObjects\ToolResult;
use Throwable;

class ObservabilityRecorder
{
    public function recordChat(array $requestPayload, TextResponse $response, ?PendingRequest $generator = null): void
    {
        if (! config('prism.observability.enabled')) {
            return;
        }

        try {
            if (! Schema::hasTable('prism_usages')) {
                return;
            }

            PrismUsage::create([
                'model' => $response->meta->model ?? (string) Arr::get($requestPayload, 'model', ''),
                'provider' => $this->providerKey($generator),
                'messages' => Arr::get($requestPayload, 'messages', []),
                'response' => [
                    'text' => $response->text,
                    'finish_reason' => $response->finishReason->value,
                    'meta' => [
                        'id' => $response->meta->id,
                        'service_tier' => $response->meta->serviceTier,
                    ],
                ],
                'tool_calls' => $this->mapToolCalls($response->toolCalls),
                'tool_results' => $this->mapToolResults($response->toolResults),
                'prompt_tokens' => $response->usage->promptTokens,
                'completion_tokens' => $response->usage->completionTokens,
                'total_tokens' => $response->usage->promptTokens + $response->usage->completionTokens,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function providerKey(?PendingRequest $generator): ?string
    {
        if ($generator instanceof MockInterface || $generator === null) {
            return null;
        }

        if (method_exists($generator, 'providerKey')) {
            return $generator->providerKey();
        }

        return null;
    }

    /**
     * @param  ToolCall[]  $toolCalls
     * @return array<int, array<string, mixed>>
     */
    protected function mapToolCalls(array $toolCalls): array
    {
        return array_map(
            fn (ToolCall $toolCall): array => [
                'id' => $toolCall->id,
                'name' => $toolCall->name,
                'arguments' => $toolCall->arguments(),
                'result_id' => $toolCall->resultId,
                'reasoning_id' => $toolCall->reasoningId,
                'reasoning_summary' => $toolCall->reasoningSummary,
            ],
            $toolCalls
        );
    }

    /**
     * @param  ToolResult[]  $toolResults
     * @return array<int, array<string, mixed>>
     */
    protected function mapToolResults(array $toolResults): array
    {
        return array_map(
            fn (ToolResult $toolResult): array => [
                'tool_call_id' => $toolResult->toolCallId,
                'tool_name' => $toolResult->toolName,
                'args' => $toolResult->args,
                'result' => $toolResult->result,
                'tool_call_result_id' => $toolResult->toolCallResultId,
            ],
            $toolResults
        );
    }
}
