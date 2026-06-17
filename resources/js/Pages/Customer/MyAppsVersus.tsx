import { Head } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import VersusHeader from '@/Components/versus/VersusHeader';
import VersusGrid from '@/Components/versus/VersusGrid';
import VersusSummaryCard from '@/Components/versus/VersusSummaryCard';
import { useFeaturePolling } from '@/hooks/useFeaturePolling';

interface Versus {
  mine: { id: number; name: string };
  competitors: { id: number; name: string }[];
  columns: any[];
  rows: any[];
  cached_summary: any | null;
}

interface Props {
  versus: Versus;
  mineSelection: number;
  competitorSelection: number[];
  mineOptions: { id: number; name: string }[];
  competitorOptions: { id: number; name: string }[];
}

export default function MyAppsVersus({ versus, mineSelection, competitorSelection, mineOptions, competitorOptions }: Props) {
  const hasPending = versus.columns.some((c: any) => c.features_pending);
  useFeaturePolling(hasPending);

  const winnerId = versus.cached_summary?.winner_shopify_app_id ?? null;
  const winnerName = winnerId
    ? versus.columns.find((c: any) => c.app.id === winnerId)?.app.name ?? null
    : null;

  return (
    <CustomerLayout>
      <Head title="Versus" />
      <VersusHeader
        mineOptions={mineOptions}
        competitorOptions={competitorOptions}
        initialMineId={mineSelection}
        initialCompetitorIds={competitorSelection}
        max={3}
      />

      {versus.columns.length === 1 ? (
        <div className="rounded-lg border border-dashed p-8 text-center">
          <p className="text-gray-600">Pick one or more competitors to start comparing.</p>
          <a href="/customer/apps?kind=competitor" className="text-primary mt-2 inline-block">Browse competitors</a>
        </div>
      ) : (
        <>
          <VersusGrid columns={versus.columns} rows={versus.rows} />
          <div className="mt-6">
            <VersusSummaryCard
              cached={versus.cached_summary}
              mineId={mineSelection}
              competitorIds={competitorSelection}
              winnerAppName={winnerName}
            />
          </div>
        </>
      )}
    </CustomerLayout>
  );
}
