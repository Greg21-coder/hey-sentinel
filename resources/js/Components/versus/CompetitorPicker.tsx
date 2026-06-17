import { useState } from 'react';
import { AppOption } from './MineAppSelector';

interface Props {
  available: AppOption[];
  selected: number[];
  max: number;
  onChange: (ids: number[]) => void;
}

export default function CompetitorPicker({ available, selected, max, onChange }: Props) {
  const [open, setOpen] = useState(false);
  const selectedSet = new Set(selected);

  const toggle = (id: number) => {
    if (selectedSet.has(id)) {
      onChange(selected.filter((s) => s !== id));
    } else if (selected.length < max) {
      onChange([...selected, id]);
    }
  };

  return (
    <div className="relative inline-block">
      <div className="flex items-center gap-2 flex-wrap">
        {selected.map((id) => {
          const app = available.find((a) => a.id === id);
          if (!app) return null;
          return (
            <span key={id} className="rounded bg-gray-100 px-2 py-0.5 text-xs flex items-center gap-1">
              {app.name}
              <button onClick={() => toggle(id)} className="text-gray-500 hover:text-gray-700">x</button>
            </span>
          );
        })}
        {selected.length < max && (
          <button onClick={() => setOpen(!open)} className="rounded border border-dashed px-2 py-0.5 text-xs">
            + Add competitor
          </button>
        )}
      </div>
      {open && (
        <div className="absolute mt-1 z-10 w-64 rounded border bg-white shadow">
          {available
            .filter((a) => !selectedSet.has(a.id))
            .map((a) => (
              <button
                key={a.id}
                onClick={() => { toggle(a.id); setOpen(false); }}
                className="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
              >
                {a.name}
              </button>
            ))}
        </div>
      )}
    </div>
  );
}
