import { router, Link } from '@inertiajs/react';
import { useState } from 'react';
import MineAppSelector, { AppOption } from './MineAppSelector';
import CompetitorPicker from './CompetitorPicker';

interface Props {
  mineOptions: AppOption[];
  competitorOptions: AppOption[];
  initialMineId: number;
  initialCompetitorIds: number[];
  max: number;
}

export default function VersusHeader({ mineOptions, competitorOptions, initialMineId, initialCompetitorIds, max }: Props) {
  const [mineId, setMineId] = useState(initialMineId);
  const [competitorIds, setCompetitorIds] = useState(initialCompetitorIds);

  const save = () => {
    router.visit(
      `/customer/my-apps/versus?mine=${mineId}&${new URLSearchParams(competitorIds.map((id) => ['competitors[]', String(id)])).toString()}`
    );
  };

  return (
    <div className="sticky top-0 bg-white z-10 border-b py-3 mb-4">
      <div className="flex items-center gap-2 text-sm mb-3">
        <Link href="/customer/my-apps" className="text-gray-500">My Apps</Link>
        <span className="text-gray-400">/</span>
        <span className="text-gray-700">Versus</span>
      </div>
      <div className="flex items-center gap-4 flex-wrap">
        <div>
          <label className="block text-xs text-gray-500 mb-1">Mi app</label>
          <MineAppSelector options={mineOptions} selectedId={mineId} onChange={setMineId} />
        </div>
        <div>
          <label className="block text-xs text-gray-500 mb-1">Competidores</label>
          <CompetitorPicker available={competitorOptions} selected={competitorIds} max={max} onChange={setCompetitorIds} />
        </div>
        <button onClick={save} className="rounded bg-primary text-white px-3 py-1.5 text-sm">
          Save selection
        </button>
      </div>
    </div>
  );
}
