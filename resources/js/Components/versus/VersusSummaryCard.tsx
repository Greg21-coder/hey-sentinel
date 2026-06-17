import { router } from '@inertiajs/react';
import { useState } from 'react';

interface CachedSummary {
  summary: string;
  winner_shopify_app_id: number | null;
  winner_reasoning: string;
  per_metric_comments: Record<string, string> | null;
  model: string;
  prompt_version: string;
  generated_at: string;
}

interface Props {
  cached: CachedSummary | null;
  mineId: number;
  competitorIds: number[];
  winnerAppName: string | null;
}

export default function VersusSummaryCard({ cached, mineId, competitorIds, winnerAppName }: Props) {
  const [submitting, setSubmitting] = useState(false);
  const [expanded, setExpanded] = useState(false);

  const regenerate = () => {
    setSubmitting(true);
    router.post(
      '/customer/my-apps/versus/summary',
      { mine: mineId, competitors: competitorIds },
      {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => setSubmitting(false),
      }
    );
  };

  if (!cached) {
    return (
      <div className="rounded-lg border p-4 text-center">
        <p className="text-gray-600 mb-3">No analysis yet for this combination.</p>
        <button
          disabled={submitting}
          onClick={regenerate}
          className="rounded bg-primary text-white px-4 py-2 disabled:opacity-50"
        >
          {submitting ? 'Analyzing with AI...' : 'Generate AI analysis'}
        </button>
      </div>
    );
  }

  return (
    <div className="rounded-lg border p-4">
      <div className="flex items-start justify-between gap-3">
        <div>
          {winnerAppName && (
            <p className="text-sm text-gray-500">Winner: <span className="font-medium text-gray-900">{winnerAppName}</span></p>
          )}
          <p className="mt-1">{cached.winner_reasoning || cached.summary}</p>
        </div>
        <button
          disabled={submitting}
          onClick={regenerate}
          className="rounded border px-3 py-1 text-sm disabled:opacity-50"
        >
          {submitting ? 'Regenerating...' : 'Regenerate'}
        </button>
      </div>

      {cached.per_metric_comments && Object.keys(cached.per_metric_comments).length > 0 && (
        <div className="mt-3">
          <button onClick={() => setExpanded(!expanded)} className="text-sm text-primary">
            {expanded ? 'Hide' : 'Show'} per-metric comments
          </button>
          {expanded && (
            <ul className="mt-2 list-disc pl-5 text-sm text-gray-700">
              {Object.entries(cached.per_metric_comments).map(([k, v]) => (
                <li key={k}><span className="font-medium">{k}:</span> {v}</li>
              ))}
            </ul>
          )}
        </div>
      )}

      <p className="mt-3 text-xs text-gray-500">
        Generated {new Date(cached.generated_at).toLocaleString()} · model {cached.model}
      </p>
    </div>
  );
}
