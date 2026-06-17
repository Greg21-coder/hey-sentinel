import { BarChart, Bar, ResponsiveContainer, XAxis, YAxis } from 'recharts';
import WinnerHighlight from './WinnerHighlight';
import NicheMismatchBadge from './NicheMismatchBadge';
import FeatureMatrix from './FeatureMatrix';

interface Column {
  app: {
    id: number;
    name: string;
    developer_name: string;
    avatar_url: string | null;
    pricing_has_free: boolean;
    pricing_min_usd: number | null;
    pricing_structured: any[] | null;
  };
  is_mine: boolean;
  category_mismatch: boolean;
  features_pending: boolean;
}

interface Row {
  metric: string;
  values: any[];
  winner_index: number | null;
  feature_matrix?: { name: string; presence: boolean[] }[];
}

interface Props {
  columns: Column[];
  rows: Row[];
}

export default function VersusGrid({ columns, rows }: Props) {
  const Header = () => (
    <tr>
      <th className="text-left text-xs uppercase text-gray-500 w-32"></th>
      {columns.map((col, i) => (
        <th key={i} className="text-left p-3 align-top">
          <div className="flex items-start gap-3">
            {col.app.avatar_url && <img src={col.app.avatar_url} alt="" className="w-10 h-10 rounded" />}
            <div>
              <p className="font-medium">{col.app.name}</p>
              <p className="text-xs text-gray-500">{col.app.developer_name}</p>
              {col.is_mine && <span className="rounded bg-green-100 text-green-800 px-1.5 py-0.5 text-[10px] mt-1 inline-block">Mine</span>}
              {col.category_mismatch && <div className="mt-1"><NicheMismatchBadge /></div>}
            </div>
          </div>
        </th>
      ))}
    </tr>
  );

  const Cell = ({ winnerIndex, index, children }: { winnerIndex: number | null; index: number; children: React.ReactNode }) => (
    <td className="p-3 align-top">
      <WinnerHighlight isWinner={winnerIndex === index}>{children}</WinnerHighlight>
    </td>
  );

  return (
    <table className="w-full border-collapse">
      <thead><Header /></thead>
      <tbody>
        {rows.map((row) => {
          if (row.metric === 'rating') {
            return (
              <tr key={row.metric} className="border-t">
                <td className="text-xs uppercase text-gray-500 align-top pt-3">Rating</td>
                {row.values.map((v, i) => (
                  <Cell key={i} winnerIndex={row.winner_index} index={i}>
                    <p className="text-2xl font-medium">{(v as number).toFixed(2)} ★</p>
                    <div className="h-12">
                      <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={[{ name: 'rating', value: v }]} layout="vertical">
                          <XAxis type="number" domain={[0, 5]} hide />
                          <YAxis type="category" dataKey="name" hide />
                          <Bar dataKey="value" fill="var(--color-primary, #008060)" />
                        </BarChart>
                      </ResponsiveContainer>
                    </div>
                  </Cell>
                ))}
              </tr>
            );
          }
          if (row.metric === 'total_reviews') {
            return (
              <tr key={row.metric} className="border-t">
                <td className="text-xs uppercase text-gray-500 align-top pt-3">Reviews</td>
                {row.values.map((v, i) => (
                  <Cell key={i} winnerIndex={row.winner_index} index={i}>
                    <p className="text-2xl font-medium">{(v as number).toLocaleString()}</p>
                  </Cell>
                ))}
              </tr>
            );
          }
          if (row.metric === 'sentiment') {
            return (
              <tr key={row.metric} className="border-t">
                <td className="text-xs uppercase text-gray-500 align-top pt-3">Sentiment</td>
                {row.values.map((v: any, i) => (
                  <Cell key={i} winnerIndex={row.winner_index} index={i}>
                    <div className="text-xs space-y-0.5">
                      <p>positive {v.positive}%</p>
                      <p>neutral {v.neutral}%</p>
                      <p>mixed {v.mixed}%</p>
                      <p>negative {v.negative}%</p>
                    </div>
                  </Cell>
                ))}
              </tr>
            );
          }
          if (row.metric === 'pain_points') {
            return (
              <tr key={row.metric} className="border-t">
                <td className="text-xs uppercase text-gray-500 align-top pt-3">Pain points</td>
                {row.values.map((counts: Record<string, number>, i) => (
                  <Cell key={i} winnerIndex={row.winner_index} index={i}>
                    <ul className="text-xs space-y-0.5">
                      {Object.entries(counts).slice(0, 5).map(([k, n]) => (
                        <li key={k}>{k} ({n})</li>
                      ))}
                    </ul>
                  </Cell>
                ))}
              </tr>
            );
          }
          if (row.metric === 'pricing') {
            return (
              <tr key={row.metric} className="border-t">
                <td className="text-xs uppercase text-gray-500 align-top pt-3">Pricing</td>
                {row.values.map((plans: any[], i) => (
                  <Cell key={i} winnerIndex={row.winner_index} index={i}>
                    {columns[i].app.pricing_has_free && <span className="rounded bg-green-100 text-green-800 px-1.5 py-0.5 text-[10px] mb-2 inline-block">Free tier</span>}
                    {plans && plans.length > 0 ? (
                      <ul className="text-xs space-y-0.5">
                        {plans.slice(0, 4).map((p, j) => (
                          <li key={j}>{p.name ?? `Plan ${j+1}`}: ${p.price_usd ?? '—'}</li>
                        ))}
                      </ul>
                    ) : (
                      <p className="text-xs text-gray-500">No pricing data</p>
                    )}
                  </Cell>
                ))}
              </tr>
            );
          }
          if (row.metric === 'features') {
            return (
              <tr key={row.metric} className="border-t">
                <td className="text-xs uppercase text-gray-500 align-top pt-3">Features</td>
                <td colSpan={columns.length} className="p-3">
                  <FeatureMatrix
                    matrix={row.feature_matrix ?? []}
                    columnLabels={columns.map((c) => c.app.name)}
                    pendingFlags={columns.map((c) => c.features_pending)}
                  />
                </td>
              </tr>
            );
          }
          return null;
        })}
      </tbody>
    </table>
  );
}
