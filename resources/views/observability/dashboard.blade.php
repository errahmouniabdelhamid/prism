@php
    use Illuminate\Support\Str;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prism Observability</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; padding: 24px; background: #f5f5f7; color: #111827; }
        h1 { margin: 0 0 8px; }
        p.lead { margin: 0 0 20px; color: #4b5563; }
        .grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 20px; }
        .card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05); }
        .card h3 { margin: 0; font-size: 14px; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; }
        .card .value { margin-top: 6px; font-size: 28px; font-weight: 700; }
        .card small { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table thead { background: #111827; color: #fff; }
        table th, table td { padding: 10px 12px; text-align: left; }
        table tbody tr:nth-child(even) { background: #f9fafb; }
        table tbody tr:nth-child(odd) { background: #fff; }
        .pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; background: #eef2ff; color: #4338ca; }
        .section { margin-top: 28px; }
        .models ul { list-style: none; padding: 0; margin: 12px 0 0; }
        .models li { padding: 6px 0; display: flex; justify-content: space-between; border-bottom: 1px solid #e5e7eb; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>Prism Observability</h1>
    <p class="lead">Track agent inputs, outputs, tool calls, and token usage in one place.</p>

    <div class="grid">
        <div class="card">
            <h3>Total Requests</h3>
            <div class="value">{{ number_format($stats['total_requests']) }}</div>
        </div>
        <div class="card">
            <h3>Prompt Tokens</h3>
            <div class="value">{{ number_format($stats['prompt_tokens']) }}</div>
        </div>
        <div class="card">
            <h3>Completion Tokens</h3>
            <div class="value">{{ number_format($stats['completion_tokens']) }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="card models">
            <h3>Top Models</h3>
            @if($topModels->isEmpty())
                <p class="muted">No usage recorded yet.</p>
            @else
                <ul>
                    @foreach ($topModels as $model)
                        <li>
                            <span>{{ $model->model }}</span>
                            <strong>{{ $model->total }}</strong>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="card">
            <h3>Latest Tool Calls</h3>
            @php
                $recentTools = $usages->flatMap(fn ($usage) => $usage->tool_calls ?? [])->take(5);
            @endphp
            @if($recentTools->isEmpty())
                <p class="muted">No tool calls recorded yet.</p>
            @else
                <ul>
                    @foreach ($recentTools as $toolCall)
                        <li class="muted">
                            <strong>{{ $toolCall['name'] ?? 'Tool' }}</strong>
                            <div>Call ID: {{ Str::limit((string) ($toolCall['id'] ?? ''), 24) }}</div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="section card">
        <h3>Recent Usage</h3>
        @if($usages->isEmpty())
            <p class="muted">No usage has been recorded yet. Trigger a request to see it here.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Model</th>
                        <th>Provider</th>
                        <th>Prompt</th>
                        <th>Output</th>
                        <th>Tokens</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($usages as $usage)
                        <tr>
                            <td>{{ optional($usage->created_at)->format('Y-m-d H:i') }}</td>
                            <td><span class="pill">{{ $usage->model }}</span></td>
                            <td>{{ $usage->provider ?? '—' }}</td>
                            <td>{{ Str::limit((string) data_get($usage->messages, '0.content', ''), 60) }}</td>
                            <td>{{ Str::limit((string) data_get($usage->response, 'text', ''), 80) }}</td>
                            <td>
                                <div class="muted">P {{ $usage->prompt_tokens ?? 0 }} / C {{ $usage->completion_tokens ?? 0 }}</div>
                                <strong>{{ $usage->total_tokens ?? 0 }}</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
